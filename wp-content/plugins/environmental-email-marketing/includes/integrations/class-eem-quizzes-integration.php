<?php
/**
 * Quizzes Integration Class
 *
 * Handles integration with environmental quizzes, assessments, and educational content
 * for automated email campaigns, knowledge tracking, and engagement scoring.
 *
 * @package Environmental_Email_Marketing
 * @subpackage Integrations
 */

if (!defined('ABSPATH')) {
    exit;
}

class EEM_Quizzes_Integration {

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Database manager instance
     */
    private $db_manager;

    /**
     * Subscriber manager instance
     */
    private $subscriber_manager;

    /**
     * Campaign manager instance
     */
    private $campaign_manager;

    /**
     * Automation engine instance
     */
    private $automation_engine;

    /**
     * Analytics tracker instance
     */
    private $analytics_tracker;

    /**
     * Quiz post type
     */
    const QUIZ_POST_TYPE = 'env_quiz';

    /**
     * Question post type
     */
    const QUESTION_POST_TYPE = 'env_question';

    /**
     * Quiz attempts meta key
     */
    const ATTEMPTS_META_KEY = '_quiz_attempts';

    /**
     * Environmental knowledge score meta key
     */
    const KNOWLEDGE_SCORE_META_KEY = '_environmental_knowledge_score';

    /**
     * Get instance
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
        $this->init_dependencies();
        $this->init_hooks();
    }

    /**
     * Initialize dependencies
     */
    private function init_dependencies() {
        $this->db_manager = EEM_Database_Manager::get_instance();
        $this->subscriber_manager = EEM_Subscriber_Manager::get_instance();
        $this->campaign_manager = EEM_Campaign_Manager::get_instance();
        $this->automation_engine = EEM_Automation_Engine::get_instance();
        $this->analytics_tracker = EEM_Analytics_Tracker::get_instance();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // WordPress hooks
        add_action('init', array($this, 'register_post_types'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_quiz_meta'));

        // AJAX handlers
        add_action('wp_ajax_eem_submit_quiz', array($this, 'handle_quiz_submission'));
        add_action('wp_ajax_nopriv_eem_submit_quiz', array($this, 'handle_quiz_submission'));
        add_action('wp_ajax_eem_get_quiz_results', array($this, 'get_quiz_results'));
        add_action('wp_ajax_nopriv_eem_get_quiz_results', array($this, 'get_quiz_results'));

        // Automation triggers
        add_action('eem_quiz_completed', array($this, 'trigger_completion_automation'), 10, 3);
        add_action('eem_quiz_passed', array($this, 'trigger_passed_automation'), 10, 3);
        add_action('eem_quiz_failed', array($this, 'trigger_failed_automation'), 10, 3);
        add_action('eem_high_score_achieved', array($this, 'trigger_high_score_automation'), 10, 3);

        // Shortcodes
        add_shortcode('environmental_quiz', array($this, 'quiz_shortcode'));
        add_shortcode('quiz_leaderboard', array($this, 'leaderboard_shortcode'));
        add_shortcode('user_quiz_stats', array($this, 'user_stats_shortcode'));

        // Scheduled events
        add_action('eem_process_quiz_follow_ups', array($this, 'process_quiz_follow_ups'));
        add_action('eem_generate_quiz_reports', array($this, 'generate_quiz_reports'));

        // WP Cron schedules
        if (!wp_next_scheduled('eem_process_quiz_follow_ups')) {
            wp_schedule_event(time(), 'daily', 'eem_process_quiz_follow_ups');
        }
        if (!wp_next_scheduled('eem_generate_quiz_reports')) {
            wp_schedule_event(time(), 'weekly', 'eem_generate_quiz_reports');
        }
    }

    /**
     * Register post types
     */
    public function register_post_types() {
        // Register quiz post type
        $quiz_labels = array(
            'name'               => 'Environmental Quizzes',
            'singular_name'      => 'Environmental Quiz',
            'menu_name'          => 'Quizzes',
            'add_new'            => 'Add New Quiz',
            'add_new_item'       => 'Add New Environmental Quiz',
            'edit_item'          => 'Edit Quiz',
            'new_item'           => 'New Quiz',
            'view_item'          => 'View Quiz',
            'search_items'       => 'Search Quizzes',
            'not_found'          => 'No quizzes found',
            'not_found_in_trash' => 'No quizzes found in trash'
        );

        $quiz_args = array(
            'labels'              => $quiz_labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'quizzes'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 26,
            'menu_icon'           => 'dashicons-awards',
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest'        => true
        );

        register_post_type(self::QUIZ_POST_TYPE, $quiz_args);

        // Register question post type
        $question_labels = array(
            'name'               => 'Quiz Questions',
            'singular_name'      => 'Quiz Question',
            'menu_name'          => 'Questions',
            'add_new'            => 'Add New Question',
            'add_new_item'       => 'Add New Quiz Question',
            'edit_item'          => 'Edit Question',
            'new_item'           => 'New Question',
            'view_item'          => 'View Question',
            'search_items'       => 'Search Questions',
            'not_found'          => 'No questions found',
            'not_found_in_trash' => 'No questions found in trash'
        );

        $question_args = array(
            'labels'              => $question_labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => 'edit.php?post_type=' . self::QUIZ_POST_TYPE,
            'query_var'           => true,
            'capability_type'     => 'post',
            'has_archive'         => false,
            'hierarchical'        => false,
            'supports'            => array('title', 'editor', 'custom-fields'),
            'show_in_rest'        => true
        );

        register_post_type(self::QUESTION_POST_TYPE, $question_args);

        // Register taxonomies
        register_taxonomy('quiz_category', array(self::QUIZ_POST_TYPE, self::QUESTION_POST_TYPE), array(
            'hierarchical'      => true,
            'labels'            => array(
                'name'              => 'Quiz Categories',
                'singular_name'     => 'Quiz Category',
                'search_items'      => 'Search Categories',
                'all_items'         => 'All Categories',
                'parent_item'       => 'Parent Category',
                'parent_item_colon' => 'Parent Category:',
                'edit_item'         => 'Edit Category',
                'update_item'       => 'Update Category',
                'add_new_item'      => 'Add New Category',
                'new_item_name'     => 'New Category Name',
                'menu_name'         => 'Categories',
            ),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'quiz-category'),
            'show_in_rest'      => true
        ));

        register_taxonomy('quiz_difficulty', self::QUIZ_POST_TYPE, array(
            'hierarchical'      => false,
            'labels'            => array(
                'name'                       => 'Difficulty Levels',
                'singular_name'              => 'Difficulty Level',
                'search_items'               => 'Search Levels',
                'popular_items'              => 'Popular Levels',
                'all_items'                  => 'All Levels',
                'edit_item'                  => 'Edit Level',
                'update_item'                => 'Update Level',
                'add_new_item'               => 'Add New Level',
                'new_item_name'              => 'New Level Name',
                'separate_items_with_commas' => 'Separate levels with commas',
                'add_or_remove_items'        => 'Add or remove levels',
                'choose_from_most_used'      => 'Choose from the most used levels',
                'menu_name'                  => 'Difficulty',
            ),
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'quiz-difficulty'),
            'show_in_rest'      => true
        ));
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Quiz meta boxes
        add_meta_box(
            'quiz-settings',
            'Quiz Settings',
            array($this, 'render_quiz_settings_meta_box'),
            self::QUIZ_POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'quiz-questions',
            'Quiz Questions',
            array($this, 'render_quiz_questions_meta_box'),
            self::QUIZ_POST_TYPE,
            'normal',
            'default'
        );

        add_meta_box(
            'quiz-email-settings',
            'Email Marketing Settings',
            array($this, 'render_quiz_email_settings_meta_box'),
            self::QUIZ_POST_TYPE,
            'side',
            'default'
        );

        add_meta_box(
            'quiz-results',
            'Quiz Results & Analytics',
            array($this, 'render_quiz_results_meta_box'),
            self::QUIZ_POST_TYPE,
            'normal',
            'default'
        );

        // Question meta boxes
        add_meta_box(
            'question-details',
            'Question Details',
            array($this, 'render_question_details_meta_box'),
            self::QUESTION_POST_TYPE,
            'normal',
            'high'
        );
    }

