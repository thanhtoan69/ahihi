<?php
/**
 * WooCommerce Integration for Environmental Platform
 * 
 * This class handles WooCommerce integration with custom eco-friendly features
 * including sustainability scoring, eco-certifications, and environmental impact tracking.
 * 
 * Phase 32: E-commerce Integration (WooCommerce)
 * 
 * @package EnvironmentalPlatform
 * @subpackage WooCommerce
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class EP_WooCommerce_Integration {
    
    /**
     * Instance of this class
     * @var EP_WooCommerce_Integration
     */
    private static $instance = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Check if WooCommerce is active
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize WooCommerce integration
     */
    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Initialize hooks
        $this->init_hooks();
        
        // Add custom product types
        $this->init_custom_product_types();
        
        // Add eco-friendly features
        $this->init_eco_features();
        
        // Add custom fields
        $this->init_custom_fields();
        
        // Add checkout customizations
        $this->init_checkout_customizations();
        
        // Add email customizations
        $this->init_email_customizations();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_eco_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_eco_assets'));
        
        // REST API endpoints
        add_action('rest_api_init', array($this, 'register_eco_api_endpoints'));
        
        // Product hooks
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_eco_product_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_eco_product_fields'));
        
        // Shop hooks
        add_action('woocommerce_shop_loop_item_title', array($this, 'add_eco_badge'), 5);
        add_filter('woocommerce_product_tabs', array($this, 'add_sustainability_tab'));
        
        // Cart hooks
        add_filter('woocommerce_cart_item_name', array($this, 'add_eco_info_to_cart'), 10, 3);
        add_action('woocommerce_cart_totals_after_order_total', array($this, 'add_environmental_impact_display'));
        
        // Checkout hooks
        add_action('woocommerce_checkout_fields', array($this, 'add_eco_checkout_fields'));
        add_action('woocommerce_checkout_process', array($this, 'validate_eco_checkout_fields'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_eco_checkout_fields'));
        
        // Order hooks
        add_action('woocommerce_order_status_completed', array($this, 'award_eco_points'));
        add_action('woocommerce_order_status_completed', array($this, 'track_environmental_impact'));
        
        // Email hooks
        add_action('woocommerce_email_order_meta', array($this, 'add_eco_info_to_emails'), 10, 3);
        
        // Admin hooks
        add_filter('woocommerce_product_data_tabs', array($this, 'add_eco_product_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'add_eco_product_panel'));
        
        // Reports hooks
        add_filter('woocommerce_admin_reports', array($this, 'add_eco_reports'));
        
        // Search and filtering
        add_action('pre_get_posts', array($this, 'add_eco_product_filters'));
        add_filter('woocommerce_product_query_meta_query', array($this, 'eco_product_meta_query'), 10, 2);
    }
    
    /**
     * Initialize custom product types
     */
    private function init_custom_product_types() {
        // Add eco-friendly product type
        add_filter('product_type_selector', array($this, 'add_eco_product_type'));
        add_action('woocommerce_product_options_general_product_data', array($this, 'eco_product_type_options'));
        add_action('woocommerce_process_product_meta_eco_friendly', array($this, 'save_eco_product_type_options'));
    }
    
    /**
     * Initialize eco-friendly features
     */
    private function init_eco_features() {
        // Carbon footprint calculation
        add_action('woocommerce_add_to_cart', array($this, 'calculate_cart_carbon_footprint'), 10, 6);
        
        // Sustainability scoring
        add_filter('woocommerce_product_get_rating_html', array($this, 'add_sustainability_rating'), 10, 3);
        
        // Eco-certifications display
        add_action('woocommerce_single_product_summary', array($this, 'display_eco_certifications'), 25);
        
        // Green shipping options
        add_filter('woocommerce_package_rates', array($this, 'add_green_shipping_options'), 10, 2);
        
        // Eco-friendly payment options
        add_action('woocommerce_review_order_after_payment', array($this, 'display_eco_payment_info'));
    }
    
    /**
     * Initialize custom fields
     */
    private function init_custom_fields() {
        // Product custom fields
        add_action('woocommerce_product_options_advanced', array($this, 'add_advanced_eco_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_advanced_eco_fields'));
        
        // Category custom fields
        add_action('product_cat_add_form_fields', array($this, 'add_category_eco_fields'));
        add_action('product_cat_edit_form_fields', array($this, 'edit_category_eco_fields'), 10, 2);
        add_action('edited_product_cat', array($this, 'save_category_eco_fields'), 10, 2);
        add_action('create_product_cat', array($this, 'save_category_eco_fields'), 10, 2);
    }
    
    /**
     * Initialize checkout customizations
     */
    private function init_checkout_customizations() {
        // Eco-friendly delivery options
        add_action('woocommerce_after_checkout_billing_form', array($this, 'add_eco_delivery_options'));
        
        // Carbon offset options
        add_action('woocommerce_checkout_after_order_review', array($this, 'add_carbon_offset_options'));
        
        // Packaging preferences
        add_action('woocommerce_after_checkout_shipping_form', array($this, 'add_packaging_preferences'));
    }
    
    /**
     * Initialize email customizations
     */
    private function init_email_customizations() {
        // Add eco-impact to order emails
        add_action('woocommerce_email_customer_details', array($this, 'add_eco_impact_to_customer_email'), 15, 4);
        
        // Add sustainability tips to emails
        add_action('woocommerce_email_order_meta', array($this, 'add_sustainability_tips_to_email'), 20, 3);
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('Environmental Platform WooCommerce Integration requires WooCommerce to be installed and activated.', 'environmental-platform-core');
        echo '</p></div>';
    }
    
    /**
     * Add eco-friendly product fields
     */
    public function add_eco_product_fields() {
        global $woocommerce, $post;
        
        echo '<div class="options_group">';
        
        // Sustainability Score
        woocommerce_wp_text_input(array(
            'id' => '_sustainability_score',
            'label' => __('Sustainability Score (1-100)', 'environmental-platform-core'),
            'placeholder' => '0-100',
            'desc_tip' => true,
            'description' => __('Overall sustainability rating of this product', 'environmental-platform-core'),
            'type' => 'number',
            'custom_attributes' => array(
                'min' => '1',
                'max' => '100',
                'step' => '1'
            )
        ));
        
        // Carbon Footprint
        woocommerce_wp_text_input(array(
            'id' => '_carbon_footprint_kg',
            'label' => __('Carbon Footprint (kg CO2)', 'environmental-platform-core'),
            'placeholder' => '0.00',
            'desc_tip' => true,
            'description' => __('Carbon footprint per unit in kg CO2 equivalent', 'environmental-platform-core'),
            'type' => 'number',
            'custom_attributes' => array(
                'min' => '0',
                'step' => '0.01'
            )
        ));
        
        // Eco Rating
        woocommerce_wp_select(array(
            'id' => '_eco_rating',
            'label' => __('Eco Rating', 'environmental-platform-core'),
            'desc_tip' => true,
            'description' => __('Environmental impact rating', 'environmental-platform-core'),
            'options' => array(
                '' => __('Select rating...', 'environmental-platform-core'),
                'A+' => __('A+ (Excellent)', 'environmental-platform-core'),
                'A' => __('A (Very Good)', 'environmental-platform-core'),
                'B' => __('B (Good)', 'environmental-platform-core'),
                'C' => __('C (Fair)', 'environmental-platform-core'),
                'D' => __('D (Poor)', 'environmental-platform-core'),
                'E' => __('E (Very Poor)', 'environmental-platform-core')
            )
        ));
        
        // Is Eco-Friendly
        woocommerce_wp_checkbox(array(
            'id' => '_is_eco_friendly',
            'label' => __('Eco-Friendly Product', 'environmental-platform-core'),
            'desc_tip' => true,
            'description' => __('Mark this product as eco-friendly', 'environmental-platform-core')
        ));
        
        // Recyclable
        woocommerce_wp_checkbox(array(
            'id' => '_is_recyclable',
            'label' => __('Recyclable', 'environmental-platform-core'),
            'desc_tip' => true,
            'description' => __('Product or packaging is recyclable', 'environmental-platform-core')
        ));
        
        // Biodegradable
        woocommerce_wp_checkbox(array(
            'id' => '_is_biodegradable',
            'label' => __('Biodegradable', 'environmental-platform-core'),
            'desc_tip' => true,
            'description' => __('Product is biodegradable', 'environmental-platform-core')
        ));
        
        echo '</div>';
    }
    
    /**
     * Save eco-friendly product fields
     */
    public function save_eco_product_fields($post_id) {
        // Sustainability Score
        $sustainability_score = isset($_POST['_sustainability_score']) ? intval($_POST['_sustainability_score']) : '';
        if ($sustainability_score !== '') {
            $sustainability_score = max(1, min(100, $sustainability_score));
            update_post_meta($post_id, '_sustainability_score', $sustainability_score);
        }
        
        // Carbon Footprint
        $carbon_footprint = isset($_POST['_carbon_footprint_kg']) ? floatval($_POST['_carbon_footprint_kg']) : '';
        if ($carbon_footprint !== '') {
            update_post_meta($post_id, '_carbon_footprint_kg', $carbon_footprint);
        }
        
        // Eco Rating
        $eco_rating = isset($_POST['_eco_rating']) ? sanitize_text_field($_POST['_eco_rating']) : '';
        update_post_meta($post_id, '_eco_rating', $eco_rating);
        
        // Checkboxes
        $is_eco_friendly = isset($_POST['_is_eco_friendly']) ? 'yes' : 'no';
        update_post_meta($post_id, '_is_eco_friendly', $is_eco_friendly);
        
        $is_recyclable = isset($_POST['_is_recyclable']) ? 'yes' : 'no';
        update_post_meta($post_id, '_is_recyclable', $is_recyclable);
        
        $is_biodegradable = isset($_POST['_is_biodegradable']) ? 'yes' : 'no';
        update_post_meta($post_id, '_is_biodegradable', $is_biodegradable);
        
        // Update product in environmental platform database
        $this->sync_product_to_ep_database($post_id);
    }
    
    /**
     * Add eco badge to shop loop
     */
    public function add_eco_badge() {
        global $product;
        
        $is_eco_friendly = get_post_meta($product->get_id(), '_is_eco_friendly', true);
        $eco_rating = get_post_meta($product->get_id(), '_eco_rating', true);
        
        if ($is_eco_friendly === 'yes' || !empty($eco_rating)) {
            echo '<div class="eco-badge">';
            if ($is_eco_friendly === 'yes') {
                echo '<span class="eco-friendly-badge">' . __('Eco-Friendly', 'environmental-platform-core') . '</span>';
            }
            if (!empty($eco_rating)) {
                echo '<span class="eco-rating-badge">Eco: ' . esc_html($eco_rating) . '</span>';
            }
            echo '</div>';
        }
    }
    
    /**
     * Add sustainability tab to product page
     */
    public function add_sustainability_tab($tabs) {
        $tabs['sustainability'] = array(
            'title' => __('Sustainability', 'environmental-platform-core'),
            'priority' => 50,
            'callback' => array($this, 'sustainability_tab_content')
        );
        return $tabs;
    }
    
    /**
     * Sustainability tab content
     */
    public function sustainability_tab_content() {
        global $product;
        
        $sustainability_score = get_post_meta($product->get_id(), '_sustainability_score', true);
        $carbon_footprint = get_post_meta($product->get_id(), '_carbon_footprint_kg', true);
        $eco_rating = get_post_meta($product->get_id(), '_eco_rating', true);
        $is_recyclable = get_post_meta($product->get_id(), '_is_recyclable', true);
        $is_biodegradable = get_post_meta($product->get_id(), '_is_biodegradable', true);
        
        echo '<div class="sustainability-info">';
        echo '<h3>' . __('Environmental Impact', 'environmental-platform-core') . '</h3>';
        
        if (!empty($sustainability_score)) {
            echo '<div class="sustainability-score">';
            echo '<strong>' . __('Sustainability Score:', 'environmental-platform-core') . '</strong> ';
            echo '<span class="score">' . esc_html($sustainability_score) . '/100</span>';
            echo '<div class="score-bar"><div class="score-fill" style="width: ' . esc_attr($sustainability_score) . '%"></div></div>';
            echo '</div>';
        }
        
        if (!empty($carbon_footprint)) {
            echo '<div class="carbon-footprint">';
            echo '<strong>' . __('Carbon Footprint:', 'environmental-platform-core') . '</strong> ';
            echo '<span class="footprint">' . esc_html($carbon_footprint) . ' kg CO2</span>';
            echo '</div>';
        }
        
        if (!empty($eco_rating)) {
            echo '<div class="eco-rating">';
            echo '<strong>' . __('Eco Rating:', 'environmental-platform-core') . '</strong> ';
            echo '<span class="rating rating-' . strtolower(str_replace('+', 'plus', $eco_rating)) . '">' . esc_html($eco_rating) . '</span>';
            echo '</div>';
        }
        
        echo '<div class="eco-features">';
        echo '<h4>' . __('Eco Features:', 'environmental-platform-core') . '</h4>';
        echo '<ul>';
        
        if ($is_recyclable === 'yes') {
            echo '<li class="feature recyclable">♻️ ' . __('Recyclable', 'environmental-platform-core') . '</li>';
        }
        
        if ($is_biodegradable === 'yes') {
            echo '<li class="feature biodegradable">🌱 ' . __('Biodegradable', 'environmental-platform-core') . '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Add eco info to cart items
     */
    public function add_eco_info_to_cart($item_name, $cart_item, $cart_item_key) {
        $product = $cart_item['data'];
        $is_eco_friendly = get_post_meta($product->get_id(), '_is_eco_friendly', true);
        
        if ($is_eco_friendly === 'yes') {
            $item_name .= ' <span class="eco-cart-badge">🌱 ' . __('Eco', 'environmental-platform-core') . '</span>';
        }
        
        return $item_name;
    }
    
    /**
     * Add environmental impact display to cart totals
     */
    public function add_environmental_impact_display() {
        $total_carbon = $this->calculate_total_cart_carbon_footprint();
        $total_sustainability = $this->calculate_total_cart_sustainability_score();
        
        if ($total_carbon > 0 || $total_sustainability > 0) {
            echo '<tr class="environmental-impact">';
            echo '<th colspan="2">' . __('Environmental Impact', 'environmental-platform-core') . '</th>';
            echo '</tr>';
            
            if ($total_carbon > 0) {
                echo '<tr class="carbon-footprint-total">';
                echo '<th>' . __('Total Carbon Footprint:', 'environmental-platform-core') . '</th>';
                echo '<td><span class="amount">' . number_format($total_carbon, 2) . ' kg CO2</span></td>';
                echo '</tr>';
            }
            
            if ($total_sustainability > 0) {
                echo '<tr class="sustainability-score-total">';
                echo '<th>' . __('Avg. Sustainability Score:', 'environmental-platform-core') . '</th>';
                echo '<td><span class="amount">' . number_format($total_sustainability, 1) . '/100</span></td>';
                echo '</tr>';
            }
        }
    }
    
    /**
     * Award eco points when order is completed
     */
    public function award_eco_points($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        
        if (!$user_id) {
            return;
        }
        
        $eco_points = 0;
        $carbon_saved = 0;
        
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $quantity = $item->get_quantity();
            
            $is_eco_friendly = get_post_meta($product->get_id(), '_is_eco_friendly', true);
            $sustainability_score = get_post_meta($product->get_id(), '_sustainability_score', true);
            $carbon_footprint = get_post_meta($product->get_id(), '_carbon_footprint_kg', true);
            
            if ($is_eco_friendly === 'yes') {
                $eco_points += 10 * $quantity; // Base eco points
            }
            
            if (!empty($sustainability_score)) {
                $eco_points += intval($sustainability_score / 10) * $quantity; // Bonus based on score
            }
            
            if (!empty($carbon_footprint)) {
                // Calculate carbon saved compared to average product
                $average_carbon = 2.0; // Average carbon footprint estimate
                $carbon_saved += max(0, ($average_carbon - floatval($carbon_footprint)) * $quantity);
            }
        }
        
        // Save to environmental platform database
        global $wpdb;
        
        if ($eco_points > 0) {
            // Add eco points to user
            $wpdb->query($wpdb->prepare("
                INSERT INTO {$wpdb->prefix}user_points (user_id, points_type, points_value, source, reference_type, reference_id, created_at)
                VALUES (%d, 'eco_points', %d, 'woocommerce_purchase', 'order', %d, NOW())
            ", $user_id, $eco_points, $order_id));
            
            // Update user's total eco points
            $wpdb->query($wpdb->prepare("
                UPDATE {$wpdb->prefix}users 
                SET green_points = green_points + %d 
                WHERE user_id = %d
            ", $eco_points, $user_id));
        }
        
        if ($carbon_saved > 0) {
            // Track carbon footprint reduction
            $wpdb->query($wpdb->prepare("
                INSERT INTO {$wpdb->prefix}carbon_footprints (user_id, activity_type, carbon_amount, category, description, recorded_date)
                VALUES (%d, 'eco_purchase', %f, 'shopping', 'Carbon saved through eco-friendly purchase', NOW())
            ", $user_id, -$carbon_saved));
        }
        
        // Add order meta for tracking
        $order->add_meta_data('_eco_points_awarded', $eco_points);
        $order->add_meta_data('_carbon_saved', $carbon_saved);
        $order->save();
    }
    
    /**
     * Calculate total cart carbon footprint
     */
    private function calculate_total_cart_carbon_footprint() {
        $total = 0;
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $quantity = $cart_item['quantity'];
            $carbon_footprint = get_post_meta($product->get_id(), '_carbon_footprint_kg', true);
            
            if (!empty($carbon_footprint)) {
                $total += floatval($carbon_footprint) * $quantity;
            }
        }
        
        return $total;
    }
    
    /**
     * Calculate total cart sustainability score
     */
    private function calculate_total_cart_sustainability_score() {
        $total_score = 0;
        $total_items = 0;
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $quantity = $cart_item['quantity'];
            $sustainability_score = get_post_meta($product->get_id(), '_sustainability_score', true);
            
            if (!empty($sustainability_score)) {
                $total_score += intval($sustainability_score) * $quantity;
                $total_items += $quantity;
            }
        }
        
        return $total_items > 0 ? $total_score / $total_items : 0;
    }
    
    /**
     * Sync product to environmental platform database
     */
    private function sync_product_to_ep_database($post_id) {
        global $wpdb;
        
        $product = wc_get_product($post_id);
        if (!$product) {
            return;
        }
        
        // Get product data
        $sustainability_score = get_post_meta($post_id, '_sustainability_score', true);
        $carbon_footprint = get_post_meta($post_id, '_carbon_footprint_kg', true);
        $eco_rating = get_post_meta($post_id, '_eco_rating', true);
        $is_eco_friendly = get_post_meta($post_id, '_is_eco_friendly', true) === 'yes';
        $is_recyclable = get_post_meta($post_id, '_is_recyclable', true) === 'yes';
        $is_biodegradable = get_post_meta($post_id, '_is_biodegradable', true) === 'yes';
        
        // Check if product exists in EP database
        $ep_product = $wpdb->get_row($wpdb->prepare("
            SELECT product_id FROM {$wpdb->prefix}products 
            WHERE wc_product_id = %d
        ", $post_id));
        
        $data = array(
            'name' => $product->get_name(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'price' => $product->get_price(),
            'sustainability_score' => !empty($sustainability_score) ? intval($sustainability_score) : null,
            'carbon_footprint_kg' => !empty($carbon_footprint) ? floatval($carbon_footprint) : null,
            'eco_rating' => !empty($eco_rating) ? $eco_rating : null,
            'is_eco_friendly' => $is_eco_friendly,
            'is_recyclable' => $is_recyclable,
            'is_biodegradable' => $is_biodegradable,
            'wc_product_id' => $post_id,
            'updated_at' => current_time('mysql')
        );
        
        if ($ep_product) {
            // Update existing product
            $wpdb->update(
                $wpdb->prefix . 'products',
                $data,
                array('product_id' => $ep_product->product_id)
            );
        } else {
            // Insert new product
            $data['created_at'] = current_time('mysql');
            $data['status'] = 'active';
            
            $wpdb->insert(
                $wpdb->prefix . 'products',
                $data
            );
        }
    }
    
    /**
     * Add green shipping options
     */
    public function add_green_shipping_options($rates, $package) {
        $settings = get_option('ep_woocommerce_settings', array());
        
        if (empty($settings['green_shipping_enabled'])) {
            return $rates;
        }
        
        // Add carbon-neutral shipping option
        $rates['ep_carbon_neutral'] = new WC_Shipping_Rate(
            'ep_carbon_neutral',
            __('Carbon Neutral Delivery', 'environmental-platform-core'),
            '5.00',
            array(),
            'ep_green_shipping'
        );
        
        // Add electric vehicle delivery option
        $rates['ep_electric_delivery'] = new WC_Shipping_Rate(
            'ep_electric_delivery',
            __('Electric Vehicle Delivery', 'environmental-platform-core'),
            '7.50',
            array(),
            'ep_green_shipping'
        );
        
        // Add consolidated shipping option
        $rates['ep_consolidated'] = new WC_Shipping_Rate(
            'ep_consolidated',
            __('Eco-Friendly Consolidated Shipping', 'environmental-platform-core'),
            '3.00',
            array(),
            'ep_green_shipping'
        );
        
        return $rates;
    }
    
    /**
     * Add eco checkout fields
     */
    public function add_eco_checkout_fields($fields) {
        $settings = get_option('ep_woocommerce_settings', array());
        
        // Carbon offset option
        if (!empty($settings['carbon_offset_checkout'])) {
            $total_carbon = $this->calculate_total_cart_carbon_footprint();
            if ($total_carbon > 0) {
                $offset_cost = number_format($total_carbon * 0.02, 2); // $0.02 per kg CO2
                
                $fields['billing']['billing_carbon_offset'] = array(
                    'type' => 'checkbox',
                    'label' => sprintf(
                        __('Purchase carbon offset (+$%s to neutralize %s kg CO2)', 'environmental-platform-core'),
                        $offset_cost,
                        number_format($total_carbon, 2)
                    ),
                    'required' => false,
                    'class' => array('form-row-wide'),
                    'priority' => 200
                );
            }
        }
        
        // Eco packaging preferences
        if (!empty($settings['eco_packaging_options'])) {
            $fields['shipping']['shipping_eco_packaging'] = array(
                'type' => 'select',
                'label' => __('Packaging Preference', 'environmental-platform-core'),
                'required' => false,
                'class' => array('form-row-wide'),
                'options' => array(
                    '' => __('Standard packaging', 'environmental-platform-core'),
                    'minimal' => __('Minimal packaging', 'environmental-platform-core'),
                    'recyclable' => __('100% recyclable packaging', 'environmental-platform-core'),
                    'biodegradable' => __('Biodegradable packaging', 'environmental-platform-core'),
                    'reusable' => __('Reusable packaging (+$2.00)', 'environmental-platform-core')
                ),
                'priority' => 110
            );
        }
        
        // Newsletter signup for sustainability tips
        $fields['billing']['billing_eco_newsletter'] = array(
            'type' => 'checkbox',
            'label' => __('Send me sustainability tips and eco-friendly product updates', 'environmental-platform-core'),
            'required' => false,
            'class' => array('form-row-wide'),
            'priority' => 210
        );
        
        return $fields;
    }
    
    /**
     * Validate eco checkout fields
     */
    public function validate_eco_checkout_fields() {
        // Add any validation if needed for eco fields
        // Currently no validation required for optional eco fields
    }
    
    /**
     * Save eco checkout fields
     */
    public function save_eco_checkout_fields($order_id) {
        $order = wc_get_order($order_id);
        
        // Save carbon offset choice
        if (isset($_POST['billing_carbon_offset']) && $_POST['billing_carbon_offset']) {
            $total_carbon = $this->calculate_total_cart_carbon_footprint();
            $offset_cost = $total_carbon * 0.02;
            
            $order->add_meta_data('_carbon_offset_purchased', true);
            $order->add_meta_data('_carbon_offset_amount', $total_carbon);
            $order->add_meta_data('_carbon_offset_cost', $offset_cost);
            
            // Add offset cost to order
            $item = new WC_Order_Item_Fee();
            $item->set_name(__('Carbon Offset', 'environmental-platform-core'));
            $item->set_amount($offset_cost);
            $item->set_total($offset_cost);
            $order->add_item($item);
        }
        
        // Save packaging preference
        if (isset($_POST['shipping_eco_packaging']) && !empty($_POST['shipping_eco_packaging'])) {
            $packaging = sanitize_text_field($_POST['shipping_eco_packaging']);
            $order->add_meta_data('_eco_packaging_preference', $packaging);
            
            // Add cost for reusable packaging
            if ($packaging === 'reusable') {
                $item = new WC_Order_Item_Fee();
                $item->set_name(__('Reusable Packaging', 'environmental-platform-core'));
                $item->set_amount(2.00);
                $item->set_total(2.00);
                $order->add_item($item);
            }
        }
        
        // Save newsletter preference
        if (isset($_POST['billing_eco_newsletter']) && $_POST['billing_eco_newsletter']) {
            $order->add_meta_data('_eco_newsletter_signup', true);
            
            // Add user to newsletter list (integrate with your newsletter system)
            $user_id = $order->get_user_id();
            if ($user_id) {
                update_user_meta($user_id, 'ep_eco_newsletter', true);
            }
        }
        
        // Calculate and save environmental impact
        $environmental_impact = $this->calculate_order_environmental_impact($order);
        $order->add_meta_data('_order_environmental_impact', $environmental_impact);
        
        $order->save();
    }
    
    /**
     * Calculate order environmental impact
     */
    private function calculate_order_environmental_impact($order) {
        $total_carbon = 0;
        $total_score = 0;
        $item_count = 0;
        $eco_products = 0;
        
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $quantity = $item->get_quantity();
            
            $carbon_footprint = get_post_meta($product->get_id(), '_carbon_footprint_kg', true);
            $sustainability_score = get_post_meta($product->get_id(), '_sustainability_score', true);
            $is_eco_friendly = get_post_meta($product->get_id(), '_is_eco_friendly', true);
            
            if (!empty($carbon_footprint)) {
                $total_carbon += floatval($carbon_footprint) * $quantity;
            }
            
            if (!empty($sustainability_score)) {
                $total_score += intval($sustainability_score) * $quantity;
            }
            
            if ($is_eco_friendly === 'yes') {
                $eco_products += $quantity;
            }
            
            $item_count += $quantity;
        }
        
        return array(
            'total_carbon_kg' => $total_carbon,
            'average_sustainability_score' => $item_count > 0 ? $total_score / $item_count : 0,
            'total_eco_score' => $total_score,
            'eco_products_count' => $eco_products,
            'total_items' => $item_count,
            'eco_percentage' => $item_count > 0 ? ($eco_products / $item_count) * 100 : 0
        );
    }
    
    /**
     * Track environmental impact when order is completed
     */
    public function track_environmental_impact($order_id) {
        global $wpdb;
        
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        
        if (!$user_id) {
            return;
        }
        
        $environmental_impact = $order->get_meta('_order_environmental_impact');
        if (empty($environmental_impact)) {
            return;
        }
        
        // Save to environmental tracking table
        $wpdb->insert(
            $wpdb->prefix . 'carbon_footprints',
            array(
                'user_id' => $user_id,
                'activity_type' => 'online_purchase',
                'carbon_amount' => $environmental_impact['total_carbon_kg'],
                'category' => 'shopping',
                'description' => 'WooCommerce order #' . $order->get_order_number(),
                'recorded_date' => current_time('mysql')
            )
        );
        
        // Update user's carbon footprint totals
        $wpdb->query($wpdb->prepare("
            UPDATE {$wpdb->prefix}users 
            SET carbon_footprint = carbon_footprint + %f
            WHERE user_id = %d
        ", $environmental_impact['total_carbon_kg'], $user_id));
        
        // Award additional eco points for high eco percentage
        if ($environmental_impact['eco_percentage'] > 50) {
            $bonus_points = intval($environmental_impact['eco_percentage'] * 2);
            
            $wpdb->insert(
                $wpdb->prefix . 'user_points',
                array(
                    'user_id' => $user_id,
                    'points_type' => 'eco_points',
                    'points_value' => $bonus_points,
                    'source' => 'eco_shopping_bonus',
                    'reference_type' => 'order',
                    'reference_id' => $order_id,
                    'created_at' => current_time('mysql')
                )
            );
            
            $wpdb->query($wpdb->prepare("
                UPDATE {$wpdb->prefix}users 
                SET green_points = green_points + %d 
                WHERE user_id = %d
            ", $bonus_points, $user_id));
            
            $order->add_meta_data('_eco_bonus_points', $bonus_points);
            $order->save();
        }
    }
    
    /**
     * Add eco info to emails
     */
    public function add_eco_info_to_emails($order, $sent_to_admin, $plain_text) {
        if ($plain_text) {
            return;
        }
        
        $environmental_impact = $order->get_meta('_order_environmental_impact');
        $eco_points_earned = $order->get_meta('_eco_points_awarded');
        
        if ($environmental_impact || $eco_points_earned) {
            // Include the email template
            wc_get_template(
                'emails/environmental-impact.php',
                array('order' => $order),
                '',
                EP_CORE_PLUGIN_DIR . 'templates/'
            );
        }
    }
    
    /**
     * Add eco impact to customer email
     */
    public function add_eco_impact_to_customer_email($order, $sent_to_admin, $plain_text, $email) {
        // Only show to customers, not admin
        if ($sent_to_admin) {
            return;
        }
        
        $this->add_eco_info_to_emails($order, $sent_to_admin, $plain_text);
    }
    
    /**
     * Add sustainability tips to email
     */
    public function add_sustainability_tips_to_email($order, $sent_to_admin, $plain_text) {
        if ($plain_text || $sent_to_admin) {
            return;
        }
        
        $eco_newsletter = $order->get_meta('_eco_newsletter_signup');
        if ($eco_newsletter) {
            echo '<div style="margin: 20px 0; padding: 15px; background-color: #f0f9ff; border-left: 4px solid #2196F3;">';
            echo '<h3 style="color: #1976D2; margin-top: 0;">💡 ' . __('Sustainability Tips', 'environmental-platform-core') . '</h3>';
            echo '<ul style="margin: 0; padding-left: 20px;">';
            echo '<li>' . __('Share your eco-friendly purchase on social media to inspire others', 'environmental-platform-core') . '</li>';
            echo '<li>' . __('Properly dispose of or recycle the product packaging', 'environmental-platform-core') . '</li>';
            echo '<li>' . __('Consider the product\'s full lifecycle when using it', 'environmental-platform-core') . '</li>';
            echo '</ul>';
            echo '</div>';
        }
    }
    
    /**
     * Add eco product tab to admin
     */
    public function add_eco_product_tab($tabs) {
        $tabs['environmental'] = array(
            'label' => __('Environmental', 'environmental-platform-core'),
            'target' => 'environmental_product_data',
            'class' => array('show_if_simple', 'show_if_variable'),
            'priority' => 21
        );
        return $tabs;
    }
    
    /**
     * Add eco product panel to admin
     */
    public function add_eco_product_panel() {
        global $woocommerce, $post;
        
        echo '<div id="environmental_product_data" class="panel woocommerce_options_panel">';
        
        echo '<div class="options_group">';
        
        // Eco certifications
        woocommerce_wp_textarea_input(array(
            'id' => '_eco_certifications',
            'label' => __('Eco Certifications', 'environmental-platform-core'),
            'placeholder' => __('List any environmental certifications (e.g., ENERGY STAR, EPEAT, etc.)', 'environmental-platform-core'),
            'desc_tip' => true,
            'description' => __('Environmental certifications and standards this product meets', 'environmental-platform-core'),
            'rows' => 3
        ));
        
        // Materials used
        woocommerce_wp_textarea_input(array(
            'id' => '_eco_materials',
            'label' => __('Sustainable Materials', 'environmental-platform-core'),
            'placeholder' => __('Describe sustainable materials used', 'environmental-platform-core'),
            'desc_tip' => true,
            'description' => __('Information about sustainable or recycled materials used', 'environmental-platform-core'),
            'rows' => 3
        ));
        
        // Manufacturing process
        woocommerce_wp_textarea_input(array(
            'id' => '_eco_manufacturing',
            'label' => __('Eco Manufacturing', 'environmental-platform-core'),
            'placeholder' => __('Describe eco-friendly manufacturing processes', 'environmental-platform-core'),
            'desc_tip' => true,
            'description' => __('Information about environmentally friendly manufacturing', 'environmental-platform-core'),
            'rows' => 3
        ));
        
        // End of life disposal
        woocommerce_wp_textarea_input(array(
            'id' => '_eco_disposal',
            'label' => __('End-of-Life Disposal', 'environmental-platform-core'),
            'placeholder' => __('Instructions for eco-friendly disposal or recycling', 'environmental-platform-core'),
            'desc_tip' => true,
            'description' => __('How to properly dispose of or recycle this product', 'environmental-platform-core'),
            'rows' => 3
        ));
        
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Add eco reports to WooCommerce reports
     */
    public function add_eco_reports($reports) {
        $reports['environmental'] = array(
            'title' => __('Environmental Impact', 'environmental-platform-core'),
            'reports' => array(
                'eco_products' => array(
                    'title' => __('Eco Products Performance', 'environmental-platform-core'),
                    'description' => '',
                    'hide_title' => true,
                    'callback' => array($this, 'eco_products_report')
                ),
                'carbon_footprint' => array(
                    'title' => __('Carbon Footprint Report', 'environmental-platform-core'),
                    'description' => '',
                    'hide_title' => true,
                    'callback' => array($this, 'carbon_footprint_report')
                )
            )
        );
        
        return $reports;
    }
    
    /**
     * Eco products report
     */
    public function eco_products_report() {
        global $wpdb;
        
        // Get eco products sales data
        $eco_sales = $wpdb->get_results("
            SELECT p.ID, p.post_title, pm1.meta_value as sustainability_score, 
                   pm2.meta_value as carbon_footprint, COUNT(oi.order_item_id) as sales_count,
                   SUM(oi.product_qty) as total_quantity
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_sustainability_score'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_carbon_footprint_kg'
            LEFT JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.product_id = p.ID
            WHERE p.post_type = 'product' AND p.post_status = 'publish'
            AND (pm1.meta_value IS NOT NULL OR pm2.meta_value IS NOT NULL)
            GROUP BY p.ID
            ORDER BY total_quantity DESC
        ");
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Product', 'environmental-platform-core') . '</th>';
        echo '<th>' . __('Eco Score', 'environmental-platform-core') . '</th>';
        echo '<th>' . __('Carbon Footprint', 'environmental-platform-core') . '</th>';
        echo '<th>' . __('Sales Count', 'environmental-platform-core') . '</th>';
        echo '<th>' . __('Total Quantity', 'environmental-platform-core') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($eco_sales as $product) {
            echo '<tr>';
            echo '<td>' . esc_html($product->post_title) . '</td>';
            echo '<td>' . (!empty($product->sustainability_score) ? esc_html($product->sustainability_score) . '/100' : '-') . '</td>';
            echo '<td>' . (!empty($product->carbon_footprint) ? esc_html($product->carbon_footprint) . ' kg CO2' : '-') . '</td>';
            echo '<td>' . intval($product->sales_count) . '</td>';
            echo '<td>' . intval($product->total_quantity) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Carbon footprint report
     */
    public function carbon_footprint_report() {
        global $wpdb;
        
        // Get carbon footprint data by month
        $carbon_data = $wpdb->get_results("
            SELECT DATE_FORMAT(om.meta_value, '%Y-%m') as month,
                   SUM(JSON_EXTRACT(omi.meta_value, '$.total_carbon_kg')) as total_carbon,
                   COUNT(*) as order_count
            FROM {$wpdb->postmeta} om
            JOIN {$wpdb->postmeta} omi ON om.post_id = omi.post_id
            WHERE om.meta_key = '_completed_date'
            AND omi.meta_key = '_order_environmental_impact'
            AND om.meta_value >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY month
            ORDER BY month DESC
        ");
        
        echo '<div class="carbon-footprint-chart">';
        echo '<h3>' . __('Monthly Carbon Footprint from Orders', 'environmental-platform-core') . '</h3>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Month', 'environmental-platform-core') . '</th>';
        echo '<th>' . __('Total CO2 (kg)', 'environmental-platform-core') . '</th>';
        echo '<th>' . __('Orders', 'environmental-platform-core') . '</th>';
        echo '<th>' . __('Avg per Order', 'environmental-platform-core') . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($carbon_data as $data) {
            $avg_per_order = $data->order_count > 0 ? $data->total_carbon / $data->order_count : 0;
            echo '<tr>';
            echo '<td>' . esc_html($data->month) . '</td>';
            echo '<td>' . number_format($data->total_carbon, 2) . '</td>';
            echo '<td>' . intval($data->order_count) . '</td>';
            echo '<td>' . number_format($avg_per_order, 2) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }
    
    /**
     * Enqueue eco-friendly assets for frontend
     */
    public function enqueue_eco_assets() {
        if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
            return;
        }
        
        wp_enqueue_style(
            'ep-woocommerce-eco',
            EP_CORE_PLUGIN_URL . 'assets/woocommerce-eco.css',
            array(),
            EP_CORE_VERSION
        );
        
        wp_enqueue_script(
            'ep-woocommerce-eco',
            EP_CORE_PLUGIN_URL . 'assets/woocommerce-eco.js',
            array('jquery', 'wc-checkout'),
            EP_CORE_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('ep-woocommerce-eco', 'ep_wc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'home_url' => home_url(),
            'nonce' => wp_create_nonce('ep_wc_nonce')
        ));
        
        // Add inline styles for dynamic eco badges
        $this->add_dynamic_eco_styles();
    }
    
    /**
     * Enqueue eco-friendly assets for admin
     */
    public function enqueue_admin_eco_assets($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php', 'edit.php'))) {
            return;
        }
        
        if (get_post_type() !== 'product') {
            return;
        }
        
        wp_enqueue_style(
            'ep-admin-eco',
            EP_CORE_PLUGIN_URL . 'assets/woocommerce-eco.css',
            array(),
            EP_CORE_VERSION
        );
    }
    
    /**
     * Add dynamic eco styles based on settings
     */
    private function add_dynamic_eco_styles() {
        $settings = get_option('ep_woocommerce_settings', array());
        
        $css = '';
        
        // Hide eco elements if disabled
        if (empty($settings['eco_scoring_enabled'])) {
            $css .= '.ep-sustainability-score { display: none !important; }';
        }
        
        if (empty($settings['carbon_footprint_tracking'])) {
            $css .= '.ep-carbon-footprint { display: none !important; }';
        }
        
        if (empty($settings['eco_certification_display'])) {
            $css .= '.ep-eco-badge { display: none !important; }';
        }
        
        if (!empty($css)) {
            wp_add_inline_style('ep-woocommerce-eco', $css);
        }
    }
    
    /**
     * Register REST API endpoints for mobile app integration
     */
    public function register_eco_api_endpoints() {
        // Get eco-friendly products
        register_rest_route('environmental-platform/v1', '/eco-products', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_eco_products_api'),
            'permission_callback' => function() {
                return current_user_can('read');
            },
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'sanitize_callback' => 'absint'
                ),
                'per_page' => array(
                    'default' => 10,
                    'sanitize_callback' => 'absint'
                ),
                'eco_rating' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'min_score' => array(
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        // Get product environmental data
        register_rest_route('environmental-platform/v1', '/product/(?P<id>\d+)/eco-data', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_product_eco_data_api'),
            'permission_callback' => function() {
                return current_user_can('read');
            },
            'args' => array(
                'id' => array(
                    'required' => true,
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        // Get user's eco points and environmental impact
        register_rest_route('environmental-platform/v1', '/user/eco-profile', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_eco_profile_api'),
            'permission_callback' => function() {
                return is_user_logged_in();
            }
        ));
        
        // Calculate cart environmental impact
        register_rest_route('environmental-platform/v1', '/cart/environmental-impact', array(
            'methods' => 'POST',
            'callback' => array($this, 'calculate_cart_impact_api'),
            'permission_callback' => function() {
                return current_user_can('read');
            },
            'args' => array(
                'products' => array(
                    'required' => true,
                    'type' => 'array'
                )
            )
        ));
        
        // Submit eco preferences
        register_rest_route('environmental-platform/v1', '/user/eco-preferences', array(
            'methods' => 'POST',
            'callback' => array($this, 'save_user_eco_preferences_api'),
            'permission_callback' => function() {
                return is_user_logged_in();
            },
            'args' => array(
                'carbon_offset_preference' => array(
                    'sanitize_callback' => 'rest_sanitize_boolean'
                ),
                'packaging_preference' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'newsletter_signup' => array(
                    'sanitize_callback' => 'rest_sanitize_boolean'
                )
            )
        ));
    }
    
    /**
     * API: Get eco-friendly products
     */
    public function get_eco_products_api($request) {
        $page = $request->get_param('page');
        $per_page = min($request->get_param('per_page'), 50); // Max 50 items
        $eco_rating = $request->get_param('eco_rating');
        $min_score = $request->get_param('min_score');
        
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_is_eco_friendly',
                    'value' => 'yes'
                ),
                array(
                    'key' => '_sustainability_score',
                    'value' => $min_score ?: 1,
                    'compare' => '>=',
                    'type' => 'NUMERIC'
                )
            )
        );
        
        if ($eco_rating) {
            $args['meta_query'][] = array(
                'key' => '_eco_rating',
                'value' => $eco_rating
            );
        }
        
        $products = get_posts($args);
        $products_data = array();
        
        foreach ($products as $post) {
            $product = wc_get_product($post->ID);
            $products_data[] = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
                'permalink' => get_permalink($product->get_id()),
                'eco_data' => array(
                    'sustainability_score' => get_post_meta($product->get_id(), '_sustainability_score', true),
                    'carbon_footprint_kg' => get_post_meta($product->get_id(), '_carbon_footprint_kg', true),
                    'eco_rating' => get_post_meta($product->get_id(), '_eco_rating', true),
                    'is_eco_friendly' => get_post_meta($product->get_id(), '_is_eco_friendly', true) === 'yes',
                    'is_recyclable' => get_post_meta($product->get_id(), '_is_recyclable', true) === 'yes',
                    'is_biodegradable' => get_post_meta($product->get_id(), '_is_biodegradable', true) === 'yes'
                )
            );
        }
        
        return rest_ensure_response(array(
            'products' => $products_data,
            'pagination' => array(
                'page' => $page,
                'per_page' => $per_page,
                'total' => wp_count_posts('product')->publish
            )
        ));
    }
    
    /**
     * API: Get product environmental data
     */
    public function get_product_eco_data_api($request) {
        $product_id = $request->get_param('id');
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return new WP_Error('product_not_found', 'Product not found', array('status' => 404));
        }
        
        $eco_data = array(
            'sustainability_score' => get_post_meta($product_id, '_sustainability_score', true),
            'carbon_footprint_kg' => get_post_meta($product_id, '_carbon_footprint_kg', true),
            'eco_rating' => get_post_meta($product_id, '_eco_rating', true),
            'is_eco_friendly' => get_post_meta($product_id, '_is_eco_friendly', true) === 'yes',
            'is_recyclable' => get_post_meta($product_id, '_is_recyclable', true) === 'yes',
            'is_biodegradable' => get_post_meta($product_id, '_is_biodegradable', true) === 'yes',
            'eco_certifications' => get_post_meta($product_id, '_eco_certifications', true),
            'eco_materials' => get_post_meta($product_id, '_eco_materials', true),
            'eco_manufacturing' => get_post_meta($product_id, '_eco_manufacturing', true),
            'eco_disposal' => get_post_meta($product_id, '_eco_disposal', true)
        );
        
        return rest_ensure_response($eco_data);
    }
    
    /**
     * API: Get user eco profile
     */
    public function get_user_eco_profile_api($request) {
        $user_id = get_current_user_id();
        global $wpdb;
        
        // Get user's eco points
        $eco_points = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(points_value) 
            FROM {$wpdb->prefix}user_points 
            WHERE user_id = %d AND points_type = 'eco_points'
        ", $user_id));
        
        // Get user's carbon footprint
        $carbon_footprint = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(carbon_amount) 
            FROM {$wpdb->prefix}carbon_footprints 
            WHERE user_id = %d AND activity_type = 'online_purchase'
        ", $user_id));
        
        // Get user's eco orders count
        $eco_orders = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_customer_user' 
            AND pm.meta_value = %d
            AND EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm2 
                WHERE pm2.post_id = p.ID 
                AND pm2.meta_key = '_eco_points_awarded'
                AND pm2.meta_value > 0
            )
        ", $user_id));
        
        return rest_ensure_response(array(
            'eco_points' => intval($eco_points),
            'carbon_footprint_kg' => floatval($carbon_footprint),
            'eco_orders_count' => intval($eco_orders),
            'preferences' => array(
                'carbon_offset_preference' => get_user_meta($user_id, 'ep_carbon_offset_preference', true),
                'packaging_preference' => get_user_meta($user_id, 'ep_packaging_preference', true),
                'newsletter_signup' => get_user_meta($user_id, 'ep_eco_newsletter', true)
            )
        ));
    }
    
    /**
     * API: Calculate cart environmental impact
     */
    public function calculate_cart_impact_api($request) {
        $products = $request->get_param('products');
        $total_carbon = 0;
        $total_score = 0;
        $item_count = 0;
        $eco_products = 0;
        
        foreach ($products as $item) {
            $product_id = intval($item['id']);
            $quantity = intval($item['quantity']);
            
            $carbon_footprint = get_post_meta($product_id, '_carbon_footprint_kg', true);
            $sustainability_score = get_post_meta($product_id, '_sustainability_score', true);
            $is_eco_friendly = get_post_meta($product_id, '_is_eco_friendly', true);
            
            if (!empty($carbon_footprint)) {
                $total_carbon += floatval($carbon_footprint) * $quantity;
            }
            
            if (!empty($sustainability_score)) {
                $total_score += intval($sustainability_score) * $quantity;
            }
            
            if ($is_eco_friendly === 'yes') {
                $eco_products += $quantity;
            }
            
            $item_count += $quantity;
        }
        
        return rest_ensure_response(array(
            'total_carbon_kg' => $total_carbon,
            'average_sustainability_score' => $item_count > 0 ? $total_score / $item_count : 0,
            'eco_products_count' => $eco_products,
            'total_items' => $item_count,
            'eco_percentage' => $item_count > 0 ? ($eco_products / $item_count) * 100 : 0,
            'carbon_offset_cost' => $total_carbon * 0.02
        ));
    }
    
    /**
     * API: Save user eco preferences
     */
    public function save_user_eco_preferences_api($request) {
        $user_id = get_current_user_id();
        
        $carbon_offset = $request->get_param('carbon_offset_preference');
        $packaging = $request->get_param('packaging_preference');
        $newsletter = $request->get_param('newsletter_signup');
        
        if ($carbon_offset !== null) {
            update_user_meta($user_id, 'ep_carbon_offset_preference', $carbon_offset);
        }
        
        if ($packaging !== null) {
            update_user_meta($user_id, 'ep_packaging_preference', $packaging);
        }
        
        if ($newsletter !== null) {
            update_user_meta($user_id, 'ep_eco_newsletter', $newsletter);
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Eco preferences saved successfully', 'environmental-platform-core')
        ));
    }
}

// Initialize the integration
EP_WooCommerce_Integration::get_instance();
