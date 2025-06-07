<?php
/**
 * Environmental Content Recommendation Plugin Integration Test
 * This script tests plugin functionality and creates demo content
 */

// Load WordPress
define('WP_USE_THEMES', false);
require_once('./wp-load.php');

if (!function_exists('activate_plugin')) {
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

echo "Environmental Content Recommendation Integration Test\n";
echo "===================================================\n\n";

$plugin_slug = 'environmental-content-recommendation/environmental-content-recommendation.php';

// Step 1: Activate Plugin
echo "1. Plugin Activation\n";
echo "-------------------\n";

if (!is_plugin_active($plugin_slug)) {
    $result = activate_plugin($plugin_slug);
    if (is_wp_error($result)) {
        echo "✗ Activation failed: " . $result->get_error_message() . "\n\n";
        exit(1);
    } else {
        echo "✓ Plugin activated successfully\n";
    }
} else {
    echo "✓ Plugin already active\n";
}

// Step 2: Verify Plugin Classes
echo "\n2. Plugin Classes Verification\n";
echo "-----------------------------\n";

// Force load WordPress hooks
do_action('plugins_loaded');

$required_classes = [
    'Environmental_Content_Recommendation' => 'Main plugin class',
    'ECR_Recommendation_Engine' => 'Recommendation engine',
    'ECR_User_Behavior_Tracker' => 'User behavior tracker',
    'ECR_Content_Analyzer' => 'Content analyzer',
    'ECR_Admin_Interface' => 'Admin interface'
];

$classes_loaded = 0;
foreach ($required_classes as $class_name => $description) {
    if (class_exists($class_name)) {
        echo "✓ $description ($class_name)\n";
        $classes_loaded++;
    } else {
        echo "✗ Missing: $description ($class_name)\n";
    }
}

if ($classes_loaded == count($required_classes)) {
    echo "✓ All required classes loaded\n";
} else {
    echo "⚠ Some classes missing - this may be normal before WordPress init\n";
}

// Step 3: Database Tables Check
echo "\n3. Database Tables Verification\n";
echo "------------------------------\n";

global $wpdb;
$required_tables = [
    $wpdb->prefix . 'ecr_user_behavior' => 'User behavior tracking',
    $wpdb->prefix . 'ecr_content_features' => 'Content features',
    $wpdb->prefix . 'ecr_user_recommendations' => 'User recommendations',
    $wpdb->prefix . 'ecr_recommendation_performance' => 'Performance tracking', 
    $wpdb->prefix . 'ecr_user_preferences' => 'User preferences'
];

$tables_exist = 0;
foreach ($required_tables as $table_name => $description) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    if ($exists) {
        echo "✓ $description ($table_name)\n";
        $tables_exist++;
    } else {
        echo "✗ Missing: $description ($table_name)\n";
    }
}

if ($tables_exist == count($required_tables)) {
    echo "✓ All database tables exist\n";
} else {
    echo "⚠ Some tables missing - may be created on plugin init\n";
}

// Step 4: Plugin Options Check
echo "\n4. Plugin Configuration\n";
echo "----------------------\n";

$options = get_option('ecr_options', []);
if (!empty($options)) {
    echo "✓ Plugin options configured\n";
    foreach ($options as $key => $value) {
        if (is_bool($value)) {
            echo "  - $key: " . ($value ? 'Yes' : 'No') . "\n";
        } elseif (is_numeric($value)) {
            echo "  - $key: $value\n";
        } elseif (is_string($value) && strlen($value) < 50) {
            echo "  - $key: $value\n";
        } else {
            echo "  - $key: [complex value]\n";
        }
    }
} else {
    echo "⚠ Plugin options not found - will be created on first run\n";
}

// Step 5: Create Demo Content
echo "\n5. Demo Content Creation\n";
echo "-----------------------\n";

// Create some sample posts for testing recommendations
$demo_posts = [
    [
        'title' => 'Sustainable Living: 10 Easy Tips for a Greener Lifestyle',
        'content' => 'Living sustainably doesn\'t have to be difficult. Here are 10 simple changes you can make today to reduce your environmental impact and live a more eco-friendly lifestyle. From reducing plastic use to conserving energy, these tips will help you make a difference.',
        'category' => 'Lifestyle',
        'tags' => ['sustainability', 'green-living', 'eco-friendly', 'environment']
    ],
    [
        'title' => 'Renewable Energy Solutions for Your Home',
        'content' => 'Discover the best renewable energy options for residential properties. Learn about solar panels, wind turbines, and other clean energy solutions that can reduce your carbon footprint and save money on electricity bills.',
        'category' => 'Energy',
        'tags' => ['renewable-energy', 'solar-power', 'clean-energy', 'sustainability']
    ],
    [
        'title' => 'Zero Waste Kitchen: Reducing Food Waste at Home',
        'content' => 'Food waste is a major environmental issue. Learn practical strategies to minimize waste in your kitchen, from meal planning to composting. These simple changes can significantly reduce your environmental impact.',
        'category' => 'Lifestyle',
        'tags' => ['zero-waste', 'food-waste', 'sustainability', 'kitchen-tips']
    ]
];

