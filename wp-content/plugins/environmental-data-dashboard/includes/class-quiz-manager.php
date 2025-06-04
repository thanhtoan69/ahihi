<?php
/**
 * Quiz Management System
 * 
 * Handles quiz creation, management, and user interaction for the Environmental Platform
 * 
 * @package Environmental_Data_Dashboard
 * @since 1.0.0 - Phase 40
 */

if (!defined('ABSPATH')) {
    exit;
}

class Environmental_Quiz_Manager {
    
    private $wpdb;
    private $gamification;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->gamification = new Environmental_Gamification_System();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_start_quiz_session', array($this, 'ajax_start_quiz_session'));
        add_action('wp_ajax_submit_quiz_answer', array($this, 'ajax_submit_quiz_answer'));
        add_action('wp_ajax_complete_quiz_session', array($this, 'ajax_complete_quiz_session'));
        add_action('wp_ajax_get_quiz_categories', array($this, 'ajax_get_quiz_categories'));
        add_action('wp_ajax_get_quiz_questions', array($this, 'ajax_get_quiz_questions'));
        add_action('wp_ajax_get_user_quiz_stats', array($this, 'ajax_get_user_quiz_stats'));
        add_action('wp_ajax_get_quiz_leaderboard', array($this, 'ajax_get_quiz_leaderboard'));
        
