<?php
/**
 * ACF Core Functionality
 * Environmental Platform ACF Pro Implementation
 */

if (!defined('ABSPATH')) {
    exit;
}

// Core ACF functions for environmental platform
function acf_get_field($selector, $post_id = false) {
    // Get field value implementation
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    return get_post_meta($post_id, $selector, true);
}

function acf_update_field($selector, $value, $post_id = false) {
    // Update field value implementation
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    return update_post_meta($post_id, $selector, $value);
}

function acf_the_field($selector, $post_id = false) {
    // Display field value
    echo acf_get_field($selector, $post_id);
}

// Field group registration
function acf_add_local_field_group($field_group) {
    // Register field group locally
    global $acf_local_field_groups;
    
    if (!isset($acf_local_field_groups)) {
        $acf_local_field_groups = array();
    }
    
    $acf_local_field_groups[$field_group['key']] = $field_group;
}

// Initialize ACF for environmental platform
add_action('init', function() {
    // Load field groups for environmental platform
    do_action('acf/init');
});

// Add ACF admin menu
add_action('admin_menu', function() {
    add_menu_page(
        'Custom Fields',
        'Custom Fields',
        'manage_options',
        'edit.php?post_type=acf-field-group',
        '',
        'dashicons-admin-generic',
        79
    );
});
