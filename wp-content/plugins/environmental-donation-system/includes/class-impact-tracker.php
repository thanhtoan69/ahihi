<?php
/**
 * Environmental Impact Tracker
 * 
 * Tracks and calculates environmental impact metrics from donations
 * 
 * @package Environmental_Donation_System
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class EDS_Impact_Tracker
 */
class EDS_Impact_Tracker {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Impact metrics configuration
     */
    private $impact_metrics = array();
    
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
     * Constructor
     */
    private function __construct() {
        $this->init_impact_metrics();
        $this->init_hooks();
    }
    
    /**
     * Initialize impact metrics configuration
     */
    private function init_impact_metrics() {
        $this->impact_metrics = array(
            'trees_planted' => array(
                'name' => __('Trees Planted', 'environmental-donation-system'),
                'icon' => 'tree',
                'unit' => 'trees',
                'conversion_rate' => 0.1, // $10 per tree
                'color' => '#4CAF50',
                'description' => __('Each tree planted helps absorb CO2 and provides habitat', 'environmental-donation-system')
            ),
            'co2_reduced' => array(
                'name' => __('CO2 Reduced', 'environmental-donation-system'),
                'icon' => 'cloud',
                'unit' => 'kg CO2',
                'conversion_rate' => 0.05, // $1 = 0.05kg CO2 reduction
                'color' => '#2196F3',
                'description' => __('Carbon dioxide emissions prevented or offset', 'environmental-donation-system')
            ),
            'water_saved' => array(
                'name' => __('Water Saved', 'environmental-donation-system'),
                'icon' => 'water-drop',
                'unit' => 'liters',
                'conversion_rate' => 5, // $1 = 5 liters saved
                'color' => '#00BCD4',
                'description' => __('Clean water conserved through our initiatives', 'environmental-donation-system')
            ),
            'plastic_removed' => array(
                'name' => __('Plastic Removed', 'environmental-donation-system'),
                'icon' => 'trash',
                'unit' => 'kg',
                'conversion_rate' => 0.2, // $1 = 0.2kg plastic removed
                'color' => '#FF9800',
                'description' => __('Plastic waste removed from environment', 'environmental-donation-system')
            ),
            'energy_saved' => array(
                'name' => __('Energy Saved', 'environmental-donation-system'),
                'icon' => 'lightning',
                'unit' => 'kWh',
                'conversion_rate' => 2, // $1 = 2kWh saved
                'color' => '#FFEB3B',
                'description' => __('Renewable energy generated or fossil fuel energy saved', 'environmental-donation-system')
            ),
            'wildlife_protected' => array(
                'name' => __('Wildlife Protected', 'environmental-donation-system'),
                'icon' => 'paw',
                'unit' => 'animals',
                'conversion_rate' => 0.01, // $100 per animal protected
                'color' => '#8BC34A',
                'description' => __('Animals and habitats protected through conservation efforts', 'environmental-donation-system')
            ),
            'area_conserved' => array(
                'name' => __('Area Conserved', 'environmental-donation-system'),
                'icon' => 'map',
                'unit' => 'm²',
                'conversion_rate' => 1, // $1 = 1m² conserved
                'color' => '#4CAF50',
                'description' => __('Natural habitat area conserved or restored', 'environmental-donation-system')
            ),
            'people_educated' => array(
                'name' => __('People Educated', 'environmental-donation-system'),
                'icon' => 'graduation-cap',
                'unit' => 'people',
                'conversion_rate' => 0.5, // $2 per person educated
                'color' => '#9C27B0',
                'description' => __('Individuals reached through environmental education programs', 'environmental-donation-system')
            )
        );
        
        // Allow customization through filters
        $this->impact_metrics = apply_filters('eds_impact_metrics', $this->impact_metrics);
    }
    
