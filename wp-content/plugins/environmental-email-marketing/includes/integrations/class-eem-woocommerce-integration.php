<?php
/**
 * Environmental Email Marketing WooCommerce Integration
 * 
 * Integrates email marketing with WooCommerce for eco-friendly
 * product promotions, purchase-based automations, and environmental scoring.
 *
 * @package     EnvironmentalEmailMarketing
 * @subpackage  Integrations
 * @version     1.0.0
 * @author      Environmental Email Marketing Team
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_WooCommerce_Integration {
    
    /**
     * Initialize WooCommerce integration
     */
    public function __construct() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        add_action('woocommerce_checkout_order_processed', array($this, 'handle_order_processed'), 10, 3);
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_completed'));
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete'));
        add_action('woocommerce_checkout_update_user_meta', array($this, 'handle_checkout_subscription'), 10, 2);
        add_action('woocommerce_after_checkout_validation', array($this, 'validate_checkout_subscription'), 10, 2);
        add_filter('woocommerce_checkout_fields', array($this, 'add_checkout_subscription_field'));
        add_action('wp_ajax_eem_woo_product_recommendations', array($this, 'get_product_recommendations'));
        add_action('wp_ajax_nopriv_eem_woo_product_recommendations', array($this, 'get_product_recommendations'));
        
        // Add newsletter subscription checkbox to checkout
        add_action('woocommerce_review_order_before_submit', array($this, 'add_checkout_newsletter_field'));
        
        // Product view tracking
        add_action('woocommerce_single_product_summary', array($this, 'track_product_view'), 5);
        
        // Cart abandonment tracking
        add_action('woocommerce_add_to_cart', array($this, 'track_cart_addition'), 10, 6);
        add_action('wp_footer', array($this, 'track_cart_abandonment'));
        
        // Environmental product scoring
        add_action('add_meta_boxes', array($this, 'add_environmental_meta_box'));
        add_action('save_post', array($this, 'save_environmental_meta'));
        
        // Email marketing triggers
        add_action('eem_automation_trigger_product_purchased', array($this, 'trigger_product_purchase_automation'), 10, 2);
        add_action('eem_automation_trigger_cart_abandoned', array($this, 'trigger_cart_abandonment_automation'), 10, 2);
    }

    /**
     * Handle order processed
     */
    public function handle_order_processed($order_id, $posted_data, $order) {
        $billing_email = $order->get_billing_email();
        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        
        // Check if customer opted in for newsletter
        $newsletter_optin = get_post_meta($order_id, '_newsletter_subscription', true);
        
        if ($newsletter_optin) {
            $subscriber_manager = new EEM_Subscriber_Manager();
            
            // Add or update subscriber
            $subscriber_data = array(
                'email' => $billing_email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'status' => 'subscribed', // Auto-confirm for customers
                'source' => 'woocommerce_checkout',
                'lists' => array(get_option('eem_woocommerce_default_list', 1)),
                'environmental_score' => $this->calculate_order_environmental_score($order)
            );
            
            $existing_subscriber = $subscriber_manager->get_subscriber_by_email($billing_email);
            
            if ($existing_subscriber) {
                $subscriber_manager->update_subscriber($existing_subscriber['id'], $subscriber_data);
            } else {
                $subscriber_manager->add_subscriber($subscriber_data);
            }
        }
        
        // Track purchase event
        $analytics = new EEM_Analytics_Tracker();
        $analytics->track_event('woocommerce_purchase', array(
            'order_id' => $order_id,
            'email' => $billing_email,
            'total' => $order->get_total(),
            'products' => $this->get_order_products($order),
            'environmental_score' => $this->calculate_order_environmental_score($order)
        ));
    }

    /**
     * Handle order completed
     */
    public function handle_order_completed($order_id) {
        $order = wc_get_order($order_id);
        $billing_email = $order->get_billing_email();
        
        // Trigger product purchase automation
        $automation = new EEM_Automation_Engine();
        $automation->trigger_automation('product_purchased', array(
            'email' => $billing_email,
            'order_id' => $order_id,
            'products' => $this->get_order_products($order),
            'total' => $order->get_total()
        ));
        
        // Update subscriber environmental score
        $this->update_subscriber_environmental_score($billing_email, $order);
    }

    /**
     * Handle payment complete
     */
    public function handle_payment_complete($order_id) {
        $order = wc_get_order($order_id);
        $billing_email = $order->get_billing_email();
        
        // Send purchase confirmation email with environmental impact
        $this->send_purchase_confirmation_email($order);
        
        // Track environmental impact
        $environmental_impact = $this->calculate_environmental_impact($order);
        
        $analytics = new EEM_Analytics_Tracker();
        $analytics->track_event('environmental_impact', array(
            'order_id' => $order_id,
            'email' => $billing_email,
            'carbon_footprint' => $environmental_impact['carbon_footprint'],
            'sustainability_score' => $environmental_impact['sustainability_score']
        ));
    }

    /**
     * Add checkout subscription field
     */
    public function add_checkout_newsletter_field() {
        if (get_option('eem_woocommerce_checkout_optin', 1)) {
            woocommerce_form_field('newsletter_subscription', array(
                'type' => 'checkbox',
                'class' => array('form-row-wide'),
                'label' => get_option('eem_woocommerce_optin_text', __('Subscribe to our environmental newsletter', 'environmental-email-marketing')),
                'default' => get_option('eem_woocommerce_optin_default', 0)
            ), WC()->checkout->get_value('newsletter_subscription'));
        }
    }

    /**
     * Handle checkout subscription
     */
    public function handle_checkout_subscription($user_id, $data) {
        if (isset($data['newsletter_subscription']) && $data['newsletter_subscription']) {
            update_user_meta($user_id, 'newsletter_subscription', 1);
        }
    }

    /**
     * Add checkout subscription field to fields array
     */
    public function add_checkout_subscription_field($fields) {
        if (get_option('eem_woocommerce_checkout_optin', 1)) {
            $fields['billing']['newsletter_subscription'] = array(
                'type' => 'checkbox',
                'label' => get_option('eem_woocommerce_optin_text', __('Subscribe to our environmental newsletter', 'environmental-email-marketing')),
                'default' => get_option('eem_woocommerce_optin_default', 0),
                'class' => array('form-row-wide')
            );
        }
        
        return $fields;
    }

    /**
     * Track product view
     */
    public function track_product_view() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $user_email = '';
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $user_email = $user->user_email;
        }
        
        $analytics = new EEM_Analytics_Tracker();
        $analytics->track_event('product_view', array(
            'product_id' => $product->get_id(),
            'product_name' => $product->get_name(),
            'email' => $user_email,
            'environmental_score' => $this->get_product_environmental_score($product->get_id())
        ));
    }

    /**
     * Track cart addition
     */
    public function track_cart_addition($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        $user_email = '';
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $user_email = $user->user_email;
        }
        
        $product = wc_get_product($product_id);
        
        $analytics = new EEM_Analytics_Tracker();
        $analytics->track_event('cart_addition', array(
            'product_id' => $product_id,
            'product_name' => $product->get_name(),
            'quantity' => $quantity,
            'email' => $user_email,
            'cart_total' => WC()->cart->get_cart_contents_total()
        ));
    }

    /**
     * Track cart abandonment
     */
    public function track_cart_abandonment() {
        if (!is_cart() || WC()->cart->is_empty()) {
            return;
        }
        
        // JavaScript to track cart abandonment
        ?>
        <script>
        jQuery(document).ready(function($) {
            var abandonmentTimer = setTimeout(function() {
                if (typeof eem_ajax !== 'undefined') {
                    $.ajax({
                        url: eem_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'eem_track_cart_abandonment',
                            nonce: eem_ajax.nonce,
                            cart_contents: <?php echo json_encode(WC()->cart->get_cart_contents()); ?>
                        }
                    });
                }
            }, 30 * 60 * 1000); // 30 minutes
            
            // Clear timer if user proceeds to checkout
            $('body').on('click', '.checkout-button', function() {
                clearTimeout(abandonmentTimer);
            });
        });
        </script>
        <?php
    }

    /**
     * Add environmental meta box to products
     */
    public function add_environmental_meta_box() {
        add_meta_box(
            'eem_product_environmental',
            __('Environmental Impact', 'environmental-email-marketing'),
            array($this, 'render_environmental_meta_box'),
            'product',
            'normal',
            'default'
        );
    }

    /**
     * Render environmental meta box
     */
    public function render_environmental_meta_box($post) {
        wp_nonce_field('eem_product_environmental_nonce', 'eem_product_environmental_nonce');
        
        $carbon_footprint = get_post_meta($post->ID, '_eem_carbon_footprint', true);
        $sustainability_score = get_post_meta($post->ID, '_eem_sustainability_score', true);
        $eco_friendly = get_post_meta($post->ID, '_eem_eco_friendly', true);
        $renewable_materials = get_post_meta($post->ID, '_eem_renewable_materials', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="eem_carbon_footprint"><?php esc_html_e('Carbon Footprint (kg CO2)', 'environmental-email-marketing'); ?></label></th>
                <td><input type="number" id="eem_carbon_footprint" name="eem_carbon_footprint" value="<?php echo esc_attr($carbon_footprint); ?>" step="0.01" /></td>
            </tr>
            <tr>
                <th><label for="eem_sustainability_score"><?php esc_html_e('Sustainability Score (0-100)', 'environmental-email-marketing'); ?></label></th>
                <td><input type="number" id="eem_sustainability_score" name="eem_sustainability_score" value="<?php echo esc_attr($sustainability_score); ?>" min="0" max="100" /></td>
            </tr>
            <tr>
                <th><label for="eem_eco_friendly"><?php esc_html_e('Eco-Friendly Product', 'environmental-email-marketing'); ?></label></th>
                <td><input type="checkbox" id="eem_eco_friendly" name="eem_eco_friendly" value="1" <?php checked($eco_friendly, 1); ?> /></td>
            </tr>
            <tr>
                <th><label for="eem_renewable_materials"><?php esc_html_e('Made from Renewable Materials', 'environmental-email-marketing'); ?></label></th>
                <td><input type="checkbox" id="eem_renewable_materials" name="eem_renewable_materials" value="1" <?php checked($renewable_materials, 1); ?> /></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save environmental meta
     */
    public function save_environmental_meta($post_id) {
        if (!isset($_POST['eem_product_environmental_nonce']) || !wp_verify_nonce($_POST['eem_product_environmental_nonce'], 'eem_product_environmental_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = array('eem_carbon_footprint', 'eem_sustainability_score', 'eem_eco_friendly', 'eem_renewable_materials');
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    /**
     * Get product recommendations via AJAX
     */
    public function get_product_recommendations() {
        if (!wp_verify_nonce($_POST['nonce'], 'eem_frontend_nonce')) {
            wp_die('Security check failed');
        }
        
        $email = sanitize_email($_POST['email']);
        $category = sanitize_text_field($_POST['category'] ?? '');
        $limit = intval($_POST['limit'] ?? 5);
        
        $recommendations = $this->get_personalized_recommendations($email, $category, $limit);
        
        wp_send_json_success($recommendations);
    }

    /**
     * Get personalized product recommendations
     */
    private function get_personalized_recommendations($email, $category = '', $limit = 5) {
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => array(
                array(
                    'key' => '_eem_eco_friendly',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'orderby' => 'meta_value_num',
            'meta_key' => '_eem_sustainability_score',
            'order' => 'DESC'
        );
        
        if (!empty($category)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $category
                )
            );
        }
        
        $products = get_posts($args);
        $recommendations = array();
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            $recommendations[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
                'url' => $product->get_permalink(),
                'sustainability_score' => get_post_meta($product->get_id(), '_eem_sustainability_score', true),
                'eco_friendly' => get_post_meta($product->get_id(), '_eem_eco_friendly', true)
            );
        }
        
        return $recommendations;
    }

    /**
     * Calculate order environmental score
     */
    private function calculate_order_environmental_score($order) {
        $total_score = 0;
        $items = $order->get_items();
        
        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $quantity = $item->get_quantity();
            $product_score = $this->get_product_environmental_score($product_id);
            $total_score += $product_score * $quantity;
        }
        
        return $total_score;
    }

    /**
     * Get product environmental score
     */
    private function get_product_environmental_score($product_id) {
        $sustainability_score = get_post_meta($product_id, '_eem_sustainability_score', true);
        $eco_friendly = get_post_meta($product_id, '_eem_eco_friendly', true);
        $renewable_materials = get_post_meta($product_id, '_eem_renewable_materials', true);
        
        $score = intval($sustainability_score) ?: 0;
        
        if ($eco_friendly) {
            $score += 20;
        }
        
        if ($renewable_materials) {
            $score += 15;
        }
        
        return $score;
    }

    /**
     * Get order products
     */
    private function get_order_products($order) {
        $products = array();
        $items = $order->get_items();
        
        foreach ($items as $item) {
            $product = $item->get_product();
            $products[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'quantity' => $item->get_quantity(),
                'price' => $item->get_total(),
                'environmental_score' => $this->get_product_environmental_score($product->get_id())
            );
        }
        
        return $products;
    }

    /**
     * Calculate environmental impact
     */
    private function calculate_environmental_impact($order) {
        $total_carbon = 0;
        $total_sustainability = 0;
        $item_count = 0;
        
        $items = $order->get_items();
        
        foreach ($items as $item) {
            $product_id = $item->get_product_id();
            $quantity = $item->get_quantity();
            
            $carbon_footprint = floatval(get_post_meta($product_id, '_eem_carbon_footprint', true));
            $sustainability_score = intval(get_post_meta($product_id, '_eem_sustainability_score', true));
            
            $total_carbon += $carbon_footprint * $quantity;
            $total_sustainability += $sustainability_score * $quantity;
            $item_count += $quantity;
        }
        
        return array(
            'carbon_footprint' => $total_carbon,
            'sustainability_score' => $item_count > 0 ? $total_sustainability / $item_count : 0
        );
    }

    /**
     * Update subscriber environmental score
     */
    private function update_subscriber_environmental_score($email, $order) {
        $subscriber_manager = new EEM_Subscriber_Manager();
        $subscriber = $subscriber_manager->get_subscriber_by_email($email);
        
        if ($subscriber) {
            $order_score = $this->calculate_order_environmental_score($order);
            $new_score = $subscriber['environmental_score'] + $order_score;
            
            $subscriber_manager->update_subscriber($subscriber['id'], array(
                'environmental_score' => $new_score
            ));
        }
    }

    /**
     * Send purchase confirmation email
     */
    private function send_purchase_confirmation_email($order) {
        $template_engine = new EEM_Template_Engine();
        $billing_email = $order->get_billing_email();
        $first_name = $order->get_billing_first_name();
        
        $environmental_impact = $this->calculate_environmental_impact($order);
        
        $variables = array(
            'first_name' => $first_name,
            'order_id' => $order->get_id(),
            'order_total' => $order->get_total(),
            'products' => $this->get_order_products($order),
            'carbon_footprint' => $environmental_impact['carbon_footprint'],
            'sustainability_score' => $environmental_impact['sustainability_score'],
            'environmental_tips' => $this->get_environmental_tips()
        );
        
        $subject = sprintf(__('Your Eco-Friendly Purchase - Order #%s', 'environmental-email-marketing'), $order->get_id());
        $message = $template_engine->render_template('woocommerce_purchase_confirmation', $variables);
        
        wp_mail($billing_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }

    /**
     * Get environmental tips
     */
    private function get_environmental_tips() {
        return array(
            __('Recycle the packaging materials from your order', 'environmental-email-marketing'),
            __('Share your eco-friendly purchase with friends', 'environmental-email-marketing'),
            __('Sign up for our environmental newsletter for more tips', 'environmental-email-marketing'),
            __('Consider carbon offset options for shipping', 'environmental-email-marketing')
        );
    }

    /**
     * Trigger product purchase automation
     */
    public function trigger_product_purchase_automation($trigger_data, $subscriber_id) {
        // Custom logic for product purchase automation
        $campaign_manager = new EEM_Campaign_Manager();
        
        // Send personalized follow-up based on product categories
        $products = $trigger_data['products'] ?? array();
        
        foreach ($products as $product) {
            // Get product categories and send relevant follow-up campaigns
            $product_obj = wc_get_product($product['id']);
            $categories = wp_get_post_terms($product['id'], 'product_cat', array('fields' => 'names'));
            
            // Create targeted follow-up campaign based on categories
            $this->create_category_followup_campaign($categories, $subscriber_id);
        }
    }

    /**
     * Create category-specific follow-up campaign
     */
    private function create_category_followup_campaign($categories, $subscriber_id) {
        $campaign_manager = new EEM_Campaign_Manager();
        
        $category_campaigns = array(
            'solar-panels' => 'solar_followup',
            'organic-products' => 'organic_followup',
            'recycled-materials' => 'recycling_followup'
        );
        
        foreach ($categories as $category) {
            $category_slug = sanitize_title($category);
            
            if (isset($category_campaigns[$category_slug])) {
                $template_name = $category_campaigns[$category_slug];
                
                // Queue follow-up email
                wp_schedule_single_event(
                    time() + (7 * DAY_IN_SECONDS), // Send after 1 week
                    'eem_send_followup_campaign',
                    array($subscriber_id, $template_name)
                );
            }
        }
    }
}

// Initialize WooCommerce integration
new EEM_WooCommerce_Integration();
