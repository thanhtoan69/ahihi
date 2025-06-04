<?php
/**
 * Challenge System
 * 
 * Manages daily/weekly environmental challenges and gamification
 * 
 * @package Environmental_Data_Dashboard
 * @since 1.0.0 - Phase 40
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Challenge_System {
    
    private $wpdb;
    private $gamification;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->gamification = new Environmental_Gamification_System();
        $this->init_hooks();
        $this->setup_cron_jobs();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_join_challenge', array($this, 'ajax_join_challenge'));
        add_action('wp_ajax_complete_challenge_task', array($this, 'ajax_complete_challenge_task'));
        add_action('wp_ajax_get_active_challenges', array($this, 'ajax_get_active_challenges'));
        add_action('wp_ajax_get_user_challenges', array($this, 'ajax_get_user_challenges'));
        add_action('wp_ajax_get_challenge_leaderboard', array($this, 'ajax_get_challenge_leaderboard'));
        
        // Admin hooks
        add_action('wp_ajax_create_challenge', array($this, 'ajax_create_challenge'));
        add_action('wp_ajax_update_challenge', array($this, 'ajax_update_challenge'));
        add_action('wp_ajax_delete_challenge', array($this, 'ajax_delete_challenge'));
        add_action('wp_ajax_get_challenge_analytics', array($this, 'ajax_get_challenge_analytics'));
        
        // Cron hooks
        add_action('env_generate_daily_challenges', array($this, 'generate_daily_challenges'));
        add_action('env_generate_weekly_challenges', array($this, 'generate_weekly_challenges'));
        add_action('env_process_challenge_rewards', array($this, 'process_challenge_rewards'));
        add_action('env_cleanup_expired_challenges', array($this, 'cleanup_expired_challenges'));
    }
    
    /**
     * Setup cron jobs
     */
    private function setup_cron_jobs() {
        if (!wp_next_scheduled('env_generate_daily_challenges')) {
            wp_schedule_event(strtotime('tomorrow 00:01'), 'daily', 'env_generate_daily_challenges');
        }
        
        if (!wp_next_scheduled('env_generate_weekly_challenges')) {
            wp_schedule_event(strtotime('next monday 00:01'), 'weekly', 'env_generate_weekly_challenges');
        }
        
        if (!wp_next_scheduled('env_process_challenge_rewards')) {
            wp_schedule_event(time(), 'hourly', 'env_process_challenge_rewards');
        }
        
        if (!wp_next_scheduled('env_cleanup_expired_challenges')) {
            wp_schedule_event(time(), 'daily', 'env_cleanup_expired_challenges');
        }
    }
    
    /**
     * Create challenge tables if they don't exist
     */
    public function create_challenge_tables() {
        $sql = "
            CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}env_challenges (
                challenge_id INT AUTO_INCREMENT PRIMARY KEY,
                challenge_name VARCHAR(255) NOT NULL,
                challenge_description TEXT,
                challenge_type ENUM('daily', 'weekly', 'monthly', 'special', 'seasonal') NOT NULL,
                difficulty_level ENUM('easy', 'medium', 'hard', 'expert') DEFAULT 'medium',
                category ENUM('carbon', 'waste', 'energy', 'water', 'transport', 'consumption', 'education', 'social') DEFAULT 'general',
                requirements JSON,
                rewards JSON,
                start_date DATETIME NOT NULL,
                end_date DATETIME NOT NULL,
                max_participants INT DEFAULT 0,
                current_participants INT DEFAULT 0,
                is_active BOOLEAN DEFAULT TRUE,
                auto_generated BOOLEAN DEFAULT FALSE,
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_active_dates (is_active, start_date, end_date),
                INDEX idx_type_category (challenge_type, category),
                INDEX idx_difficulty (difficulty_level)
            ) ENGINE=InnoDB;
            
            CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}env_challenge_participants (
                participation_id INT AUTO_INCREMENT PRIMARY KEY,
                challenge_id INT NOT NULL,
                user_id INT NOT NULL,
                joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                progress JSON,
                completion_percentage DECIMAL(5,2) DEFAULT 0,
                is_completed BOOLEAN DEFAULT FALSE,
                completed_at TIMESTAMP NULL,
                points_earned INT DEFAULT 0,
                bonus_points INT DEFAULT 0,
                ranking INT DEFAULT 0,
                FOREIGN KEY (challenge_id) REFERENCES {$this->wpdb->prefix}env_challenges(challenge_id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_challenge (challenge_id, user_id),
                INDEX idx_user_challenges (user_id, is_completed),
                INDEX idx_challenge_completion (challenge_id, is_completed, completion_percentage)
            ) ENGINE=InnoDB;
            
            CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}env_challenge_tasks (
                task_id INT AUTO_INCREMENT PRIMARY KEY,
                challenge_id INT NOT NULL,
                task_name VARCHAR(255) NOT NULL,
                task_description TEXT,
                task_type ENUM('quiz', 'action', 'upload', 'measurement', 'social') NOT NULL,
                requirements JSON,
                points_value INT DEFAULT 10,
                order_number INT DEFAULT 0,
                is_required BOOLEAN DEFAULT TRUE,
                FOREIGN KEY (challenge_id) REFERENCES {$this->wpdb->prefix}env_challenges(challenge_id) ON DELETE CASCADE,
                INDEX idx_challenge_order (challenge_id, order_number)
            ) ENGINE=InnoDB;
            
            CREATE TABLE IF NOT EXISTS {$this->wpdb->prefix}env_challenge_task_completions (
                completion_id INT AUTO_INCREMENT PRIMARY KEY,
                participation_id INT NOT NULL,
                task_id INT NOT NULL,
                completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                submission_data JSON,
                points_earned INT DEFAULT 0,
                verified BOOLEAN DEFAULT FALSE,
                verified_by INT NULL,
                verified_at TIMESTAMP NULL,
                FOREIGN KEY (participation_id) REFERENCES {$this->wpdb->prefix}env_challenge_participants(participation_id) ON DELETE CASCADE,
                FOREIGN KEY (task_id) REFERENCES {$this->wpdb->prefix}env_challenge_tasks(task_id) ON DELETE CASCADE,
                UNIQUE KEY unique_task_completion (participation_id, task_id),
                INDEX idx_verification (verified, verified_at)
            ) ENGINE=InnoDB;
        ";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get active challenges
     */
    public function get_active_challenges($user_id = null, $type = null, $category = null) {
        $where_clauses = array(
            "is_active = 1",
            "start_date <= NOW()",
            "end_date >= NOW()"
        );
        $values = array();
        
        if ($type) {
            $where_clauses[] = "challenge_type = %s";
            $values[] = $type;
        }
        
        if ($category) {
            $where_clauses[] = "category = %s";
            $values[] = $category;
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $sql = "SELECT 
                    c.*,
                    CASE WHEN p.participation_id IS NOT NULL THEN TRUE ELSE FALSE END as user_joined,
                    p.progress,
                    p.completion_percentage,
                    p.is_completed,
                    DATEDIFF(end_date, NOW()) as days_remaining
                FROM {$this->wpdb->prefix}env_challenges c";
        
        if ($user_id) {
            $sql .= " LEFT JOIN {$this->wpdb->prefix}env_challenge_participants p 
                      ON c.challenge_id = p.challenge_id AND p.user_id = %d";
            array_unshift($values, $user_id);
        }
        
        $sql .= " WHERE {$where_sql} ORDER BY c.end_date ASC, c.difficulty_level ASC";
        
        if (!empty($values)) {
            $sql = $this->wpdb->prepare($sql, ...$values);
        }
        
        $challenges = $this->wpdb->get_results($sql);
        
        // Parse JSON fields
        foreach ($challenges as $challenge) {
            $challenge->requirements = json_decode($challenge->requirements, true);
            $challenge->rewards = json_decode($challenge->rewards, true);
            if (isset($challenge->progress)) {
                $challenge->progress = json_decode($challenge->progress, true);
            }
        }
        
        return $challenges;
    }
    
    /**
     * Join a challenge
     */
    public function join_challenge($user_id, $challenge_id) {
        // Validate challenge
        $challenge = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}env_challenges 
             WHERE challenge_id = %d AND is_active = 1 
             AND start_date <= NOW() AND end_date >= NOW()",
            $challenge_id
        ));
        
        if (!$challenge) {
            return new WP_Error('invalid_challenge', 'Challenge not found or not active');
        }
        
        // Check if already joined
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}env_challenge_participants 
             WHERE challenge_id = %d AND user_id = %d",
            $challenge_id, $user_id
        ));
        
        if ($existing) {
            return new WP_Error('already_joined', 'Already joined this challenge');
        }
        
        // Check participant limit
        if ($challenge->max_participants > 0 && $challenge->current_participants >= $challenge->max_participants) {
            return new WP_Error('challenge_full', 'Challenge is full');
        }
        
        // Initialize progress
        $initial_progress = array();
        $requirements = json_decode($challenge->requirements, true);
        
        if (is_array($requirements)) {
            foreach ($requirements as $key => $requirement) {
                $initial_progress[$key] = array(
                    'required' => $requirement,
                    'current' => 0,
                    'completed' => false
                );
            }
        }
        
        // Join challenge
        $participation_data = array(
            'challenge_id' => $challenge_id,
            'user_id' => $user_id,
            'progress' => wp_json_encode($initial_progress),
            'joined_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->insert(
            $this->wpdb->prefix . 'env_challenge_participants',
            $participation_data
        );
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to join challenge');
        }
        
        // Update participant count
        $this->wpdb->update(
            $this->wpdb->prefix . 'env_challenges',
            array('current_participants' => $challenge->current_participants + 1),
            array('challenge_id' => $challenge_id)
        );
        
        // Award joining points
        $join_points = 5; // Base points for joining
        $this->gamification->award_points($user_id, $join_points, 'challenge_join', "Joined challenge: {$challenge->challenge_name}");
        
        return array(
            'participation_id' => $this->wpdb->insert_id,
            'message' => 'Successfully joined challenge',
            'points_earned' => $join_points
        );
    }
    
    /**
     * Update challenge progress
     */
    public function update_challenge_progress($user_id, $action_type, $action_data = array()) {
        // Get user's active challenge participations
        $participations = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT p.*, c.requirements, c.rewards, c.challenge_name 
             FROM {$this->wpdb->prefix}env_challenge_participants p
             JOIN {$this->wpdb->prefix}env_challenges c ON p.challenge_id = c.challenge_id
             WHERE p.user_id = %d AND p.is_completed = 0 
             AND c.is_active = 1 AND c.end_date >= NOW()",
            $user_id
        ));
        
        foreach ($participations as $participation) {
            $progress = json_decode($participation->progress, true) ?: array();
            $requirements = json_decode($participation->requirements, true) ?: array();
            $updated = false;
            
            // Update progress based on action type
            switch ($action_type) {
                case 'quiz_completed':
                    if (isset($requirements['quizzes'])) {
                        $progress['quizzes']['current'] = min(
                            $progress['quizzes']['current'] + 1,
                            $requirements['quizzes']
                        );
                        $updated = true;
                    }
                    break;
                    
                case 'carbon_calculated':
                    if (isset($requirements['carbon_calculations'])) {
                        $progress['carbon_calculations']['current'] = min(
                            $progress['carbon_calculations']['current'] + 1,
                            $requirements['carbon_calculations']
                        );
                        $updated = true;
                    }
                    break;
                    
                case 'waste_classified':
                    if (isset($requirements['waste_classifications'])) {
                        $progress['waste_classifications']['current'] = min(
                            $progress['waste_classifications']['current'] + 1,
                            $requirements['waste_classifications']
                        );
                        $updated = true;
                    }
                    break;
                    
                case 'article_shared':
                    if (isset($requirements['social_shares'])) {
                        $progress['social_shares']['current'] = min(
                            $progress['social_shares']['current'] + 1,
                            $requirements['social_shares']
                        );
                        $updated = true;
                    }
                    break;
                    
                case 'daily_login':
                    if (isset($requirements['daily_logins'])) {
                        $progress['daily_logins']['current'] = min(
                            $progress['daily_logins']['current'] + 1,
                            $requirements['daily_logins']
                        );
                        $updated = true;
                    }
                    break;
            }
            
            if ($updated) {
                // Calculate completion percentage
                $total_requirements = count($requirements);
                $completed_requirements = 0;
                
                foreach ($progress as $key => $prog) {
                    if ($prog['current'] >= $prog['required']) {
                        $progress[$key]['completed'] = true;
                        $completed_requirements++;
                    }
                }
                
                $completion_percentage = $total_requirements > 0 ? 
                    ($completed_requirements / $total_requirements) * 100 : 0;
                
                $is_completed = $completion_percentage >= 100;
                
                // Update participation
                $update_data = array(
                    'progress' => wp_json_encode($progress),
                    'completion_percentage' => $completion_percentage
                );
                
                if ($is_completed && !$participation->is_completed) {
                    $update_data['is_completed'] = 1;
                    $update_data['completed_at'] = current_time('mysql');
                    
                    // Award completion rewards
                    $this->award_challenge_completion($user_id, $participation);
                }
                
                $this->wpdb->update(
                    $this->wpdb->prefix . 'env_challenge_participants',
                    $update_data,
                    array('participation_id' => $participation->participation_id)
                );
            }
        }
    }
    
    /**
     * Award challenge completion rewards
     */
    private function award_challenge_completion($user_id, $participation) {
        $rewards = json_decode($participation->rewards, true) ?: array();
        $total_points = 0;
        
        // Award points
        if (isset($rewards['points'])) {
            $points = intval($rewards['points']);
            $this->gamification->award_points($user_id, $points, 'challenge_complete', 
                "Completed challenge: {$participation->challenge_name}");
            $total_points += $points;
        }
        
        // Award bonus points for difficulty
        $difficulty_bonus = array(
            'easy' => 10,
            'medium' => 25,
            'hard' => 50,
            'expert' => 100
        );
        
        if (isset($difficulty_bonus[$participation->difficulty_level])) {
            $bonus = $difficulty_bonus[$participation->difficulty_level];
            $this->gamification->award_points($user_id, $bonus, 'challenge_difficulty_bonus', 
                "Difficulty bonus for completing {$participation->difficulty_level} challenge");
            $total_points += $bonus;
        }
        
        // Award achievements
        if (isset($rewards['achievements'])) {
            foreach ($rewards['achievements'] as $achievement_code) {
                $this->gamification->award_achievement($user_id, $achievement_code);
            }
        }
        
        // Award badges
        if (isset($rewards['badges'])) {
            foreach ($rewards['badges'] as $badge_id) {
                $this->gamification->award_badge($user_id, $badge_id);
            }
        }
        
        // Update participation with earned points
        $this->wpdb->update(
            $this->wpdb->prefix . 'env_challenge_participants',
            array('points_earned' => $total_points),
            array('participation_id' => $participation->participation_id)
        );
        
        // Check for completion achievements
        $this->check_challenge_achievements($user_id);
    }
    
    /**
     * Check challenge-related achievements
     */
    private function check_challenge_achievements($user_id) {
        // Get user's challenge completion stats
        $stats = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT 
                COUNT(*) as total_completed,
                COUNT(CASE WHEN DATEDIFF(completed_at, joined_at) <= 1 THEN 1 END) as completed_in_one_day,
                COUNT(DISTINCT DATE(completed_at)) as completion_days,
                AVG(completion_percentage) as avg_completion
             FROM {$this->wpdb->prefix}env_challenge_participants 
             WHERE user_id = %d AND is_completed = 1",
            $user_id
        ));
        
        $achievements_to_check = array();
        
        // Challenge master achievements
        if ($stats->total_completed >= 5) {
            $achievements_to_check[] = 'CHALLENGE_MASTER_5';
        }
        if ($stats->total_completed >= 10) {
            $achievements_to_check[] = 'CHALLENGE_MASTER_10';
        }
        if ($stats->total_completed >= 25) {
            $achievements_to_check[] = 'CHALLENGE_MASTER_25';
        }
        
        // Speed challenger
        if ($stats->completed_in_one_day >= 3) {
            $achievements_to_check[] = 'SPEED_CHALLENGER';
        }
        
        // Consistent challenger
        if ($stats->completion_days >= 7) {
            $achievements_to_check[] = 'CONSISTENT_CHALLENGER';
        }
        
        // Award achievements
        foreach ($achievements_to_check as $achievement_code) {
            $this->gamification->award_achievement($user_id, $achievement_code);
        }
    }
    
    /**
     * Generate daily challenges
     */
    public function generate_daily_challenges() {
        $daily_challenges = array(
            array(
                'name' => 'Eco Quiz Master',
                'description' => 'Complete 3 environmental quizzes today',
                'type' => 'daily',
                'difficulty' => 'easy',
                'category' => 'education',
                'requirements' => array('quizzes' => 3),
                'rewards' => array('points' => 50)
            ),
            array(
                'name' => 'Carbon Tracker',
                'description' => 'Calculate your carbon footprint for 2 activities',
                'type' => 'daily',
                'difficulty' => 'medium',
                'category' => 'carbon',
                'requirements' => array('carbon_calculations' => 2),
                'rewards' => array('points' => 40)
            ),
            array(
                'name' => 'Waste Classifier',
                'description' => 'Classify 5 waste items using AI',
                'type' => 'daily',
                'difficulty' => 'easy',
                'category' => 'waste',
                'requirements' => array('waste_classifications' => 5),
                'rewards' => array('points' => 30)
            ),
            array(
                'name' => 'Social Environmentalist',
                'description' => 'Share 2 environmental articles on social media',
                'type' => 'daily',
                'difficulty' => 'easy',
                'category' => 'social',
                'requirements' => array('social_shares' => 2),
                'rewards' => array('points' => 25)
            ),
            array(
                'name' => 'Daily Learner',
                'description' => 'Read 3 environmental articles',
                'type' => 'daily',
                'difficulty' => 'easy',
                'category' => 'education',
                'requirements' => array('articles_read' => 3),
                'rewards' => array('points' => 20)
            )
        );
        
        // Create 2-3 random daily challenges
        $selected_challenges = array_rand($daily_challenges, rand(2, 3));
        if (!is_array($selected_challenges)) {
            $selected_challenges = array($selected_challenges);
        }
        
        $start_date = date('Y-m-d 00:00:00');
        $end_date = date('Y-m-d 23:59:59');
        
        foreach ($selected_challenges as $index) {
            $challenge = $daily_challenges[$index];
            
            $this->wpdb->insert(
                $this->wpdb->prefix . 'env_challenges',
                array(
                    'challenge_name' => $challenge['name'],
                    'challenge_description' => $challenge['description'],
                    'challenge_type' => 'daily',
                    'difficulty_level' => $challenge['difficulty'],
                    'category' => $challenge['category'],
                    'requirements' => wp_json_encode($challenge['requirements']),
                    'rewards' => wp_json_encode($challenge['rewards']),
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'auto_generated' => 1,
                    'created_at' => current_time('mysql')
                )
            );
        }
    }
    
    /**
     * Generate weekly challenges
     */
    public function generate_weekly_challenges() {
        $weekly_challenges = array(
            array(
                'name' => 'Weekly Eco Expert',
                'description' => 'Complete 15 quizzes and achieve 80% average score',
                'type' => 'weekly',
                'difficulty' => 'hard',
                'category' => 'education',
                'requirements' => array('quizzes' => 15, 'min_avg_score' => 80),
                'rewards' => array('points' => 300, 'achievements' => array('WEEKLY_EXPERT'))
            ),
            array(
                'name' => 'Carbon Conscious Week',
                'description' => 'Track carbon footprint for 7 days and reduce by 10%',
                'type' => 'weekly',
                'difficulty' => 'medium',
                'category' => 'carbon',
                'requirements' => array('daily_logins' => 7, 'carbon_calculations' => 7),
                'rewards' => array('points' => 200)
            ),
            array(
                'name' => 'Waste Warrior Week',
                'description' => 'Classify 50 waste items with 90% accuracy',
                'type' => 'weekly',
                'difficulty' => 'hard',
                'category' => 'waste',
                'requirements' => array('waste_classifications' => 50, 'min_accuracy' => 90),
                'rewards' => array('points' => 250)
            ),
            array(
                'name' => 'Community Connector',
                'description' => 'Share 10 articles and interact with 5 community posts',
                'type' => 'weekly',
                'difficulty' => 'medium',
                'category' => 'social',
                'requirements' => array('social_shares' => 10, 'community_interactions' => 5),
                'rewards' => array('points' => 180)
            )
        );
        
        // Create 1-2 weekly challenges
        $selected_challenges = array_rand($weekly_challenges, rand(1, 2));
        if (!is_array($selected_challenges)) {
            $selected_challenges = array($selected_challenges);
        }
        
        $start_date = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $end_date = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        
        foreach ($selected_challenges as $index) {
            $challenge = $weekly_challenges[$index];
            
            $this->wpdb->insert(
                $this->wpdb->prefix . 'env_challenges',
                array(
                    'challenge_name' => $challenge['name'],
                    'challenge_description' => $challenge['description'],
                    'challenge_type' => 'weekly',
                    'difficulty_level' => $challenge['difficulty'],
                    'category' => $challenge['category'],
                    'requirements' => wp_json_encode($challenge['requirements']),
                    'rewards' => wp_json_encode($challenge['rewards']),
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'auto_generated' => 1,
                    'created_at' => current_time('mysql')
                )
            );
        }
    }
    
    /**
     * Cleanup expired challenges
     */
    public function cleanup_expired_challenges() {
        // Archive expired challenges instead of deleting
        $this->wpdb->update(
            $this->wpdb->prefix . 'env_challenges',
            array('is_active' => 0),
            array('end_date <' => current_time('mysql'))
        );
        
        // Delete very old auto-generated challenges (older than 30 days)
        $this->wpdb->delete(
            $this->wpdb->prefix . 'env_challenges',
            array(
                'auto_generated' => 1,
                'end_date <' => date('Y-m-d H:i:s', strtotime('-30 days'))
            )
        );
    }
    
    /**
     * AJAX: Join challenge
     */
    public function ajax_join_challenge() {
        if (!check_ajax_referer('env_challenge_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        $challenge_id = intval($_POST['challenge_id']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $result = $this->join_challenge($user_id, $challenge_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Get active challenges
     */
    public function ajax_get_active_challenges() {
        $user_id = get_current_user_id();
        $type = sanitize_text_field($_GET['type']) ?: null;
        $category = sanitize_text_field($_GET['category']) ?: null;
        
        $challenges = $this->get_active_challenges($user_id, $type, $category);
        wp_send_json_success($challenges);
    }
    
    /**
     * AJAX: Get user challenges
     */
    public function ajax_get_user_challenges() {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $challenges = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                c.*,
                p.progress,
                p.completion_percentage,
                p.is_completed,
                p.completed_at,
                p.points_earned,
                DATEDIFF(c.end_date, NOW()) as days_remaining
             FROM {$this->wpdb->prefix}env_challenge_participants p
             JOIN {$this->wpdb->prefix}env_challenges c ON p.challenge_id = c.challenge_id
             WHERE p.user_id = %d
             ORDER BY p.is_completed ASC, c.end_date ASC",
            $user_id
        ));
        
        // Parse JSON fields
        foreach ($challenges as $challenge) {
            $challenge->requirements = json_decode($challenge->requirements, true);
            $challenge->rewards = json_decode($challenge->rewards, true);
            $challenge->progress = json_decode($challenge->progress, true);
        }
        
        wp_send_json_success($challenges);
    }
}
