<?php
/**
 * Environmental Data API Endpoints
 *
 * @package EnvironmentalMobileAPI
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Environmental_Mobile_API_Environmental_Data_Endpoints
 */
class Environmental_Mobile_API_Environmental_Data_Endpoints {
    
    /**
     * Auth manager instance
     */
    private $auth_manager;
    
    /**
     * Cache manager instance
     */
    private $cache_manager;
    
    /**
     * Security instance
     */
    private $security;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->auth_manager = new Environmental_Mobile_API_Auth_Manager();
        $this->cache_manager = new Environmental_Mobile_API_Cache_Manager();
        $this->security = new Environmental_Mobile_API_Security();
        
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        $namespace = 'environmental-mobile-api/v1';
        
        // Get environmental statistics
        register_rest_route($namespace, '/environmental/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_environmental_stats'),
            'permission_callback' => '__return_true',
            'args' => array(
                'period' => array(
                    'default' => 'month',
                    'enum' => array('day', 'week', 'month', 'year', 'all'),
                ),
                'category' => array(
                    'type' => 'string',
                ),
            ),
        ));
        
        // Get impact data
        register_rest_route($namespace, '/environmental/impact', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_impact_data'),
            'permission_callback' => '__return_true',
            'args' => array(
                'user_id' => array(
                    'type' => 'integer',
                ),
                'type' => array(
                    'default' => 'all',
                    'enum' => array('all', 'carbon', 'waste', 'water', 'energy'),
                ),
            ),
        ));
        
        // Get achievements and badges
        register_rest_route($namespace, '/environmental/achievements', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_achievements'),
            'permission_callback' => array($this, 'check_authentication'),
        ));
        
        // Get leaderboard
        register_rest_route($namespace, '/environmental/leaderboard', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_leaderboard'),
            'permission_callback' => '__return_true',
            'args' => array(
                'type' => array(
                    'default' => 'points',
                    'enum' => array('points', 'impact', 'petitions', 'items'),
                ),
                'period' => array(
                    'default' => 'all',
                    'enum' => array('day', 'week', 'month', 'year', 'all'),
                ),
                'limit' => array(
                    'default' => 10,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ),
            ),
        ));
        
        // Submit environmental action
        register_rest_route($namespace, '/environmental/actions', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_action'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'action_type' => array(
                    'required' => true,
                    'enum' => array('recycle', 'reduce_energy', 'use_public_transport', 'plant_tree', 'reduce_water', 'other'),
                ),
                'description' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
                'impact_value' => array(
                    'type' => 'number',
                ),
                'impact_unit' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'location' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'image' => array(
                    'type' => 'string',
                ),
            ),
        ));
        
        // Get user actions history
        register_rest_route($namespace, '/environmental/actions', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_actions'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'type' => 'integer',
                    'minimum' => 1,
                ),
                'per_page' => array(
                    'default' => 20,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 100,
                ),
                'action_type' => array(
                    'type' => 'string',
                ),
                'from_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                ),
                'to_date' => array(
                    'type' => 'string',
                    'format' => 'date',
                ),
            ),
        ));
        
        // Get carbon footprint calculator
        register_rest_route($namespace, '/environmental/carbon-calculator', array(
            'methods' => 'POST',
            'callback' => array($this, 'calculate_carbon_footprint'),
            'permission_callback' => '__return_true',
            'args' => array(
                'transport' => array(
                    'type' => 'object',
                ),
                'energy' => array(
                    'type' => 'object',
                ),
                'consumption' => array(
                    'type' => 'object',
                ),
            ),
        ));
        
        // Get environmental tips
        register_rest_route($namespace, '/environmental/tips', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_environmental_tips'),
            'permission_callback' => '__return_true',
            'args' => array(
                'category' => array(
                    'type' => 'string',
                ),
                'limit' => array(
                    'default' => 5,
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 50,
                ),
                'random' => array(
                    'type' => 'boolean',
                    'default' => false,
                ),
            ),
        ));
        
        // Report environmental issue
        register_rest_route($namespace, '/environmental/report-issue', array(
            'methods' => 'POST',
            'callback' => array($this, 'report_issue'),
            'permission_callback' => array($this, 'check_authentication'),
            'args' => array(
                'issue_type' => array(
                    'required' => true,
                    'enum' => array('pollution', 'waste_dumping', 'deforestation', 'water_contamination', 'air_quality', 'other'),
                ),
                'description' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
                'location' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'latitude' => array(
                    'type' => 'number',
                ),
                'longitude' => array(
                    'type' => 'number',
                ),
                'severity' => array(
                    'default' => 'medium',
                    'enum' => array('low', 'medium', 'high', 'critical'),
                ),
                'images' => array(
                    'type' => 'array',
                    'items' => array(
                        'type' => 'string',
                    ),
                ),
            ),
        ));
    }
    
    /**
     * Get environmental statistics
     */
    public function get_environmental_stats($request) {
        try {
            $period = $request->get_param('period');
            $category = $request->get_param('category');
            
            $cache_key = "env_stats_{$period}_{$category}";
            $cached_result = $this->cache_manager->get($cache_key);
            
            if ($cached_result !== false) {
                return new WP_REST_Response($cached_result, 200);
            }
            
            global $wpdb;
            
            // Calculate date range
            $date_condition = $this->get_date_condition($period);
            
            // Get petition statistics
            $petition_stats = $wpdb->get_row("
                SELECT 
                    COUNT(*) as total_petitions,
                    SUM(CAST(meta_value AS UNSIGNED)) as total_signatures
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'signature_count'
                WHERE p.post_type = 'environmental_petition' 
                AND p.post_status = 'publish'
                {$date_condition}
            ");
            
            // Get item exchange statistics
            $item_stats = $wpdb->get_row("
                SELECT 
                    COUNT(*) as total_items,
                    COUNT(CASE WHEN pm.meta_value = 'exchanged' THEN 1 END) as exchanged_items
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'item_status'
                WHERE p.post_type = 'environmental_item' 
                AND p.post_status = 'publish'
                {$date_condition}
            ");
            
            // Get user engagement statistics
            $user_stats = $wpdb->get_row("
                SELECT 
                    COUNT(DISTINCT user_id) as active_users,
                    SUM(CAST(meta_value AS UNSIGNED)) as total_points
                FROM {$wpdb->usermeta}
                WHERE meta_key = 'environmental_points'
            ");
            
            // Get environmental impact data
            $impact_stats = $this->calculate_environmental_impact($period);
            
            $stats = array(
                'petitions' => array(
                    'total' => (int) $petition_stats->total_petitions,
                    'signatures' => (int) $petition_stats->total_signatures,
                    'success_rate' => $this->calculate_petition_success_rate($period),
                ),
                'item_exchange' => array(
                    'total_items' => (int) $item_stats->total_items,
                    'exchanged_items' => (int) $item_stats->exchanged_items,
                    'exchange_rate' => $item_stats->total_items > 0 ? round(($item_stats->exchanged_items / $item_stats->total_items) * 100, 2) : 0,
                ),
                'community' => array(
                    'active_users' => (int) $user_stats->active_users,
                    'total_points' => (int) $user_stats->total_points,
                    'average_points' => $user_stats->active_users > 0 ? round($user_stats->total_points / $user_stats->active_users, 2) : 0,
                ),
                'environmental_impact' => $impact_stats,
                'period' => $period,
                'generated_at' => current_time('mysql'),
            );
            
            $result = array(
                'success' => true,
                'data' => $stats,
            );
            
            // Cache for 30 minutes
            $this->cache_manager->set($cache_key, $result, 1800);
            
            return new WP_REST_Response($result, 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Environmental Stats Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get impact data
     */
    public function get_impact_data($request) {
        try {
            $user_id = $request->get_param('user_id') ?: get_current_user_id();
            $type = $request->get_param('type');
            
            $cache_key = "impact_data_{$user_id}_{$type}";
            $cached_result = $this->cache_manager->get($cache_key);
            
            if ($cached_result !== false) {
                return new WP_REST_Response($cached_result, 200);
            }
            
            global $wpdb;
            $table_actions = $wpdb->prefix . 'environmental_user_actions';
            
            $impact_data = array();
            
            if ($type === 'all' || $type === 'carbon') {
                $carbon_data = $wpdb->get_results($wpdb->prepare("
                    SELECT 
                        DATE(action_date) as date,
                        SUM(impact_value) as total_impact
                    FROM {$table_actions}
                    WHERE user_id = %d AND impact_unit = 'kg_co2'
                    GROUP BY DATE(action_date)
                    ORDER BY date DESC
                    LIMIT 30
                ", $user_id));
                
                $impact_data['carbon'] = array(
                    'unit' => 'kg CO2 saved',
                    'total' => array_sum(array_column($carbon_data, 'total_impact')),
                    'daily_data' => $carbon_data,
                );
            }
            
            if ($type === 'all' || $type === 'waste') {
                $waste_data = $wpdb->get_results($wpdb->prepare("
                    SELECT 
                        DATE(action_date) as date,
                        SUM(impact_value) as total_impact
                    FROM {$table_actions}
                    WHERE user_id = %d AND impact_unit = 'kg_waste'
                    GROUP BY DATE(action_date)
                    ORDER BY date DESC
                    LIMIT 30
                ", $user_id));
                
                $impact_data['waste'] = array(
                    'unit' => 'kg waste reduced',
                    'total' => array_sum(array_column($waste_data, 'total_impact')),
                    'daily_data' => $waste_data,
                );
            }
            
            if ($type === 'all' || $type === 'water') {
                $water_data = $wpdb->get_results($wpdb->prepare("
                    SELECT 
                        DATE(action_date) as date,
                        SUM(impact_value) as total_impact
                    FROM {$table_actions}
                    WHERE user_id = %d AND impact_unit = 'liters_water'
                    GROUP BY DATE(action_date)
                    ORDER BY date DESC
                    LIMIT 30
                ", $user_id));
                
                $impact_data['water'] = array(
                    'unit' => 'liters water saved',
                    'total' => array_sum(array_column($water_data, 'total_impact')),
                    'daily_data' => $water_data,
                );
            }
            
            if ($type === 'all' || $type === 'energy') {
                $energy_data = $wpdb->get_results($wpdb->prepare("
                    SELECT 
                        DATE(action_date) as date,
                        SUM(impact_value) as total_impact
                    FROM {$table_actions}
                    WHERE user_id = %d AND impact_unit = 'kwh_energy'
                    GROUP BY DATE(action_date)
                    ORDER BY date DESC
                    LIMIT 30
                ", $user_id));
                
                $impact_data['energy'] = array(
                    'unit' => 'kWh energy saved',
                    'total' => array_sum(array_column($energy_data, 'total_impact')),
                    'daily_data' => $energy_data,
                );
            }
            
            $result = array(
                'success' => true,
                'data' => array(
                    'user_id' => $user_id,
                    'impact_data' => $impact_data,
                    'summary' => $this->calculate_impact_summary($user_id),
                ),
            );
            
            // Cache for 15 minutes
            $this->cache_manager->set($cache_key, $result, 900);
            
            return new WP_REST_Response($result, 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Impact Data Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get achievements
     */
    public function get_achievements($request) {
        try {
            $user_id = get_current_user_id();
            $cache_key = "achievements_{$user_id}";
            
            $cached_result = $this->cache_manager->get($cache_key);
            
            if ($cached_result !== false) {
                return new WP_REST_Response($cached_result, 200);
            }
            
            // Get user badges
            $earned_badges = get_user_meta($user_id, 'environmental_badges', true) ?: array();
            
            // Get available achievements
            $available_achievements = $this->get_available_achievements();
            
            $achievements = array();
            
            foreach ($available_achievements as $achievement) {
                $is_earned = in_array($achievement['id'], $earned_badges);
                $progress = $this->calculate_achievement_progress($user_id, $achievement);
                
                $achievements[] = array(
                    'id' => $achievement['id'],
                    'title' => $achievement['title'],
                    'description' => $achievement['description'],
                    'icon' => $achievement['icon'],
                    'category' => $achievement['category'],
                    'points' => $achievement['points'],
                    'is_earned' => $is_earned,
                    'earned_date' => $is_earned ? $this->get_badge_earned_date($user_id, $achievement['id']) : null,
                    'progress' => $progress,
                );
            }
            
            $result = array(
                'success' => true,
                'data' => array(
                    'total_achievements' => count($available_achievements),
                    'earned_achievements' => count($earned_badges),
                    'completion_percentage' => count($available_achievements) > 0 ? round((count($earned_badges) / count($available_achievements)) * 100, 2) : 0,
                    'achievements' => $achievements,
                ),
            );
            
            // Cache for 30 minutes
            $this->cache_manager->set($cache_key, $result, 1800);
            
            return new WP_REST_Response($result, 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Achievements Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get leaderboard
     */
    public function get_leaderboard($request) {
        try {
            $type = $request->get_param('type');
            $period = $request->get_param('period');
            $limit = $request->get_param('limit');
            
            $cache_key = "leaderboard_{$type}_{$period}_{$limit}";
            $cached_result = $this->cache_manager->get($cache_key);
            
            if ($cached_result !== false) {
                return new WP_REST_Response($cached_result, 200);
            }
            
            global $wpdb;
            
            $leaderboard = array();
            
            switch ($type) {
                case 'points':
                    $leaderboard = $this->get_points_leaderboard($limit, $period);
                    break;
                case 'impact':
                    $leaderboard = $this->get_impact_leaderboard($limit, $period);
                    break;
                case 'petitions':
                    $leaderboard = $this->get_petitions_leaderboard($limit, $period);
                    break;
                case 'items':
                    $leaderboard = $this->get_items_leaderboard($limit, $period);
                    break;
            }
            
            $result = array(
                'success' => true,
                'data' => array(
                    'type' => $type,
                    'period' => $period,
                    'leaderboard' => $leaderboard,
                    'user_rank' => $this->get_user_rank($type, $period),
                ),
            );
            
            // Cache for 15 minutes
            $this->cache_manager->set($cache_key, $result, 900);
            
            return new WP_REST_Response($result, 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Leaderboard Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Submit environmental action
     */
    public function submit_action($request) {
        try {
            $user_id = get_current_user_id();
            $action_type = $request->get_param('action_type');
            $description = $request->get_param('description');
            $impact_value = $request->get_param('impact_value') ?: 0;
            $impact_unit = $request->get_param('impact_unit') ?: '';
            $location = $request->get_param('location');
            $image = $request->get_param('image');
            
            global $wpdb;
            $table_actions = $wpdb->prefix . 'environmental_user_actions';
            
            // Calculate points based on action type
            $points = $this->calculate_action_points($action_type, $impact_value);
            
            // Insert action record
            $result = $wpdb->insert(
                $table_actions,
                array(
                    'user_id' => $user_id,
                    'action_type' => $action_type,
                    'description' => $description,
                    'impact_value' => $impact_value,
                    'impact_unit' => $impact_unit,
                    'location' => $location,
                    'image_url' => $image,
                    'points_earned' => $points,
                    'action_date' => current_time('mysql'),
                    'status' => 'verified', // Auto-verify for now
                ),
                array('%d', '%s', '%s', '%f', '%s', '%s', '%s', '%d', '%s', '%s')
            );
            
            if ($result === false) {
                return new WP_Error('action_failed', 'Failed to submit environmental action.', array('status' => 500));
            }
            
            // Update user points
            $current_points = (int) get_user_meta($user_id, 'environmental_points', true);
            update_user_meta($user_id, 'environmental_points', $current_points + $points);
            
            // Update impact score
            $current_impact = (int) get_user_meta($user_id, 'environmental_impact_score', true);
            update_user_meta($user_id, 'environmental_impact_score', $current_impact + $impact_value);
            
            // Check for new achievements
            $new_achievements = $this->check_for_achievements($user_id);
            
            // Clear cache
            $this->cache_manager->delete("user_stats_{$user_id}");
            $this->cache_manager->delete("achievements_{$user_id}");
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Environmental action submitted successfully',
                'data' => array(
                    'action_id' => $wpdb->insert_id,
                    'points_earned' => $points,
                    'total_points' => $current_points + $points,
                    'new_achievements' => $new_achievements,
                ),
            ), 201);
            
        } catch (Exception $e) {
            error_log('Mobile API Submit Action Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get user actions history
     */
    public function get_user_actions($request) {
        try {
            $user_id = get_current_user_id();
            $page = $request->get_param('page');
            $per_page = $request->get_param('per_page');
            $action_type = $request->get_param('action_type');
            $from_date = $request->get_param('from_date');
            $to_date = $request->get_param('to_date');
            
            global $wpdb;
            $table_actions = $wpdb->prefix . 'environmental_user_actions';
            
            $where_conditions = array("user_id = %d");
            $where_values = array($user_id);
            
            if ($action_type) {
                $where_conditions[] = "action_type = %s";
                $where_values[] = $action_type;
            }
            
            if ($from_date) {
                $where_conditions[] = "DATE(action_date) >= %s";
                $where_values[] = $from_date;
            }
            
            if ($to_date) {
                $where_conditions[] = "DATE(action_date) <= %s";
                $where_values[] = $to_date;
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            $offset = ($page - 1) * $per_page;
            
            // Get total count
            $total_query = "SELECT COUNT(*) FROM {$table_actions} WHERE {$where_clause}";
            $total = $wpdb->get_var($wpdb->prepare($total_query, $where_values));
            
            // Get actions
            $actions_query = "
                SELECT * FROM {$table_actions} 
                WHERE {$where_clause}
                ORDER BY action_date DESC 
                LIMIT %d OFFSET %d
            ";
            
            $where_values[] = $per_page;
            $where_values[] = $offset;
            
            $actions = $wpdb->get_results($wpdb->prepare($actions_query, $where_values));
            
            $formatted_actions = array();
            
            foreach ($actions as $action) {
                $formatted_actions[] = array(
                    'id' => $action->id,
                    'action_type' => $action->action_type,
                    'description' => $action->description,
                    'impact_value' => (float) $action->impact_value,
                    'impact_unit' => $action->impact_unit,
                    'location' => $action->location,
                    'image_url' => $action->image_url,
                    'points_earned' => (int) $action->points_earned,
                    'action_date' => $action->action_date,
                    'status' => $action->status,
                );
            }
            
            return new WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'actions' => $formatted_actions,
                    'pagination' => array(
                        'page' => $page,
                        'per_page' => $per_page,
                        'total' => (int) $total,
                        'total_pages' => ceil($total / $per_page),
                    ),
                ),
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get User Actions Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Calculate carbon footprint
     */
    public function calculate_carbon_footprint($request) {
        try {
            $transport = $request->get_param('transport') ?: array();
            $energy = $request->get_param('energy') ?: array();
            $consumption = $request->get_param('consumption') ?: array();
            
            $carbon_footprint = array(
                'transport' => $this->calculate_transport_emissions($transport),
                'energy' => $this->calculate_energy_emissions($energy),
                'consumption' => $this->calculate_consumption_emissions($consumption),
            );
            
            $total_emissions = array_sum($carbon_footprint);
            
            // Get recommendations based on highest emissions
            $recommendations = $this->get_carbon_reduction_recommendations($carbon_footprint);
            
            return new WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'total_emissions' => round($total_emissions, 2),
                    'unit' => 'kg CO2/year',
                    'breakdown' => array(
                        'transport' => round($carbon_footprint['transport'], 2),
                        'energy' => round($carbon_footprint['energy'], 2),
                        'consumption' => round($carbon_footprint['consumption'], 2),
                    ),
                    'comparison' => array(
                        'global_average' => 4800, // kg CO2/year
                        'sustainable_target' => 2000, // kg CO2/year
                        'your_performance' => $total_emissions < 2000 ? 'excellent' : ($total_emissions < 4800 ? 'good' : 'needs_improvement'),
                    ),
                    'recommendations' => $recommendations,
                ),
            ), 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Calculate Carbon Footprint Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Get environmental tips
     */
    public function get_environmental_tips($request) {
        try {
            $category = $request->get_param('category');
            $limit = $request->get_param('limit');
            $random = $request->get_param('random');
            
            $cache_key = "env_tips_{$category}_{$limit}_{$random}";
            $cached_result = $this->cache_manager->get($cache_key);
            
            if ($cached_result !== false) {
                return new WP_REST_Response($cached_result, 200);
            }
            
            $tips = $this->get_tips_database();
            
            // Filter by category if specified
            if ($category && $category !== 'all') {
                $tips = array_filter($tips, function($tip) use ($category) {
                    return $tip['category'] === $category;
                });
            }
            
            // Randomize if requested
            if ($random) {
                shuffle($tips);
            }
            
            // Limit results
            $tips = array_slice($tips, 0, $limit);
            
            $result = array(
                'success' => true,
                'data' => array(
                    'tips' => $tips,
                    'total_available' => count($this->get_tips_database()),
                ),
            );
            
            // Cache for 1 hour
            $this->cache_manager->set($cache_key, $result, 3600);
            
            return new WP_REST_Response($result, 200);
            
        } catch (Exception $e) {
            error_log('Mobile API Get Environmental Tips Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Report environmental issue
     */
    public function report_issue($request) {
        try {
            $user_id = get_current_user_id();
            $issue_type = $request->get_param('issue_type');
            $description = $request->get_param('description');
            $location = $request->get_param('location');
            $latitude = $request->get_param('latitude');
            $longitude = $request->get_param('longitude');
            $severity = $request->get_param('severity');
            $images = $request->get_param('images') ?: array();
            
            global $wpdb;
            $table_reports = $wpdb->prefix . 'environmental_issue_reports';
            
            // Insert issue report
            $result = $wpdb->insert(
                $table_reports,
                array(
                    'user_id' => $user_id,
                    'issue_type' => $issue_type,
                    'description' => $description,
                    'location' => $location,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'severity' => $severity,
                    'images' => json_encode($images),
                    'status' => 'reported',
                    'reported_date' => current_time('mysql'),
                ),
                array('%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                return new WP_Error('report_failed', 'Failed to submit issue report.', array('status' => 500));
            }
            
            $report_id = $wpdb->insert_id;
            
            // Award points for reporting
            $points = 5;
            $current_points = (int) get_user_meta($user_id, 'environmental_points', true);
            update_user_meta($user_id, 'environmental_points', $current_points + $points);
            
            // Send notification to administrators
            $this->notify_admins_of_report($report_id, $issue_type, $severity);
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Environmental issue reported successfully',
                'data' => array(
                    'report_id' => $report_id,
                    'points_earned' => $points,
                    'status' => 'reported',
                ),
            ), 201);
            
        } catch (Exception $e) {
            error_log('Mobile API Report Issue Error: ' . $e->getMessage());
            return new WP_Error('internal_error', 'An internal error occurred.', array('status' => 500));
        }
    }
    
    /**
     * Check authentication permission callback
     */
    public function check_authentication($request) {
        return $this->auth_manager->authenticate_request($request);
    }
    
    // ... Helper methods would continue here ...
    // Due to length constraints, I'll continue with the remaining helper methods in the next file
    
    /**
     * Get date condition for SQL queries
     */
    private function get_date_condition($period) {
        $condition = '';
        
        switch ($period) {
            case 'day':
                $condition = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $condition = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $condition = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $condition = "AND p.post_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            default: // 'all'
                break;
        }
        
        return $condition;
    }
    
    /**
     * Calculate environmental impact
     */
    private function calculate_environmental_impact($period) {
        global $wpdb;
        $table_actions = $wpdb->prefix . 'environmental_user_actions';
        
        $date_condition = '';
        switch ($period) {
            case 'day':
                $date_condition = "AND action_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $date_condition = "AND action_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $date_condition = "AND action_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $date_condition = "AND action_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }
        
        $impact = $wpdb->get_row("
            SELECT 
                SUM(CASE WHEN impact_unit = 'kg_co2' THEN impact_value ELSE 0 END) as carbon_saved,
                SUM(CASE WHEN impact_unit = 'kg_waste' THEN impact_value ELSE 0 END) as waste_reduced,
                SUM(CASE WHEN impact_unit = 'liters_water' THEN impact_value ELSE 0 END) as water_saved,
                SUM(CASE WHEN impact_unit = 'kwh_energy' THEN impact_value ELSE 0 END) as energy_saved
            FROM {$table_actions}
            WHERE status = 'verified' {$date_condition}
        ");
        
        return array(
            'carbon_saved' => round((float) $impact->carbon_saved, 2),
            'waste_reduced' => round((float) $impact->waste_reduced, 2),
            'water_saved' => round((float) $impact->water_saved, 2),
            'energy_saved' => round((float) $impact->energy_saved, 2),
        );
    }
    
    /**
     * Calculate petition success rate
     */
    private function calculate_petition_success_rate($period) {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN pm.meta_value = 'completed' THEN 1 END) as completed
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'petition_status'
            WHERE p.post_type = 'environmental_petition' 
            AND p.post_status = 'publish'
            {$date_condition}
        ");
        
        return $stats->total > 0 ? round(($stats->completed / $stats->total) * 100, 2) : 0;
    }
    
    /**
     * Calculate impact summary
     */
    private function calculate_impact_summary($user_id) {
        global $wpdb;
        $table_actions = $wpdb->prefix . 'environmental_user_actions';
        
        $summary = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_actions,
                SUM(impact_value) as total_impact,
                SUM(points_earned) as total_points
            FROM {$table_actions}
            WHERE user_id = %d AND status = 'verified'
        ", $user_id));
        
        return array(
            'total_actions' => (int) $summary->total_actions,
            'total_impact' => round((float) $summary->total_impact, 2),
            'total_points' => (int) $summary->total_points,
            'average_impact_per_action' => $summary->total_actions > 0 ? round($summary->total_impact / $summary->total_actions, 2) : 0,
        );
    }
    
    /**
     * Get available achievements
     */
    private function get_available_achievements() {
        return array(
            array(
                'id' => 'first_petition',
                'title' => 'Activist',
                'description' => 'Sign your first petition',
                'icon' => 'petition',
                'category' => 'petitions',
                'points' => 10,
                'criteria' => array('type' => 'petition_count', 'value' => 1),
            ),
            array(
                'id' => 'eco_warrior',
                'title' => 'Eco Warrior',
                'description' => 'Sign 10 petitions',
                'icon' => 'warrior',
                'category' => 'petitions',
                'points' => 50,
                'criteria' => array('type' => 'petition_count', 'value' => 10),
            ),
            array(
                'id' => 'recycler',
                'title' => 'Recycler',
                'description' => 'Post your first item for exchange',
                'icon' => 'recycle',
                'category' => 'items',
                'points' => 15,
                'criteria' => array('type' => 'item_count', 'value' => 1),
            ),
            array(
                'id' => 'green_points_100',
                'title' => 'Green Starter',
                'description' => 'Earn 100 environmental points',
                'icon' => 'points',
                'category' => 'points',
                'points' => 20,
                'criteria' => array('type' => 'points', 'value' => 100),
            ),
            array(
                'id' => 'carbon_saver',
                'title' => 'Carbon Saver',
                'description' => 'Save 10kg of CO2 emissions',
                'icon' => 'carbon',
                'category' => 'impact',
                'points' => 25,
                'criteria' => array('type' => 'carbon_saved', 'value' => 10),
            ),
        );
    }
    
    /**
     * Calculate achievement progress
     */
    private function calculate_achievement_progress($user_id, $achievement) {
        $criteria = $achievement['criteria'];
        $current_value = 0;
        
        switch ($criteria['type']) {
            case 'petition_count':
                global $wpdb;
                $current_value = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->prefix}environmental_petition_signatures 
                    WHERE user_id = %d
                ", $user_id));
                break;
            case 'item_count':
                $current_value = get_user_meta($user_id, 'items_posted_count', true) ?: 0;
                break;
            case 'points':
                $current_value = get_user_meta($user_id, 'environmental_points', true) ?: 0;
                break;
            case 'carbon_saved':
                global $wpdb;
                $table_actions = $wpdb->prefix . 'environmental_user_actions';
                $current_value = $wpdb->get_var($wpdb->prepare("
                    SELECT SUM(impact_value) FROM {$table_actions}
                    WHERE user_id = %d AND impact_unit = 'kg_co2'
                ", $user_id)) ?: 0;
                break;
        }
        
        $progress_percentage = min(100, ($current_value / $criteria['value']) * 100);
        
        return array(
            'current' => (float) $current_value,
            'target' => $criteria['value'],
            'percentage' => round($progress_percentage, 2),
        );
    }
    
    /**
     * Get tips database
     */
    private function get_tips_database() {
        return array(
            array(
                'id' => 1,
                'title' => 'Use LED Bulbs',
                'description' => 'Replace incandescent bulbs with LED bulbs to save up to 80% energy.',
                'category' => 'energy',
                'impact' => 'Saves 50kg CO2/year per bulb',
                'difficulty' => 'easy',
            ),
            array(
                'id' => 2,
                'title' => 'Unplug Electronics',
                'description' => 'Unplug electronics when not in use to prevent phantom energy consumption.',
                'category' => 'energy',
                'impact' => 'Saves 10% on electricity bill',
                'difficulty' => 'easy',
            ),
            array(
                'id' => 3,
                'title' => 'Use Public Transport',
                'description' => 'Take public transport instead of driving to reduce carbon emissions.',
                'category' => 'transport',
                'impact' => 'Saves 2.3kg CO2 per trip',
                'difficulty' => 'medium',
            ),
            array(
                'id' => 4,
                'title' => 'Reduce Meat Consumption',
                'description' => 'Eat less meat and more plant-based foods to reduce your carbon footprint.',
                'category' => 'food',
                'impact' => 'Saves 300kg CO2/year',
                'difficulty' => 'medium',
            ),
            array(
                'id' => 5,
                'title' => 'Fix Water Leaks',
                'description' => 'Fix leaky faucets and pipes to save water and money.',
                'category' => 'water',
                'impact' => 'Saves 1000L water/month',
                'difficulty' => 'medium',
            ),
        );
    }
    
    /**
     * Calculate action points
     */
    private function calculate_action_points($action_type, $impact_value) {
        $base_points = array(
            'recycle' => 5,
            'reduce_energy' => 10,
            'use_public_transport' => 8,
            'plant_tree' => 20,
            'reduce_water' => 6,
            'other' => 3,
        );
        
        $points = $base_points[$action_type] ?? 3;
        
        // Bonus points for higher impact
        if ($impact_value > 10) {
            $points += 5;
        } elseif ($impact_value > 5) {
            $points += 2;
        }
        
        return $points;
    }
    
    /**
     * Check for new achievements
     */
    private function check_for_achievements($user_id) {
        $achievements = $this->get_available_achievements();
        $earned_badges = get_user_meta($user_id, 'environmental_badges', true) ?: array();
        $new_achievements = array();
        
        foreach ($achievements as $achievement) {
            if (!in_array($achievement['id'], $earned_badges)) {
                $progress = $this->calculate_achievement_progress($user_id, $achievement);
                
                if ($progress['percentage'] >= 100) {
                    $earned_badges[] = $achievement['id'];
                    $new_achievements[] = $achievement;
                    
                    // Award achievement points
                    $current_points = (int) get_user_meta($user_id, 'environmental_points', true);
                    update_user_meta($user_id, 'environmental_points', $current_points + $achievement['points']);
                }
            }
        }
        
        if (!empty($new_achievements)) {
            update_user_meta($user_id, 'environmental_badges', $earned_badges);
        }
        
        return $new_achievements;
    }
    
    /**
     * Calculate transport emissions
     */
    private function calculate_transport_emissions($transport) {
        $emissions = 0;
        
        // Car emissions (kg CO2 per km)
        if (isset($transport['car_km'])) {
            $emissions += $transport['car_km'] * 0.21; // Average car emission
        }
        
        // Flight emissions (kg CO2 per km)
        if (isset($transport['flight_km'])) {
            $emissions += $transport['flight_km'] * 0.285; // Average flight emission
        }
        
        // Public transport is much lower
        if (isset($transport['public_transport_km'])) {
            $emissions += $transport['public_transport_km'] * 0.04;
        }
        
        return $emissions * 365; // Annual emissions
    }
    
    /**
     * Calculate energy emissions
     */
    private function calculate_energy_emissions($energy) {
        $emissions = 0;
        
        // Electricity (kg CO2 per kWh)
        if (isset($energy['electricity_kwh'])) {
            $emissions += $energy['electricity_kwh'] * 0.5; // Average grid emission factor
        }
        
        // Gas (kg CO2 per m3)
        if (isset($energy['gas_m3'])) {
            $emissions += $energy['gas_m3'] * 2.2;
        }
        
        return $emissions * 12; // Annual emissions
    }
    
    /**
     * Calculate consumption emissions
     */
    private function calculate_consumption_emissions($consumption) {
        $emissions = 0;
        
        // Meat consumption (kg CO2 per kg)
        if (isset($consumption['meat_kg'])) {
            $emissions += $consumption['meat_kg'] * 27;
        }
        
        // Dairy consumption (kg CO2 per kg)
        if (isset($consumption['dairy_kg'])) {
            $emissions += $consumption['dairy_kg'] * 3.2;
        }
        
        return $emissions * 52; // Annual emissions (weekly data)
    }
    
    /**
     * Get carbon reduction recommendations
     */
    private function get_carbon_reduction_recommendations($carbon_footprint) {
        $recommendations = array();
        
        // Find highest emission source and recommend accordingly
        $highest = array_keys($carbon_footprint, max($carbon_footprint))[0];
        
        switch ($highest) {
            case 'transport':
                $recommendations[] = 'Consider using public transport or cycling more often';
                $recommendations[] = 'Work from home when possible to reduce commuting';
                $recommendations[] = 'Choose direct flights and economy class for air travel';
                break;
            case 'energy':
                $recommendations[] = 'Switch to renewable energy sources';
                $recommendations[] = 'Improve home insulation to reduce heating/cooling needs';
                $recommendations[] = 'Use energy-efficient appliances';
                break;
            case 'consumption':
                $recommendations[] = 'Reduce meat consumption and eat more plant-based meals';
                $recommendations[] = 'Buy local and seasonal products';
                $recommendations[] = 'Reduce food waste';
                break;
        }
        
        return $recommendations;
    }
    
    /**
     * Get points leaderboard
     */
    private function get_points_leaderboard($limit, $period) {
        global $wpdb;
        
        $users = $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.ID as user_id,
                u.display_name,
                CAST(um.meta_value AS UNSIGNED) as points
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = 'environmental_points'
            ORDER BY points DESC
            LIMIT %d
        ", $limit));
        
        $leaderboard = array();
        $rank = 1;
        
        foreach ($users as $user) {
            $leaderboard[] = array(
                'rank' => $rank++,
                'user_id' => $user->user_id,
                'display_name' => $user->display_name,
                'avatar' => get_avatar_url($user->user_id),
                'points' => (int) $user->points,
            );
        }
        
        return $leaderboard;
    }
    
    /**
     * Get impact leaderboard
     */
    private function get_impact_leaderboard($limit, $period) {
        global $wpdb;
        
        $users = $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.ID as user_id,
                u.display_name,
                CAST(um.meta_value AS UNSIGNED) as impact_score
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = 'environmental_impact_score'
            ORDER BY impact_score DESC
            LIMIT %d
        ", $limit));
        
        $leaderboard = array();
        $rank = 1;
        
        foreach ($users as $user) {
            $leaderboard[] = array(
                'rank' => $rank++,
                'user_id' => $user->user_id,
                'display_name' => $user->display_name,
                'avatar' => get_avatar_url($user->user_id),
                'impact_score' => (int) $user->impact_score,
            );
        }
        
        return $leaderboard;
    }
    
    /**
     * Get petitions leaderboard
     */
    private function get_petitions_leaderboard($limit, $period) {
        global $wpdb;
        
        $users = $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.ID as user_id,
                u.display_name,
                COUNT(ps.id) as petition_count
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->prefix}environmental_petition_signatures ps ON u.ID = ps.user_id
            GROUP BY u.ID
            ORDER BY petition_count DESC
            LIMIT %d
        ", $limit));
        
        $leaderboard = array();
        $rank = 1;
        
        foreach ($users as $user) {
            $leaderboard[] = array(
                'rank' => $rank++,
                'user_id' => $user->user_id,
                'display_name' => $user->display_name,
                'avatar' => get_avatar_url($user->user_id),
                'petition_count' => (int) $user->petition_count,
            );
        }
        
        return $leaderboard;
    }
    
    /**
     * Get items leaderboard
     */
    private function get_items_leaderboard($limit, $period) {
        global $wpdb;
        
        $users = $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.ID as user_id,
                u.display_name,
                COUNT(p.ID) as item_count
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->posts} p ON u.ID = p.post_author
            WHERE p.post_type = 'environmental_item' AND p.post_status = 'publish'
            GROUP BY u.ID
            ORDER BY item_count DESC
            LIMIT %d
        ", $limit));
        
        $leaderboard = array();
        $rank = 1;
        
        foreach ($users as $user) {
            $leaderboard[] = array(
                'rank' => $rank++,
                'user_id' => $user->user_id,
                'display_name' => $user->display_name,
                'avatar' => get_avatar_url($user->user_id),
                'item_count' => (int) $user->item_count,
            );
        }
        
        return $leaderboard;
    }
    
    /**
     * Get user rank
     */
    private function get_user_rank($type, $period) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return null;
        }
        
        global $wpdb;
        
        switch ($type) {
            case 'points':
                $user_points = (int) get_user_meta($user_id, 'environmental_points', true);
                $higher_count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->usermeta} 
                    WHERE meta_key = 'environmental_points' AND CAST(meta_value AS UNSIGNED) > %d
                ", $user_points));
                return $higher_count + 1;
                
            case 'impact':
                $user_impact = (int) get_user_meta($user_id, 'environmental_impact_score', true);
                $higher_count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->usermeta} 
                    WHERE meta_key = 'environmental_impact_score' AND CAST(meta_value AS UNSIGNED) > %d
                ", $user_impact));
                return $higher_count + 1;
                
            default:
                return null;
        }
    }
    
    /**
     * Get badge earned date
     */
    private function get_badge_earned_date($user_id, $badge_id) {
        // This would need to be implemented with a proper badge history table
        // For now, return a placeholder
        return get_user_meta($user_id, "badge_{$badge_id}_earned_date", true) ?: current_time('mysql');
    }
    
    /**
     * Notify admins of report
     */
    private function notify_admins_of_report($report_id, $issue_type, $severity) {
        // Get admin emails
        $admins = get_users(array('role' => 'administrator'));
        
        $subject = "New Environmental Issue Report - {$issue_type} ({$severity})";
        $message = "A new environmental issue has been reported.\n\n";
        $message .= "Report ID: {$report_id}\n";
        $message .= "Issue Type: {$issue_type}\n";
        $message .= "Severity: {$severity}\n\n";
        $message .= "Please review the report in the admin dashboard.";
        
        foreach ($admins as $admin) {
            wp_mail($admin->user_email, $subject, $message);
        }
    }
}

// Initialize the environmental data endpoints
new Environmental_Mobile_API_Environmental_Data_Endpoints();
