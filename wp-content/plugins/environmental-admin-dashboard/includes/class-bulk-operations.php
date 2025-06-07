<?php
/**
 * Bulk Operations Manager Class
 * 
 * Handles bulk operations for activities, goals, users, and content
 * 
 * @package EnvironmentalAdminDashboard
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Bulk_Operations {
    
    private static $instance = null;
    
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
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_env_bulk_operation', array($this, 'ajax_bulk_operation'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'env-admin-dashboard',
            __('Bulk Operations', 'env-admin-dashboard'),
            __('Bulk Operations', 'env-admin-dashboard'),
            'manage_options',
            'env-bulk-operations',
            array($this, 'render_bulk_operations_page')
        );
    }
    
    /**
     * Render bulk operations page
     */
    public function render_bulk_operations_page() {
        // Include the bulk operations template
        $template_path = ENV_ADMIN_DASHBOARD_PLUGIN_PATH . 'admin/bulk-operations.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="wrap"><h1>' . __('Bulk Operations', 'env-admin-dashboard') . '</h1>';
            echo '<p>' . __('Bulk operations interface not found.', 'env-admin-dashboard') . '</p></div>';
        }
    }
    
    /**
     * Perform bulk operation
     */
    public function perform_bulk_operation($operation, $items) {
        if (empty($items) || !is_array($items)) {
            return array(
                'success' => false,
                'message' => __('No items selected for bulk operation', 'env-admin-dashboard')
            );
        }
        
        $processed = 0;
        $errors = array();
        
        switch ($operation) {
            case 'activate':
                $processed = $this->bulk_activate($items);
                break;
            case 'deactivate':
                $processed = $this->bulk_deactivate($items);
                break;
            case 'delete':
                $processed = $this->bulk_delete($items);
                break;
            case 'reset':
                $processed = $this->bulk_reset($items);
                break;
            case 'export':
                return $this->bulk_export($items);
            default:
                return array(
                    'success' => false,
                    'message' => __('Invalid bulk operation', 'env-admin-dashboard')
                );
        }
        
        if ($processed > 0) {
            return array(
                'success' => true,
                'message' => sprintf(
                    __('Successfully processed %d items', 'env-admin-dashboard'),
                    $processed
                ),
                'processed' => $processed
            );
        } else {
            return array(
                'success' => false,
                'message' => __('No items were processed', 'env-admin-dashboard')
            );
        }
    }
    
    /**
     * Bulk activate items
     */
    private function bulk_activate($items) {
        global $wpdb;
        $processed = 0;
        
        foreach ($items as $item_id) {
            $item_id = intval($item_id);
            if ($item_id <= 0) continue;
            
            // Try to activate different types of items
            $result = false;
            
            // Check if it's a post
            if (get_post($item_id)) {
                $result = wp_update_post(array(
                    'ID' => $item_id,
                    'post_status' => 'publish'
                ));
            } else {
                // Check for custom environmental items
                $result = $wpdb->update(
                    $wpdb->prefix . 'environmental_activities',
                    array('status' => 'active'),
                    array('id' => $item_id),
                    array('%s'),
                    array('%d')
                );
                
                if ($result === false) {
                    $result = $wpdb->update(
                        $wpdb->prefix . 'environmental_goals',
                        array('status' => 'active'),
                        array('id' => $item_id),
                        array('%s'),
                        array('%d')
                    );
                }
            }
            
            if ($result !== false) {
                $processed++;
            }
        }
        
        return $processed;
    }
    
    /**
     * Bulk deactivate items
     */
    private function bulk_deactivate($items) {
        global $wpdb;
        $processed = 0;
        
        foreach ($items as $item_id) {
            $item_id = intval($item_id);
            if ($item_id <= 0) continue;
            
            $result = false;
            
            // Check if it's a post
            if (get_post($item_id)) {
                $result = wp_update_post(array(
                    'ID' => $item_id,
                    'post_status' => 'draft'
                ));
            } else {
                // Check for custom environmental items
                $result = $wpdb->update(
                    $wpdb->prefix . 'environmental_activities',
                    array('status' => 'inactive'),
                    array('id' => $item_id),
                    array('%s'),
                    array('%d')
                );
                
                if ($result === false) {
                    $result = $wpdb->update(
                        $wpdb->prefix . 'environmental_goals',
                        array('status' => 'inactive'),
                        array('id' => $item_id),
                        array('%s'),
                        array('%d')
                    );
                }
            }
            
            if ($result !== false) {
                $processed++;
            }
        }
        
        return $processed;
    }
    
    /**
     * Bulk delete items
     */
    private function bulk_delete($items) {
        global $wpdb;
        $processed = 0;
        
        foreach ($items as $item_id) {
            $item_id = intval($item_id);
            if ($item_id <= 0) continue;
            
            $result = false;
            
            // Check if it's a post
            if (get_post($item_id)) {
                $result = wp_delete_post($item_id, true);
            } else {
                // Check for custom environmental items
                $result = $wpdb->delete(
                    $wpdb->prefix . 'environmental_activities',
                    array('id' => $item_id),
                    array('%d')
                );
                
                if ($result === false || $result === 0) {
                    $result = $wpdb->delete(
                        $wpdb->prefix . 'environmental_goals',
                        array('id' => $item_id),
                        array('%d')
                    );
                }
                
                if ($result === false || $result === 0) {
                    $result = $wpdb->delete(
                        $wpdb->prefix . 'users',
                        array('ID' => $item_id),
                        array('%d')
                    );
                }
            }
            
            if ($result !== false && $result !== 0) {
                $processed++;
            }
        }
        
        return $processed;
    }
    
    /**
     * Bulk reset items
     */
    private function bulk_reset($items) {
        global $wpdb;
        $processed = 0;
        
        foreach ($items as $item_id) {
            $item_id = intval($item_id);
            if ($item_id <= 0) continue;
            
            $result = false;
            
            // Reset progress/scores for activities
            $result = $wpdb->update(
                $wpdb->prefix . 'environmental_user_progress',
                array(
                    'progress' => 0,
                    'score' => 0,
                    'completion_date' => null
                ),
                array('activity_id' => $item_id),
                array('%d', '%d', '%s'),
                array('%d')
            );
            
            // Reset user levels if it's a user
            if ($result === false || $result === 0) {
                $result = $wpdb->update(
                    $wpdb->prefix . 'users',
                    array('user_level' => 1),
                    array('ID' => $item_id),
                    array('%d'),
                    array('%d')
                );
            }
            
            if ($result !== false) {
                $processed++;
            }
        }
        
        return $processed;
    }
    
    /**
     * Bulk export items
     */
    private function bulk_export($items) {
        global $wpdb;
        
        $export_data = array();
        
        foreach ($items as $item_id) {
            $item_id = intval($item_id);
            if ($item_id <= 0) continue;
            
            // Try to get data from different tables
            $item_data = null;
            
            // Check posts
            $post = get_post($item_id);
            if ($post) {
                $item_data = array(
                    'type' => 'post',
                    'id' => $post->ID,
                    'title' => $post->post_title,
                    'content' => $post->post_content,
                    'status' => $post->post_status,
                    'date' => $post->post_date
                );
            }
            
            // Check environmental tables
            if (!$item_data) {
                $activity = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}environmental_activities WHERE id = %d",
                    $item_id
                ), ARRAY_A);
                
                if ($activity) {
                    $item_data = array_merge(array('type' => 'activity'), $activity);
                }
            }
            
            if (!$item_data) {
                $goal = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}environmental_goals WHERE id = %d",
                    $item_id
                ), ARRAY_A);
                
                if ($goal) {
                    $item_data = array_merge(array('type' => 'goal'), $goal);
                }
            }
            
            if (!$item_data) {
                $user = get_user_by('ID', $item_id);
                if ($user) {
                    $item_data = array(
                        'type' => 'user',
                        'id' => $user->ID,
                        'username' => $user->user_login,
                        'email' => $user->user_email,
                        'display_name' => $user->display_name,
                        'registered' => $user->user_registered
                    );
                }
            }
            
            if ($item_data) {
                $export_data[] = $item_data;
            }
        }
        
        if (empty($export_data)) {
            return array(
                'success' => false,
                'message' => __('No data found for export', 'env-admin-dashboard')
            );
        }
        
        // Generate CSV content
        $csv_data = $this->generate_csv($export_data);
        
        return array(
            'success' => true,
            'message' => sprintf(__('Exported %d items', 'env-admin-dashboard'), count($export_data)),
            'export_data' => $csv_data,
            'filename' => 'environmental-bulk-export-' . date('Y-m-d-H-i-s') . '.csv'
        );
    }
    
    /**
     * Generate CSV from array data
     */
    private function generate_csv($data) {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Headers
        $headers = array_keys($data[0]);
        fputcsv($output, $headers);
        
        // Data rows
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);
        
        return $csv_content;
    }
    
    /**
     * AJAX handler for bulk operations
     */
    public function ajax_bulk_operation() {
        check_ajax_referer('env_dashboard_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Insufficient permissions')));
        }
        
        $operation = sanitize_text_field($_POST['operation']);
        $items = array_map('intval', $_POST['items']);
        
        $result = $this->perform_bulk_operation($operation, $items);
        
        wp_die(json_encode($result));
    }
}
