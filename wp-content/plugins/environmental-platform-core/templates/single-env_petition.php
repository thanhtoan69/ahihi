<?php
/**
 * Single Environmental Petition Template
 *
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="ep-petition-container">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('ep-petition-single'); ?>>
            
            <!-- Petition Header -->
            <header class="ep-petition-header">
                <div class="ep-petition-hero">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="ep-petition-image">
                            <?php the_post_thumbnail('large'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="ep-petition-info">
                        <h1 class="ep-petition-title"><?php the_title(); ?></h1>
                        
                        <div class="ep-petition-meta">
                            <div class="ep-meta-item">
                                <i class="dashicons dashicons-admin-users"></i>
                                <span><?php _e('Started by', 'environmental-platform-core'); ?> <?php the_author(); ?></span>
                            </div>
                            <div class="ep-meta-item">
                                <i class="dashicons dashicons-calendar-alt"></i>
                                <span><?php echo get_the_date(); ?></span>
                            </div>
                            <?php
                            $petition_type = get_the_terms(get_the_ID(), 'petition_type');
                            if ($petition_type && !is_wp_error($petition_type)) :
                            ?>
                                <div class="ep-meta-item">
                                    <i class="dashicons dashicons-tag"></i>
                                    <span><?php echo esc_html($petition_type[0]->name); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Petition Progress -->
                        <?php
                        $target_signatures = get_post_meta(get_the_ID(), '_ep_target_signatures', true);
                        $current_signatures = get_post_meta(get_the_ID(), '_ep_current_signatures', true);
                        $target_signatures = $target_signatures ?: 1000;
                        $current_signatures = $current_signatures ?: 0;
                        $progress_percentage = min(100, ($current_signatures / $target_signatures) * 100);
                        ?>
                        
                        <div class="ep-petition-progress">
                            <div class="ep-progress-stats">
                                <div class="ep-signatures-count">
                                    <span class="ep-current"><?php echo number_format($current_signatures); ?></span>
                                    <span class="ep-separator">/</span>
                                    <span class="ep-target"><?php echo number_format($target_signatures); ?></span>
                                    <span class="ep-label"><?php _e('signatures', 'environmental-platform-core'); ?></span>
                                </div>
                                <div class="ep-progress-percentage">
                                    <?php echo round($progress_percentage); ?>%
                                </div>
                            </div>
                            
                            <div class="ep-progress-bar">
                                <div class="ep-progress-fill" style="width: <?php echo esc_attr($progress_percentage); ?>%"></div>
                            </div>
                            
                            <div class="ep-progress-text">
                                <?php 
                                $remaining = $target_signatures - $current_signatures;
                                if ($remaining > 0) {
                                    printf(
                                        __('%s more signatures needed to reach the goal', 'environmental-platform-core'),
                                        number_format($remaining)
                                    );
                                } else {
                                    _e('Goal achieved! Help us reach even more supporters.', 'environmental-platform-core');
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Sign Petition Button -->
                        <div class="ep-petition-action">
                            <button class="ep-sign-btn" onclick="showSignForm()">
                                <i class="dashicons dashicons-edit"></i>
                                <?php _e('Sign This Petition', 'environmental-platform-core'); ?>
                            </button>
                            <div class="ep-share-petition">
                                <span><?php _e('Share:', 'environmental-platform-core'); ?></span>
                                <a href="#" class="ep-share-link" onclick="sharePetition('facebook')">
                                    <i class="dashicons dashicons-facebook"></i>
                                </a>
                                <a href="#" class="ep-share-link" onclick="sharePetition('twitter')">
                                    <i class="dashicons dashicons-twitter"></i>
                                </a>
                                <a href="#" class="ep-share-link" onclick="sharePetition('email')">
                                    <i class="dashicons dashicons-email"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Petition Content -->
            <div class="ep-petition-content">
                <div class="ep-petition-main">
                    <!-- Petition Description -->
                    <section class="ep-petition-description">
                        <h2><?php _e('The Issue', 'environmental-platform-core'); ?></h2>
                        <div class="ep-description-content">
                            <?php the_content(); ?>
                        </div>
                    </section>

                    <!-- Problem Statement -->
                    <?php
                    $problem_statement = get_post_meta(get_the_ID(), '_ep_problem_statement', true);
                    if ($problem_statement):
                    ?>
                        <section class="ep-problem-statement">
                            <h2><?php _e('The Problem', 'environmental-platform-core'); ?></h2>
                            <div class="ep-problem-content">
                                <?php echo wp_kses_post($problem_statement); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Proposed Solution -->
                    <?php
                    $proposed_solution = get_post_meta(get_the_ID(), '_ep_proposed_solution', true);
                    if ($proposed_solution):
                    ?>
                        <section class="ep-proposed-solution">
                            <h2><?php _e('Our Solution', 'environmental-platform-core'); ?></h2>
                            <div class="ep-solution-content">
                                <?php echo wp_kses_post($proposed_solution); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Key Facts & Figures -->
                    <?php
                    $key_facts = get_post_meta(get_the_ID(), '_ep_key_facts', true);
                    if ($key_facts && is_array($key_facts)):
                    ?>
                        <section class="ep-key-facts">
                            <h2><?php _e('Key Facts & Figures', 'environmental-platform-core'); ?></h2>
                            <div class="ep-facts-grid">
                                <?php foreach ($key_facts as $fact): ?>
                                    <div class="ep-fact-item">
                                        <div class="ep-fact-number"><?php echo esc_html($fact['number']); ?></div>
                                        <div class="ep-fact-description"><?php echo esc_html($fact['description']); ?></div>
                                        <?php if (!empty($fact['source'])): ?>
                                            <div class="ep-fact-source">
                                                <small><?php _e('Source:', 'environmental-platform-core'); ?> <?php echo esc_html($fact['source']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Call to Action -->
                    <?php
                    $call_to_action = get_post_meta(get_the_ID(), '_ep_call_to_action', true);
                    if ($call_to_action):
                    ?>
                        <section class="ep-call-to-action">
                            <h2><?php _e('Take Action Now', 'environmental-platform-core'); ?></h2>
                            <div class="ep-cta-content">
                                <?php echo wp_kses_post($call_to_action); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Petition Updates -->
                    <?php
                    $updates = get_post_meta(get_the_ID(), '_ep_petition_updates', true);
                    if ($updates && is_array($updates)):
                    ?>
                        <section class="ep-petition-updates">
                            <h2><?php _e('Updates', 'environmental-platform-core'); ?></h2>
                            <div class="ep-updates-timeline">
                                <?php foreach ($updates as $update): ?>
                                    <div class="ep-update-item">
                                        <div class="ep-update-date">
                                            <?php echo date('M j, Y', strtotime($update['date'])); ?>
                                        </div>
                                        <div class="ep-update-content">
                                            <h3><?php echo esc_html($update['title']); ?></h3>
                                            <p><?php echo wp_kses_post($update['content']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Recent Signatures -->
                    <?php
                    $recent_signatures = get_post_meta(get_the_ID(), '_ep_recent_signatures', true);
                    if ($recent_signatures && is_array($recent_signatures)):
                    ?>
                        <section class="ep-recent-signatures">
                            <h2><?php _e('Recent Supporters', 'environmental-platform-core'); ?></h2>
                            <div class="ep-signatures-list">
                                <?php foreach (array_slice($recent_signatures, 0, 10) as $signature): ?>
                                    <div class="ep-signature-item">
                                        <div class="ep-signature-avatar">
                                            <?php echo get_avatar($signature['email'], 40); ?>
                                        </div>
                                        <div class="ep-signature-info">
                                            <div class="ep-signature-name"><?php echo esc_html($signature['name']); ?></div>
                                            <div class="ep-signature-location"><?php echo esc_html($signature['location']); ?></div>
                                            <?php if (!empty($signature['comment'])): ?>
                                                <div class="ep-signature-comment">"<?php echo esc_html($signature['comment']); ?>"</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ep-signature-date">
                                            <?php echo human_time_diff(strtotime($signature['date']), current_time('timestamp')) . ' ago'; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (count($recent_signatures) > 10): ?>
                                <div class="ep-view-all-signatures">
                                    <button class="ep-view-all-btn" onclick="viewAllSignatures()">
                                        <?php _e('View All Signatures', 'environmental-platform-core'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                </div>

                <!-- Petition Sidebar -->
                <aside class="ep-petition-sidebar">
                    <!-- Petition Status -->
                    <div class="ep-sidebar-widget ep-petition-status">
                        <h3><?php _e('Petition Status', 'environmental-platform-core'); ?></h3>
                        <div class="ep-status-info">
                            <div class="ep-status-item">
                                <span class="ep-status-label"><?php _e('Target:', 'environmental-platform-core'); ?></span>
                                <span class="ep-status-value"><?php echo esc_html(get_post_meta(get_the_ID(), '_ep_petition_target', true)); ?></span>
                            </div>
                            
                            <?php
                            $deadline = get_post_meta(get_the_ID(), '_ep_deadline', true);
                            if ($deadline):
                            ?>
                                <div class="ep-status-item">
                                    <span class="ep-status-label"><?php _e('Deadline:', 'environmental-platform-core'); ?></span>
                                    <span class="ep-status-value"><?php echo date('M j, Y', strtotime($deadline)); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="ep-status-item">
                                <span class="ep-status-label"><?php _e('Category:', 'environmental-platform-core'); ?></span>
                                <span class="ep-status-value">
                                    <?php
                                    $categories = get_the_terms(get_the_ID(), 'env_category');
                                    if ($categories && !is_wp_error($categories)) {
                                        echo esc_html($categories[0]->name);
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Petition Organizer -->
                    <div class="ep-sidebar-widget ep-petition-organizer">
                        <h3><?php _e('Petition Organizer', 'environmental-platform-core'); ?></h3>
                        <div class="ep-organizer-info">
                            <div class="ep-organizer-avatar">
                                <?php echo get_avatar(get_the_author_meta('email'), 60); ?>
                            </div>
                            <div class="ep-organizer-details">
                                <h4><?php the_author(); ?></h4>
                                <p><?php echo esc_html(get_the_author_meta('description')); ?></p>
                                <a href="mailto:<?php echo esc_attr(get_the_author_meta('email')); ?>" class="ep-contact-organizer">
                                    <i class="dashicons dashicons-email"></i>
                                    <?php _e('Contact Organizer', 'environmental-platform-core'); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Share Widget -->
                    <div class="ep-sidebar-widget ep-share-widget">
                        <h3><?php _e('Spread the Word', 'environmental-platform-core'); ?></h3>
                        <p><?php _e('Help this petition reach more people by sharing it on social media.', 'environmental-platform-core'); ?></p>
                        <div class="ep-share-buttons">
                            <button class="ep-share-btn ep-share-facebook" onclick="sharePetition('facebook')">
                                <i class="dashicons dashicons-facebook"></i>
                                <?php _e('Facebook', 'environmental-platform-core'); ?>
                            </button>
                            <button class="ep-share-btn ep-share-twitter" onclick="sharePetition('twitter')">
                                <i class="dashicons dashicons-twitter"></i>
                                <?php _e('Twitter', 'environmental-platform-core'); ?>
                            </button>
                            <button class="ep-share-btn ep-share-email" onclick="sharePetition('email')">
                                <i class="dashicons dashicons-email"></i>
                                <?php _e('Email', 'environmental-platform-core'); ?>
                            </button>
                            <button class="ep-share-btn ep-share-copy" onclick="copyPetitionLink()">
                                <i class="dashicons dashicons-admin-links"></i>
                                <?php _e('Copy Link', 'environmental-platform-core'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Related Petitions -->
                    <?php
                    $related_petitions = new WP_Query(array(
                        'post_type' => 'env_petition',
                        'posts_per_page' => 3,
                        'post__not_in' => array(get_the_ID())
                    ));
                    ?>
                    
                    <?php if ($related_petitions->have_posts()) : ?>
                        <div class="ep-sidebar-widget ep-related-petitions">
                            <h3><?php _e('Related Petitions', 'environmental-platform-core'); ?></h3>
                            <div class="ep-related-list">
                                <?php while ($related_petitions->have_posts()) : $related_petitions->the_post(); ?>
                                    <div class="ep-related-item">
                                        <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                        <p><?php echo wp_trim_words(get_the_excerpt(), 15); ?></p>
                                        <div class="ep-related-progress">
                                            <?php
                                            $related_current = get_post_meta(get_the_ID(), '_ep_current_signatures', true);
                                            $related_target = get_post_meta(get_the_ID(), '_ep_target_signatures', true);
                                            $related_progress = $related_target ? min(100, ($related_current / $related_target) * 100) : 0;
                                            ?>
                                            <div class="ep-mini-progress">
                                                <div class="ep-mini-fill" style="width: <?php echo esc_attr($related_progress); ?>%"></div>
                                            </div>
                                            <span class="ep-mini-stats"><?php echo number_format($related_current); ?> / <?php echo number_format($related_target); ?></span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        <?php wp_reset_postdata(); ?>
                    <?php endif; ?>

                    <!-- Petition Tips -->
                    <div class="ep-sidebar-widget ep-petition-tips">
                        <h3><?php _e('Make a Difference', 'environmental-platform-core'); ?></h3>
                        <ul class="ep-tips-list">
                            <li><?php _e('Sign the petition to show your support', 'environmental-platform-core'); ?></li>
                            <li><?php _e('Share with friends and family', 'environmental-platform-core'); ?></li>
                            <li><?php _e('Follow the petition for updates', 'environmental-platform-core'); ?></li>
                            <li><?php _e('Contact your local representatives', 'environmental-platform-core'); ?></li>
                        </ul>
                    </div>
                </aside>
            </div>

            <!-- Sign Petition Modal -->
            <div id="signModal" class="ep-modal" style="display: none;">
                <div class="ep-modal-content">
                    <span class="ep-modal-close" onclick="hideSignForm()">&times;</span>
                    <h2><?php _e('Sign This Petition', 'environmental-platform-core'); ?></h2>
                    <p><?php _e('Your signature helps make a difference. Every voice counts!', 'environmental-platform-core'); ?></p>
                    
                    <form id="signForm" class="ep-sign-form">
                        <div class="ep-form-group">
                            <label for="signer_name"><?php _e('Full Name', 'environmental-platform-core'); ?> *</label>
                            <input type="text" id="signer_name" name="signer_name" required>
                        </div>
                        
                        <div class="ep-form-group">
                            <label for="signer_email"><?php _e('Email Address', 'environmental-platform-core'); ?> *</label>
                            <input type="email" id="signer_email" name="signer_email" required>
                        </div>
                        
                        <div class="ep-form-group">
                            <label for="signer_location"><?php _e('Location (City, Country)', 'environmental-platform-core'); ?></label>
                            <input type="text" id="signer_location" name="signer_location">
                        </div>
                        
                        <div class="ep-form-group">
                            <label for="signer_comment"><?php _e('Personal Comment (Optional)', 'environmental-platform-core'); ?></label>
                            <textarea id="signer_comment" name="signer_comment" rows="3" placeholder="<?php _e('Why is this important to you?', 'environmental-platform-core'); ?>"></textarea>
                        </div>
                        
                        <div class="ep-form-group ep-checkbox-group">
                            <label>
                                <input type="checkbox" id="updates_consent" name="updates_consent">
                                <?php _e('Keep me updated on this petition and related campaigns', 'environmental-platform-core'); ?>
                            </label>
                        </div>
                        
                        <div class="ep-form-group ep-checkbox-group">
                            <label>
                                <input type="checkbox" id="public_signature" name="public_signature" checked>
                                <?php _e('Display my name publicly on this petition', 'environmental-platform-core'); ?>
                            </label>
                        </div>
                        
                        <div class="ep-form-actions">
                            <button type="submit" class="ep-submit-btn">
                                <i class="dashicons dashicons-edit"></i>
                                <?php _e('Add My Signature', 'environmental-platform-core'); ?>
                            </button>
                            <button type="button" class="ep-cancel-btn" onclick="hideSignForm()">
                                <?php _e('Cancel', 'environmental-platform-core'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </article>
    <?php endwhile; ?>
</div>

<style>
.ep-petition-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.ep-petition-single {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.ep-petition-hero {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 40px;
    padding: 40px;
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
}

.ep-petition-image img {
    width: 100%;
    max-width: 400px;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

.ep-petition-title {
    font-size: 2.5em;
    margin: 0 0 20px 0;
    font-weight: bold;
    line-height: 1.2;
}

.ep-petition-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
    opacity: 0.9;
}

.ep-meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}

.ep-petition-progress {
    background: rgba(255,255,255,0.1);
    padding: 25px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.ep-progress-stats {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.ep-signatures-count {
    font-size: 1.2em;
}

.ep-current {
    font-size: 2em;
    font-weight: bold;
}

.ep-separator {
    margin: 0 10px;
    opacity: 0.7;
}

.ep-target {
    font-size: 1.2em;
    opacity: 0.8;
}

.ep-progress-percentage {
    font-size: 1.5em;
    font-weight: bold;
}

.ep-progress-bar {
    height: 20px;
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 10px;
}

.ep-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    transition: width 0.5s ease;
}

.ep-progress-text {
    text-align: center;
    opacity: 0.9;
    font-size: 0.9em;
}

.ep-petition-action {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.ep-sign-btn {
    background: #fff;
    color: #dc3545;
    border: none;
    padding: 15px 30px;
    border-radius: 25px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}

.ep-sign-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255,255,255,0.3);
}

.ep-share-petition {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ep-share-link {
    color: white;
    font-size: 20px;
    padding: 8px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    transition: background 0.3s ease;
}

.ep-share-link:hover {
    background: rgba(255,255,255,0.3);
    color: white;
}

.ep-petition-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
    padding: 40px;
}

.ep-petition-main section {
    margin-bottom: 40px;
}

.ep-petition-main h2 {
    color: #dc3545;
    border-bottom: 2px solid #dc3545;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.ep-facts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ep-fact-item {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
    text-align: center;
    border-left: 4px solid #dc3545;
}

.ep-fact-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #dc3545;
    margin-bottom: 10px;
}

.ep-fact-description {
    font-weight: 600;
    margin-bottom: 10px;
    color: #495057;
}

.ep-fact-source {
    color: #6c757d;
    font-style: italic;
}

.ep-updates-timeline {
    position: relative;
    padding-left: 30px;
}

.ep-update-item {
    position: relative;
    margin-bottom: 30px;
    padding-left: 40px;
}

.ep-update-item::before {
    content: '';
    position: absolute;
    left: -15px;
    top: 25px;
    bottom: -30px;
    width: 2px;
    background: #dee2e6;
}

.ep-update-item:last-child::before {
    display: none;
}

.ep-update-date {
    position: absolute;
    left: -35px;
    top: 0;
    background: #dc3545;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.9em;
    font-weight: bold;
    white-space: nowrap;
}

.ep-update-content h3 {
    margin: 0 0 10px 0;
    color: #495057;
}

.ep-signatures-list {
    max-height: 500px;
    overflow-y: auto;
}

.ep-signature-item {
    display: flex;
    gap: 15px;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #dee2e6;
}

.ep-signature-avatar img {
    border-radius: 50%;
}

.ep-signature-info {
    flex: 1;
}

.ep-signature-name {
    font-weight: 600;
    color: #495057;
}

.ep-signature-location {
    color: #6c757d;
    font-size: 0.9em;
}

.ep-signature-comment {
    font-style: italic;
    color: #6c757d;
    margin-top: 5px;
    font-size: 0.9em;
}

.ep-signature-date {
    color: #6c757d;
    font-size: 0.8em;
}

.ep-view-all-signatures {
    text-align: center;
    margin-top: 20px;
}

.ep-view-all-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
}

.ep-petition-sidebar {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.ep-sidebar-widget {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
    border-left: 4px solid #dc3545;
}

.ep-sidebar-widget h3 {
    margin: 0 0 20px 0;
    color: #dc3545;
    font-size: 1.2em;
}

.ep-status-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #dee2e6;
}

.ep-status-label {
    font-weight: 600;
    color: #495057;
}

.ep-status-value {
    color: #dc3545;
    font-weight: 600;
}

.ep-organizer-info {
    display: flex;
    gap: 15px;
    align-items: center;
}

.ep-organizer-avatar img {
    border-radius: 50%;
}

.ep-organizer-details h4 {
    margin: 0 0 5px 0;
}

.ep-organizer-details p {
    margin: 0 0 10px 0;
    color: #6c757d;
    font-size: 0.9em;
}

.ep-contact-organizer {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #dc3545;
    color: white;
    padding: 8px 15px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    transition: background 0.3s ease;
}

.ep-contact-organizer:hover {
    background: #c82333;
    color: white;
}

.ep-share-buttons {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.ep-share-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.ep-share-facebook { background: #3b5998; color: white; }
.ep-share-twitter { background: #1da1f2; color: white; }
.ep-share-email { background: #6c757d; color: white; }
.ep-share-copy { background: #28a745; color: white; }

.ep-share-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.ep-related-item {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #dee2e6;
}

.ep-related-item h4 {
    margin: 0 0 5px 0;
    font-size: 0.9em;
}

.ep-related-item h4 a {
    color: #495057;
    text-decoration: none;
}

.ep-related-item h4 a:hover {
    color: #dc3545;
}

.ep-related-item p {
    margin: 0 0 10px 0;
    color: #6c757d;
    font-size: 0.8em;
}

.ep-related-progress {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ep-mini-progress {
    flex: 1;
    height: 8px;
    background: #dee2e6;
    border-radius: 4px;
    overflow: hidden;
}

.ep-mini-fill {
    height: 100%;
    background: #dc3545;
    transition: width 0.3s ease;
}

.ep-mini-stats {
    font-size: 0.8em;
    color: #6c757d;
}

.ep-tips-list {
    list-style: none;
    padding: 0;
}

.ep-tips-list li {
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
    position: relative;
    padding-left: 25px;
}

.ep-tips-list li::before {
    content: "✊";
    position: absolute;
    left: 0;
    top: 10px;
}

/* Modal Styles */
.ep-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
}