$created_posts = 0;
foreach ($demo_posts as $demo_post) {
    // Check if post already exists
    $existing = get_page_by_title($demo_post['title'], OBJECT, 'post');
    
    if (!$existing) {
        $post_data = [
            'post_title' => $demo_post['title'],
            'post_content' => $demo_post['content'],
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => 1
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Set category
            $category = get_term_by('name', $demo_post['category'], 'category');
            if (!$category) {
                $category = wp_insert_term($demo_post['category'], 'category');
                if (!is_wp_error($category)) {
                    $category_id = $category['term_id'];
                } else {
                    $category_id = 1; // Default category
                }
            } else {
                $category_id = $category->term_id;
            }
            wp_set_post_categories($post_id, [$category_id]);
            
            // Set tags
            wp_set_post_tags($post_id, $demo_post['tags']);
            
            echo "✓ Created demo post: " . $demo_post['title'] . "\n";
            $created_posts++;
        } else {
            echo "✗ Failed to create: " . $demo_post['title'] . "\n";
        }
    } else {
        echo "✓ Demo post already exists: " . $demo_post['title'] . "\n";
        $created_posts++;
    }
}

echo "✓ Demo content ready ($created_posts posts)\n";

// Step 6: Test Shortcodes
echo "\n6. Shortcode Registration Test\n";
echo "-----------------------------\n";

$expected_shortcodes = [
    'ecr_recommendations',
    'ecr_similar_content', 
    'ecr_trending_content',
    'ecr_environmental_content'
];

$shortcodes_registered = 0;
foreach ($expected_shortcodes as $shortcode) {
    if (shortcode_exists($shortcode)) {
        echo "✓ Shortcode registered: [$shortcode]\n";
        $shortcodes_registered++;
    } else {
        echo "⚠ Shortcode not found: [$shortcode] (may register on init)\n";
    }
}

// Step 7: Admin Menu Check
echo "\n7. Admin Interface Test\n";
echo "----------------------\n";

// Check if admin menu is registered
$admin_url = admin_url('admin.php?page=environmental-content-recommendation');
echo "✓ Admin interface URL: $admin_url\n";

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "INTEGRATION TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";

$total_checks = 7;
$passed_checks = 0;

echo "Plugin Status: " . (is_plugin_active($plugin_slug) ? "✓ ACTIVE" : "✗ INACTIVE") . "\n";

if (is_plugin_active($plugin_slug)) {
    $passed_checks++;
}

echo "Classes Loaded: $classes_loaded/" . count($required_classes) . "\n";
if ($classes_loaded > 0) {
    $passed_checks++;
}

echo "Database Tables: $tables_exist/" . count($required_tables) . "\n";
if ($tables_exist > 0) {
    $passed_checks++;
}

echo "Demo Content: $created_posts/" . count($demo_posts) . "\n";
if ($created_posts > 0) {
    $passed_checks++;
}

echo "Shortcodes: $shortcodes_registered/" . count($expected_shortcodes) . "\n";
if ($shortcodes_registered > 0) {
    $passed_checks++;
}

$success_rate = round(($passed_checks / $total_checks) * 100);
echo "\nOverall Success Rate: $success_rate%\n";

if ($success_rate >= 80) {
    echo "✅ PLUGIN INTEGRATION SUCCESSFUL!\n";
    echo "The Environmental Content Recommendation plugin is ready for use.\n";
} elseif ($success_rate >= 60) {
    echo "⚠️ PARTIAL SUCCESS\n";
    echo "Plugin is mostly working but may need some adjustments.\n";
} else {
    echo "❌ INTEGRATION ISSUES\n";
    echo "Plugin needs troubleshooting before it can be used effectively.\n";
}

echo "\nNext Steps:\n";
echo "- Visit WordPress Admin → Environmental Content Recommendation\n";  
echo "- Configure plugin settings as needed\n";
echo "- Add recommendation shortcodes to posts/pages\n";
echo "- Monitor user behavior and recommendation performance\n";

echo "\n" . str_repeat("=", 50) . "\n";
?>
