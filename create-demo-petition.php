<?php
/**
 * Complete Petition System Demo
 * 
 * This script creates a complete working petition demonstration
 */

define('WP_USE_THEMES', false);
require_once('wp-load.php');

echo "=== PETITION SYSTEM COMPLETE DEMO ===\n";

// Step 1: Create a demo petition
$petition_data = array(
    'post_title'    => 'Save the Amazon Rainforest - Urgent Action Needed',
    'post_content'  => '<h2>The Amazon is in Crisis</h2>
                       <p>The Amazon rainforest, often called the "lungs of the Earth," is facing unprecedented threats from deforestation, illegal logging, and climate change. Every minute, we lose an area the size of a football field.</p>
                       
                       <h3>Why This Matters</h3>
                       <ul>
                       <li>🌳 The Amazon produces 20% of the world\'s oxygen</li>
                       <li>🐆 Home to 10% of known species on Earth</li>
                       <li>🌍 Critical for global climate regulation</li>
                       <li>👥 Home to 400+ indigenous tribes</li>
                       </ul>
                       
                       <h3>What We\'re Asking</h3>
                       <p>We call upon world leaders to implement immediate and comprehensive protection measures for the Amazon rainforest, including:</p>
                       <ul>
                       <li>Immediate halt to all illegal deforestation</li>
                       <li>International funding for conservation efforts</li>
                       <li>Support for indigenous land rights</li>
                       <li>Sustainable development alternatives</li>
                       </ul>
                       
                       <p><strong>Every signature counts. Together, we can save the Amazon!</strong></p>',
    'post_status'   => 'publish',
    'post_type'     => 'env_petition',
    'post_author'   => 1,
    'meta_input'    => array(
        'petition_goal' => 10000,
        'petition_current_signatures' => 0,
        'petition_status' => 'active',
        'petition_description' => 'Urgent action needed to protect the Amazon rainforest from destruction',
        'petition_target' => 'World Leaders, UN Environmental Council, Brazilian Government',
        'petition_category' => 'Conservation',
        'petition_deadline' => date('Y-m-d', strtotime('+90 days')),
        'petition_featured_image' => '',
        'petition_video_url' => '',
        'petition_external_link' => '',
        'petition_allow_anonymous' => 'yes',
        'petition_require_verification' => 'email',
        'petition_show_signature_count' => 'yes',
        'petition_show_progress_bar' => 'yes',
        'petition_enable_comments' => 'yes',
        'petition_enable_sharing' => 'yes'
    )
);

$petition_id = wp_insert_post($petition_data);

