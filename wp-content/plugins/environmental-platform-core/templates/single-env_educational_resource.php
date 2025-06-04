<?php
/**
 * Single Educational Resource Template
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="ep-education-single">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('ep-education-article'); ?>>
            
            <!-- Educational Resource Header -->
            <header class="ep-education-header">
                <div class="ep-container">
                    <div class="ep-resource-meta">
                        <?php
                        $resource_type = get_post_meta(get_the_ID(), '_ep_resource_type', true);
                        $difficulty_level = get_post_meta(get_the_ID(), '_ep_difficulty_level', true);
                        $duration = get_post_meta(get_the_ID(), '_ep_duration', true);
                        $age_group = get_post_meta(get_the_ID(), '_ep_age_group', true);
                        ?>
                        
                        <div class="ep-meta-badges">
                            <?php if ($resource_type): ?>
                                <span class="ep-resource-type ep-badge-<?php echo esc_attr(sanitize_title($resource_type)); ?>">
                                    <?php echo esc_html($resource_type); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($difficulty_level): ?>
                                <span class="ep-difficulty ep-difficulty-<?php echo esc_attr(sanitize_title($difficulty_level)); ?>">
                                    <?php echo esc_html($difficulty_level); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($age_group): ?>
                                <span class="ep-age-group">
                                    <?php echo esc_html($age_group); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="ep-resource-details">
                            <?php if ($duration): ?>
                                <span class="ep-duration">
                                    <i class="ep-icon-clock"></i>
                                    <?php echo esc_html($duration); ?>
                                </span>
                            <?php endif; ?>
                            
                            <span class="ep-publish-date">
                                <i class="ep-icon-calendar"></i>
                                <?php echo get_the_date(); ?>
                            </span>
                        </div>
                    </div>
                    
                    <h1 class="ep-education-title"><?php the_title(); ?></h1>
                    
                    <?php if (has_excerpt()): ?>
                        <div class="ep-education-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Learning Objectives -->
                    <?php
                    $learning_objectives = get_post_meta(get_the_ID(), '_ep_learning_objectives', true);
                    if ($learning_objectives):
                    ?>
                        <div class="ep-learning-objectives">
                            <h3><?php _e('What You\'ll Learn', 'environmental-platform-core'); ?></h3>
                            <ul class="ep-objectives-list">
                                <?php 
                                $objectives = explode("\n", $learning_objectives);
                                foreach ($objectives as $objective):
                                    if (trim($objective)):
                                ?>
                                    <li><?php echo esc_html(trim($objective)); ?></li>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </header>
            
            <!-- Educational Resource Content -->
            <div class="ep-education-content">
                <div class="ep-container">
                    <div class="ep-row">
                        <div class="ep-col-8">
                            
                            <!-- Featured Media -->
                            <?php if (has_post_thumbnail()): ?>
                                <div class="ep-featured-media">
                                    <?php the_post_thumbnail('large', array('class' => 'ep-responsive-image')); ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Resource Content -->
                            <div class="ep-resource-content">
                                <?php the_content(); ?>
                            </div>
                            
                            <!-- Interactive Elements -->
                            <?php
                            $interactive_content = get_post_meta(get_the_ID(), '_ep_interactive_content', true);
                            if ($interactive_content):
                            ?>
                                <section class="ep-interactive-section">
                                    <h2><?php _e('Interactive Learning', 'environmental-platform-core'); ?></h2>
                                    <div class="ep-interactive-content">
                                        <?php echo wp_kses_post($interactive_content); ?>
                                    </div>
                                </section>
                            <?php endif; ?>
                            
                            <!-- Quiz Section -->
                            <?php
                            $quiz_questions = get_post_meta(get_the_ID(), '_ep_quiz_questions', true);
                            if ($quiz_questions && is_array($quiz_questions)):
                            ?>
                                <section class="ep-quiz-section">
                                    <h2><?php _e('Test Your Knowledge', 'environmental-platform-core'); ?></h2>
                                    <div class="ep-quiz-container">
                                        <?php foreach ($quiz_questions as $index => $question): ?>
                                            <div class="ep-quiz-question" data-question="<?php echo $index; ?>">
                                                <h4><?php echo esc_html($question['question']); ?></h4>
                                                <div class="ep-quiz-options">
                                                    <?php foreach ($question['options'] as $option_index => $option): ?>
                                                        <label class="ep-quiz-option">
                                                            <input type="radio" 
                                                                   name="question_<?php echo $index; ?>" 
                                                                   value="<?php echo $option_index; ?>">
                                                            <span><?php echo esc_html($option); ?></span>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div class="ep-quiz-feedback" style="display: none;">
                                                    <p class="ep-correct-answer" style="display: none;">
                                                        <i class="ep-icon-check"></i>
                                                        <?php _e('Correct!', 'environmental-platform-core'); ?>
                                                        <?php if (isset($question['explanation'])): ?>
                                                            <?php echo esc_html($question['explanation']); ?>
                                                        <?php endif; ?>
                                                    </p>
                                                    <p class="ep-wrong-answer" style="display: none;">
                                                        <i class="ep-icon-close"></i>
                                                        <?php _e('Not quite right.', 'environmental-platform-core'); ?>
                                                        <?php if (isset($question['explanation'])): ?>
                                                            <?php echo esc_html($question['explanation']); ?>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <button class="ep-btn ep-btn-primary ep-submit-quiz">
                                            <?php _e('Submit Quiz', 'environmental-platform-core'); ?>
                                        </button>
                                        <div class="ep-quiz-results" style="display: none;"></div>
                                    </div>
                                </section>
                            <?php endif; ?>
                            
                            <!-- Activities & Experiments -->
                            <?php
                            $activities = get_post_meta(get_the_ID(), '_ep_activities', true);
                            if ($activities):
                            ?>
                                <section class="ep-activities-section">
                                    <h2><?php _e('Hands-On Activities', 'environmental-platform-core'); ?></h2>
                                    <div class="ep-activities-content">
                                        <?php echo wp_kses_post($activities); ?>
                                    </div>
                                </section>
                            <?php endif; ?>
                            
                            <!-- Additional Resources -->
                            <?php
                            $additional_resources = get_post_meta(get_the_ID(), '_ep_additional_resources', true);
                            if ($additional_resources && is_array($additional_resources)):
                            ?>
                                <section class="ep-additional-resources">
                                    <h2><?php _e('Additional Resources', 'environmental-platform-core'); ?></h2>
                                    <div class="ep-resources-grid">
                                        <?php foreach ($additional_resources as $resource): ?>
                                            <div class="ep-resource-card">
                                                <h4>
                                                    <a href="<?php echo esc_url($resource['url']); ?>" 
                                                       target="_blank" 
                                                       rel="noopener">
                                                        <?php echo esc_html($resource['title']); ?>
                                                    </a>
                                                </h4>
                                                <p><?php echo esc_html($resource['description']); ?></p>
                                                <span class="ep-resource-type-tag">
                                                    <?php echo esc_html($resource['type']); ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </section>
                            <?php endif; ?>
                            
                            <!-- Progress Tracking -->
                            <section class="ep-progress-section">
                                <h2><?php _e('Track Your Progress', 'environmental-platform-core'); ?></h2>
                                <div class="ep-progress-container">
                                    <div class="ep-progress-bar">
                                        <div class="ep-progress-fill" data-progress="0"></div>
                                    </div>
                                    <div class="ep-progress-actions">
                                        <button class="ep-btn ep-btn-secondary ep-mark-complete">
                                            <?php _e('Mark as Complete', 'environmental-platform-core'); ?>
                                        </button>
                                        <button class="ep-btn ep-btn-secondary ep-bookmark">
                                            <?php _e('Bookmark', 'environmental-platform-core'); ?>
                                        </button>
                                    </div>
                                </div>
                            </section>
                            
                        </div>
                        
                        <!-- Sidebar -->
                        <div class="ep-col-4">
                            <aside class="ep-education-sidebar">
                                
                                <!-- Resource Information -->
                                <div class="ep-widget ep-resource-info">
                                    <h3><?php _e('Resource Information', 'environmental-platform-core'); ?></h3>
                                    <dl class="ep-info-list">
                                        <?php if ($resource_type): ?>
                                            <dt><?php _e('Type', 'environmental-platform-core'); ?></dt>
                                            <dd><?php echo esc_html($resource_type); ?></dd>
                                        <?php endif; ?>
                                        
                                        <?php if ($difficulty_level): ?>
                                            <dt><?php _e('Difficulty', 'environmental-platform-core'); ?></dt>
                                            <dd><?php echo esc_html($difficulty_level); ?></dd>
                                        <?php endif; ?>
                                        
                                        <?php if ($duration): ?>
                                            <dt><?php _e('Duration', 'environmental-platform-core'); ?></dt>
                                            <dd><?php echo esc_html($duration); ?></dd>
                                        <?php endif; ?>
                                        
                                        <?php if ($age_group): ?>
                                            <dt><?php _e('Age Group', 'environmental-platform-core'); ?></dt>
                                            <dd><?php echo esc_html($age_group); ?></dd>
                                        <?php endif; ?>
                                        
                                        <dt><?php _e('Views', 'environmental-platform-core'); ?></dt>
                                        <dd><?php echo get_post_meta(get_the_ID(), '_ep_view_count', true) ?: 0; ?></dd>
                                    </dl>
                                </div>
                                
                                <!-- Prerequisites -->
                                <?php
                                $prerequisites = get_post_meta(get_the_ID(), '_ep_prerequisites', true);
                                if ($prerequisites):
                                ?>
                                    <div class="ep-widget ep-prerequisites">
                                        <h3><?php _e('Prerequisites', 'environmental-platform-core'); ?></h3>
                                        <div class="ep-prereq-content">
                                            <?php echo wp_kses_post($prerequisites); ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Related Topics -->
                                <?php
                                $topics = get_the_terms(get_the_ID(), 'educational_topic');
                                if ($topics && !is_wp_error($topics)):
                                ?>
                                    <div class="ep-widget ep-related-topics">
                                        <h3><?php _e('Topics Covered', 'environmental-platform-core'); ?></h3>
                                        <ul class="ep-topic-list">
                                            <?php foreach ($topics as $topic): ?>
                                                <li>
                                                    <a href="<?php echo get_term_link($topic); ?>" class="ep-topic-link">
                                                        <?php echo esc_html($topic->name); ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Downloads -->
                                <?php
                                $downloadable_files = get_post_meta(get_the_ID(), '_ep_downloadable_files', true);
                                if ($downloadable_files && is_array($downloadable_files)):
                                ?>
                                    <div class="ep-widget ep-downloads">
                                        <h3><?php _e('Downloads', 'environmental-platform-core'); ?></h3>
                                        <ul class="ep-download-list">
                                            <?php foreach ($downloadable_files as $file): ?>
                                                <li>
                                                    <a href="<?php echo esc_url($file['url']); ?>" 
                                                       class="ep-download-link" 
                                                       download>
                                                        <i class="ep-icon-download"></i>
                                                        <?php echo esc_html($file['title']); ?>
                                                        <span class="ep-file-size"><?php echo esc_html($file['size']); ?></span>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Related Resources -->
                                <?php
                                $related_resources = get_posts(array(
                                    'post_type' => 'env_educational_resource',
                                    'posts_per_page' => 3,
                                    'post__not_in' => array(get_the_ID()),
                                    'meta_query' => array(
                                        array(
                                            'key' => '_ep_difficulty_level',
                                            'value' => $difficulty_level,
                                            'compare' => '='
                                        )
                                    )
                                ));
                                
                                if ($related_resources):
                                ?>
                                    <div class="ep-widget ep-related-resources">
                                        <h3><?php _e('Related Resources', 'environmental-platform-core'); ?></h3>
                                        <ul class="ep-related-list">
                                            <?php foreach ($related_resources as $related): ?>
                                                <li class="ep-related-item">
                                                    <a href="<?php echo get_permalink($related->ID); ?>" 
                                                       class="ep-related-link">
                                                        <h4><?php echo get_the_title($related->ID); ?></h4>
                                                        <span class="ep-related-type">
                                                            <?php echo get_post_meta($related->ID, '_ep_resource_type', true); ?>
                                                        </span>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Share -->
                                <div class="ep-widget ep-share">
                                    <h3><?php _e('Share This Resource', 'environmental-platform-core'); ?></h3>
                                    <div class="ep-share-buttons">
                                        <button class="ep-share-btn ep-share-general" onclick="epShareResource()">
                                            <i class="ep-icon-share"></i>
                                            <?php _e('Share', 'environmental-platform-core'); ?>
                                        </button>
                                        <button class="ep-share-btn ep-share-email" onclick="epShareByEmail()">
                                            <i class="ep-icon-email"></i>
                                            <?php _e('Email', 'environmental-platform-core'); ?>
                                        </button>
                                    </div>
                                </div>
                                
                            </aside>
                        </div>
                    </div>
                </div>
            </div>
            
        </article>
    <?php endwhile; ?>
</div>

<style>
.ep-education-single {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    line-height: 1.6;
}

.ep-education-header {
    background: linear-gradient(135deg, #8e44ad 0%, #3498db 100%);
    color: white;
    padding: 60px 0;
}

.ep-meta-badges {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.ep-resource-type {
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    background-color: rgba(255, 255, 255, 0.2);
}

.ep-difficulty {
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.ep-difficulty-beginner { background-color: #27ae60; }
.ep-difficulty-intermediate { background-color: #f39c12; }
.ep-difficulty-advanced { background-color: #e74c3c; }

.ep-age-group {
    background-color: rgba(255, 255, 255, 0.2);
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 12px;
}

.ep-resource-details {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    font-size: 0.9em;
    opacity: 0.9;
}

.ep-education-title {
    font-size: 2.5em;
    font-weight: 700;
    margin: 20px 0;
    line-height: 1.2;
}

.ep-education-excerpt {
    font-size: 1.2em;
    opacity: 0.9;
    margin-bottom: 30px;
    max-width: 800px;
}

.ep-learning-objectives {
    background-color: rgba(255, 255, 255, 0.1);
    padding: 25px;
    border-radius: 8px;
    margin-top: 30px;
}

.ep-learning-objectives h3 {
    margin-bottom: 15px;
    font-size: 1.2em;
}

.ep-objectives-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.ep-objectives-list li {
    padding: 8px 0;
    padding-left: 25px;
    position: relative;
}

.ep-objectives-list li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #2ecc71;
    font-weight: bold;
}

.ep-education-content {
    padding: 60px 0;
}

.ep-featured-media {
    margin-bottom: 40px;
}

.ep-responsive-image {
    width: 100%;
    height: auto;
    border-radius: 8px;
}

.ep-resource-content {
    font-size: 1.1em;
    margin-bottom: 50px;
}

.ep-interactive-section,
.ep-quiz-section,
.ep-activities-section,
.ep-additional-resources {
    margin-bottom: 50px;
    padding: 40px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #8e44ad;
}

.ep-interactive-section h2,
.ep-quiz-section h2,
.ep-activities-section h2,
.ep-additional-resources h2 {
    color: #2c3e50;
    margin-bottom: 25px;
    font-size: 1.5em;
}

.ep-quiz-question {
    background-color: white;
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #e9ecef;
}

.ep-quiz-question h4 {
    margin-bottom: 15px;
    color: #2c3e50;
}

.ep-quiz-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.ep-quiz-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.ep-quiz-option:hover {
    background-color: #f8f9fa;
}

.ep-quiz-option input[type="radio"] {
    margin: 0;
}

.ep-quiz-feedback {
    margin-top: 15px;
    padding: 15px;
    border-radius: 5px;
}

.ep-correct-answer {
    background-color: #d4edda;
    color: #155724;
}

.ep-wrong-answer {
    background-color: #f8d7da;
    color: #721c24;
}

.ep-submit-quiz {
    margin-top: 20px;
    padding: 12px 30px;
    background-color: #8e44ad;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
}

.ep-resources-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ep-resource-card {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.ep-resource-card h4 {
    margin-bottom: 10px;
}

.ep-resource-card h4 a {
    color: #8e44ad;
    text-decoration: none;
}

.ep-resource-type-tag {
    display: inline-block;
    background-color: #e9ecef;
    padding: 4px 8px;
    border-radius: 10px;
    font-size: 0.8em;
    margin-top: 10px;
}

.ep-progress-section {
    background-color: #f8f9fa;
    padding: 30px;
    border-radius: 8px;
    text-align: center;
}

.ep-progress-bar {
    width: 100%;
    height: 10px;
    background-color: #e9ecef;
    border-radius: 5px;
    overflow: hidden;
    margin: 20px 0;
}

.ep-progress-fill {
    height: 100%;
    background-color: #27ae60;
    transition: width 0.3s ease;
    width: 0%;
}

.ep-progress-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.ep-education-sidebar {
    padding-left: 30px;
}

.ep-widget {
    background-color: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 30px;
}

.ep-widget h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.1em;
    font-weight: 600;
}

.ep-info-list dt {
    font-weight: 600;
    color: #495057;
    margin-top: 10px;
}

.ep-info-list dd {
    margin-bottom: 10px;
    margin-left: 0;
}

.ep-topic-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.ep-topic-list li {
    margin-bottom: 8px;
}

.ep-topic-link {
    color: #8e44ad;
    text-decoration: none;
    font-weight: 500;
}

.ep-topic-link:hover {
    text-decoration: underline;
}

.ep-download-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.ep-download-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 5px;
    text-decoration: none;
    color: #495057;
    margin-bottom: 10px;
    transition: background-color 0.3s ease;
}

.ep-download-link:hover {
    background-color: #e9ecef;
}

.ep-file-size {
    margin-left: auto;
    font-size: 0.8em;
    color: #6c757d;
}

.ep-related-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.ep-related-item {
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.ep-related-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.ep-related-link {
    text-decoration: none;
    color: #333;
}

.ep-related-link h4 {
    margin: 0 0 5px 0;
    color: #8e44ad;
    font-size: 0.9em;
}

.ep-related-type {
    font-size: 0.8em;
    color: #6c757d;
}

.ep-share-buttons {
    display: flex;
    gap: 10px;
}

.ep-share-btn {
    flex: 1;
    padding: 10px;
    border: 1px solid #e9ecef;
    background-color: #f8f9fa;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.ep-share-btn:hover {
    background-color: #8e44ad;
    color: white;
}

@media (max-width: 768px) {
    .ep-education-title {
        font-size: 2em;
    }
    
    .ep-progress-actions {
        flex-direction: column;
    }
    
    .ep-education-sidebar {
        padding-left: 0;
        margin-top: 40px;
    }
    
    .ep-resources-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
const quizData = <?php echo json_encode($quiz_questions ?: []); ?>;

function epShareResource() {
    if (navigator.share) {
        navigator.share({
            title: document.title,
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href).then(function() {
            alert('<?php _e('Link copied to clipboard!', 'environmental-platform-core'); ?>');
        });
    }
}

function epShareByEmail() {
    const subject = encodeURIComponent(document.title);
    const body = encodeURIComponent('Check out this educational resource: ' + window.location.href);
    window.location.href = `mailto:?subject=${subject}&body=${body}`;
}

// Quiz functionality
document.addEventListener('DOMContentLoaded', function() {
    const submitButton = document.querySelector('.ep-submit-quiz');
    if (submitButton) {
        submitButton.addEventListener('click', function() {
            checkQuizAnswers();
        });
    }
    
    // Track progress
    updateProgress();
});

function checkQuizAnswers() {
    const questions = document.querySelectorAll('.ep-quiz-question');
    let correctAnswers = 0;
    
    questions.forEach((question, index) => {
        const selectedOption = question.querySelector('input[name="question_' + index + '"]:checked');
        const feedback = question.querySelector('.ep-quiz-feedback');
        const correctAnswer = question.querySelector('.ep-correct-answer');
        const wrongAnswer = question.querySelector('.ep-wrong-answer');
        
        feedback.style.display = 'block';
        
        if (selectedOption && quizData[index]) {
            const selectedValue = parseInt(selectedOption.value);
            const correctValue = quizData[index].correct;
            
            if (selectedValue === correctValue) {
                correctAnswer.style.display = 'block';
                wrongAnswer.style.display = 'none';
                correctAnswers++;
            } else {
                correctAnswer.style.display = 'none';
                wrongAnswer.style.display = 'block';
            }
        }
    });
    
    // Show results
    const resultsDiv = document.querySelector('.ep-quiz-results');
    if (resultsDiv) {
        const percentage = Math.round((correctAnswers / questions.length) * 100);
        resultsDiv.innerHTML = `
            <h4><?php _e('Quiz Results', 'environmental-platform-core'); ?></h4>
            <p><?php _e('You scored', 'environmental-platform-core'); ?> ${correctAnswers}/${questions.length} (${percentage}%)</p>
        `;
        resultsDiv.style.display = 'block';
    }
    
    // Update progress
    updateProgress();
}

function updateProgress() {
    const progressFill = document.querySelector('.ep-progress-fill');
    if (progressFill) {
        // Calculate progress based on page scroll and interactions
        const scrollPercent = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
        progressFill.style.width = Math.min(scrollPercent, 100) + '%';
    }
}

// Update progress on scroll
window.addEventListener('scroll', updateProgress);

// Mark as complete functionality
document.querySelector('.ep-mark-complete')?.addEventListener('click', function() {
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=ep_mark_complete&post_id=<?php echo get_the_ID(); ?>&nonce=<?php echo wp_create_nonce('ep_complete_nonce'); ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            this.textContent = '<?php _e('Completed!', 'environmental-platform-core'); ?>';
            this.disabled = true;
            const progressFill = document.querySelector('.ep-progress-fill');
            if (progressFill) {
                progressFill.style.width = '100%';
            }
        }
    });
});
</script>

<?php get_footer(); ?>
