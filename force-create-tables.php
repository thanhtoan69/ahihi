<?php
/**
 * Force Create Quiz & Gamification Database Tables
 * Phase 40 Force Setup Script
 */

// Include WordPress
require_once('../../../wp-config.php');

echo "<h1>Force Creating Quiz & Gamification Tables</h1>";

// Manually trigger the plugin's database creation
if (function_exists('activate_environmental_data_dashboard')) {
    echo "<p>Calling plugin activation function...</p>";
    activate_environmental_data_dashboard();
} else {
    echo "<p>Direct database creation...</p>";
    force_create_tables();
}

function force_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    echo "<h2>Creating Quiz System Tables...</h2>";
    
    // Quiz Categories
    $quiz_categories_table = $wpdb->prefix . 'quiz_categories';
    $sql_quiz_categories = "CREATE TABLE $quiz_categories_table (
        category_id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        description text,
        icon varchar(50),
        difficulty_level enum('beginner','intermediate','advanced') DEFAULT 'beginner',
        is_active tinyint(1) DEFAULT 1,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (category_id),
        UNIQUE KEY unique_category_name (name)
    ) $charset_collate;";
    
    // Quiz Questions
    $quiz_questions_table = $wpdb->prefix . 'quiz_questions';
    $sql_quiz_questions = "CREATE TABLE $quiz_questions_table (
        question_id int(11) NOT NULL AUTO_INCREMENT,
        category_id int(11) NOT NULL,
        question_text text NOT NULL,
        question_type enum('multiple_choice','true_false','fill_blank') DEFAULT 'multiple_choice',
        options json,
        correct_answer varchar(500) NOT NULL,
        explanation text,
        difficulty enum('easy','medium','hard') DEFAULT 'medium',
        points int(11) DEFAULT 10,
        is_active tinyint(1) DEFAULT 1,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (question_id),
        KEY idx_category (category_id),
        KEY idx_difficulty (difficulty),
        FOREIGN KEY (category_id) REFERENCES $quiz_categories_table(category_id) ON DELETE CASCADE
    ) $charset_collate;";
    
    // Quiz Sessions
    $quiz_sessions_table = $wpdb->prefix . 'quiz_sessions';
    $sql_quiz_sessions = "CREATE TABLE $quiz_sessions_table (
        session_id int(11) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        category_id int(11),
        total_questions int(11) NOT NULL,
        correct_answers int(11) DEFAULT 0,
        total_points int(11) DEFAULT 0,
        completion_time int(11),
        started_at timestamp DEFAULT CURRENT_TIMESTAMP,
        completed_at timestamp NULL,
        status enum('active','completed','abandoned') DEFAULT 'active',
        PRIMARY KEY (session_id),
        KEY idx_user_sessions (user_id),
        KEY idx_category_sessions (category_id),
        KEY idx_session_status (status),
        FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES $quiz_categories_table(category_id) ON DELETE SET NULL
    ) $charset_collate;";
    
    // Quiz Responses
    $quiz_responses_table = $wpdb->prefix . 'quiz_responses';
    $sql_quiz_responses = "CREATE TABLE $quiz_responses_table (
        response_id int(11) NOT NULL AUTO_INCREMENT,
        session_id int(11) NOT NULL,
        question_id int(11) NOT NULL,
        user_answer varchar(500),
        is_correct tinyint(1) DEFAULT 0,
        points_earned int(11) DEFAULT 0,
        time_taken int(11),
        answered_at timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (response_id),
        KEY idx_session_responses (session_id),
        KEY idx_question_responses (question_id),
        FOREIGN KEY (session_id) REFERENCES $quiz_sessions_table(session_id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES $quiz_questions_table(question_id) ON DELETE CASCADE
    ) $charset_collate;";
    
    // Enhanced Challenges
    $env_challenges_table = $wpdb->prefix . 'env_challenges';
    $sql_env_challenges = "CREATE TABLE $env_challenges_table (
        challenge_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        title varchar(200) NOT NULL,
        description text NOT NULL,
        type enum('daily','weekly','monthly','seasonal','special') NOT NULL DEFAULT 'weekly',
        category varchar(100) DEFAULT 'general',
        difficulty enum('easy','medium','hard') DEFAULT 'medium',
        start_date datetime NOT NULL,
        end_date datetime NOT NULL,
        requirements json DEFAULT NULL,
        points_reward int(11) unsigned NOT NULL DEFAULT 0,
        badge_reward varchar(100) DEFAULT NULL,
        max_participants int(11) unsigned DEFAULT NULL,
        is_active tinyint(1) NOT NULL DEFAULT 1,
        created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (challenge_id),
        KEY idx_type_dates (type, start_date, end_date),
        KEY idx_active_challenges (is_active, start_date),
        KEY idx_category (category)
    ) $charset_collate;";
    
    // Challenge Participants
    $env_challenge_participants_table = $wpdb->prefix . 'env_challenge_participants';
    $sql_env_challenge_participants = "CREATE TABLE $env_challenge_participants_table (
        participation_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        challenge_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        progress json DEFAULT NULL,
        points_earned int(11) unsigned NOT NULL DEFAULT 0,
        is_completed tinyint(1) NOT NULL DEFAULT 0,
        completion_date datetime DEFAULT NULL,
        joined_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        notes text,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (participation_id),
        UNIQUE KEY unique_participation (challenge_id, user_id),
        KEY idx_user_challenges (user_id),
        KEY idx_completion_status (is_completed),
        FOREIGN KEY (challenge_id) REFERENCES $env_challenges_table(challenge_id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
    ) $charset_collate;";
    
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    // Create tables
    $results = array();
    $tables = array(
        'Quiz Categories' => $sql_quiz_categories,
        'Quiz Questions' => $sql_quiz_questions,
        'Quiz Sessions' => $sql_quiz_sessions,
        'Quiz Responses' => $sql_quiz_responses,
        'Enhanced Challenges' => $sql_env_challenges,
        'Challenge Participants' => $sql_env_challenge_participants
    );
    
    foreach ($tables as $name => $sql) {
        $result = dbDelta($sql);
        $results[$name] = $result;
        echo "<p><strong>$name:</strong> " . (empty($result) ? "Already exists or created" : "Created/Updated") . "</p>";
    }
    
    // Insert sample data
    echo "<h2>Inserting Sample Data...</h2>";
    insert_sample_quiz_data();
    insert_sample_challenge_data();
    
    echo "<h2>✅ Database setup completed!</h2>";
    echo "<p><a href='test-phase40-database.php'>← Back to Database Test</a></p>";
}