        // Admin hooks
        add_action('wp_ajax_save_quiz_question', array($this, 'ajax_save_quiz_question'));
        add_action('wp_ajax_delete_quiz_question', array($this, 'ajax_delete_quiz_question'));
        add_action('wp_ajax_update_quiz_category', array($this, 'ajax_update_quiz_category'));
        add_action('wp_ajax_get_quiz_analytics', array($this, 'ajax_get_quiz_analytics'));
    }
    
    /**
     * Get quiz categories
     */
    public function get_quiz_categories($difficulty = null, $active_only = true) {
        $where_clauses = array();
        
        if ($active_only) {
            $where_clauses[] = "is_active = 1";
        }
        
        if ($difficulty) {
            $where_clauses[] = $this->wpdb->prepare("difficulty_level = %s", $difficulty);
        }
        
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        $sql = "SELECT * FROM quiz_categories {$where_sql} ORDER BY sort_order ASC, category_name ASC";
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * Get quiz questions for a category
     */
    public function get_quiz_questions($category_id, $difficulty = null, $limit = null) {
        $where_clauses = array("qc.category_id = %d", "qq.is_active = 1");
        $values = array($category_id);
        
        if ($difficulty) {
            $where_clauses[] = "qq.difficulty_level = %s";
            $values[] = $difficulty;
        }
        
        $limit_sql = $limit ? $this->wpdb->prepare("LIMIT %d", $limit) : '';
        
        $sql = $this->wpdb->prepare(
            "SELECT qq.*, qc.category_name, qc.points_per_question 
             FROM quiz_questions qq 
             JOIN quiz_categories qc ON qq.category_id = qc.category_id 
             WHERE " . implode(' AND ', $where_clauses) . " 
             ORDER BY RAND() {$limit_sql}",
            ...$values
        );
        
        $questions = $this->wpdb->get_results($sql);
        
        // Parse JSON options for multiple choice questions
        foreach ($questions as $question) {
            if ($question->question_type === 'multiple_choice' && $question->options) {
                $question->parsed_options = json_decode($question->options, true);
            }
        }
        
        return $questions;
    }
    
    /**
     * Start a new quiz session
     */
    public function start_quiz_session($user_id, $category_id, $session_type = 'practice', $question_count = 10) {
        // Validate category
        $category = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM quiz_categories WHERE category_id = %d AND is_active = 1",
            $category_id
        ));
        
        if (!$category) {
            return new WP_Error('invalid_category', 'Invalid quiz category');
        }
        
        // Create quiz session
        $session_data = array(
            'user_id' => $user_id,
            'category_id' => $category_id,
            'session_type' => $session_type,
            'status' => 'started',
            'total_questions' => $question_count,
            'current_question' => 1,
            'score' => 0,
            'started_at' => current_time('mysql'),
            'time_limit_minutes' => $category->time_limit_seconds ? ceil($category->time_limit_seconds / 60) : 30
        );
        
        $result = $this->wpdb->insert('quiz_sessions', $session_data);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create quiz session');
        }
        
        $session_id = $this->wpdb->insert_id;
        
        // Get questions for this session
        $questions = $this->get_quiz_questions($category_id, null, $question_count);
        
        if (empty($questions)) {
            return new WP_Error('no_questions', 'No questions available for this category');
        }
        
        // Store questions in session (for answer validation)
        update_user_meta($user_id, "quiz_session_{$session_id}_questions", wp_json_encode(array_column($questions, 'question_id')));
        
        return array(
            'session_id' => $session_id,
            'category' => $category,
            'questions' => $questions,
            'total_questions' => $question_count,
            'time_limit' => $category->time_limit_seconds
        );
    }
    
    /**
     * Submit quiz answer
     */
    public function submit_quiz_answer($session_id, $question_id, $user_answer, $time_taken = 0, $confidence_level = 'confident') {
        // Validate session
        $session = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM quiz_sessions WHERE session_id = %d AND status = 'started'",
            $session_id
        ));
        
        if (!$session) {
            return new WP_Error('invalid_session', 'Invalid or completed quiz session');
        }
        
        // Get question details
        $question = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM quiz_questions WHERE question_id = %d",
            $question_id
        ));
        
        if (!$question) {
            return new WP_Error('invalid_question', 'Invalid question');
        }
        
        // Check if already answered
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT * FROM quiz_responses WHERE session_id = %d AND question_id = %d",
            $session_id, $question_id
        ));
        
        if ($existing) {
            return new WP_Error('already_answered', 'Question already answered');
        }
        
        // Determine if answer is correct
        $is_correct = false;
        $points_earned = 0;
        
        switch ($question->question_type) {
            case 'multiple_choice':
                $is_correct = ($user_answer === $question->correct_answer);
                break;
            case 'true_false':
                $is_correct = (strtolower($user_answer) === strtolower($question->correct_answer));
                break;
            case 'fill_blank':
                $correct_answers = array_map('trim', explode(',', strtolower($question->correct_answer)));
                $is_correct = in_array(strtolower(trim($user_answer)), $correct_answers);
                break;
        }
        
        if ($is_correct) {
            $category = $this->wpdb->get_row($this->wpdb->prepare(
                "SELECT * FROM quiz_categories WHERE category_id = %d",
                $question->category_id
            ));
            $points_earned = $category ? $category->points_per_question : 10;
            
            // Time bonus (faster answers get bonus points)
            if ($time_taken > 0 && $time_taken < ($category->time_limit_seconds * 0.5)) {
                $points_earned += ceil($points_earned * 0.2); // 20% bonus
            }
        }
        
        // Save response
        $response_data = array(
            'session_id' => $session_id,
            'question_id' => $question_id,
            'user_answer' => $user_answer,
            'is_correct' => $is_correct ? 1 : 0,
            'points_earned' => $points_earned,
            'time_taken_seconds' => $time_taken,
            'question_order' => $session->current_question,
            'confidence_level' => $confidence_level,
            'answered_at' => current_time('mysql')
        );
        
        $result = $this->wpdb->insert('quiz_responses', $response_data);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to save quiz response');
        }
        
        // Update session progress
        $this->wpdb->update(
            'quiz_sessions',
            array(
                'current_question' => $session->current_question + 1,
                'score' => $session->score + $points_earned
            ),
            array('session_id' => $session_id)
        );
        
        return array(
            'is_correct' => $is_correct,
            'points_earned' => $points_earned,
            'correct_answer' => $question->correct_answer,
            'explanation' => $question->explanation,
            'total_score' => $session->score + $points_earned
        );
    }
    
    /**
     * Complete quiz session
     */
    public function complete_quiz_session($session_id, $user_id) {
        // Get session details
        $session = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT qs.*, qc.category_name, qc.difficulty_level 
             FROM quiz_sessions qs 
             JOIN quiz_categories qc ON qs.category_id = qc.category_id 
             WHERE qs.session_id = %d AND qs.user_id = %d",
            $session_id, $user_id
        ));
        
        if (!$session) {
            return new WP_Error('invalid_session', 'Invalid quiz session');
        }
        
        // Get session statistics
        $stats = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT 
                COUNT(*) as total_answered,
                SUM(is_correct) as correct_answers,
                SUM(points_earned) as total_points,
                AVG(time_taken_seconds) as avg_time,
                MIN(answered_at) as first_answer,
                MAX(answered_at) as last_answer
             FROM quiz_responses 
             WHERE session_id = %d",
            $session_id
        ));
        
        // Calculate final score and percentage
        $percentage = $stats->total_answered > 0 ? ($stats->correct_answers / $stats->total_answered) * 100 : 0;
        $completion_time = strtotime($stats->last_answer) - strtotime($stats->first_answer);
        
        // Update session status
        $this->wpdb->update(
            'quiz_sessions',
            array(
                'status' => 'completed',
                'score' => $stats->total_points,
                'percentage' => $percentage,
                'correct_answers' => $stats->correct_answers,
                'total_answered' => $stats->total_answered,
                'completion_time_seconds' => $completion_time,
                'completed_at' => current_time('mysql')
            ),
            array('session_id' => $session_id)
        );
        
        // Award gamification points
        $this->gamification->award_quiz_completion_points($user_id, $session, $stats);
        
        // Check for achievements
        $this->check_quiz_achievements($user_id, $session, $stats);
        
        // Clean up temporary data
        delete_user_meta($user_id, "quiz_session_{$session_id}_questions");
        
        return array(
            'session' => $session,
            'stats' => $stats,
            'percentage' => $percentage,
            'completion_time' => $completion_time,
            'points_earned' => $stats->total_points
        );
    }
    
    /**
     * Check quiz achievements
     */
    private function check_quiz_achievements($user_id, $session, $stats) {
        // Get user's quiz history
        $user_stats = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT 
                COUNT(*) as total_quizzes,
                SUM(score) as total_points,
                AVG(percentage) as avg_percentage,
                MAX(percentage) as best_percentage,
                COUNT(CASE WHEN percentage = 100 THEN 1 END) as perfect_scores
             FROM quiz_sessions 
             WHERE user_id = %d AND status = 'completed'",
            $user_id
        ));
        
        // Check various achievements
        $achievements_to_check = array();
        
        // Perfect score achievement
        if ($stats->correct_answers == $stats->total_answered && $stats->total_answered >= 5) {
            $achievements_to_check[] = 'QUIZ_PERFECT_SCORE';
        }
        
        // Fast completion achievement
        if ($stats->avg_time <= 10 && $stats->correct_answers >= 8) {
            $achievements_to_check[] = 'QUIZ_SPEED_DEMON';
        }
        
        // Quiz master achievements
        if ($user_stats->total_quizzes >= 10) {
            $achievements_to_check[] = 'QUIZ_MASTER_10';
        }
        if ($user_stats->total_quizzes >= 50) {
            $achievements_to_check[] = 'QUIZ_MASTER_50';
        }
        if ($user_stats->total_quizzes >= 100) {
            $achievements_to_check[] = 'QUIZ_MASTER_100';
        }
        
        // High scorer achievement
        if ($user_stats->avg_percentage >= 85) {
            $achievements_to_check[] = 'QUIZ_HIGH_SCORER';
        }
        
        // Perfect streak achievement
        if ($user_stats->perfect_scores >= 5) {
            $achievements_to_check[] = 'QUIZ_PERFECT_STREAK';
        }
        
        // Award achievements
        foreach ($achievements_to_check as $achievement_code) {
            $this->gamification->award_achievement($user_id, $achievement_code);
        }
    }
    
    /**
     * Get user quiz statistics
     */
    public function get_user_quiz_stats($user_id) {
        $stats = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT 
                COUNT(*) as total_sessions,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions,
                AVG(CASE WHEN status = 'completed' THEN percentage END) as avg_percentage,
                MAX(percentage) as best_percentage,
                SUM(CASE WHEN status = 'completed' THEN score END) as total_points,
                COUNT(CASE WHEN percentage = 100 THEN 1 END) as perfect_scores,
                COUNT(DISTINCT category_id) as categories_attempted
             FROM quiz_sessions 
             WHERE user_id = %d",
            $user_id
        ));
        
        // Get category breakdown
        $category_stats = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                qc.category_name,
                qc.difficulty_level,
                COUNT(*) as attempts,
                AVG(qs.percentage) as avg_percentage,
                MAX(qs.percentage) as best_percentage
             FROM quiz_sessions qs
             JOIN quiz_categories qc ON qs.category_id = qc.category_id
             WHERE qs.user_id = %d AND qs.status = 'completed'
             GROUP BY qs.category_id
             ORDER BY avg_percentage DESC",
            $user_id
        ));
        
        // Get recent sessions
        $recent_sessions = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT 
                qs.session_id,
                qs.score,
                qs.percentage,
                qs.completed_at,
                qc.category_name,
                qc.difficulty_level
             FROM quiz_sessions qs
             JOIN quiz_categories qc ON qs.category_id = qc.category_id
             WHERE qs.user_id = %d AND qs.status = 'completed'
             ORDER BY qs.completed_at DESC
             LIMIT 10",
            $user_id
        ));
        
        return array(
            'overall_stats' => $stats,
            'category_stats' => $category_stats,
            'recent_sessions' => $recent_sessions
        );
    }
    
    /**
     * Get quiz leaderboard
     */
    public function get_quiz_leaderboard($category_id = null, $timeframe = 'all', $limit = 20) {
        $where_clauses = array("qs.status = 'completed'");
        $values = array();
        
        if ($category_id) {
            $where_clauses[] = "qs.category_id = %d";
            $values[] = $category_id;
        }
        
        // Add timeframe filter
        switch ($timeframe) {
            case 'week':
                $where_clauses[] = "qs.completed_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $where_clauses[] = "qs.completed_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $where_clauses[] = "qs.completed_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $sql = "SELECT 
                    u.ID as user_id,
                    u.display_name,
                    COUNT(*) as total_quizzes,
                    AVG(qs.percentage) as avg_percentage,
                    SUM(qs.score) as total_points,
                    MAX(qs.percentage) as best_percentage
                FROM quiz_sessions qs
                JOIN {$this->wpdb->users} u ON qs.user_id = u.ID
                WHERE {$where_sql}
                GROUP BY qs.user_id
                ORDER BY total_points DESC, avg_percentage DESC
                LIMIT %d";
        
        $values[] = $limit;
        
        if (!empty($values)) {
            $sql = $this->wpdb->prepare($sql, ...$values);
        }
        
        return $this->wpdb->get_results($sql);
    }
    
    /**
     * AJAX: Start quiz session
     */
    public function ajax_start_quiz_session() {
        if (!check_ajax_referer('env_quiz_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        $category_id = intval($_POST['category_id']);
        $session_type = sanitize_text_field($_POST['session_type']) ?: 'practice';
        $question_count = intval($_POST['question_count']) ?: 10;
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $result = $this->start_quiz_session($user_id, $category_id, $session_type, $question_count);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Submit quiz answer
     */
    public function ajax_submit_quiz_answer() {
        if (!check_ajax_referer('env_quiz_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        $session_id = intval($_POST['session_id']);
        $question_id = intval($_POST['question_id']);
        $user_answer = sanitize_text_field($_POST['user_answer']);
        $time_taken = intval($_POST['time_taken']);
        $confidence_level = sanitize_text_field($_POST['confidence_level']);
        
        $result = $this->submit_quiz_answer($session_id, $question_id, $user_answer, $time_taken, $confidence_level);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Complete quiz session
     */
    public function ajax_complete_quiz_session() {
        if (!check_ajax_referer('env_quiz_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        $session_id = intval($_POST['session_id']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $result = $this->complete_quiz_session($session_id, $user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Get quiz categories
     */
    public function ajax_get_quiz_categories() {
        $difficulty = sanitize_text_field($_GET['difficulty']) ?: null;
        $categories = $this->get_quiz_categories($difficulty);
        wp_send_json_success($categories);
    }
    
    /**
     * AJAX: Get user quiz stats
     */
    public function ajax_get_user_quiz_stats() {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $stats = $this->get_user_quiz_stats($user_id);
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Get quiz leaderboard
     */
    public function ajax_get_quiz_leaderboard() {
        $category_id = intval($_GET['category_id']) ?: null;
        $timeframe = sanitize_text_field($_GET['timeframe']) ?: 'all';
        $limit = intval($_GET['limit']) ?: 20;
        
        $leaderboard = $this->get_quiz_leaderboard($category_id, $timeframe, $limit);
        wp_send_json_success($leaderboard);
    }
}
