<?php
/**
 * Waste Classification Interface
 * 
 * Handles the frontend interface for waste classification,
 * image upload, results display, and user interactions.
 * 
 * @package Environmental_Data_Dashboard
 * @since 1.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Waste_Classification_Interface {
    
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('waste_classifier', array($this, 'waste_classifier_shortcode'));
        add_shortcode('waste_classification_history', array($this, 'classification_history_shortcode'));
        add_shortcode('waste_classification_stats', array($this, 'classification_stats_shortcode'));
        add_shortcode('waste_classification_leaderboard', array($this, 'classification_leaderboard_shortcode'));
        
        // AJAX handlers for interface interactions
        add_action('wp_ajax_get_classification_tips', array($this, 'ajax_get_classification_tips'));
        add_action('wp_ajax_nopriv_get_classification_tips', array($this, 'ajax_get_classification_tips'));
        add_action('wp_ajax_save_classification_preference', array($this, 'ajax_save_classification_preference'));
    }
    
    /**
     * Enqueue scripts and styles for waste classification interface
     */
    public function enqueue_scripts() {
        // Only enqueue on pages with waste classification shortcodes
        global $post;
        if (!$post || !has_shortcode($post->post_content, 'waste_classifier')) {
            return;
        }
        
        wp_enqueue_media(); // For image upload
        
        wp_enqueue_style(
            'waste-classification-css',
            ENV_DASHBOARD_PLUGIN_URL . 'assets/css/waste-classification.css',
            array('environmental-dashboard-css'),
            ENV_DASHBOARD_VERSION
        );
        
        wp_enqueue_script(
            'waste-classification-js',
            ENV_DASHBOARD_PLUGIN_URL . 'assets/js/waste-classification.js',
            array('jquery', 'wp-util', 'environmental-dashboard-js'),
            ENV_DASHBOARD_VERSION,
            true
        );
        
        // Localize script with data
        wp_localize_script('waste-classification-js', 'wasteClassificationData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('environmental_dashboard_nonce'),
            'maxFileSize' => 5242880, // 5MB
            'allowedTypes' => array('image/jpeg', 'image/png', 'image/webp'),
            'messages' => array(
                'uploading' => __('Analyzing image...', 'env-data-dashboard'),
                'success' => __('Classification complete!', 'env-data-dashboard'),
                'error' => __('Classification failed. Please try again.', 'env-data-dashboard'),
                'invalidFile' => __('Please select a valid image file (JPG, PNG, WebP)', 'env-data-dashboard'),
                'fileTooLarge' => __('File too large. Maximum size is 5MB.', 'env-data-dashboard'),
                'confirmFeedback' => __('Are you sure about this feedback?', 'env-data-dashboard')
            ),
            'categories' => AI_Service_Manager::get_instance()->get_waste_categories()
        ));
    }
    
    /**
     * Waste classifier shortcode
     */
    public function waste_classifier_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'full', // full, compact, minimal
            'show_tips' => 'true',
            'show_history' => 'true',
            'show_gamification' => 'true'
        ), $atts);
        
        ob_start();
        $this->render_waste_classifier($atts);
        return ob_get_clean();
    }
    
    /**
     * Classification history shortcode
     */
    public function classification_history_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '10',
            'user_id' => get_current_user_id(),
            'show_images' => 'true'
        ), $atts);
        
        ob_start();
        $this->render_classification_history($atts);
        return ob_get_clean();
    }
    
    /**
     * Classification statistics shortcode
     */
    public function classification_stats_shortcode($atts) {
        $atts = shortcode_atts(array(
            'chart_type' => 'pie', // pie, bar, line
            'show_accuracy' => 'true',
            'time_period' => '30' // days
        ), $atts);
        
        ob_start();
        $this->render_classification_stats($atts);
        return ob_get_clean();
    }
    
    /**
     * Classification leaderboard shortcode
     */
    public function classification_leaderboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => '10',
            'period' => 'monthly', // weekly, monthly, all-time
            'show_avatars' => 'true'
        ), $atts);
        
        ob_start();
        $this->render_classification_leaderboard($atts);
        return ob_get_clean();
    }
    
    /**
     * Render waste classifier interface
     */
    private function render_waste_classifier($atts) {
        $style = $atts['style'];
        $show_tips = filter_var($atts['show_tips'], FILTER_VALIDATE_BOOLEAN);
        $show_history = filter_var($atts['show_history'], FILTER_VALIDATE_BOOLEAN);
        $show_gamification = filter_var($atts['show_gamification'], FILTER_VALIDATE_BOOLEAN);
        
        ?>
        <div class="waste-classifier-container" data-style="<?php echo esc_attr($style); ?>">
            
            <?php if ($show_gamification): ?>
            <div class="classification-gamification">
                <div class="user-stats">
                    <div class="stat-item">
                        <span class="stat-icon">🏆</span>
                        <span class="stat-value" id="user-classification-count">0</span>
                        <span class="stat-label"><?php _e('Classifications', 'env-data-dashboard'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-icon">🎯</span>
                        <span class="stat-value" id="user-accuracy-rate">0%</span>
                        <span class="stat-label"><?php _e('Accuracy', 'env-data-dashboard'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-icon">⭐</span>
                        <span class="stat-value" id="user-points">0</span>
                        <span class="stat-label"><?php _e('Points', 'env-data-dashboard'); ?></span>
                    </div>
                </div>
                
                <div class="achievement-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" id="classification-progress"></div>
                    </div>
                    <span class="progress-text"><?php _e('Next Badge: 5 more classifications', 'env-data-dashboard'); ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="classification-upload-area">
                <div class="upload-zone" id="waste-upload-zone">
                    <div class="upload-content">
                        <span class="upload-icon">📷</span>
                        <h3><?php _e('Upload Waste Image', 'env-data-dashboard'); ?></h3>
                        <p><?php _e('Take a photo or select an image of waste to classify', 'env-data-dashboard'); ?></p>
                        
                        <div class="upload-buttons">
                            <button type="button" class="btn btn-camera" id="camera-capture">
                                <span class="icon">📸</span>
                                <?php _e('Take Photo', 'env-data-dashboard'); ?>
                            </button>
                            <button type="button" class="btn btn-upload" id="file-upload">
                                <span class="icon">📁</span>
                                <?php _e('Upload Image', 'env-data-dashboard'); ?>
                            </button>
                        </div>
                        
                        <input type="file" id="image-file-input" accept="image/*" style="display: none;">
                        <video id="camera-preview" style="display: none;" autoplay playsinline></video>
                        <canvas id="camera-canvas" style="display: none;"></canvas>
                    </div>
                    
                    <div class="upload-progress" id="upload-progress" style="display: none;">
                        <div class="progress-spinner"></div>
                        <p><?php _e('Analyzing image...', 'env-data-dashboard'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="classification-result" id="classification-result" style="display: none;">
                <div class="result-header">
                    <h3><?php _e('Classification Result', 'env-data-dashboard'); ?></h3>
                    <div class="confidence-score">
                        <span class="confidence-label"><?php _e('Confidence:', 'env-data-dashboard'); ?></span>
                        <span class="confidence-value" id="confidence-percentage">85%</span>
                        <div class="confidence-bar">
                            <div class="confidence-fill" id="confidence-fill"></div>
                        </div>
                    </div>
                </div>
                
                <div class="result-content">
                    <div class="result-image">
                        <img id="classified-image" src="" alt="Classified waste">
                    </div>
                    
                    <div class="result-details">
                        <div class="waste-category">
                            <span class="category-icon" id="category-icon">♻️</span>
                            <div class="category-info">
                                <h4 class="category-name" id="category-name"><?php _e('Recyclable', 'env-data-dashboard'); ?></h4>
                                <p class="category-description" id="category-description"></p>
                            </div>
                        </div>
                        
                        <div class="disposal-instructions">
                            <h5><?php _e('Disposal Instructions', 'env-data-dashboard'); ?></h5>
                            <p class="disposal-action" id="disposal-action"></p>
                            <ul class="disposal-tips" id="disposal-tips"></ul>
                        </div>
                        
                        <div class="environmental-impact">
                            <h5><?php _e('Environmental Impact', 'env-data-dashboard'); ?></h5>
                            <p class="impact-statement" id="impact-statement"></p>
                        </div>
                    </div>
                </div>
                
                <div class="result-actions">
                    <div class="feedback-section">
                        <h5><?php _e('Is this classification correct?', 'env-data-dashboard'); ?></h5>
                        <div class="feedback-buttons">
                            <button type="button" class="btn btn-success" id="feedback-correct">
                                <span class="icon">✅</span>
                                <?php _e('Correct', 'env-data-dashboard'); ?>
                            </button>
                            <button type="button" class="btn btn-error" id="feedback-incorrect">
                                <span class="icon">❌</span>
                                <?php _e('Incorrect', 'env-data-dashboard'); ?>
                            </button>
                        </div>
                        
                        <div class="correction-form" id="correction-form" style="display: none;">
                            <label for="correct-category"><?php _e('What should it be?', 'env-data-dashboard'); ?></label>
                            <select id="correct-category">
                                <option value=""><?php _e('Select correct category', 'env-data-dashboard'); ?></option>
                                <option value="recyclable"><?php _e('Recyclable', 'env-data-dashboard'); ?></option>
                                <option value="organic"><?php _e('Organic', 'env-data-dashboard'); ?></option>
                                <option value="hazardous"><?php _e('Hazardous', 'env-data-dashboard'); ?></option>
                                <option value="electronic"><?php _e('Electronic', 'env-data-dashboard'); ?></option>
                                <option value="general"><?php _e('General Waste', 'env-data-dashboard'); ?></option>
                            </select>
                            
                            <textarea id="feedback-comment" placeholder="<?php _e('Additional comments (optional)', 'env-data-dashboard'); ?>"></textarea>
                            
                            <button type="button" class="btn btn-primary" id="submit-correction">
                                <?php _e('Submit Correction', 'env-data-dashboard'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="button" class="btn btn-secondary" id="classify-another">
                            <?php _e('Classify Another', 'env-data-dashboard'); ?>
                        </button>
                        <button type="button" class="btn btn-primary" id="share-result">
                            <span class="icon">🔗</span>
                            <?php _e('Share Result', 'env-data-dashboard'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if ($show_tips): ?>
            <div class="classification-tips">
                <h4><?php _e('Classification Tips', 'env-data-dashboard'); ?></h4>
                <div class="tips-grid">
                    <div class="tip-item">
                        <span class="tip-icon">💡</span>
                        <p><?php _e('Ensure good lighting for better accuracy', 'env-data-dashboard'); ?></p>
                    </div>
                    <div class="tip-item">
                        <span class="tip-icon">🎯</span>
                        <p><?php _e('Focus on one item at a time', 'env-data-dashboard'); ?></p>
                    </div>
                    <div class="tip-item">
                        <span class="tip-icon">📏</span>
                        <p><?php _e('Fill the frame with the waste item', 'env-data-dashboard'); ?></p>
                    </div>
                    <div class="tip-item">
                        <span class="tip-icon">🧽</span>
                        <p><?php _e('Clear images work best', 'env-data-dashboard'); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($show_history && is_user_logged_in()): ?>
            <div class="classification-history-preview">
                <h4><?php _e('Recent Classifications', 'env-data-dashboard'); ?></h4>
                <div class="history-grid" id="recent-classifications">
                    <!-- Populated via AJAX -->
                </div>
                <a href="#" class="view-all-link" id="view-all-history">
                    <?php _e('View All History', 'env-data-dashboard'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Modal for detailed history -->
        <div class="modal" id="history-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><?php _e('Classification History', 'env-data-dashboard'); ?></h3>
                    <button type="button" class="modal-close" id="close-history-modal">&times;</button>
                </div>
                <div class="modal-body" id="history-modal-content">
                    <!-- Populated via AJAX -->
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render classification history
     */
    private function render_classification_history($atts) {
        if (!is_user_logged_in()) {
            echo '<p>' . __('Please log in to view your classification history.', 'env-data-dashboard') . '</p>';
            return;
        }
        
        $limit = intval($atts['limit']);
        $user_id = intval($atts['user_id']);
        $show_images = filter_var($atts['show_images'], FILTER_VALIDATE_BOOLEAN);
        
        $history = AI_Service_Manager::get_instance()->get_user_classification_history($user_id, $limit);
        
        ?>
        <div class="classification-history">
            <div class="history-header">
                <h3><?php _e('Your Classification History', 'env-data-dashboard'); ?></h3>
                <div class="history-stats">
                    <span class="total-count"><?php printf(__('Total: %d classifications', 'env-data-dashboard'), count($history)); ?></span>
                </div>
            </div>
            
            <?php if (empty($history)): ?>
                <div class="no-history">
                    <span class="empty-icon">📱</span>
                    <h4><?php _e('No Classifications Yet', 'env-data-dashboard'); ?></h4>
                    <p><?php _e('Start classifying waste items to see your history here.', 'env-data-dashboard'); ?></p>
                </div>
            <?php else: ?>
                <div class="history-list">
                    <?php foreach ($history as $item): ?>
                        <div class="history-item">
                            <?php if ($show_images): ?>
                                <div class="history-image">
                                    <img src="<?php echo esc_url($item->image_url); ?>" alt="<?php echo esc_attr($item->description); ?>">
                                </div>
                            <?php endif; ?>
                            
                            <div class="history-details">
                                <div class="history-category">
                                    <span class="category-badge category-<?php echo esc_attr($item->category); ?>">
                                        <?php echo esc_html($item->category_info['icon'] ?? '🗑️'); ?>
                                        <?php echo esc_html($item->category_info['name'] ?? ucfirst($item->category)); ?>
                                    </span>
                                    <span class="confidence-badge"><?php echo round($item->confidence * 100); ?>%</span>
                                </div>
                                
                                <p class="history-description"><?php echo esc_html($item->description); ?></p>
                                
                                <div class="history-meta">
                                    <span class="classification-date">
                                        <?php echo human_time_diff(strtotime($item->created_at), current_time('timestamp')) . ' ' . __('ago', 'env-data-dashboard'); ?>
                                    </span>
                                    <?php if ($item->is_verified): ?>
                                        <span class="verified-badge">✓ <?php _e('Verified', 'env-data-dashboard'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="history-actions">
                                <button type="button" class="btn-icon" data-action="view-details" data-id="<?php echo $item->id; ?>">
                                    <span class="icon">👁️</span>
                                </button>
                                <button type="button" class="btn-icon" data-action="share" data-id="<?php echo $item->id; ?>">
                                    <span class="icon">🔗</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($history) >= $limit): ?>
                    <div class="load-more-container">
                        <button type="button" class="btn btn-secondary" id="load-more-history" data-offset="<?php echo $limit; ?>">
                            <?php _e('Load More', 'env-data-dashboard'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render classification statistics
     */
    private function render_classification_stats($atts) {
        $chart_type = $atts['chart_type'];
        $show_accuracy = filter_var($atts['show_accuracy'], FILTER_VALIDATE_BOOLEAN);
        $time_period = intval($atts['time_period']);
        
        ?>
        <div class="classification-stats">
            <div class="stats-header">
                <h3><?php _e('Classification Statistics', 'env-data-dashboard'); ?></h3>
                <div class="stats-controls">
                    <select id="stats-period">
                        <option value="7" <?php selected($time_period, 7); ?>><?php _e('Last 7 days', 'env-data-dashboard'); ?></option>
                        <option value="30" <?php selected($time_period, 30); ?>><?php _e('Last 30 days', 'env-data-dashboard'); ?></option>
                        <option value="90" <?php selected($time_period, 90); ?>><?php _e('Last 90 days', 'env-data-dashboard'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="stats-summary">
                <div class="summary-card">
                    <h4 class="summary-number" id="total-classifications">0</h4>
                    <p class="summary-label"><?php _e('Total Classifications', 'env-data-dashboard'); ?></p>
                </div>
                
                <?php if ($show_accuracy): ?>
                <div class="summary-card">
                    <h4 class="summary-number" id="accuracy-rate">0%</h4>
                    <p class="summary-label"><?php _e('Accuracy Rate', 'env-data-dashboard'); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="summary-card">
                    <h4 class="summary-number" id="most-common-category">-</h4>
                    <p class="summary-label"><?php _e('Most Common Category', 'env-data-dashboard'); ?></p>
                </div>
                
                <div class="summary-card">
                    <h4 class="summary-number" id="environmental-impact">0</h4>
                    <p class="summary-label"><?php _e('Items Properly Classified', 'env-data-dashboard'); ?></p>
                </div>
            </div>
            
            <div class="stats-charts">
                <div class="chart-container">
                    <h4><?php _e('Categories Distribution', 'env-data-dashboard'); ?></h4>
                    <canvas id="category-distribution-chart" width="400" height="300"></canvas>
                </div>
                
                <div class="chart-container">
                    <h4><?php _e('Classification Trend', 'env-data-dashboard'); ?></h4>
                    <canvas id="classification-trend-chart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render classification leaderboard
     */
    private function render_classification_leaderboard($atts) {
        $limit = intval($atts['limit']);
        $period = $atts['period'];
        $show_avatars = filter_var($atts['show_avatars'], FILTER_VALIDATE_BOOLEAN);
        
        $leaderboard_data = $this->get_leaderboard_data($period, $limit);
        
        ?>
        <div class="classification-leaderboard">
            <div class="leaderboard-header">
                <h3><?php _e('Classification Champions', 'env-data-dashboard'); ?></h3>
                <div class="period-selector">
                    <button type="button" class="period-btn <?php echo $period === 'weekly' ? 'active' : ''; ?>" data-period="weekly">
                        <?php _e('Weekly', 'env-data-dashboard'); ?>
                    </button>
                    <button type="button" class="period-btn <?php echo $period === 'monthly' ? 'active' : ''; ?>" data-period="monthly">
                        <?php _e('Monthly', 'env-data-dashboard'); ?>
                    </button>
                    <button type="button" class="period-btn <?php echo $period === 'all-time' ? 'active' : ''; ?>" data-period="all-time">
                        <?php _e('All Time', 'env-data-dashboard'); ?>
                    </button>
                </div>
            </div>
            
            <?php if (empty($leaderboard_data)): ?>
                <div class="no-data">
                    <span class="empty-icon">🏆</span>
                    <h4><?php _e('No Data Yet', 'env-data-dashboard'); ?></h4>
                    <p><?php _e('Be the first to start classifying waste!', 'env-data-dashboard'); ?></p>
                </div>
            <?php else: ?>
                <div class="leaderboard-list">
                    <?php foreach ($leaderboard_data as $index => $user): ?>
                        <div class="leaderboard-item <?php echo $index < 3 ? 'top-three' : ''; ?>" data-rank="<?php echo $index + 1; ?>">
                            <div class="rank-badge">
                                <?php if ($index === 0): ?>
                                    <span class="rank-icon gold">🥇</span>
                                <?php elseif ($index === 1): ?>
                                    <span class="rank-icon silver">🥈</span>
                                <?php elseif ($index === 2): ?>
                                    <span class="rank-icon bronze">🥉</span>
                                <?php else: ?>
                                    <span class="rank-number"><?php echo $index + 1; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="user-info">
                                <?php if ($show_avatars): ?>
                                    <div class="user-avatar">
                                        <?php echo get_avatar($user->user_id, 40); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="user-details">
                                    <h4 class="user-name"><?php echo esc_html($user->display_name); ?></h4>
                                    <p class="user-stats">
                                        <?php printf(
                                            __('%d classifications • %d%% accuracy', 'env-data-dashboard'),
                                            $user->classification_count,
                                            round($user->accuracy_rate * 100)
                                        ); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="user-score">
                                <span class="score-value"><?php echo number_format($user->points); ?></span>
                                <span class="score-label"><?php _e('pts', 'env-data-dashboard'); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (is_user_logged_in()): ?>
                <div class="user-position">
                    <h4><?php _e('Your Position', 'env-data-dashboard'); ?></h4>
                    <div class="position-info" id="user-leaderboard-position">
                        <!-- Populated via AJAX -->
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Get leaderboard data
     */
    private function get_leaderboard_data($period, $limit) {
        global $wpdb;
        
        $classifications_table = $wpdb->prefix . 'env_ai_classifications';
        $feedback_table = $wpdb->prefix . 'env_ai_feedback';
        $users_table = $wpdb->prefix . 'users';
        
        // Determine date filter based on period
        $date_filter = '';
        switch ($period) {
            case 'weekly':
                $date_filter = "AND c.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'monthly':
                $date_filter = "AND c.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'all-time':
            default:
                $date_filter = '';
                break;
        }
        
        $query = "
            SELECT 
                u.ID as user_id,
                u.display_name,
                COUNT(c.id) as classification_count,
                COALESCE(AVG(f.is_correct), 0.8) as accuracy_rate,
                (COUNT(c.id) * 10 + COALESCE(SUM(f.is_correct), COUNT(c.id) * 0.8) * 5) as points
            FROM {$users_table} u
            INNER JOIN {$classifications_table} c ON u.ID = c.user_id
            LEFT JOIN {$feedback_table} f ON c.id = f.classification_id
            WHERE c.user_id > 0 {$date_filter}
            GROUP BY u.ID
            ORDER BY points DESC, classification_count DESC
            LIMIT %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($query, $limit));
    }
    
    /**
     * AJAX handler for getting classification tips
     */
    public function ajax_get_classification_tips() {
        check_ajax_referer('environmental_dashboard_nonce', 'nonce');
        
        $tips = array(
            array(
                'icon' => '💡',
                'title' => __('Lighting Matters', 'env-data-dashboard'),
                'description' => __('Use natural light or bright indoor lighting for best results.', 'env-data-dashboard')
            ),
            array(
                'icon' => '🎯',
                'title' => __('Focus on One Item', 'env-data-dashboard'),
                'description' => __('Classify one waste item at a time for more accurate results.', 'env-data-dashboard')
            ),
            array(
                'icon' => '📏',
                'title' => __('Fill the Frame', 'env-data-dashboard'),
                'description' => __('Make sure the waste item takes up most of the image.', 'env-data-dashboard')
            ),
            array(
                'icon' => '🧽',
                'title' => __('Clear Images', 'env-data-dashboard'),
                'description' => __('Avoid blurry or dark images for better classification accuracy.', 'env-data-dashboard')
            ),
            array(
                'icon' => '🏷️',
                'title' => __('Show Labels', 'env-data-dashboard'),
                'description' => __('Include any recycling symbols or labels in the image.', 'env-data-dashboard')
            ),
            array(
                'icon' => '📱',
                'title' => __('Multiple Angles', 'env-data-dashboard'),
                'description' => __('Try different angles if the first classification seems uncertain.', 'env-data-dashboard')
            )
        );
        
        wp_send_json_success(array('tips' => $tips));
    }
    
    /**
     * AJAX handler for saving classification preferences
     */
    public function ajax_save_classification_preference() {
        check_ajax_referer('environmental_dashboard_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please log in to save preferences.', 'env-data-dashboard')));
        }
        
        $user_id = get_current_user_id();
        $preference_type = sanitize_text_field($_POST['preference_type'] ?? '');
        $preference_value = sanitize_text_field($_POST['preference_value'] ?? '');
        
        if (empty($preference_type)) {
            wp_send_json_error(array('message' => __('Invalid preference type.', 'env-data-dashboard')));
        }
        
        $preferences = get_user_meta($user_id, 'waste_classification_preferences', true) ?: array();
        $preferences[$preference_type] = $preference_value;
        
        $result = update_user_meta($user_id, 'waste_classification_preferences', $preferences);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Preference saved successfully.', 'env-data-dashboard')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save preference.', 'env-data-dashboard')));
        }
    }
    
    /**
     * Get user's classification badges and achievements
     */
    public function get_user_achievements($user_id) {
        if (!$user_id) return array();
        
        global $wpdb;
        
        $classifications_table = $wpdb->prefix . 'env_ai_classifications';
        $feedback_table = $wpdb->prefix . 'env_ai_feedback';
        
        // Get user stats
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(c.id) as total_classifications,
                COUNT(DISTINCT c.category) as categories_used,
                AVG(c.confidence) as avg_confidence,
                COALESCE(AVG(f.is_correct), 0.85) as accuracy_rate
             FROM {$classifications_table} c
             LEFT JOIN {$feedback_table} f ON c.id = f.classification_id
             WHERE c.user_id = %d",
            $user_id
        ));
        
        $achievements = array();
        
        // Classification count badges
        if ($stats->total_classifications >= 1) {
            $achievements[] = array('badge' => 'first_classification', 'name' => __('First Steps', 'env-data-dashboard'), 'icon' => '🌱');
        }
        if ($stats->total_classifications >= 10) {
            $achievements[] = array('badge' => 'classifier_novice', 'name' => __('Novice Classifier', 'env-data-dashboard'), 'icon' => '📚');
        }
        if ($stats->total_classifications >= 50) {
            $achievements[] = array('badge' => 'classifier_expert', 'name' => __('Expert Classifier', 'env-data-dashboard'), 'icon' => '🏆');
        }
        if ($stats->total_classifications >= 100) {
            $achievements[] = array('badge' => 'classification_master', 'name' => __('Classification Master', 'env-data-dashboard'), 'icon' => '👑');
        }
        
        // Accuracy badges
        if ($stats->accuracy_rate >= 0.9) {
            $achievements[] = array('badge' => 'accuracy_champion', 'name' => __('Accuracy Champion', 'env-data-dashboard'), 'icon' => '🎯');
        }
        
        // Category diversity badges
        if ($stats->categories_used >= 3) {
            $achievements[] = array('badge' => 'category_explorer', 'name' => __('Category Explorer', 'env-data-dashboard'), 'icon' => '🔍');
        }
        if ($stats->categories_used >= 5) {
            $achievements[] = array('badge' => 'waste_specialist', 'name' => __('Waste Specialist', 'env-data-dashboard'), 'icon' => '⚡');
        }
        
        return $achievements;
    }
}

// Initialize the Waste Classification Interface
Waste_Classification_Interface::get_instance();