function insert_sample_quiz_data() {
    global $wpdb;
    
    $categories_table = $wpdb->prefix . 'quiz_categories';
    $questions_table = $wpdb->prefix . 'quiz_questions';
    
    // Check if categories already exist
    $existing_categories = $wpdb->get_var("SELECT COUNT(*) FROM $categories_table");
    
    if ($existing_categories == 0) {
        echo "<p>Inserting quiz categories...</p>";
        
        $categories = array(
            array(
                'name' => 'Waste Management',
                'description' => 'Learn about proper waste sorting, recycling, and disposal methods',
                'icon' => '♻️',
                'difficulty_level' => 'beginner'
            ),
            array(
                'name' => 'Carbon Footprint',
                'description' => 'Understand your environmental impact and how to reduce it',
                'icon' => '🌱',
                'difficulty_level' => 'intermediate'
            ),
            array(
                'name' => 'Energy Conservation',
                'description' => 'Discover ways to save energy and use renewable resources',
                'icon' => '⚡',
                'difficulty_level' => 'beginner'
            ),
            array(
                'name' => 'Water Conservation',
                'description' => 'Learn about water saving techniques and water cycle protection',
                'icon' => '💧',
                'difficulty_level' => 'intermediate'
            ),
            array(
                'name' => 'Sustainable Living',
                'description' => 'Adopt eco-friendly lifestyle practices for a better future',
                'icon' => '🌍',
                'difficulty_level' => 'advanced'
            )
        );
        
        foreach ($categories as $category) {
            $wpdb->insert($categories_table, $category);
        }
        
        echo "<p>✅ Quiz categories inserted successfully!</p>";
    }
    
    // Insert sample questions
    $existing_questions = $wpdb->get_var("SELECT COUNT(*) FROM $questions_table");
    
    if ($existing_questions == 0) {
        echo "<p>Inserting quiz questions...</p>";
        
        $questions = array(
            array(
                'category_id' => 1, // Waste Management
                'question_text' => 'Which bin should plastic bottles go into?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['Recycling bin', 'General waste', 'Organic waste', 'Hazardous waste']),
                'correct_answer' => 'Recycling bin',
                'explanation' => 'Plastic bottles are recyclable materials and should go into the recycling bin.',
                'difficulty' => 'easy',
                'points' => 10
            ),
            array(
                'category_id' => 2, // Carbon Footprint
                'question_text' => 'What is the average carbon footprint of a car per kilometer?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['50g CO2', '120g CO2', '200g CO2', '350g CO2']),
                'correct_answer' => '120g CO2',
                'explanation' => 'The average car emits approximately 120 grams of CO2 per kilometer driven.',
                'difficulty' => 'medium',
                'points' => 15
            ),
            array(
                'category_id' => 3, // Energy Conservation
                'question_text' => 'LED bulbs use how much less energy than incandescent bulbs?',
                'question_type' => 'multiple_choice',
                'options' => json_encode(['25% less', '50% less', '75% less', '90% less']),
                'correct_answer' => '75% less',
                'explanation' => 'LED bulbs typically use about 75% less energy than traditional incandescent bulbs.',
                'difficulty' => 'medium',
                'points' => 15
            )
        );
        
        foreach ($questions as $question) {
            $wpdb->insert($questions_table, $question);
        }
        
        echo "<p>✅ Quiz questions inserted successfully!</p>";
    }
}