    /**
     * Initialize hooks and filters
     */
    private function init_hooks() {
        // Update impact when donations are processed
        add_action('eds_donation_completed', array($this, 'update_donation_impact'), 10, 2);
        add_action('eds_donation_refunded', array($this, 'reverse_donation_impact'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_eds_get_impact_data', array($this, 'handle_get_impact_data'));
        add_action('wp_ajax_nopriv_eds_get_impact_data', array($this, 'handle_get_impact_data'));
        add_action('wp_ajax_eds_update_campaign_metrics', array($this, 'handle_update_campaign_metrics'));
        
        // Shortcodes
        add_shortcode('eds_impact_counter', array($this, 'impact_counter_shortcode'));
        add_shortcode('eds_campaign_impact', array($this, 'campaign_impact_shortcode'));
        add_shortcode('eds_total_impact', array($this, 'total_impact_shortcode'));
        
        // Cron jobs
        add_action('eds_update_impact_statistics', array($this, 'update_impact_statistics'));
        add_action('eds_generate_impact_reports', array($this, 'generate_impact_reports'));
        
        // Schedule cron events
        if (!wp_next_scheduled('eds_update_impact_statistics')) {
            wp_schedule_event(time(), 'hourly', 'eds_update_impact_statistics');
        }
        
        if (!wp_next_scheduled('eds_generate_impact_reports')) {
            wp_schedule_event(time(), 'daily', 'eds_generate_impact_reports');
        }
    }
    
    /**
     * Update impact when donation is completed
     */
    public function update_donation_impact($donation_id, $donation_data) {
        $this->calculate_and_store_impact($donation_id, $donation_data['amount'], 'add');
    }
    
    /**
     * Reverse impact when donation is refunded
     */
    public function reverse_donation_impact($donation_id, $donation_data) {
        $this->calculate_and_store_impact($donation_id, $donation_data['amount'], 'subtract');
    }
    
    /**
     * Calculate and store environmental impact
     */
    private function calculate_and_store_impact($donation_id, $amount, $operation = 'add') {
        global $wpdb;
        
        try {
            // Get donation details
            $donation = $wpdb->get_row($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}eds_donations 
                WHERE id = %d
            ", $donation_id));
            
            if (!$donation) {
                return false;
            }
            
            // Get campaign-specific impact metrics if available
            $campaign_metrics = $this->get_campaign_impact_metrics($donation->campaign_id);
            $organization_metrics = $this->get_organization_impact_metrics($donation->organization_id);
            
            // Use campaign metrics, then organization metrics, then default metrics
            $metrics_to_use = $campaign_metrics ?: ($organization_metrics ?: $this->impact_metrics);
            
            foreach ($metrics_to_use as $metric_key => $metric_config) {
                $impact_value = $amount * $metric_config['conversion_rate'];
                
                if ($operation === 'subtract') {
                    $impact_value = -$impact_value;
                }
                
                // Store individual donation impact
                $this->store_donation_impact($donation_id, $metric_key, $impact_value, $metric_config);
                
                // Update campaign totals
                if ($donation->campaign_id) {
                    $this->update_campaign_impact_total($donation->campaign_id, $metric_key, $impact_value);
                }
                
                // Update organization totals
                if ($donation->organization_id) {
                    $this->update_organization_impact_total($donation->organization_id, $metric_key, $impact_value);
                }
                
                // Update global totals
                $this->update_global_impact_total($metric_key, $impact_value);
            }
            
        } catch (Exception $e) {
            error_log('EDS Impact Calculation Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Store individual donation impact
     */
    private function store_donation_impact($donation_id, $metric_key, $impact_value, $metric_config) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'eds_donation_impacts',
            array(
                'donation_id' => $donation_id,
                'metric_key' => $metric_key,
                'metric_name' => $metric_config['name'],
                'impact_value' => $impact_value,
                'unit' => $metric_config['unit'],
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%f', '%s', '%s')
        );
    }
    
    /**
     * Update campaign impact totals
     */
    private function update_campaign_impact_total($campaign_id, $metric_key, $impact_value) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare("
            INSERT INTO {$wpdb->prefix}eds_campaign_impacts 
            (campaign_id, metric_key, total_impact, updated_at) 
            VALUES (%d, %s, %f, %s)
            ON DUPLICATE KEY UPDATE 
            total_impact = total_impact + VALUES(total_impact),
            updated_at = VALUES(updated_at)
        ", $campaign_id, $metric_key, $impact_value, current_time('mysql')));
    }
    
    /**
     * Update organization impact totals
     */
    private function update_organization_impact_total($organization_id, $metric_key, $impact_value) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare("
            INSERT INTO {$wpdb->prefix}eds_organization_impacts 
            (organization_id, metric_key, total_impact, updated_at) 
            VALUES (%d, %s, %f, %s)
            ON DUPLICATE KEY UPDATE 
            total_impact = total_impact + VALUES(total_impact),
            updated_at = VALUES(updated_at)
        ", $organization_id, $metric_key, $impact_value, current_time('mysql')));
    }
    
    /**
     * Update global impact totals
     */
    private function update_global_impact_total($metric_key, $impact_value) {
        global $wpdb;
        
        $wpdb->query($wpdb->prepare("
            INSERT INTO {$wpdb->prefix}eds_global_impacts 
            (metric_key, total_impact, updated_at) 
            VALUES (%s, %f, %s)
            ON DUPLICATE KEY UPDATE 
            total_impact = total_impact + VALUES(total_impact),
            updated_at = VALUES(updated_at)
        ", $metric_key, $impact_value, current_time('mysql')));
    }
    
    /**
     * Get campaign impact metrics
     */
    private function get_campaign_impact_metrics($campaign_id) {
        if (!$campaign_id) return null;
        
        $metrics = get_post_meta($campaign_id, '_eds_impact_metrics', true);
        return $metrics ?: null;
    }
    
    /**
     * Get organization impact metrics
     */
    private function get_organization_impact_metrics($organization_id) {
        if (!$organization_id) return null;
        
        global $wpdb;
        $metrics = $wpdb->get_var($wpdb->prepare("
            SELECT impact_metrics FROM {$wpdb->prefix}eds_donation_organizations 
            WHERE id = %d
        ", $organization_id));
        
        return $metrics ? json_decode($metrics, true) : null;
    }
    
    /**
     * Get campaign impact data
     */
    public function get_campaign_impact($campaign_id) {
        global $wpdb;
        
        $impacts = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}eds_campaign_impacts 
            WHERE campaign_id = %d
        ", $campaign_id), ARRAY_A);
        
        $formatted_impacts = array();
        foreach ($impacts as $impact) {
            $metric_config = $this->impact_metrics[$impact['metric_key']] ?? array();
            $formatted_impacts[$impact['metric_key']] = array_merge($impact, $metric_config);
        }
        
        return $formatted_impacts;
    }
    
    /**
     * Get organization impact data
     */
    public function get_organization_impact($organization_id) {
        global $wpdb;
        
        $impacts = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}eds_organization_impacts 
            WHERE organization_id = %d
        ", $organization_id), ARRAY_A);
        
        $formatted_impacts = array();
        foreach ($impacts as $impact) {
            $metric_config = $this->impact_metrics[$impact['metric_key']] ?? array();
            $formatted_impacts[$impact['metric_key']] = array_merge($impact, $metric_config);
        }
        
        return $formatted_impacts;
    }
    
    /**
     * Get global impact data
     */
    public function get_global_impact() {
        global $wpdb;
        
        $impacts = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}eds_global_impacts
        ", ARRAY_A);
        
        $formatted_impacts = array();
        foreach ($impacts as $impact) {
            $metric_config = $this->impact_metrics[$impact['metric_key']] ?? array();
            $formatted_impacts[$impact['metric_key']] = array_merge($impact, $metric_config);
        }
        
        return $formatted_impacts;
    }
    
    /**
     * Get impact summary for time period
     */
    public function get_impact_summary($start_date, $end_date, $campaign_id = null, $organization_id = null) {
        global $wpdb;
        
        $where_clauses = array();
        $params = array();
        
        // Date range
        $where_clauses[] = "d.created_at >= %s";
        $where_clauses[] = "d.created_at <= %s";
        $params[] = $start_date;
        $params[] = $end_date;
        
        // Campaign filter
        if ($campaign_id) {
            $where_clauses[] = "d.campaign_id = %d";
            $params[] = $campaign_id;
        }
        
        // Organization filter
        if ($organization_id) {
            $where_clauses[] = "d.organization_id = %d";
            $params[] = $organization_id;
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $impacts = $wpdb->get_results($wpdb->prepare("
            SELECT di.metric_key, di.metric_name, di.unit,
                   SUM(di.impact_value) as total_impact,
                   COUNT(DISTINCT di.donation_id) as donations_count
            FROM {$wpdb->prefix}eds_donation_impacts di
            JOIN {$wpdb->prefix}eds_donations d ON di.donation_id = d.id
            WHERE {$where_sql}
            GROUP BY di.metric_key
            ORDER BY total_impact DESC
        ", $params), ARRAY_A);
        
        $formatted_impacts = array();
        foreach ($impacts as $impact) {
            $metric_config = $this->impact_metrics[$impact['metric_key']] ?? array();
            $formatted_impacts[$impact['metric_key']] = array_merge($impact, $metric_config);
        }
        
        return $formatted_impacts;
    }
    
    /**
     * Get impact trends over time
     */
    public function get_impact_trends($metric_key, $period = 'month', $limit = 12) {
        global $wpdb;
        
        $date_format = $period === 'week' ? '%Y-%u' : ($period === 'day' ? '%Y-%m-%d' : '%Y-%m');
        
        $trends = $wpdb->get_results($wpdb->prepare("
            SELECT DATE_FORMAT(d.created_at, %s) as period,
                   SUM(di.impact_value) as total_impact,
                   COUNT(DISTINCT di.donation_id) as donations_count
            FROM {$wpdb->prefix}eds_donation_impacts di
            JOIN {$wpdb->prefix}eds_donations d ON di.donation_id = d.id
            WHERE di.metric_key = %s
            AND d.created_at >= DATE_SUB(NOW(), INTERVAL %d {$period})
            GROUP BY period
            ORDER BY period DESC
            LIMIT %d
        ", $date_format, $metric_key, $limit, $limit), ARRAY_A);
        
        return array_reverse($trends);
    }
    
    /**
     * Generate impact report
     */
    public function generate_impact_report($start_date, $end_date, $format = 'array') {
        $report = array(
            'period' => array(
                'start' => $start_date,
                'end' => $end_date
            ),
            'summary' => $this->get_impact_summary($start_date, $end_date),
            'trends' => array(),
            'top_campaigns' => $this->get_top_campaigns_by_impact($start_date, $end_date),
            'top_organizations' => $this->get_top_organizations_by_impact($start_date, $end_date)
        );
        
        // Add trends for each metric
        foreach (array_keys($this->impact_metrics) as $metric_key) {
            $report['trends'][$metric_key] = $this->get_impact_trends($metric_key, 'month', 6);
        }
        
        if ($format === 'json') {
            return json_encode($report);
        }
        
        return $report;
    }
    
    /**
     * Get top campaigns by impact
     */
    private function get_top_campaigns_by_impact($start_date, $end_date, $limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT c.id, c.title, SUM(di.impact_value) as total_impact,
                   COUNT(DISTINCT di.donation_id) as donations_count
            FROM {$wpdb->prefix}eds_donation_impacts di
            JOIN {$wpdb->prefix}eds_donations d ON di.donation_id = d.id
            JOIN {$wpdb->prefix}eds_donation_campaigns c ON d.campaign_id = c.id
            WHERE d.created_at >= %s AND d.created_at <= %s
            GROUP BY c.id
            ORDER BY total_impact DESC
            LIMIT %d
        ", $start_date, $end_date, $limit), ARRAY_A);
    }
    
    /**
     * Get top organizations by impact
     */
    private function get_top_organizations_by_impact($start_date, $end_date, $limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT o.id, o.name, SUM(di.impact_value) as total_impact,
                   COUNT(DISTINCT di.donation_id) as donations_count
            FROM {$wpdb->prefix}eds_donation_impacts di
            JOIN {$wpdb->prefix}eds_donations d ON di.donation_id = d.id
            JOIN {$wpdb->prefix}eds_donation_organizations o ON d.organization_id = o.id
            WHERE d.created_at >= %s AND d.created_at <= %s
            GROUP BY o.id
            ORDER BY total_impact DESC
            LIMIT %d
        ", $start_date, $end_date, $limit), ARRAY_A);
    }
    
    /**
     * Update impact statistics (cron job)
     */
    public function update_impact_statistics() {
        // Recalculate campaign totals
        $this->recalculate_campaign_totals();
        
        // Recalculate organization totals
        $this->recalculate_organization_totals();
        
        // Update global statistics
        $this->update_global_statistics();
    }
    
    /**
     * Recalculate campaign totals
     */
    private function recalculate_campaign_totals() {
        global $wpdb;
        
        $wpdb->query("
            INSERT INTO {$wpdb->prefix}eds_campaign_impacts (campaign_id, metric_key, total_impact, updated_at)
            SELECT d.campaign_id, di.metric_key, SUM(di.impact_value), NOW()
            FROM {$wpdb->prefix}eds_donation_impacts di
            JOIN {$wpdb->prefix}eds_donations d ON di.donation_id = d.id
            WHERE d.campaign_id IS NOT NULL
            GROUP BY d.campaign_id, di.metric_key
            ON DUPLICATE KEY UPDATE
            total_impact = VALUES(total_impact),
            updated_at = VALUES(updated_at)
        ");
    }
    
    /**
     * Recalculate organization totals
     */
    private function recalculate_organization_totals() {
        global $wpdb;
        
        $wpdb->query("
            INSERT INTO {$wpdb->prefix}eds_organization_impacts (organization_id, metric_key, total_impact, updated_at)
            SELECT d.organization_id, di.metric_key, SUM(di.impact_value), NOW()
            FROM {$wpdb->prefix}eds_donation_impacts di
            JOIN {$wpdb->prefix}eds_donations d ON di.donation_id = d.id
            WHERE d.organization_id IS NOT NULL
            GROUP BY d.organization_id, di.metric_key
            ON DUPLICATE KEY UPDATE
            total_impact = VALUES(total_impact),
            updated_at = VALUES(updated_at)
        ");
    }
    
    /**
     * Update global statistics
     */
    private function update_global_statistics() {
        global $wpdb;
        
        $wpdb->query("
            INSERT INTO {$wpdb->prefix}eds_global_impacts (metric_key, total_impact, updated_at)
            SELECT metric_key, SUM(impact_value), NOW()
            FROM {$wpdb->prefix}eds_donation_impacts
            GROUP BY metric_key
            ON DUPLICATE KEY UPDATE
            total_impact = VALUES(total_impact),
            updated_at = VALUES(updated_at)
        ");
    }
    
    /**
     * Generate impact reports (cron job)
     */
    public function generate_impact_reports() {
        // Generate monthly report
        $last_month_start = date('Y-m-01', strtotime('-1 month'));
        $last_month_end = date('Y-m-t', strtotime('-1 month'));
        $monthly_report = $this->generate_impact_report($last_month_start, $last_month_end);
        
        // Store report
        update_option('eds_last_monthly_impact_report', $monthly_report);
        
        // Generate yearly report if it's January
        if (date('n') === '1') {
            $last_year_start = date('Y-01-01', strtotime('-1 year'));
            $last_year_end = date('Y-12-31', strtotime('-1 year'));
            $yearly_report = $this->generate_impact_report($last_year_start, $last_year_end);
            
            update_option('eds_last_yearly_impact_report', $yearly_report);
        }
    }
    
    /**
     * Shortcode: Impact Counter
     */
    public function impact_counter_shortcode($atts) {
        $atts = shortcode_atts(array(
            'metric' => 'trees_planted',
            'campaign_id' => null,
            'organization_id' => null,
            'animated' => 'true',
            'show_unit' => 'true'
        ), $atts);
        
        $impact_data = null;
        
        if ($atts['campaign_id']) {
            $campaign_impacts = $this->get_campaign_impact($atts['campaign_id']);
            $impact_data = $campaign_impacts[$atts['metric']] ?? null;
        } elseif ($atts['organization_id']) {
            $organization_impacts = $this->get_organization_impact($atts['organization_id']);
            $impact_data = $organization_impacts[$atts['metric']] ?? null;
        } else {
            $global_impacts = $this->get_global_impact();
            $impact_data = $global_impacts[$atts['metric']] ?? null;
        }
        
        if (!$impact_data) {
            return '<span class="eds-impact-counter no-data">0</span>';
        }
        
        $metric_config = $this->impact_metrics[$atts['metric']] ?? array();
        $value = number_format($impact_data['total_impact'], 0);
        $unit = $atts['show_unit'] === 'true' ? $metric_config['unit'] : '';
        $animated_class = $atts['animated'] === 'true' ? 'animated' : '';
        
        ob_start();
        ?>
        <div class="eds-impact-counter <?php echo esc_attr($animated_class); ?>" 
             data-target="<?php echo esc_attr($impact_data['total_impact']); ?>"
             style="color: <?php echo esc_attr($metric_config['color'] ?? '#333'); ?>">
            <span class="counter-value"><?php echo esc_html($value); ?></span>
            <?php if ($unit): ?>
                <span class="counter-unit"><?php echo esc_html($unit); ?></span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Campaign Impact
     */
    public function campaign_impact_shortcode($atts) {
        $atts = shortcode_atts(array(
            'campaign_id' => null,
            'metrics' => 'trees_planted,co2_reduced,water_saved',
            'layout' => 'grid'
        ), $atts);
        
        if (!$atts['campaign_id']) {
            return '<p>' . __('Campaign ID required', 'environmental-donation-system') . '</p>';
        }
        
        $metrics = explode(',', $atts['metrics']);
        $campaign_impacts = $this->get_campaign_impact($atts['campaign_id']);
        
        ob_start();
        ?>
        <div class="eds-campaign-impact <?php echo esc_attr($atts['layout']); ?>">
            <?php foreach ($metrics as $metric_key): ?>
                <?php 
                $metric_key = trim($metric_key);
                $impact_data = $campaign_impacts[$metric_key] ?? null;
                $metric_config = $this->impact_metrics[$metric_key] ?? null;
                
                if (!$impact_data || !$metric_config) continue;
                ?>
                <div class="impact-metric" style="border-color: <?php echo esc_attr($metric_config['color']); ?>">
                    <div class="metric-icon">
                        <i class="eds-icon-<?php echo esc_attr($metric_config['icon']); ?>"></i>
                    </div>
                    <div class="metric-value">
                        <?php echo number_format($impact_data['total_impact'], 0); ?>
                    </div>
                    <div class="metric-unit">
                        <?php echo esc_html($metric_config['unit']); ?>
                    </div>
                    <div class="metric-name">
                        <?php echo esc_html($metric_config['name']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode: Total Impact
     */
    public function total_impact_shortcode($atts) {
        $atts = shortcode_atts(array(
            'metrics' => 'all',
            'layout' => 'list',
            'show_descriptions' => 'false'
        ), $atts);
        
        $global_impacts = $this->get_global_impact();
        
        if ($atts['metrics'] !== 'all') {
            $metrics = explode(',', $atts['metrics']);
            $filtered_impacts = array();
            foreach ($metrics as $metric_key) {
                $metric_key = trim($metric_key);
                if (isset($global_impacts[$metric_key])) {
                    $filtered_impacts[$metric_key] = $global_impacts[$metric_key];
                }
            }
            $global_impacts = $filtered_impacts;
        }
        
        ob_start();
        ?>
        <div class="eds-total-impact <?php echo esc_attr($atts['layout']); ?>">
            <?php foreach ($global_impacts as $metric_key => $impact_data): ?>
                <?php $metric_config = $this->impact_metrics[$metric_key] ?? null; ?>
                <?php if (!$metric_config) continue; ?>
                
                <div class="impact-item">
                    <div class="impact-header">
                        <span class="impact-name"><?php echo esc_html($metric_config['name']); ?></span>
                        <span class="impact-value" style="color: <?php echo esc_attr($metric_config['color']); ?>">
                            <?php echo number_format($impact_data['total_impact'], 0); ?> <?php echo esc_html($metric_config['unit']); ?>
                        </span>
                    </div>
                    <?php if ($atts['show_descriptions'] === 'true'): ?>
                        <div class="impact-description">
                            <?php echo esc_html($metric_config['description']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX handler: Get impact data
     */
    public function handle_get_impact_data() {
        $type = sanitize_text_field($_REQUEST['type'] ?? 'global');
        $metric = sanitize_text_field($_REQUEST['metric'] ?? '');
        $campaign_id = intval($_REQUEST['campaign_id'] ?? 0);
        $organization_id = intval($_REQUEST['organization_id'] ?? 0);
        
        $data = array();
        
        switch ($type) {
            case 'campaign':
                if ($campaign_id) {
                    $data = $this->get_campaign_impact($campaign_id);
                }
                break;
                
            case 'organization':
                if ($organization_id) {
                    $data = $this->get_organization_impact($organization_id);
                }
                break;
                
            case 'trends':
                if ($metric) {
                    $data = $this->get_impact_trends($metric);
                }
                break;
                
            default:
                $data = $this->get_global_impact();
                break;
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler: Update campaign metrics
     */
    public function handle_update_campaign_metrics() {
        check_ajax_referer('eds_ajax_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $campaign_id = intval($_POST['campaign_id']);
        $metrics = json_decode(stripslashes($_POST['metrics']), true);
        
        if (!$campaign_id || !$metrics) {
            wp_send_json_error('Invalid data');
        }
        
        update_post_meta($campaign_id, '_eds_impact_metrics', $metrics);
        
        wp_send_json_success(array(
            'message' => __('Impact metrics updated successfully', 'environmental-donation-system')
        ));
    }
}
