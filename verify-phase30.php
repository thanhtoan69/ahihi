<?php
/**
 * Phase 30 Verification Script
 * Advanced Custom Fields (ACF) Setup Verification
 * 
 * This script verifies that Phase 30 has been completed successfully:
 * - ACF Pro plugin is installed and active
 * - All custom field groups are registered
 * - Field groups are properly configured with conditional logic
 * - Export/Import functionality is working
 * - Integration with custom post types is complete
 * 
 * @package Environmental_Platform_Core
 * @version 1.0.0 - Phase 30
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    require_once dirname(dirname(__FILE__)) . '/wp-config.php';
}

class Phase30Verification {
    
    private $results = array();
    private $errors = array();
    private $warnings = array();
    
    public function __construct() {
        $this->run_verification();
        $this->display_results();
    }
    
    /**
     * Run all verification tests
     */
    private function run_verification() {
        echo "<h1>Phase 30 Verification - Advanced Custom Fields (ACF) Setup</h1>\n";
        echo "<hr>\n";
        
        // Test 1: ACF Plugin Installation
        $this->verify_acf_plugin();
        
        // Test 2: ACF Field Groups Registration
        $this->verify_field_groups();
        
        // Test 3: Field Groups Structure
        $this->verify_field_groups_structure();
        
        // Test 4: Conditional Logic
        $this->verify_conditional_logic();
        
        // Test 5: Custom Post Types Integration
        $this->verify_post_types_integration();
        
        // Test 6: Export/Import Functionality
        $this->verify_export_import();
        
        // Test 7: ACF Assets
        $this->verify_acf_assets();
        
        // Test 8: Environmental Data Processing
        $this->verify_environmental_data_processing();
        
        // Test 9: Global Environmental Fields
        $this->verify_global_environmental_fields();
        
        // Test 10: Item Exchange Fields
        $this->verify_item_exchange_fields();
    }
    
    /**
     * Verify ACF Plugin Installation
     */
    private function verify_acf_plugin() {
        echo "<h2>1. ACF Plugin Installation</h2>\n";
        
        // Check if ACF functions are available
        if (function_exists('acf_get_field_groups')) {
            $this->add_result('✓ ACF functions are available', 'success');
            
            // Check ACF version
            if (defined('ACF_VERSION')) {
                $this->add_result('✓ ACF Version: ' . ACF_VERSION, 'info');
            }
            
            // Check if it's ACF Pro
            if (function_exists('acf_pro_get_license')) {
                $this->add_result('✓ ACF Pro is active', 'success');
            } else {
                $this->add_result('⚠ ACF Pro features may not be available', 'warning');
            }
        } else {
            $this->add_result('✗ ACF plugin is not active or installed', 'error');
            return false;
        }
        
        // Check ACF plugin file
        $acf_plugin_file = WP_CONTENT_DIR . '/plugins/advanced-custom-fields-pro/acf.php';
        if (file_exists($acf_plugin_file)) {
            $this->add_result('✓ ACF Pro plugin file exists', 'success');
        } else {
            $this->add_result('⚠ ACF Pro plugin file not found at expected location', 'warning');
        }
        
        return true;
    }
    
    /**
     * Verify Field Groups Registration
     */
    private function verify_field_groups() {
        echo "<h2>2. Field Groups Registration</h2>\n";
        
        if (!function_exists('acf_get_field_groups')) {
            $this->add_result('✗ Cannot verify field groups - ACF not available', 'error');
            return false;
        }
        
        $field_groups = acf_get_field_groups();
        $expected_groups = array(
            'group_environmental_article',
            'group_environmental_report', 
            'group_environmental_alert',
            'group_environmental_event',
            'group_environmental_project',
            'group_eco_product',
            'group_community_post',
            'group_educational_resource',
            'group_waste_classification',
            'group_petition',
            'group_item_exchange',
            'group_global_environmental'
        );
        
        $found_groups = array();
        foreach ($field_groups as $group) {
            if (in_array($group['key'], $expected_groups)) {
                $found_groups[] = $group['key'];
                $this->add_result('✓ Found field group: ' . $group['title'] . ' (' . $group['key'] . ')', 'success');
            }
        }
        
        $missing_groups = array_diff($expected_groups, $found_groups);
        if (!empty($missing_groups)) {
            foreach ($missing_groups as $missing) {
                $this->add_result('✗ Missing field group: ' . $missing, 'error');
            }
        }
        
        $this->add_result('Found ' . count($found_groups) . ' of ' . count($expected_groups) . ' expected field groups', 'info');
        
        return count($missing_groups) === 0;
    }
    
    /**
     * Verify Field Groups Structure
     */
    private function verify_field_groups_structure() {
        echo "<h2>3. Field Groups Structure</h2>\n";
        
        $field_groups = acf_get_field_groups();
        $structure_tests = array();
        
        foreach ($field_groups as $group) {
            if (strpos($group['key'], 'group_') === 0) {
                $fields = acf_get_fields($group['key']);
                
                if ($fields && count($fields) > 0) {
                    $structure_tests[] = array(
                        'group' => $group['title'],
                        'key' => $group['key'],
                        'field_count' => count($fields),
                        'has_required_fields' => $this->check_required_fields($fields),
                        'has_conditional_logic' => $this->check_conditional_logic($fields)
                    );
                    
                    $this->add_result('✓ ' . $group['title'] . ': ' . count($fields) . ' fields', 'success');
                } else {
                    $this->add_result('⚠ ' . $group['title'] . ': No fields found', 'warning');
                }
            }
        }
        
        return !empty($structure_tests);
    }
    
    /**
     * Verify Conditional Logic
     */
    private function verify_conditional_logic() {
        echo "<h2>4. Conditional Logic</h2>\n";
        
        $field_groups = acf_get_field_groups();
        $conditional_logic_found = false;
        
        foreach ($field_groups as $group) {
            if (strpos($group['key'], 'group_') === 0) {
                $fields = acf_get_fields($group['key']);
                
                if ($fields) {
                    foreach ($fields as $field) {
                        if (isset($field['conditional_logic']) && !empty($field['conditional_logic'])) {
                            $conditional_logic_found = true;
                            $this->add_result('✓ Conditional logic found in ' . $group['title'], 'success');
                            break;
                        }
                        
                        // Check sub-fields for conditional logic
                        if (isset($field['sub_fields']) && !empty($field['sub_fields'])) {
                            foreach ($field['sub_fields'] as $sub_field) {
                                if (isset($sub_field['conditional_logic']) && !empty($sub_field['conditional_logic'])) {
                                    $conditional_logic_found = true;
                                    $this->add_result('✓ Conditional logic found in sub-field of ' . $group['title'], 'success');
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        if (!$conditional_logic_found) {
            $this->add_result('⚠ No conditional logic found in field groups', 'warning');
        }
        
        return $conditional_logic_found;
    }
    
    /**
     * Verify Custom Post Types Integration
     */
    private function verify_post_types_integration() {
        echo "<h2>5. Custom Post Types Integration</h2>\n";
        
        $custom_post_types = array(
            'env_article',
            'env_report',
            'env_alert', 
            'env_event',
            'env_project',
            'eco_product',
            'community_post',
            'edu_resource',
            'waste_class',
            'env_petition',
            'item_exchange'
        );
        
        $field_groups = acf_get_field_groups();
        $integrated_post_types = array();
        
        foreach ($field_groups as $group) {
            if (isset($group['location']) && !empty($group['location'])) {
                foreach ($group['location'] as $location_group) {
                    foreach ($location_group as $location_rule) {
                        if ($location_rule['param'] === 'post_type' && in_array($location_rule['value'], $custom_post_types)) {
                            $integrated_post_types[] = $location_rule['value'];
                            $this->add_result('✓ Field group integrated with post type: ' . $location_rule['value'], 'success');
                        }
                    }
                }
            }
        }
        
        $integrated_post_types = array_unique($integrated_post_types);
        $missing_integrations = array_diff($custom_post_types, $integrated_post_types);
        
        if (!empty($missing_integrations)) {
            foreach ($missing_integrations as $missing) {
                $this->add_result('⚠ No field group integration found for: ' . $missing, 'warning');
            }
        }
        
        $this->add_result('Integrated with ' . count($integrated_post_types) . ' of ' . count($custom_post_types) . ' custom post types', 'info');
        
        return count($missing_integrations) === 0;
    }
    
    /**
     * Verify Export/Import Functionality
     */
    private function verify_export_import() {
        echo "<h2>6. Export/Import Functionality</h2>\n";
        
        // Check if export/import class exists
        if (class_exists('EP_ACF_Export_Import')) {
            $this->add_result('✓ Export/Import class is available', 'success');
        } else {
            $this->add_result('✗ Export/Import class not found', 'error');
            return false;
        }
        
        // Check if admin menu is registered
        global $submenu;
        $menu_found = false;
        if (isset($submenu['edit.php?post_type=env_article'])) {
            foreach ($submenu['edit.php?post_type=env_article'] as $item) {
                if (isset($item[2]) && $item[2] === 'ep-acf-export-import') {
                    $menu_found = true;
                    break;
                }
            }
        }
        
        if ($menu_found) {
            $this->add_result('✓ Export/Import admin menu is registered', 'success');
        } else {
            $this->add_result('⚠ Export/Import admin menu not found', 'warning');
        }
        
        // Check if export/import assets exist
        $js_file = EP_CORE_PLUGIN_DIR . 'assets/acf-export-import.js';
        if (file_exists($js_file)) {
            $this->add_result('✓ Export/Import JavaScript file exists', 'success');
        } else {
            $this->add_result('✗ Export/Import JavaScript file missing', 'error');
        }
        
        return true;
    }
    
    /**
     * Verify ACF Assets
     */
    private function verify_acf_assets() {
        echo "<h2>7. ACF Assets</h2>\n";
        
        // Check JavaScript file
        $js_file = EP_CORE_PLUGIN_DIR . 'assets/acf-admin.js';
        if (file_exists($js_file)) {
            $this->add_result('✓ ACF admin JavaScript file exists', 'success');
            
            // Check file size
            $size = filesize($js_file);
            if ($size > 1000) {
                $this->add_result('✓ JavaScript file has content (' . round($size/1024, 1) . 'KB)', 'success');
            } else {
                $this->add_result('⚠ JavaScript file seems too small', 'warning');
            }
        } else {
            $this->add_result('✗ ACF admin JavaScript file missing', 'error');
        }
        
        // Check CSS file
        $css_file = EP_CORE_PLUGIN_DIR . 'assets/acf-admin.css';
        if (file_exists($css_file)) {
            $this->add_result('✓ ACF admin CSS file exists', 'success');
            
            // Check file size
            $size = filesize($css_file);
            if ($size > 1000) {
                $this->add_result('✓ CSS file has content (' . round($size/1024, 1) . 'KB)', 'success');
            } else {
                $this->add_result('⚠ CSS file seems too small', 'warning');
            }
        } else {
            $this->add_result('✗ ACF admin CSS file missing', 'error');
        }
        
        return true;
    }
    
    /**
     * Verify Environmental Data Processing
     */
    private function verify_environmental_data_processing() {
        echo "<h2>8. Environmental Data Processing</h2>\n";
        
        // Check if EP_ACF_Field_Groups class exists
        if (class_exists('EP_ACF_Field_Groups')) {
            $this->add_result('✓ ACF Field Groups class is available', 'success');
            
            // Check if save_environmental_data method exists
            if (method_exists('EP_ACF_Field_Groups', 'save_environmental_data')) {
                $this->add_result('✓ Environmental data save method exists', 'success');
            } else {
                $this->add_result('✗ Environmental data save method missing', 'error');
            }
            
            // Check if export functionality exists
            if (method_exists('EP_ACF_Field_Groups', 'export_field_groups_to_php')) {
                $this->add_result('✓ Export functionality exists', 'success');
            } else {
                $this->add_result('✗ Export functionality missing', 'error');
            }
        } else {
            $this->add_result('✗ ACF Field Groups class not found', 'error');
            return false;
        }
        
        return true;
    }
    
    /**
     * Verify Global Environmental Fields
     */
    private function verify_global_environmental_fields() {
        echo "<h2>9. Global Environmental Fields</h2>\n";
        
        $global_group = null;
        $field_groups = acf_get_field_groups();
        
        foreach ($field_groups as $group) {
            if ($group['key'] === 'group_global_environmental') {
                $global_group = $group;
                break;
            }
        }
        
        if ($global_group) {
            $this->add_result('✓ Global Environmental field group found', 'success');
            
            $fields = acf_get_fields('group_global_environmental');
            if ($fields && count($fields) > 0) {
                $this->add_result('✓ Global Environmental fields: ' . count($fields) . ' fields', 'success');
                
                // Check for specific global fields
                $expected_fields = array('sustainability_metrics', 'environmental_certifications', 'un_sdg_alignment', 'environmental_tags');
                $found_fields = array();
                
                foreach ($fields as $field) {
                    if (in_array($field['name'], $expected_fields)) {
                        $found_fields[] = $field['name'];
                    }
                }
                
                $this->add_result('✓ Found ' . count($found_fields) . ' of ' . count($expected_fields) . ' expected global fields', 'info');
            } else {
                $this->add_result('⚠ Global Environmental field group has no fields', 'warning');
            }
        } else {
            $this->add_result('✗ Global Environmental field group not found', 'error');
        }
        
        return $global_group !== null;
    }
    
    /**
     * Verify Item Exchange Fields
     */
    private function verify_item_exchange_fields() {
        echo "<h2>10. Item Exchange Fields</h2>\n";
        
        $exchange_group = null;
        $field_groups = acf_get_field_groups();
        
        foreach ($field_groups as $group) {
            if ($group['key'] === 'group_item_exchange') {
                $exchange_group = $group;
                break;
            }
        }
        
        if ($exchange_group) {
            $this->add_result('✓ Item Exchange field group found', 'success');
            
            $fields = acf_get_fields('group_item_exchange');
            if ($fields && count($fields) > 0) {
                $this->add_result('✓ Item Exchange fields: ' . count($fields) . ' fields', 'success');
                
                // Check for specific exchange fields
                $expected_fields = array('exchange_item_details', 'exchange_details', 'pickup_details', 'environmental_impact_exchange');
                $found_fields = array();
                
                foreach ($fields as $field) {
                    if (in_array($field['name'], $expected_fields)) {
                        $found_fields[] = $field['name'];
                    }
                }
                
                $this->add_result('✓ Found ' . count($found_fields) . ' of ' . count($expected_fields) . ' expected exchange fields', 'info');
            } else {
                $this->add_result('⚠ Item Exchange field group has no fields', 'warning');
            }
        } else {
            $this->add_result('✗ Item Exchange field group not found', 'error');
        }
        
        return $exchange_group !== null;
    }
    
    /**
     * Helper method to check required fields
     */
    private function check_required_fields($fields) {
        foreach ($fields as $field) {
            if (isset($field['required']) && $field['required']) {
                return true;
            }
            if (isset($field['sub_fields']) && !empty($field['sub_fields'])) {
                if ($this->check_required_fields($field['sub_fields'])) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Helper method to check conditional logic
     */
    private function check_conditional_logic($fields) {
        foreach ($fields as $field) {
            if (isset($field['conditional_logic']) && !empty($field['conditional_logic'])) {
                return true;
            }
            if (isset($field['sub_fields']) && !empty($field['sub_fields'])) {
                if ($this->check_conditional_logic($field['sub_fields'])) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Add result to results array
     */
    private function add_result($message, $type = 'info') {
        $this->results[] = array(
            'message' => $message,
            'type' => $type,
            'timestamp' => date('Y-m-d H:i:s')
        );
        
        // Also echo immediately for real-time feedback
        $color = '';
        switch ($type) {
            case 'success':
                $color = 'green';
                break;
            case 'error':
                $color = 'red';
                $this->errors[] = $message;
                break;
            case 'warning':
                $color = 'orange';
                $this->warnings[] = $message;
                break;
            default:
                $color = 'blue';
        }
        
        echo "<p style='color: {$color}; margin: 5px 0;'>{$message}</p>\n";
    }
    
    /**
     * Display final results summary
     */
    private function display_results() {
        echo "<hr>\n";
        echo "<h2>Phase 30 Verification Summary</h2>\n";
        
        $total_tests = count($this->results);
        $errors_count = count($this->errors);
        $warnings_count = count($this->warnings);
        $success_count = $total_tests - $errors_count - $warnings_count;
        
        echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 5px; margin: 20px 0;'>\n";
        echo "<h3>Summary Statistics</h3>\n";
        echo "<p><strong>Total Tests:</strong> {$total_tests}</p>\n";
        echo "<p><strong style='color: green;'>Successful:</strong> {$success_count}</p>\n";
        echo "<p><strong style='color: orange;'>Warnings:</strong> {$warnings_count}</p>\n";
        echo "<p><strong style='color: red;'>Errors:</strong> {$errors_count}</p>\n";
        echo "</div>\n";
        
        if ($errors_count === 0) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
            echo "<h3>🎉 Phase 30 Verification PASSED!</h3>\n";
            echo "<p>All critical components of the Advanced Custom Fields (ACF) Setup have been successfully implemented and verified.</p>\n";
            echo "</div>\n";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
            echo "<h3>❌ Phase 30 Verification FAILED</h3>\n";
            echo "<p>There are {$errors_count} critical errors that need to be resolved:</p>\n";
            echo "<ul>\n";
            foreach ($this->errors as $error) {
                echo "<li>{$error}</li>\n";
            }
            echo "</ul>\n";
            echo "</div>\n";
        }
        
        if ($warnings_count > 0) {
            echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>\n";
            echo "<h3>⚠️ Warnings</h3>\n";
            echo "<p>There are {$warnings_count} warnings that should be reviewed:</p>\n";
            echo "<ul>\n";
            foreach ($this->warnings as $warning) {
                echo "<li>{$warning}</li>\n";
            }
            echo "</ul>\n";
            echo "</div>\n";
        }
        
        echo "<hr>\n";
        echo "<p><em>Verification completed at: " . date('Y-m-d H:i:s') . "</em></p>\n";
    }
}

// Run verification if accessed directly
if (!defined('WP_CLI') && isset($_GET['verify']) && $_GET['verify'] === 'phase30') {
    new Phase30Verification();
} else {
    echo "<p>To run Phase 30 verification, add '?verify=phase30' to the URL.</p>\n";
}