if ($petition_id && !is_wp_error($petition_id)) {
    echo "✅ Demo petition created successfully!\n";
    echo "📝 Petition ID: {$petition_id}\n";
    echo "🔗 Petition URL: " . get_permalink($petition_id) . "\n";
    
    // Add petition to Conservation category
    wp_set_object_terms($petition_id, 'Conservation', 'petition_type');
    
    // Step 2: Create some demo signatures
    echo "\n--- Creating Demo Signatures ---\n";
    
    $demo_signatures = [
        ['name' => 'Maria Silva', 'email' => 'maria@example.com', 'location' => 'São Paulo, Brazil', 'comment' => 'As someone living near the Amazon, I see the impact daily. We must act now!'],
        ['name' => 'John Smith', 'email' => 'john@example.com', 'location' => 'New York, USA', 'comment' => 'The Amazon affects our entire planet. This is everyone\'s responsibility.'],
        ['name' => 'Dr. Elena Rodriguez', 'email' => 'elena@example.com', 'location' => 'Madrid, Spain', 'comment' => 'As an environmental scientist, I can confirm the urgency of this crisis.'],
        ['name' => 'Ahmed Hassan', 'email' => 'ahmed@example.com', 'location' => 'Cairo, Egypt', 'comment' => 'Climate change affects us all. The Amazon is crucial for our future.'],
        ['name' => 'Lisa Chen', 'email' => 'lisa@example.com', 'location' => 'Tokyo, Japan', 'comment' => 'For our children\'s future, we must protect the Amazon now.']
    ];
    
    if (class_exists('EPP_Database')) {
        global $wpdb;
        $signatures_table = $wpdb->prefix . 'petition_signatures';
        $current_time = current_time('mysql');
        
        foreach ($demo_signatures as $index => $signature) {
            $wpdb->insert(
                $signatures_table,
                array(
                    'petition_id' => $petition_id,
                    'signer_name' => $signature['name'],
                    'signer_email' => $signature['email'],
                    'signer_location' => $signature['location'],
                    'signature_comment' => $signature['comment'],
                    'ip_address' => '127.0.0.1',
                    'is_verified' => 1,
                    'verified_at' => $current_time,
                    'signature_date' => date('Y-m-d H:i:s', strtotime("-" . (5-$index) . " hours")),
                    'status' => 'verified'
                )
            );
        }
        
        // Update petition signature count
        update_post_meta($petition_id, 'petition_current_signatures', count($demo_signatures));
        
        echo "✅ " . count($demo_signatures) . " demo signatures created\n";
    }
    
    // Step 3: Create milestone
    if (class_exists('EPP_Database')) {
        $milestones_table = $wpdb->prefix . 'petition_milestones';
        $wpdb->insert(
            $milestones_table,
            array(
                'petition_id' => $petition_id,
                'milestone_target' => 100,
                'milestone_title' => 'First 100 Supporters!',
                'milestone_description' => 'Thank you to our first 100 supporters! Together we can reach 10,000!',
                'is_achieved' => 0,
                'created_at' => current_time('mysql')
            )
        );
        echo "✅ Milestone created for 100 signatures\n";
    }
    
    // Step 4: Create analytics entries
    if (class_exists('EPP_Analytics')) {
        echo "\n--- Setting up Analytics ---\n";
        $analytics = new EPP_Analytics();
        
        // Simulate some events
        $events = [
            'petition_view', 'form_view', 'signature_start', 'signature_complete',
            'share_facebook', 'share_twitter', 'milestone_reached'
        ];
        
        foreach ($events as $event) {
            $analytics->track_event($petition_id, $event, ['demo' => true]);
        }
        
        echo "✅ Analytics events created\n";
    }
    
    // Step 5: Display summary
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🎉 DEMO PETITION SYSTEM READY!\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "\n📊 PETITION DETAILS:\n";
    echo "• Title: " . get_the_title($petition_id) . "\n";
    echo "• Goal: 10,000 signatures\n";
    echo "• Current: " . count($demo_signatures) . " signatures\n";
    echo "• Status: Active\n";
    echo "• Deadline: " . date('F j, Y', strtotime('+90 days')) . "\n";
    
    echo "\n🔗 IMPORTANT LINKS:\n";
    echo "• Petition Page: " . get_permalink($petition_id) . "\n";
    echo "• Admin Dashboard: http://localhost/moitruong/wp-admin/admin.php?page=petition-dashboard\n";
    echo "• Analytics: http://localhost/moitruong/wp-admin/admin.php?page=petition-analytics\n";
    echo "• Settings: http://localhost/moitruong/wp-admin/admin.php?page=petition-settings\n";
    echo "• Test Page: http://localhost/moitruong/wp-admin/tools.php?page=petition-system-test\n";
    
    echo "\n📝 SHORTCODE EXAMPLES:\n";
    echo "[petition_signature_form petition_id=\"{$petition_id}\"]\n";
    echo "[petition_progress petition_id=\"{$petition_id}\"]\n";
    echo "[petition_share petition_id=\"{$petition_id}\"]\n";
    
    echo "\n✨ FEATURES TO TEST:\n";
    echo "• Sign the petition with email verification\n";
    echo "• Share on social media platforms\n";
    echo "• View real-time progress tracking\n";
    echo "• Check analytics and reporting\n";
    echo "• Test admin management features\n";
    
    echo "\n🚀 The Environmental Platform Petition System is ready for use!\n";
    
} else {
    echo "❌ Failed to create demo petition\n";
    if (is_wp_error($petition_id)) {
        echo "Error: " . $petition_id->get_error_message() . "\n";
    }
}
?>