    /**
     * Render quiz settings meta box
     */
    public function render_quiz_settings_meta_box($post) {
        wp_nonce_field('save_quiz_meta', 'quiz_meta_nonce');

        $time_limit = get_post_meta($post->ID, '_time_limit', true);
        $passing_score = get_post_meta($post->ID, '_passing_score', true) ?: 70;
        $max_attempts = get_post_meta($post->ID, '_max_attempts', true) ?: 3;
        $show_results = get_post_meta($post->ID, '_show_results_immediately', true);
        $randomize_questions = get_post_meta($post->ID, '_randomize_questions', true);
        $environmental_focus = get_post_meta($post->ID, '_environmental_focus', true);
        $knowledge_points = get_post_meta($post->ID, '_knowledge_points', true) ?: 10;

        ?>
        <table class="form-table">
            <tr>
                <th><label for="time_limit">Time Limit (minutes)</label></th>
                <td>
                    <input type="number" id="time_limit" name="time_limit" value="<?php echo esc_attr($time_limit); ?>" class="small-text" min="0" />
                    <p class="description">0 for no time limit</p>
                </td>
            </tr>
            <tr>
                <th><label for="passing_score">Passing Score (%)</label></th>
                <td><input type="number" id="passing_score" name="passing_score" value="<?php echo esc_attr($passing_score); ?>" class="small-text" min="0" max="100" /></td>
            </tr>
            <tr>
                <th><label for="max_attempts">Maximum Attempts</label></th>
                <td><input type="number" id="max_attempts" name="max_attempts" value="<?php echo esc_attr($max_attempts); ?>" class="small-text" min="1" /></td>
            </tr>
            <tr>
                <th><label for="knowledge_points">Knowledge Points Awarded</label></th>
                <td>
                    <input type="number" id="knowledge_points" name="knowledge_points" value="<?php echo esc_attr($knowledge_points); ?>" class="small-text" min="1" />
                    <p class="description">Points awarded for passing the quiz</p>
                </td>
            </tr>
            <tr>
                <th><label for="environmental_focus">Environmental Focus Area</label></th>
                <td>
                    <select id="environmental_focus" name="environmental_focus">
                        <option value="">Select Focus Area</option>
                        <option value="climate_change" <?php selected($environmental_focus, 'climate_change'); ?>>Climate Change</option>
                        <option value="renewable_energy" <?php selected($environmental_focus, 'renewable_energy'); ?>>Renewable Energy</option>
                        <option value="sustainability" <?php selected($environmental_focus, 'sustainability'); ?>>Sustainability</option>
                        <option value="conservation" <?php selected($environmental_focus, 'conservation'); ?>>Conservation</option>
                        <option value="pollution" <?php selected($environmental_focus, 'pollution'); ?>>Pollution</option>
                        <option value="biodiversity" <?php selected($environmental_focus, 'biodiversity'); ?>>Biodiversity</option>
                        <option value="waste_management" <?php selected($environmental_focus, 'waste_management'); ?>>Waste Management</option>
                        <option value="green_technology" <?php selected($environmental_focus, 'green_technology'); ?>>Green Technology</option>
                    </select>
                </td>
            </tr>
        </table>

        <h4>Quiz Options</h4>
        <p>
            <label>
                <input type="checkbox" name="show_results_immediately" value="1" <?php checked($show_results, 1); ?> />
                Show results immediately after completion
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="randomize_questions" value="1" <?php checked($randomize_questions, 1); ?> />
                Randomize question order
            </label>
        </p>
        <?php
    }

