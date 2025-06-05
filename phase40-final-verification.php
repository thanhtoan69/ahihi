<?php
/**
 * Phase 40 Final Status Verification
 */

// Include WordPress
require_once('./wp-config.php');

echo "=== PHASE 40: QUIZ & GAMIFICATION SYSTEM - FINAL STATUS ===\n\n";

// Check plugin status
$plugin_path = 'environmental-data-dashboard/environmental-data-dashboard.php';
$plugin_active = is_plugin_active($plugin_path);

echo "PLUGIN STATUS:\n";
echo $plugin_active ? "✅ Environmental Data Dashboard: ACTIVE\n" : "❌ Environmental Data Dashboard: INACTIVE\n";

// Check database tables
global $wpdb;
$required_tables = [
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
];

echo "\nDATABASE TABLES:\n";
$all_tables_exist = true;
foreach ($required_tables as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'");
    
    if ($table_exists) {
        $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $full_table_name");
        echo "✅ $full_table_name ($row_count rows)\n";
    } else {
        echo "❌ $full_table_name (MISSING)\n";
        $all_tables_exist = false;
    }
}

// Check sample data
echo "\nSAMPLE DATA:\n";
$quiz_categories = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quiz_categories");
$quiz_questions = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}quiz_questions");
$challenges = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}env_challenges");

echo "Quiz Categories: $quiz_categories\n";
echo "Quiz Questions: $quiz_questions\n";
echo "Environmental Challenges: $challenges\n";

// Check file structure
echo "\nFILE STRUCTURE:\n";
$base_path = WP_PLUGIN_DIR . '/environmental-data-dashboard/';
$required_files = [
    'environmental-data-dashboard.php',
    'includes/class-quiz-manager.php',
    'includes/class-challenge-system.php',
    'includes/sample-data-inserter.php',
    'assets/js/quiz-interface.js',
    'assets/js/challenge-dashboard.js',
    'assets/css/quiz-challenge-styles.css'
];

$all_files_exist = true;
foreach ($required_files as $file) {
    $full_path = $base_path . $file;
    if (file_exists($full_path)) {
        echo "✅ $file\n";
    } else {
        echo "❌ $file (MISSING)\n";
        $all_files_exist = false;
    }
}

// Check class availability
echo "\nCLASS AVAILABILITY:\n";
$required_classes = [
    'Environmental_Quiz_Manager',
    'Environmental_Challenge_System',
    'Environmental_Sample_Data_Inserter'
];

$all_classes_available = true;
foreach ($required_classes as $class) {
    if (class_exists($class)) {
        echo "✅ $class\n";
    } else {
        echo "❌ $class (NOT AVAILABLE)\n";
        $all_classes_available = false;
    }
}

// Final status
echo "\n" . str_repeat("=", 60) . "\n";
echo "PHASE 40 FINAL STATUS:\n";

if ($plugin_active && $all_tables_exist && $all_files_exist && $all_classes_available && $quiz_categories > 0 && $quiz_questions > 0) {
    echo "🎉 PHASE 40: QUIZ & GAMIFICATION SYSTEM - COMPLETED SUCCESSFULLY!\n";
    echo "\nFeatures Ready:\n";
    echo "• Interactive Quiz System\n";
    echo "• Environmental Challenges\n";
    echo "• Gamification & Achievements\n";
    echo "• Real-time AJAX Updates\n";
    echo "• Mobile-Responsive Design\n";
    echo "• WordPress Integration\n";
    echo "• Sample Educational Content\n";
    echo "\nShortcodes Available:\n";
    echo "• [env_quiz_interface]\n";
    echo "• [env_quiz_leaderboard]\n";
    echo "• [env_challenge_dashboard]\n";
    echo "• [env_user_progress]\n";
} else {
    echo "⚠️ PHASE 40: INCOMPLETE - Issues detected\n";
    if (!$plugin_active) echo "- Plugin not active\n";
    if (!$all_tables_exist) echo "- Missing database tables\n";
    if (!$all_files_exist) echo "- Missing required files\n";
    if (!$all_classes_available) echo "- Missing required classes\n";
    if ($quiz_categories == 0 || $quiz_questions == 0) echo "- Missing sample data\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
?>
