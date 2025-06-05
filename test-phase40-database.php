<?php
/**
 * Test Quiz & Gamification Database Setup
 * Phase 40 Testing Script
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>Phase 40: Quiz & Gamification System - Database Test</h1>";

global $wpdb;

// Test database tables
$tables_to_check = array(
    'quiz_categories',
    'quiz_questions', 
    'quiz_sessions',
    'quiz_responses',
    'env_challenges',
    'env_challenge_participants',
    'env_achievements',
    'env_user_achievements',
    'env_ai_classifications',
    'env_user_gamification'
);

echo "<h2>Database Tables Status:</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Table Name</th><th>Status</th><th>Row Count</th></tr>";

foreach ($tables_to_check as $table) {
    $full_table_name = $wpdb->prefix . $table;
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
    
    if ($table_exists) {
        $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
        echo "<tr><td>$full_table_name</td><td style='color: green;'>✓ EXISTS</td><td>$row_count</td></tr>";
    } else {
        echo "<tr><td>$full_table_name</td><td style='color: red;'>✗ MISSING</td><td>-</td></tr>";
    }
}

echo "</table>";

// Test sample data
echo "<h2>Sample Data Check:</h2>";

// Check quiz categories
$quiz_categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}quiz_categories LIMIT 5");
echo "<h3>Quiz Categories (" . count($quiz_categories) . " found):</h3>";
if ($quiz_categories) {
    echo "<ul>";
    foreach ($quiz_categories as $category) {
        echo "<li><strong>{$category->name}</strong> - {$category->description} ({$category->difficulty_level})</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: orange;'>No quiz categories found. Sample data may not be inserted yet.</p>";
}

// Check quiz questions
$quiz_questions = $wpdb->get_results("SELECT q.*, c.name as category_name FROM {$wpdb->prefix}quiz_questions q LEFT JOIN {$wpdb->prefix}quiz_categories c ON q.category_id = c.category_id LIMIT 5");
echo "<h3>Quiz Questions (" . count($quiz_questions) . " found):</h3>";
if ($quiz_questions) {
    echo "<ul>";
    foreach ($quiz_questions as $question) {
        echo "<li><strong>[{$question->category_name}]</strong> {$question->question_text} ({$question->difficulty}, {$question->points} pts)</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: orange;'>No quiz questions found. Sample data may not be inserted yet.</p>";
}

// Check challenges
$challenges = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}env_challenges LIMIT 5");
echo "<h3>Environmental Challenges (" . count($challenges) . " found):</h3>";
if ($challenges) {
    echo "<ul>";
    foreach ($challenges as $challenge) {
        echo "<li><strong>{$challenge->title}</strong> - {$challenge->description} ({$challenge->type}, {$challenge->points_reward} pts)</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: orange;'>No challenges found. Sample data may not be inserted yet.</p>";
}

// Plugin activation status
echo "<h2>Plugin Status:</h2>";
if (is_plugin_active('environmental-data-dashboard/environmental-data-dashboard.php')) {
    echo "<p style='color: green;'>✓ Environmental Data Dashboard plugin is ACTIVE</p>";
} else {
    echo "<p style='color: red;'>✗ Environmental Data Dashboard plugin is NOT ACTIVE</p>";
}

// Check if classes exist
echo "<h2>Class Availability:</h2>";
$classes_to_check = array(
    'Environmental_Quiz_Manager',
    'Environmental_Challenge_System', 
    'Environmental_Sample_Data_Inserter'
);

foreach ($classes_to_check as $class) {
    if (class_exists($class)) {
        echo "<p style='color: green;'>✓ Class $class is available</p>";
    } else {
        echo "<p style='color: red;'>✗ Class $class is NOT available</p>";
    }
}

echo "<h2>Actions:</h2>";
echo "<p><a href='force-create-tables.php' style='background: #0073aa; color: white; padding: 8px 12px; text-decoration: none; border-radius: 3px;'>Force Create Tables</a></p>";
echo "<p><a href='test-quiz-interface.php' style='background: #00a32a; color: white; padding: 8px 12px; text-decoration: none; border-radius: 3px;'>Test Quiz Interface</a></p>";
echo "<p><a href='test-challenge-system.php' style='background: #d63638; color: white; padding: 8px 12px; text-decoration: none; border-radius: 3px;'>Test Challenge System</a></p>";

?>