function insert_sample_challenge_data() {
    global $wpdb;
    
    $challenges_table = $wpdb->prefix . 'env_challenges';
    
    $existing_challenges = $wpdb->get_var("SELECT COUNT(*) FROM $challenges_table");
    
    if ($existing_challenges == 0) {
        echo "<p>Inserting sample challenges...</p>";
        
        $challenges = array(
            array(
                'title' => 'Zero Waste Week',
                'description' => 'Try to produce zero waste for one week by reducing, reusing, and recycling everything.',
                'type' => 'weekly',
                'category' => 'waste-reduction',
                'difficulty' => 'hard',
                'start_date' => date('Y-m-d H:i:s'),
                'end_date' => date('Y-m-d H:i:s', strtotime('+1 week')),
                'requirements' => json_encode([
                    'actions' => ['reduce_waste', 'recycle_items', 'compost_organic'],
                    'target_count' => 7,
                    'daily_goal' => 'Zero waste production'
                ]),
                'points_reward' => 500,
                'badge_reward' => 'Zero Waste Hero'
            ),
            array(
                'title' => 'Energy Saver Challenge',
                'description' => 'Reduce your energy consumption by 20% this month through various conservation methods.',
                'type' => 'monthly',
                'category' => 'energy-conservation',
                'difficulty' => 'medium',
                'start_date' => date('Y-m-d H:i:s'),
                'end_date' => date('Y-m-d H:i:s', strtotime('+1 month')),
                'requirements' => json_encode([
                    'actions' => ['unplug_devices', 'use_led_bulbs', 'optimize_heating'],
                    'target_reduction' => 20,
                    'measurement' => 'percentage'
                ]),
                'points_reward' => 750,
                'badge_reward' => 'Energy Guardian'
            ),
            array(
                'title' => 'Daily Green Commute',
                'description' => 'Use eco-friendly transportation (walking, cycling, public transport) for your daily commute.',
                'type' => 'daily',
                'category' => 'transportation',
                'difficulty' => 'easy',
                'start_date' => date('Y-m-d H:i:s'),
                'end_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
                'requirements' => json_encode([
                    'actions' => ['walk', 'cycle', 'public_transport'],
                    'target_count' => 1,
                    'daily_requirement' => true
                ]),
                'points_reward' => 100,
                'badge_reward' => 'Green Commuter'
            )
        );
        
        foreach ($challenges as $challenge) {
            $wpdb->insert($challenges_table, $challenge);
        }
        
        echo "<p>✅ Sample challenges inserted successfully!</p>";
    }
}

?>