    /**
     * Render quiz questions meta box
     */
    public function render_quiz_questions_meta_box($post) {
        $questions = $this->get_quiz_questions($post->ID);
        ?>
        <div id="quiz-questions-container">
            <div class="quiz-questions-list">
                <?php if (!empty($questions)): ?>
                    <?php foreach ($questions as $question): ?>
                        <div class="question-item" data-question-id="<?php echo $question->ID; ?>">
                            <h4><?php echo esc_html($question->post_title); ?></h4>
                            <p><?php echo wp_trim_words($question->post_content, 20); ?></p>
                            <div class="question-actions">
                                <a href="<?php echo get_edit_post_link($question->ID); ?>" class="button">Edit</a>
                                <button type="button" class="button button-secondary remove-question" data-question-id="<?php echo $question->ID; ?>">Remove</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No questions added yet.</p>
                <?php endif; ?>
            </div>
            
            <div class="add-question-section">
                <h4>Add Questions</h4>
                <p>
                    <a href="<?php echo admin_url('post-new.php?post_type=' . self::QUESTION_POST_TYPE . '&quiz_id=' . $post->ID); ?>" class="button button-primary">Create New Question</a>
                    <button type="button" class="button" id="add-existing-question">Add Existing Question</button>
                </p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#add-existing-question').click(function() {
                // Implementation for adding existing questions
                // This would open a modal with available questions
            });

            $('.remove-question').click(function() {
                if (confirm('Remove this question from the quiz?')) {
                    var questionId = $(this).data('question-id');
                    // AJAX call to remove question
                    $(this).closest('.question-item').fadeOut();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render quiz email settings meta box
     */
    public function render_quiz_email_settings_meta_box($post) {
        $enable_completion_email = get_post_meta($post->ID, '_enable_completion_email', true);
        $enable_pass_email = get_post_meta($post->ID, '_enable_pass_email', true);
        $enable_fail_email = get_post_meta($post->ID, '_enable_fail_email', true);
        $enable_follow_up = get_post_meta($post->ID, '_enable_follow_up_email', true);
        $follow_up_delay = get_post_meta($post->ID, '_follow_up_delay_days', true) ?: 7;

        ?>
        <p>
            <label>
                <input type="checkbox" name="enable_completion_email" value="1" <?php checked($enable_completion_email, 1); ?> />
                Send completion confirmation email
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="enable_pass_email" value="1" <?php checked($enable_pass_email, 1); ?> />
                Send congratulations email for passing
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="enable_fail_email" value="1" <?php checked($enable_fail_email, 1); ?> />
                Send encouragement email for failing
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="enable_follow_up_email" value="1" <?php checked($enable_follow_up, 1); ?> />
                Send educational follow-up email
            </label>
        </p>
        <div id="follow-up-settings" style="margin-left: 20px; <?php echo $enable_follow_up ? '' : 'display:none;'; ?>">
            <p>
                <label>Days after completion:</label>
                <input type="number" name="follow_up_delay_days" value="<?php echo esc_attr($follow_up_delay); ?>" min="1" max="30" class="small-text" />
            </p>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('input[name="enable_follow_up_email"]').change(function() {
                $('#follow-up-settings').toggle(this.checked);
            });
        });
        </script>
        <?php
    }

    /**
     * Render quiz results meta box
     */
    public function render_quiz_results_meta_box($post) {
        $results = $this->get_quiz_statistics($post->ID);
        ?>
        <div class="quiz-statistics">
            <div class="stats-grid">
                <div class="stat-item">
                    <h4><?php echo $results['total_attempts']; ?></h4>
                    <p>Total Attempts</p>
                </div>
                <div class="stat-item">
                    <h4><?php echo $results['unique_participants']; ?></h4>
                    <p>Unique Participants</p>
                </div>
                <div class="stat-item">
                    <h4><?php echo $results['pass_rate']; ?>%</h4>
                    <p>Pass Rate</p>
                </div>
                <div class="stat-item">
                    <h4><?php echo $results['average_score']; ?>%</h4>
                    <p>Average Score</p>
                </div>
            </div>
            
            <?php if ($results['recent_attempts']): ?>
                <h4>Recent Attempts</h4>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Participant</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['recent_attempts'] as $attempt): ?>
                            <tr>
                                <td><?php echo esc_html($attempt->participant_name ?: $attempt->email); ?></td>
                                <td><?php echo $attempt->score; ?>%</td>
                                <td><?php echo $attempt->passed ? 'Passed' : 'Failed'; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($attempt->completed_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f1f1f1;
            border-radius: 4px;
        }
        .stat-item h4 {
            margin: 0 0 5px 0;
            font-size: 24px;
            color: #0073aa;
        }
        .stat-item p {
            margin: 0;
            font-size: 12px;
            color: #666;
        }
        </style>
        <?php
    }

    /**
     * Render question details meta box
     */
    public function render_question_details_meta_box($post) {
        wp_nonce_field('save_question_meta', 'question_meta_nonce');

        $question_type = get_post_meta($post->ID, '_question_type', true) ?: 'multiple_choice';
        $options = get_post_meta($post->ID, '_question_options', true) ?: array();
        $correct_answer = get_post_meta($post->ID, '_correct_answer', true);
        $explanation = get_post_meta($post->ID, '_explanation', true);
        $points = get_post_meta($post->ID, '_points', true) ?: 1;

        ?>
        <table class="form-table">
            <tr>
                <th><label for="question_type">Question Type</label></th>
                <td>
                    <select id="question_type" name="question_type" onchange="toggleQuestionOptions()">
                        <option value="multiple_choice" <?php selected($question_type, 'multiple_choice'); ?>>Multiple Choice</option>
                        <option value="true_false" <?php selected($question_type, 'true_false'); ?>>True/False</option>
                        <option value="short_answer" <?php selected($question_type, 'short_answer'); ?>>Short Answer</option>
                        <option value="essay" <?php selected($question_type, 'essay'); ?>>Essay</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="points">Points</label></th>
                <td><input type="number" id="points" name="points" value="<?php echo esc_attr($points); ?>" class="small-text" min="1" /></td>
            </tr>
        </table>

        <div id="multiple-choice-options" style="<?php echo $question_type === 'multiple_choice' ? '' : 'display:none;'; ?>">
            <h4>Answer Options</h4>
            <div id="options-container">
                <?php
                if (!empty($options)) {
                    foreach ($options as $index => $option) {
                        echo '<div class="option-row">';
                        echo '<input type="text" name="question_options[]" value="' . esc_attr($option) . '" placeholder="Option ' . ($index + 1) . '" class="regular-text" />';
                        echo '<label><input type="radio" name="correct_answer" value="' . $index . '" ' . checked($correct_answer, $index, false) . ' /> Correct</label>';
                        echo '<button type="button" class="button remove-option">Remove</button>';
                        echo '</div>';
                    }
                } else {
                    for ($i = 0; $i < 4; $i++) {
                        echo '<div class="option-row">';
                        echo '<input type="text" name="question_options[]" placeholder="Option ' . ($i + 1) . '" class="regular-text" />';
                        echo '<label><input type="radio" name="correct_answer" value="' . $i . '" /> Correct</label>';
                        echo '<button type="button" class="button remove-option">Remove</button>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
            <button type="button" class="button" id="add-option">Add Option</button>
        </div>

        <div id="true-false-options" style="<?php echo $question_type === 'true_false' ? '' : 'display:none;'; ?>">
            <h4>Correct Answer</h4>
            <p>
                <label><input type="radio" name="tf_correct_answer" value="true" <?php checked($correct_answer, 'true'); ?> /> True</label>
                <label><input type="radio" name="tf_correct_answer" value="false" <?php checked($correct_answer, 'false'); ?> /> False</label>
            </p>
        </div>

        <div id="short-answer-options" style="<?php echo $question_type === 'short_answer' ? '' : 'display:none;'; ?>">
            <h4>Correct Answer(s)</h4>
            <p>
                <textarea name="sa_correct_answer" rows="3" class="large-text" placeholder="Enter correct answers separated by commas"><?php echo esc_textarea($correct_answer); ?></textarea>
            </p>
        </div>

        <h4>Explanation (Optional)</h4>
        <p>
            <textarea name="explanation" rows="4" class="large-text" placeholder="Provide an explanation for this question"><?php echo esc_textarea($explanation); ?></textarea>
        </p>

        <script>
        function toggleQuestionOptions() {
            var type = document.getElementById('question_type').value;
            
            document.getElementById('multiple-choice-options').style.display = type === 'multiple_choice' ? '' : 'none';
            document.getElementById('true-false-options').style.display = type === 'true_false' ? '' : 'none';
            document.getElementById('short-answer-options').style.display = type === 'short_answer' ? '' : 'none';
        }

        jQuery(document).ready(function($) {
            $('#add-option').click(function() {
                var optionsContainer = $('#options-container');
                var optionCount = optionsContainer.find('.option-row').length;
                var optionHtml = '<div class="option-row">' +
                    '<input type="text" name="question_options[]" placeholder="Option ' + (optionCount + 1) + '" class="regular-text" />' +
                    '<label><input type="radio" name="correct_answer" value="' + optionCount + '" /> Correct</label>' +
                    '<button type="button" class="button remove-option">Remove</button>' +
                    '</div>';
                optionsContainer.append(optionHtml);
            });

            $(document).on('click', '.remove-option', function() {
                $(this).closest('.option-row').remove();
            });
        });
        </script>

        <style>
        .option-row {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .option-row input[type="text"] {
            flex: 1;
        }
        </style>
        <?php
    }

    /**
     * Save quiz meta
     */
    public function save_quiz_meta($post_id) {
        if (get_post_type($post_id) === self::QUIZ_POST_TYPE) {
            $this->save_quiz_settings($post_id);
        } elseif (get_post_type($post_id) === self::QUESTION_POST_TYPE) {
            $this->save_question_settings($post_id);
        }
    }

    /**
     * Save quiz settings
     */
    private function save_quiz_settings($post_id) {
        if (!isset($_POST['quiz_meta_nonce']) || !wp_verify_nonce($_POST['quiz_meta_nonce'], 'save_quiz_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save quiz settings
        $fields = array(
            'time_limit', 'passing_score', 'max_attempts', 'knowledge_points', 'environmental_focus', 'follow_up_delay_days'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Save checkboxes
        $checkboxes = array(
            'show_results_immediately', 'randomize_questions', 'enable_completion_email',
            'enable_pass_email', 'enable_fail_email', 'enable_follow_up_email'
        );

        foreach ($checkboxes as $checkbox) {
            update_post_meta($post_id, '_' . $checkbox, isset($_POST[$checkbox]) ? 1 : 0);
        }
    }

    /**
     * Save question settings
     */
    private function save_question_settings($post_id) {
        if (!isset($_POST['question_meta_nonce']) || !wp_verify_nonce($_POST['question_meta_nonce'], 'save_question_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $question_type = sanitize_text_field($_POST['question_type']);
        update_post_meta($post_id, '_question_type', $question_type);
        update_post_meta($post_id, '_points', intval($_POST['points']));
        update_post_meta($post_id, '_explanation', sanitize_textarea_field($_POST['explanation']));

        // Save correct answer based on question type
        switch ($question_type) {
            case 'multiple_choice':
                if (isset($_POST['question_options']) && is_array($_POST['question_options'])) {
                    $options = array_map('sanitize_text_field', $_POST['question_options']);
                    update_post_meta($post_id, '_question_options', $options);
                }
                if (isset($_POST['correct_answer'])) {
                    update_post_meta($post_id, '_correct_answer', intval($_POST['correct_answer']));
                }
                break;
            
            case 'true_false':
                if (isset($_POST['tf_correct_answer'])) {
                    update_post_meta($post_id, '_correct_answer', sanitize_text_field($_POST['tf_correct_answer']));
                }
                break;
            
            case 'short_answer':
                if (isset($_POST['sa_correct_answer'])) {
                    update_post_meta($post_id, '_correct_answer', sanitize_textarea_field($_POST['sa_correct_answer']));
                }
                break;
        }
    }

    /**
     * Handle quiz submission
     */
    public function handle_quiz_submission() {
        if (!wp_verify_nonce($_POST['nonce'], 'eem_quiz_submission')) {
            wp_die('Security check failed');
        }

        $quiz_id = intval($_POST['quiz_id']);
        $participant_name = sanitize_text_field($_POST['participant_name']);
        $email = sanitize_email($_POST['email']);
        $answers = $_POST['answers'];

        // Validate inputs
        if (!$quiz_id || !$participant_name || !is_email($email)) {
            wp_send_json_error('Invalid input data');
        }

        // Check if quiz exists
        $quiz = get_post($quiz_id);
        if (!$quiz || $quiz->post_type !== self::QUIZ_POST_TYPE) {
            wp_send_json_error('Quiz not found');
        }

        // Check attempt limits
        $max_attempts = get_post_meta($quiz_id, '_max_attempts', true) ?: 3;
        $user_attempts = $this->get_user_attempts($quiz_id, $email);
        
        if (count($user_attempts) >= $max_attempts) {
            wp_send_json_error('Maximum attempts exceeded');
        }

        // Grade the quiz
        $result = $this->grade_quiz($quiz_id, $answers);
        
        // Save attempt
        $attempt_id = $this->save_quiz_attempt($quiz_id, $participant_name, $email, $answers, $result);
        
        if ($attempt_id) {
            // Trigger automations
            do_action('eem_quiz_completed', $quiz_id, $email, $result);
            
            if ($result['passed']) {
                do_action('eem_quiz_passed', $quiz_id, $email, $result);
                
                // Award knowledge points
                $this->award_knowledge_points($email, $quiz_id);
                
                // Check for high score
                if ($result['score'] >= 90) {
                    do_action('eem_high_score_achieved', $quiz_id, $email, $result);
                }
            } else {
                do_action('eem_quiz_failed', $quiz_id, $email, $result);
            }

            // Add environmental action score
            $this->add_environmental_action_score($email, 'quiz_completion', $quiz_id, $result['score']);

            wp_send_json_success(array(
                'result' => $result,
                'attempt_id' => $attempt_id
            ));
        } else {
            wp_send_json_error('Failed to save quiz attempt');
        }
    }

    /**
     * Grade quiz
     */
    private function grade_quiz($quiz_id, $answers) {
        $questions = $this->get_quiz_questions($quiz_id);
        $total_points = 0;
        $earned_points = 0;
        $detailed_results = array();

        foreach ($questions as $question) {
            $question_points = get_post_meta($question->ID, '_points', true) ?: 1;
            $total_points += $question_points;
            
            $correct_answer = get_post_meta($question->ID, '_correct_answer', true);
            $question_type = get_post_meta($question->ID, '_question_type', true);
            $user_answer = isset($answers[$question->ID]) ? $answers[$question->ID] : '';
            
            $is_correct = $this->check_answer($question_type, $correct_answer, $user_answer);
            
            if ($is_correct) {
                $earned_points += $question_points;
            }
            
            $detailed_results[] = array(
                'question_id' => $question->ID,
                'question_title' => $question->post_title,
                'user_answer' => $user_answer,
                'correct_answer' => $correct_answer,
                'is_correct' => $is_correct,
                'points' => $question_points,
                'earned_points' => $is_correct ? $question_points : 0
            );
        }

        $score = $total_points > 0 ? round(($earned_points / $total_points) * 100, 2) : 0;
        $passing_score = get_post_meta($quiz_id, '_passing_score', true) ?: 70;
        $passed = $score >= $passing_score;

        return array(
            'score' => $score,
            'total_points' => $total_points,
            'earned_points' => $earned_points,
            'passed' => $passed,
            'passing_score' => $passing_score,
            'detailed_results' => $detailed_results
        );
    }

    /**
     * Check individual answer
     */
    private function check_answer($question_type, $correct_answer, $user_answer) {
        switch ($question_type) {
            case 'multiple_choice':
                return intval($correct_answer) === intval($user_answer);
            
            case 'true_false':
                return strtolower($correct_answer) === strtolower($user_answer);
            
            case 'short_answer':
                $correct_answers = array_map('trim', explode(',', strtolower($correct_answer)));
                return in_array(strtolower(trim($user_answer)), $correct_answers);
            
            case 'essay':
                // Essay questions require manual grading
                return false;
            
            default:
                return false;
        }
    }

    /**
     * Save quiz attempt
     */
    private function save_quiz_attempt($quiz_id, $participant_name, $email, $answers, $result) {
        global $wpdb;
        
        $table_name = $this->db_manager->get_table_name('quiz_attempts');
        
        return $wpdb->insert(
            $table_name,
            array(
                'quiz_id' => $quiz_id,
                'participant_name' => $participant_name,
                'email' => $email,
                'answers' => json_encode($answers),
                'score' => $result['score'],
                'total_points' => $result['total_points'],
                'earned_points' => $result['earned_points'],
                'passed' => $result['passed'] ? 1 : 0,
                'detailed_results' => json_encode($result['detailed_results']),
                'completed_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%f', '%d', '%d', '%d', '%s', '%s')
        );
    }

    /**
     * Get quiz questions
     */
    private function get_quiz_questions($quiz_id) {
        return get_posts(array(
            'post_type' => self::QUESTION_POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_quiz_id',
                    'value' => $quiz_id
                )
            ),
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
    }

    /**
     * Get user attempts
     */
    private function get_user_attempts($quiz_id, $email) {
        global $wpdb;
        
        $table_name = $this->db_manager->get_table_name('quiz_attempts');
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE quiz_id = %d AND email = %s ORDER BY completed_at DESC",
            $quiz_id, $email
        ));
    }

    /**
     * Get quiz statistics
     */
    private function get_quiz_statistics($quiz_id) {
        global $wpdb;
        
        $table_name = $this->db_manager->get_table_name('quiz_attempts');
        
        // Get basic stats
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_attempts,
                COUNT(DISTINCT email) as unique_participants,
                AVG(score) as average_score,
                SUM(passed) as total_passed
            FROM $table_name 
            WHERE quiz_id = %d
        ", $quiz_id));
        
        $pass_rate = $stats->total_attempts > 0 ? round(($stats->total_passed / $stats->total_attempts) * 100, 1) : 0;
        
        // Get recent attempts
        $recent_attempts = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table_name 
            WHERE quiz_id = %d 
            ORDER BY completed_at DESC 
            LIMIT 10
        ", $quiz_id));
        
        return array(
            'total_attempts' => $stats->total_attempts,
            'unique_participants' => $stats->unique_participants,
            'average_score' => round($stats->average_score, 1),
            'pass_rate' => $pass_rate,
            'recent_attempts' => $recent_attempts
        );
    }

    /**
     * Award knowledge points
     */
    private function award_knowledge_points($email, $quiz_id) {
        $points = get_post_meta($quiz_id, '_knowledge_points', true) ?: 10;
        
        $this->analytics_tracker->track_environmental_action($email, 'quiz_passed', array(
            'quiz_id' => $quiz_id,
            'knowledge_points' => $points
        ));
    }

    /**
     * Add environmental action score
     */
    private function add_environmental_action_score($email, $action, $quiz_id, $score) {
        $environmental_focus = get_post_meta($quiz_id, '_environmental_focus', true);
        $base_score = min($score / 10, 10); // Convert percentage to 0-10 scale
        
        $this->analytics_tracker->track_environmental_action($email, $action, array(
            'quiz_id' => $quiz_id,
            'score' => $base_score,
            'focus_area' => $environmental_focus
        ));
    }

    /**
     * Quiz shortcode
     */
    public function quiz_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'style' => 'default'
        ), $atts);

        if (!$atts['id']) {
            return '<p>Quiz ID is required.</p>';
        }

        $quiz = get_post($atts['id']);
        if (!$quiz || $quiz->post_type !== self::QUIZ_POST_TYPE) {
            return '<p>Quiz not found.</p>';
        }

        ob_start();
        $this->render_quiz_frontend($quiz, $atts['style']);
        return ob_get_clean();
    }

    /**
     * Render quiz frontend
     */
    private function render_quiz_frontend($quiz, $style) {
        $questions = $this->get_quiz_questions($quiz->ID);
        $time_limit = get_post_meta($quiz->ID, '_time_limit', true);
        
        ?>
        <div class="eem-quiz-container" data-quiz-id="<?php echo $quiz->ID; ?>" data-style="<?php echo esc_attr($style); ?>">
            <div class="quiz-header">
                <h2><?php echo esc_html($quiz->post_title); ?></h2>
                <?php if ($quiz->post_content): ?>
                    <div class="quiz-description">
                        <?php echo wpautop($quiz->post_content); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($time_limit): ?>
                    <div class="quiz-timer">
                        <strong>Time Limit: <?php echo $time_limit; ?> minutes</strong>
                        <div id="timer-display"></div>
                    </div>
                <?php endif; ?>
            </div>

            <form id="quiz-form-<?php echo $quiz->ID; ?>" class="eem-quiz-form">
                <?php wp_nonce_field('eem_quiz_submission', 'quiz_nonce'); ?>
                <input type="hidden" name="quiz_id" value="<?php echo $quiz->ID; ?>" />
                
                <div class="participant-info">
                    <div class="form-group">
                        <label for="participant_name">Your Name *</label>
                        <input type="text" id="participant_name" name="participant_name" required />
                    </div>
                    <div class="form-group">
                        <label for="participant_email">Your Email *</label>
                        <input type="email" id="participant_email" name="email" required />
                    </div>
                </div>

                <div class="quiz-questions">
                    <?php foreach ($questions as $index => $question): ?>
                        <?php $this->render_question($question, $index + 1); ?>
                    <?php endforeach; ?>
                </div>

                <div class="quiz-submit">
                    <button type="submit" class="btn btn-primary">Submit Quiz</button>
                </div>
            </form>

            <div id="quiz-results" class="quiz-results" style="display: none;"></div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var quizId = <?php echo $quiz->ID; ?>;
            var timeLimit = <?php echo $time_limit ?: 0; ?>;
            
            // Initialize quiz timer if needed
            if (timeLimit > 0) {
                initializeQuizTimer(timeLimit);
            }
            
            // Handle quiz submission
            $('#quiz-form-' + quizId).on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                formData += '&action=eem_submit_quiz&nonce=' + $('#quiz_nonce').val();
                
                $.post(eem_ajax.ajax_url, formData, function(response) {
                    if (response.success) {
                        displayQuizResults(response.data.result);
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
        });

        function initializeQuizTimer(minutes) {
            var timeLeft = minutes * 60;
            var timer = setInterval(function() {
                var mins = Math.floor(timeLeft / 60);
                var secs = timeLeft % 60;
                
                document.getElementById('timer-display').textContent = 
                    mins + ':' + (secs < 10 ? '0' : '') + secs;
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    document.getElementById('quiz-form-<?php echo $quiz->ID; ?>').submit();
                }
                
                timeLeft--;
            }, 1000);
        }

        function displayQuizResults(result) {
            var resultsHtml = '<h3>Quiz Results</h3>';
            resultsHtml += '<p><strong>Score: ' + result.score + '%</strong></p>';
            resultsHtml += '<p>You ' + (result.passed ? 'passed' : 'failed') + ' the quiz.</p>';
            resultsHtml += '<p>You earned ' + result.earned_points + ' out of ' + result.total_points + ' points.</p>';
            
            jQuery('#quiz-results').html(resultsHtml).show();
            jQuery('.eem-quiz-form').hide();
        }
        </script>

        <style>
        .eem-quiz-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .quiz-header {
            margin-bottom: 30px;
            text-align: center;
        }
        .quiz-timer {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        #timer-display {
            font-size: 24px;
            font-weight: bold;
            color: #d63638;
            margin-top: 10px;
        }
        .participant-info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        .quiz-question {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }
        .question-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .question-options {
            margin-left: 20px;
        }
        .question-options label {
            display: block;
            margin-bottom: 10px;
            cursor: pointer;
        }
        .quiz-submit {
            text-align: center;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-primary {
            background-color: #0073aa;
            color: white;
        }
        .quiz-results {
            background: #f0f8f0;
            border: 1px solid #4caf50;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        </style>
        <?php
    }

    /**
     * Render individual question
     */
    private function render_question($question, $question_number) {
        $question_type = get_post_meta($question->ID, '_question_type', true);
        $options = get_post_meta($question->ID, '_question_options', true);
        
        ?>
        <div class="quiz-question" data-question-id="<?php echo $question->ID; ?>">
            <div class="question-title">
                <?php echo $question_number; ?>. <?php echo esc_html($question->post_title); ?>
            </div>
            
            <?php if ($question->post_content): ?>
                <div class="question-content">
                    <?php echo wpautop($question->post_content); ?>
                </div>
            <?php endif; ?>
            
            <div class="question-options">
                <?php
                switch ($question_type) {
                    case 'multiple_choice':
                        if (!empty($options)) {
                            foreach ($options as $index => $option) {
                                echo '<label>';
                                echo '<input type="radio" name="answers[' . $question->ID . ']" value="' . $index . '" required /> ';
                                echo esc_html($option);
                                echo '</label>';
                            }
                        }
                        break;
                    
                    case 'true_false':
                        echo '<label><input type="radio" name="answers[' . $question->ID . ']" value="true" required /> True</label>';
                        echo '<label><input type="radio" name="answers[' . $question->ID . ']" value="false" required /> False</label>';
                        break;
                    
                    case 'short_answer':
                        echo '<input type="text" name="answers[' . $question->ID . ']" class="short-answer-input" required />';
                        break;
                    
                    case 'essay':
                        echo '<textarea name="answers[' . $question->ID . ']" rows="5" class="essay-input" required></textarea>';
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Trigger automation for quiz completion
     */
    public function trigger_completion_automation($quiz_id, $email, $result) {
        $this->automation_engine->process_trigger('quiz_completion', array(
            'email' => $email,
            'quiz_id' => $quiz_id,
            'quiz_title' => get_the_title($quiz_id),
            'score' => $result['score'],
            'passed' => $result['passed']
        ));
    }

    /**
     * Trigger automation for quiz pass
     */
    public function trigger_passed_automation($quiz_id, $email, $result) {
        $this->automation_engine->process_trigger('quiz_passed', array(
            'email' => $email,
            'quiz_id' => $quiz_id,
            'quiz_title' => get_the_title($quiz_id),
            'score' => $result['score']
        ));
    }

    /**
     * Trigger automation for quiz fail
     */
    public function trigger_failed_automation($quiz_id, $email, $result) {
        $this->automation_engine->process_trigger('quiz_failed', array(
            'email' => $email,
            'quiz_id' => $quiz_id,
            'quiz_title' => get_the_title($quiz_id),
            'score' => $result['score']
        ));
    }

    /**
     * Trigger automation for high score
     */
    public function trigger_high_score_automation($quiz_id, $email, $result) {
        $this->automation_engine->process_trigger('high_score_achieved', array(
            'email' => $email,
            'quiz_id' => $quiz_id,
            'quiz_title' => get_the_title($quiz_id),
            'score' => $result['score']
        ));
    }

    /**
     * Process quiz follow-ups
     */
    public function process_quiz_follow_ups() {
        global $wpdb;
        
        // Get quiz attempts that need follow-up emails
        $table_name = $this->db_manager->get_table_name('quiz_attempts');
        
        $attempts = $wpdb->get_results("
            SELECT qa.*, p.post_title as quiz_title
            FROM $table_name qa
            LEFT JOIN {$wpdb->posts} p ON qa.quiz_id = p.ID
            WHERE qa.follow_up_sent = 0
            AND qa.completed_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm 
                WHERE pm.post_id = qa.quiz_id 
                AND pm.meta_key = '_enable_follow_up_email' 
                AND pm.meta_value = '1'
            )
        ");
        
        foreach ($attempts as $attempt) {
            $this->automation_engine->process_trigger('quiz_follow_up', array(
                'email' => $attempt->email,
                'quiz_id' => $attempt->quiz_id,
                'quiz_title' => $attempt->quiz_title,
                'score' => $attempt->score,
                'passed' => $attempt->passed
            ));
            
            // Mark as sent
            $wpdb->update(
                $table_name,
                array('follow_up_sent' => 1),
                array('id' => $attempt->id),
                array('%d'),
                array('%d')
            );
        }
    }

    /**
     * Create quiz attempts table
     */
    public function create_quiz_attempts_table() {
        global $wpdb;
        
        $table_name = $this->db_manager->get_table_name('quiz_attempts');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            quiz_id bigint(20) NOT NULL,
            participant_name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            answers longtext,
            score decimal(5,2) NOT NULL DEFAULT 0,
            total_points int(11) NOT NULL DEFAULT 0,
            earned_points int(11) NOT NULL DEFAULT 0,
            passed tinyint(1) NOT NULL DEFAULT 0,
            detailed_results longtext,
            completed_at datetime NOT NULL,
            follow_up_sent tinyint(1) NOT NULL DEFAULT 0,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY quiz_id (quiz_id),
            KEY email (email),
            KEY passed (passed),
            KEY completed_at (completed_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the integration
EEM_Quizzes_Integration::get_instance();