.ep-modal-content {
    background: white;
    padding: 30px;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.ep-modal-close {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 24px;
    cursor: pointer;
    color: #6c757d;
}

.ep-modal-close:hover {
    color: #000;
}

.ep-sign-form {
    margin-top: 20px;
}

.ep-form-group {
    margin-bottom: 20px;
}

.ep-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #495057;
}

.ep-form-group input,
.ep-form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.ep-form-group input:focus,
.ep-form-group textarea:focus {
    outline: none;
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.ep-checkbox-group label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: normal;
}

.ep-checkbox-group input[type="checkbox"] {
    width: auto;
}

.ep-form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 30px;
}

.ep-submit-btn {
    background: #dc3545;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.ep-submit-btn:hover {
    background: #c82333;
}

.ep-cancel-btn {
    background: #6c757d;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 4px;
    cursor: pointer;
}

.ep-cancel-btn:hover {
    background: #5a6268;
}

/* Responsive Design */
@media (max-width: 768px) {
    .ep-petition-container {
        padding: 10px;
    }
    
    .ep-petition-hero {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .ep-petition-content {
        grid-template-columns: 1fr;
        padding: 20px;
    }
    
    .ep-petition-title {
        font-size: 2em;
    }
    
    .ep-petition-action {
        flex-direction: column;
        align-items: center;
    }
    
    .ep-facts-grid {
        grid-template-columns: 1fr;
    }
    
    .ep-organizer-info {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .ep-petition-meta {
        flex-direction: column;
        gap: 10px;
    }
    
    .ep-signature-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .ep-form-actions {
        flex-direction: column;
    }
}
</style>

<script>
function showSignForm() {
    document.getElementById('signModal').style.display = 'flex';
}

function hideSignForm() {
    document.getElementById('signModal').style.display = 'none';
}

function sharePetition(platform) {
    const url = window.location.href;
    const title = document.title;
    
    switch(platform) {
        case 'facebook':
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank');
            break;
        case 'twitter':
            window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`, '_blank');
            break;
        case 'email':
            window.location.href = `mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(url)}`;
            break;
    }
}

function copyPetitionLink() {
    navigator.clipboard.writeText(window.location.href).then(function() {
        alert('Link copied to clipboard!');
    });
}

function viewAllSignatures() {
    // This would typically load more signatures via AJAX
    alert('Loading all signatures...');
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('signModal');
    if (event.target == modal) {
        hideSignForm();
    }
}

// Handle form submission
document.getElementById('signForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Here you would typically send the form data via AJAX
    alert('Thank you for signing! Your signature has been added.');
    hideSignForm();
    this.reset();
    
    // Update signature count (in a real implementation, this would be done server-side)
    // You could also refresh the page or update the progress bar dynamically
});
</script>

<?php get_footer(); ?>
