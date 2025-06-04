<?php
/**
 * Carbon Footprint Tracker
 * 
 * Handles tracking and calculating carbon footprint for users
 * 
 * @package Environmental_Data_Dashboard
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Carbon_Footprint_Tracker {
    
    private static $instance = null;
    private $table_name;
    private $goals_table;
    
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
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'env_carbon_footprint';
        $this->goals_table = $wpdb->prefix . 'env_user_goals';
    }
    
    /**
     * Carbon emission factors (kg CO2 equivalent)
     */
    private function get_emission_factors() {
        return array(
            'transportation' => array(
                'car_petrol' => 0.21,        // per km
                'car_diesel' => 0.18,        // per km
                'car_electric' => 0.05,      // per km
                'motorcycle' => 0.13,        // per km
                'bus' => 0.08,               // per km
                'train' => 0.04,             // per km
                'airplane_domestic' => 0.25, // per km
                'airplane_international' => 0.30, // per km
                'bicycle' => 0.0,            // per km
                'walking' => 0.0             // per km
            ),
            'energy' => array(
                'electricity_grid' => 0.5,   // per kWh (Vietnam average)
                'natural_gas' => 0.2,        // per kWh
                'coal' => 0.9,               // per kWh
                'solar' => 0.04,             // per kWh
                'wind' => 0.01,              // per kWh
                'hydroelectric' => 0.02      // per kWh
            ),
            'food' => array(
                'beef' => 27.0,              // per kg
                'pork' => 12.1,              // per kg
                'chicken' => 6.9,            // per kg
                'fish' => 6.1,               // per kg
                'dairy' => 3.2,              // per kg
                'vegetables' => 2.0,         // per kg
                'fruits' => 1.1,             // per kg
                'grains' => 1.4,             // per kg
                'local_food' => 0.5,         // per kg (locally sourced)
                'organic_food' => 0.8        // per kg (organic)
            ),
            'waste' => array(
                'general_waste' => 0.5,      // per kg
                'recycled_paper' => -0.8,    // per kg (negative = savings)
                'recycled_plastic' => -1.8,  // per kg
                'recycled_metal' => -5.6,    // per kg
                'recycled_glass' => -0.3,    // per kg
                'composted_organic' => -0.2   // per kg
            ),
            'consumption' => array(
                'new_clothing' => 8.5,       // per item
                'secondhand_clothing' => 1.2, // per item
                'electronics' => 300.0,      // per device
                'furniture' => 45.0,         // per item
                'books' => 2.7,              // per book
                'plastic_bottle' => 0.082,   // per bottle
                'reusable_bottle' => -0.05   // per use (savings)
            )
        );
    }
    
    /**
     * Save carbon footprint data
     */
    public function save_footprint_data($data) {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user_id = get_current_user_id();
        $activity_type = sanitize_text_field($data['activity_type']);
        $category = sanitize_text_field($data['category']);
        $quantity = floatval($data['quantity']);
        $description = sanitize_textarea_field($data['description'] ?? '');
        $date_recorded = sanitize_text_field($data['date_recorded'] ?? current_time('Y-m-d'));
        
        // Calculate carbon amount
        $carbon_amount = $this->calculate_carbon_emission($category, $activity_type, $quantity);
        
        if ($carbon_amount === false) {
            return false;
        }
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => $user_id,
                'activity_type' => $activity_type,
                'activity_description' => $description,
                'carbon_amount' => $carbon_amount,
                'unit' => 'kg',
                'category' => $category,
                'date_recorded' => $date_recorded,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('Failed to save carbon footprint data: ' . $wpdb->last_error);
            return false;
        }
        
        // Update user goals progress
        $this->update_goals_progress($user_id, $carbon_amount);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Calculate carbon emission for an activity
     */
    public function calculate_carbon_emission($category, $activity_type, $quantity) {
        $emission_factors = $this->get_emission_factors();
        
        if (!isset($emission_factors[$category][$activity_type])) {
            error_log("Unknown emission factor for category: $category, activity: $activity_type");
            return false;
        }
        
        $factor = $emission_factors[$category][$activity_type];
        return $quantity * $factor;
    }
    
    /**
     * Get user's carbon footprint summary
     */
    public function get_user_summary($user_id, $period = 'month') {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        
        $sql = $wpdb->prepare(
            "SELECT 
                category,
                SUM(carbon_amount) as total_carbon,
                COUNT(*) as activity_count,
                AVG(carbon_amount) as avg_carbon
             FROM {$this->table_name} 
             WHERE user_id = %d 
             AND {$date_condition}
             GROUP BY category
             ORDER BY total_carbon DESC",
            $user_id
        );
        
        $category_data = $wpdb->get_results($sql, ARRAY_A);
        
        // Get total for the period
        $total_sql = $wpdb->prepare(
            "SELECT 
                SUM(carbon_amount) as total_carbon,
                COUNT(*) as total_activities
             FROM {$this->table_name} 
             WHERE user_id = %d 
             AND {$date_condition}",
            $user_id
        );
        
        $total_data = $wpdb->get_row($total_sql, ARRAY_A);
        
        return array(
            'period' => $period,
            'total_carbon' => $total_data['total_carbon'] ?? 0,
            'total_activities' => $total_data['total_activities'] ?? 0,
            'categories' => $category_data,
            'average_daily' => $this->get_average_daily_emission($user_id, $period)
        );
    }
    
    /**
     * Get date condition for SQL queries
     */
    private function get_date_condition($period) {
        switch ($period) {
            case 'day':
                return "DATE(date_recorded) = CURDATE()";
            case 'week':
                return "date_recorded >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            case 'month':
                return "date_recorded >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            case 'year':
                return "date_recorded >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)";
            default:
                return "date_recorded >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }
    }
    
    /**
     * Get average daily emission for user
     */
    private function get_average_daily_emission($user_id, $period) {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        
        $sql = $wpdb->prepare(
            "SELECT 
                DATE(date_recorded) as date,
                SUM(carbon_amount) as daily_carbon
             FROM {$this->table_name} 
             WHERE user_id = %d 
             AND {$date_condition}
             GROUP BY DATE(date_recorded)
             ORDER BY date ASC",
            $user_id
        );
        
        $daily_data = $wpdb->get_results($sql, ARRAY_A);
        
        if (empty($daily_data)) {
            return 0;
        }
        
        $total_carbon = array_sum(array_column($daily_data, 'daily_carbon'));
        return $total_carbon / count($daily_data);
    }
    
    /**
     * Get user's carbon footprint trends
     */
    public function get_user_trends($user_id, $days = 30) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT 
                DATE(date_recorded) as date,
                SUM(carbon_amount) as daily_carbon,
                COUNT(*) as daily_activities
             FROM {$this->table_name} 
             WHERE user_id = %d 
             AND date_recorded >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
             GROUP BY DATE(date_recorded)
             ORDER BY date ASC",
            $user_id,
            $days
        );
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Set carbon reduction goal for user
     */
    public function set_carbon_goal($user_id, $target_value, $target_date, $description = '') {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->goals_table,
            array(
                'user_id' => $user_id,
                'goal_type' => 'carbon_reduction',
                'goal_description' => $description,
                'target_value' => $target_value,
                'current_value' => 0,
                'unit' => 'kg',
                'target_date' => $target_date,
                'status' => 'active',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s')
        );
        
        return $result !== false ? $wpdb->insert_id : false;
    }
    
    /**
     * Update goals progress
     */
    private function update_goals_progress($user_id, $carbon_amount) {
        global $wpdb;
        
        // Get active carbon reduction goals
        $goals_sql = $wpdb->prepare(
            "SELECT * FROM {$this->goals_table} 
             WHERE user_id = %d 
             AND goal_type = 'carbon_reduction' 
             AND status = 'active'
             AND target_date >= CURDATE()",
            $user_id
        );
        
        $goals = $wpdb->get_results($goals_sql, ARRAY_A);
        
        foreach ($goals as $goal) {
            // Calculate current carbon footprint for the goal period
            $start_date = $goal['created_at'];
            $current_carbon_sql = $wpdb->prepare(
                "SELECT SUM(carbon_amount) as total_carbon 
                 FROM {$this->table_name} 
                 WHERE user_id = %d 
                 AND date_recorded >= %s",
                $user_id,
                date('Y-m-d', strtotime($start_date))
            );
            
            $current_carbon = $wpdb->get_var($current_carbon_sql) ?: 0;
            
            // Update goal progress
            $wpdb->update(
                $this->goals_table,
                array('current_value' => $current_carbon),
                array('id' => $goal['id']),
                array('%f'),
                array('%d')
            );
            
            // Check if goal is achieved (reduction goal means lower current value is better)
            if ($current_carbon <= $goal['target_value']) {
                $wpdb->update(
                    $this->goals_table,
                    array('status' => 'completed'),
                    array('id' => $goal['id']),
                    array('%s'),
                    array('%d')
                );
            }
        }
    }
    
    /**
     * Get user's goals
     */
    public function get_user_goals($user_id) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->goals_table} 
             WHERE user_id = %d 
             ORDER BY created_at DESC",
            $user_id
        );
        
        return $wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get carbon footprint recommendations
     */
    public function get_recommendations($user_id) {
        $summary = $this->get_user_summary($user_id, 'month');
        $recommendations = array();
        
        if (empty($summary['categories'])) {
            $recommendations[] = array(
                'type' => 'general',
                'title' => 'Start tracking your carbon footprint',
                'description' => 'Begin by recording your daily activities to understand your environmental impact.',
                'impact' => 'high',
                'difficulty' => 'easy'
            );
            return $recommendations;
        }
        
        // Analyze categories and provide specific recommendations
        foreach ($summary['categories'] as $category) {
            $category_name = $category['category'];
            $carbon_amount = $category['total_carbon'];
            
            switch ($category_name) {
                case 'transportation':
                    if ($carbon_amount > 100) { // High transportation emissions
                        $recommendations[] = array(
                            'type' => 'transportation',
                            'title' => 'Reduce transportation emissions',
                            'description' => 'Consider using public transport, cycling, or electric vehicles. Your transportation generates ' . round($carbon_amount, 1) . 'kg CO2 monthly.',
                            'impact' => 'high',
                            'difficulty' => 'medium',
                            'potential_savings' => round($carbon_amount * 0.3, 1) . 'kg CO2/month'
                        );
                    }
                    break;
                    
                case 'energy':
                    if ($carbon_amount > 50) {
                        $recommendations[] = array(
                            'type' => 'energy',
                            'title' => 'Improve home energy efficiency',
                            'description' => 'Switch to LED bulbs, use energy-efficient appliances, and consider renewable energy sources.',
                            'impact' => 'medium',
                            'difficulty' => 'easy',
                            'potential_savings' => round($carbon_amount * 0.25, 1) . 'kg CO2/month'
                        );
                    }
                    break;
                    
                case 'food':
                    if ($carbon_amount > 80) {
                        $recommendations[] = array(
                            'type' => 'food',
                            'title' => 'Adopt sustainable eating habits',
                            'description' => 'Reduce meat consumption, choose local and organic foods, and minimize food waste.',
                            'impact' => 'medium',
                            'difficulty' => 'medium',
                            'potential_savings' => round($carbon_amount * 0.4, 1) . 'kg CO2/month'
                        );
                    }
                    break;
                    
                case 'waste':
                    if ($carbon_amount > 0) {
                        $recommendations[] = array(
                            'type' => 'waste',
                            'title' => 'Improve waste management',
                            'description' => 'Increase recycling, composting, and reduce single-use items to turn waste into carbon savings.',
                            'impact' => 'medium',
                            'difficulty' => 'easy',
                            'potential_savings' => round(abs($carbon_amount) * 1.5, 1) . 'kg CO2/month'
                        );
                    }
                    break;
            }
        }
        
        // Add general recommendations if total footprint is high
        if ($summary['total_carbon'] > 500) {
            $recommendations[] = array(
                'type' => 'general',
                'title' => 'Set carbon reduction goals',
                'description' => 'Your monthly footprint is ' . round($summary['total_carbon'], 1) . 'kg CO2. Set a goal to reduce it by 20% over the next 3 months.',
                'impact' => 'high',
                'difficulty' => 'medium',
                'potential_savings' => round($summary['total_carbon'] * 0.2, 1) . 'kg CO2/month'
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Get carbon footprint comparison with peers
     */
    public function get_peer_comparison($user_id) {
        global $wpdb;
        
        $user_carbon = $this->get_user_summary($user_id, 'month')['total_carbon'];
        
        // Get average carbon footprint for all users
        $avg_sql = "SELECT AVG(monthly_carbon) as avg_carbon 
                    FROM (
                        SELECT user_id, SUM(carbon_amount) as monthly_carbon 
                        FROM {$this->table_name} 
                        WHERE date_recorded >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                        GROUP BY user_id
                    ) as monthly_totals";
        
        $avg_carbon = $wpdb->get_var($avg_sql) ?: 0;
        
        // Get percentile ranking
        $percentile_sql = $wpdb->prepare(
            "SELECT COUNT(*) as users_below 
             FROM (
                 SELECT user_id, SUM(carbon_amount) as monthly_carbon 
                 FROM {$this->table_name} 
                 WHERE date_recorded >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY user_id
                 HAVING monthly_carbon < %f
             ) as lower_emissions",
            $user_carbon
        );
        
        $users_below = $wpdb->get_var($percentile_sql) ?: 0;
        
        $total_users_sql = "SELECT COUNT(DISTINCT user_id) as total_users 
                           FROM {$this->table_name} 
                           WHERE date_recorded >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        
        $total_users = $wpdb->get_var($total_users_sql) ?: 1;
        
        $percentile = ($users_below / $total_users) * 100;
        
        return array(
            'user_carbon' => $user_carbon,
            'average_carbon' => $avg_carbon,
            'percentile' => round($percentile, 1),
            'comparison' => $user_carbon < $avg_carbon ? 'below_average' : 'above_average',
            'difference' => abs($user_carbon - $avg_carbon)
        );
    }
    
    /**
     * Render carbon tracker widget
     */
    public function render_tracker_widget($atts) {
        if (!is_user_logged_in()) {
            return '<div class="env-carbon-tracker-login">
                        <p>' . __('Please log in to track your carbon footprint.', 'env-data-dashboard') . '</p>
                        <a href="' . wp_login_url() . '" class="button">' . __('Log In', 'env-data-dashboard') . '</a>
                    </div>';
        }
        
        $user_id = get_current_user_id();
        $summary = $this->get_user_summary($user_id, $atts['period']);
        $recommendations = $this->get_recommendations($user_id);
        
        ob_start();
        ?>
        <div class="env-carbon-tracker" id="env-carbon-tracker">
            <?php if ($atts['show_form'] === 'true'): ?>
            <div class="carbon-tracking-form">
                <h3><?php _e('Add Carbon Footprint Activity', 'env-data-dashboard'); ?></h3>
                <form id="carbon-footprint-form">
                    <div class="form-row">
                        <label for="category"><?php _e('Category', 'env-data-dashboard'); ?></label>
                        <select name="category" id="category" required>
                            <option value=""><?php _e('Select Category', 'env-data-dashboard'); ?></option>
                            <option value="transportation"><?php _e('Transportation', 'env-data-dashboard'); ?></option>
                            <option value="energy"><?php _e('Energy', 'env-data-dashboard'); ?></option>
                            <option value="food"><?php _e('Food', 'env-data-dashboard'); ?></option>
                            <option value="waste"><?php _e('Waste', 'env-data-dashboard'); ?></option>
                            <option value="consumption"><?php _e('Consumption', 'env-data-dashboard'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <label for="activity_type"><?php _e('Activity Type', 'env-data-dashboard'); ?></label>
                        <select name="activity_type" id="activity_type" required>
                            <option value=""><?php _e('Select Activity', 'env-data-dashboard'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <label for="quantity"><?php _e('Quantity', 'env-data-dashboard'); ?></label>
                        <input type="number" name="quantity" id="quantity" step="0.01" required>
                        <small class="quantity-unit"></small>
                    </div>
                    
                    <div class="form-row">
                        <label for="description"><?php _e('Description (Optional)', 'env-data-dashboard'); ?></label>
                        <textarea name="description" id="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <label for="date_recorded"><?php _e('Date', 'env-data-dashboard'); ?></label>
                        <input type="date" name="date_recorded" id="date_recorded" value="<?php echo current_time('Y-m-d'); ?>" required>
                    </div>
                    
                    <button type="submit" class="button button-primary"><?php _e('Add Activity', 'env-data-dashboard'); ?></button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="carbon-summary">
                <h3><?php printf(__('Your Carbon Footprint - %s', 'env-data-dashboard'), ucfirst($atts['period'])); ?></h3>
                <div class="carbon-stats">
                    <div class="carbon-stat">
                        <span class="carbon-value"><?php echo round($summary['total_carbon'], 2); ?></span>
                        <span class="carbon-unit">kg CO₂</span>
                        <span class="carbon-label"><?php _e('Total Emissions', 'env-data-dashboard'); ?></span>
                    </div>
                    <div class="carbon-stat">
                        <span class="carbon-value"><?php echo round($summary['average_daily'], 2); ?></span>
                        <span class="carbon-unit">kg CO₂/day</span>
                        <span class="carbon-label"><?php _e('Daily Average', 'env-data-dashboard'); ?></span>
                    </div>
                    <div class="carbon-stat">
                        <span class="carbon-value"><?php echo $summary['total_activities']; ?></span>
                        <span class="carbon-unit"><?php _e('activities', 'env-data-dashboard'); ?></span>
                        <span class="carbon-label"><?php _e('Recorded', 'env-data-dashboard'); ?></span>
                    </div>
                </div>
            </div>
            
            <?php if ($atts['show_chart'] === 'true'): ?>
            <div class="carbon-chart">
                <h4><?php _e('Emissions by Category', 'env-data-dashboard'); ?></h4>
                <canvas id="carbon-category-chart"></canvas>
            </div>
            <?php endif; ?>
            
            <div class="carbon-recommendations">
                <h4><?php _e('Recommendations', 'env-data-dashboard'); ?></h4>
                <?php if (!empty($recommendations)): ?>
                    <?php foreach ($recommendations as $recommendation): ?>
                        <div class="recommendation-item impact-<?php echo $recommendation['impact']; ?>">
                            <h5><?php echo esc_html($recommendation['title']); ?></h5>
                            <p><?php echo esc_html($recommendation['description']); ?></p>
                            <?php if (isset($recommendation['potential_savings'])): ?>
                                <small class="potential-savings"><?php printf(__('Potential savings: %s', 'env-data-dashboard'), $recommendation['potential_savings']); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p><?php _e('Keep tracking your activities to receive personalized recommendations.', 'env-data-dashboard'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Activity type options for each category
            const activityTypes = {
                transportation: {
                    'car_petrol': '<?php _e('Car (Petrol)', 'env-data-dashboard'); ?>',
                    'car_diesel': '<?php _e('Car (Diesel)', 'env-data-dashboard'); ?>',
                    'car_electric': '<?php _e('Car (Electric)', 'env-data-dashboard'); ?>',
                    'motorcycle': '<?php _e('Motorcycle', 'env-data-dashboard'); ?>',
                    'bus': '<?php _e('Bus', 'env-data-dashboard'); ?>',
                    'train': '<?php _e('Train', 'env-data-dashboard'); ?>',
                    'airplane_domestic': '<?php _e('Airplane (Domestic)', 'env-data-dashboard'); ?>',
                    'airplane_international': '<?php _e('Airplane (International)', 'env-data-dashboard'); ?>',
                    'bicycle': '<?php _e('Bicycle', 'env-data-dashboard'); ?>',
                    'walking': '<?php _e('Walking', 'env-data-dashboard'); ?>'
                },
                energy: {
                    'electricity_grid': '<?php _e('Grid Electricity', 'env-data-dashboard'); ?>',
                    'natural_gas': '<?php _e('Natural Gas', 'env-data-dashboard'); ?>',
                    'coal': '<?php _e('Coal', 'env-data-dashboard'); ?>',
                    'solar': '<?php _e('Solar Energy', 'env-data-dashboard'); ?>',
                    'wind': '<?php _e('Wind Energy', 'env-data-dashboard'); ?>',
                    'hydroelectric': '<?php _e('Hydroelectric', 'env-data-dashboard'); ?>'
                },
                food: {
                    'beef': '<?php _e('Beef', 'env-data-dashboard'); ?>',
                    'pork': '<?php _e('Pork', 'env-data-dashboard'); ?>',
                    'chicken': '<?php _e('Chicken', 'env-data-dashboard'); ?>',
                    'fish': '<?php _e('Fish', 'env-data-dashboard'); ?>',
                    'dairy': '<?php _e('Dairy Products', 'env-data-dashboard'); ?>',
                    'vegetables': '<?php _e('Vegetables', 'env-data-dashboard'); ?>',
                    'fruits': '<?php _e('Fruits', 'env-data-dashboard'); ?>',
                    'grains': '<?php _e('Grains', 'env-data-dashboard'); ?>',
                    'local_food': '<?php _e('Local Food', 'env-data-dashboard'); ?>',
                    'organic_food': '<?php _e('Organic Food', 'env-data-dashboard'); ?>'
                },
                waste: {
                    'general_waste': '<?php _e('General Waste', 'env-data-dashboard'); ?>',
                    'recycled_paper': '<?php _e('Recycled Paper', 'env-data-dashboard'); ?>',
                    'recycled_plastic': '<?php _e('Recycled Plastic', 'env-data-dashboard'); ?>',
                    'recycled_metal': '<?php _e('Recycled Metal', 'env-data-dashboard'); ?>',
                    'recycled_glass': '<?php _e('Recycled Glass', 'env-data-dashboard'); ?>',
                    'composted_organic': '<?php _e('Composted Organic', 'env-data-dashboard'); ?>'
                },
                consumption: {
                    'new_clothing': '<?php _e('New Clothing', 'env-data-dashboard'); ?>',
                    'secondhand_clothing': '<?php _e('Secondhand Clothing', 'env-data-dashboard'); ?>',
                    'electronics': '<?php _e('Electronics', 'env-data-dashboard'); ?>',
                    'furniture': '<?php _e('Furniture', 'env-data-dashboard'); ?>',
                    'books': '<?php _e('Books', 'env-data-dashboard'); ?>',
                    'plastic_bottle': '<?php _e('Plastic Bottle', 'env-data-dashboard'); ?>',
                    'reusable_bottle': '<?php _e('Reusable Bottle Use', 'env-data-dashboard'); ?>'
                }
            };
            
            const units = {
                transportation: 'km',
                energy: 'kWh',
                food: 'kg',
                waste: 'kg',
                consumption: 'items'
            };
            
            // Update activity types when category changes
            $('#category').change(function() {
                const category = $(this).val();
                const activitySelect = $('#activity_type');
                const quantityUnit = $('.quantity-unit');
                
                activitySelect.empty().append('<option value=""><?php _e('Select Activity', 'env-data-dashboard'); ?></option>');
                
                if (category && activityTypes[category]) {
                    $.each(activityTypes[category], function(value, label) {
                        activitySelect.append('<option value="' + value + '">' + label + '</option>');
                    });
                    
                    quantityUnit.text('(' + units[category] + ')');
                } else {
                    quantityUnit.text('');
                }
            });
            
            // Handle form submission
            $('#carbon-footprint-form').submit(function(e) {
                e.preventDefault();
                
                const formData = {
                    action: 'save_carbon_footprint',
                    nonce: envDashboard.nonce,
                    category: $('#category').val(),
                    activity_type: $('#activity_type').val(),
                    quantity: $('#quantity').val(),
                    description: $('#description').val(),
                    date_recorded: $('#date_recorded').val()
                };
                
                $.ajax({
                    url: envDashboard.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            alert('<?php _e('Carbon footprint activity added successfully!', 'env-data-dashboard'); ?>');
                            location.reload(); // Reload to show updated data
                        } else {
                            alert('<?php _e('Error adding activity: ', 'env-data-dashboard'); ?>' + response.data);
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error adding activity. Please try again.', 'env-data-dashboard'); ?>');
                    }
                });
            });
            
            // Render category chart
            <?php if (!empty($summary['categories'])): ?>
            const categoryData = {
                labels: [<?php echo implode(',', array_map(function($cat) { return "'" . ucfirst($cat['category']) . "'"; }, $summary['categories'])); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_column($summary['categories'], 'total_carbon')); ?>],
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
                    ]
                }]
            };
            
            const ctx = document.getElementById('carbon-category-chart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'doughnut',
                    data: categoryData,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.label + ': ' + context.parsed + ' kg CO₂';
                                    }
                                }
                            }
                        }
                    }
                });
            }
            <?php endif; ?>
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Clean old carbon footprint data
     */
    public function clean_old_data($days_to_keep = 365) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "DELETE FROM {$this->table_name} 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days_to_keep
        );
        
        return $wpdb->query($sql);
    }
    
    /**
     * Export carbon footprint data to CSV
     */
    public function export_user_data_to_csv($user_id, $start_date = null, $end_date = null) {
        global $wpdb;
        
        $where_conditions = array("user_id = %d");
        $where_values = array($user_id);
        
        if ($start_date) {
            $where_conditions[] = "DATE(date_recorded) >= %s";
            $where_values[] = $start_date;
        }
        
        if ($end_date) {
            $where_conditions[] = "DATE(date_recorded) <= %s";
            $where_values[] = $end_date;
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        $sql = "SELECT 
                    date_recorded,
                    category,
                    activity_type,
                    activity_description,
                    carbon_amount,
                    unit,
                    created_at
                FROM {$this->table_name} 
                {$where_clause} 
                ORDER BY date_recorded DESC";
        
        $sql = $wpdb->prepare($sql, $where_values);
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        if (empty($results)) {
            return false;
        }
        
        // Create CSV content
        $csv_data = array();
        $csv_data[] = array('Date', 'Category', 'Activity Type', 'Description', 'Carbon Amount', 'Unit', 'Recorded At');
        
        foreach ($results as $row) {
            $csv_data[] = array(
                $row['date_recorded'],
                ucfirst($row['category']),
                str_replace('_', ' ', ucfirst($row['activity_type'])),
                $row['activity_description'],
                $row['carbon_amount'],
                $row['unit'],
                $row['created_at']
            );
        }
        
        return $csv_data;
    }
}

// End of file
