<?php
/**
 * Single Environmental Project Template
 *
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="ep-project-container">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('ep-project-single'); ?>>
            
            <!-- Project Header -->
            <header class="ep-project-header">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="ep-project-hero">
                        <?php the_post_thumbnail('full'); ?>
                        <div class="ep-project-overlay">
                            <div class="ep-project-header-content">
                                <h1 class="ep-project-title"><?php the_title(); ?></h1>
                                
                                <div class="ep-project-status">
                                    <?php
                                    $status = get_post_meta(get_the_ID(), '_ep_project_status', true);
                                    $status_class = 'status-' . strtolower(str_replace(' ', '-', $status));
                                    ?>
                                    <span class="ep-status-badge <?php echo esc_attr($status_class); ?>">
                                        <?php echo esc_html($status ?: 'Planning'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="ep-project-header-simple">
                        <h1 class="ep-project-title"><?php the_title(); ?></h1>
                        <div class="ep-project-status">
                            <?php
                            $status = get_post_meta(get_the_ID(), '_ep_project_status', true);
                            $status_class = 'status-' . strtolower(str_replace(' ', '-', $status));
                            ?>
                            <span class="ep-status-badge <?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($status ?: 'Planning'); ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            </header>

            <!-- Project Info Bar -->
            <div class="ep-project-info-bar">
                <div class="ep-info-item">
                    <i class="dashicons dashicons-calendar-alt"></i>
                    <span class="ep-info-label"><?php _e('Start Date:', 'environmental-platform-core'); ?></span>
                    <span class="ep-info-value">
                        <?php 
                        $start_date = get_post_meta(get_the_ID(), '_ep_start_date', true);
                        echo $start_date ? date('M j, Y', strtotime($start_date)) : __('TBD', 'environmental-platform-core');
                        ?>
                    </span>
                </div>
                
                <div class="ep-info-item">
                    <i class="dashicons dashicons-clock"></i>
                    <span class="ep-info-label"><?php _e('Duration:', 'environmental-platform-core'); ?></span>
                    <span class="ep-info-value">
                        <?php 
                        $duration = get_post_meta(get_the_ID(), '_ep_duration', true);
                        echo esc_html($duration ?: __('Ongoing', 'environmental-platform-core'));
                        ?>
                    </span>
                </div>
                
                <div class="ep-info-item">
                    <i class="dashicons dashicons-location-alt"></i>
                    <span class="ep-info-label"><?php _e('Location:', 'environmental-platform-core'); ?></span>
                    <span class="ep-info-value">
                        <?php 
                        $location = get_post_meta(get_the_ID(), '_ep_location', true);
                        echo esc_html($location ?: __('Various', 'environmental-platform-core'));
                        ?>
                    </span>
                </div>
                
                <div class="ep-info-item">
                    <i class="dashicons dashicons-admin-users"></i>
                    <span class="ep-info-label"><?php _e('Team Size:', 'environmental-platform-core'); ?></span>
                    <span class="ep-info-value">
                        <?php 
                        $team_size = get_post_meta(get_the_ID(), '_ep_team_size', true);
                        echo esc_html($team_size ?: '1');
                        ?>
                    </span>
                </div>
            </div>

            <!-- Project Content -->
            <div class="ep-project-content">
                <div class="ep-project-main">
                    <!-- Project Description -->
                    <section class="ep-project-description">
                        <h2><?php _e('Project Overview', 'environmental-platform-core'); ?></h2>
                        <div class="ep-description-content">
                            <?php the_content(); ?>
                        </div>
                    </section>

                    <!-- Project Goals -->
                    <?php
                    $goals = get_post_meta(get_the_ID(), '_ep_project_goals', true);
                    if ($goals):
                    ?>
                        <section class="ep-project-goals">
                            <h2><?php _e('Project Goals', 'environmental-platform-core'); ?></h2>
                            <div class="ep-goals-content">
                                <?php echo wp_kses_post($goals); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Progress Tracking -->
                    <?php
                    $progress = get_post_meta(get_the_ID(), '_ep_progress_percentage', true);
                    $milestones = get_post_meta(get_the_ID(), '_ep_milestones', true);
                    ?>
                    
                    <section class="ep-project-progress">
                        <h2><?php _e('Project Progress', 'environmental-platform-core'); ?></h2>
                        
                        <?php if ($progress): ?>
                            <div class="ep-progress-bar">
                                <div class="ep-progress-label">
                                    <span><?php _e('Overall Progress', 'environmental-platform-core'); ?></span>
                                    <span class="ep-progress-percentage"><?php echo esc_html($progress); ?>%</span>
                                </div>
                                <div class="ep-progress-track">
                                    <div class="ep-progress-fill" style="width: <?php echo esc_attr($progress); ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($milestones && is_array($milestones)): ?>
                            <div class="ep-milestones">
                                <h3><?php _e('Project Milestones', 'environmental-platform-core'); ?></h3>
                                <div class="ep-milestones-timeline">
                                    <?php foreach ($milestones as $milestone): ?>
                                        <div class="ep-milestone-item <?php echo $milestone['completed'] ? 'completed' : ''; ?>">
                                            <div class="ep-milestone-marker">
                                                <i class="dashicons <?php echo $milestone['completed'] ? 'dashicons-yes-alt' : 'dashicons-clock'; ?>"></i>
                                            </div>
                                            <div class="ep-milestone-content">
                                                <h4><?php echo esc_html($milestone['title']); ?></h4>
                                                <p><?php echo esc_html($milestone['description']); ?></p>
                                                <span class="ep-milestone-date"><?php echo date('M j, Y', strtotime($milestone['date'])); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </section>

                    <!-- Environmental Impact -->
                    <?php
                    $environmental_impact = get_post_meta(get_the_ID(), '_ep_environmental_impact', true);
                    if ($environmental_impact):
                    ?>
                        <section class="ep-environmental-impact">
                            <h2><?php _e('Environmental Impact', 'environmental-platform-core'); ?></h2>
                            <div class="ep-impact-content">
                                <?php echo wp_kses_post($environmental_impact); ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- How to Get Involved -->
                    <?php
                    $how_to_help = get_post_meta(get_the_ID(), '_ep_how_to_help', true);
                    if ($how_to_help):
                    ?>
                        <section class="ep-get-involved">
                            <h2><?php _e('How You Can Get Involved', 'environmental-platform-core'); ?></h2>
                            <div class="ep-involvement-content">
                                <?php echo wp_kses_post($how_to_help); ?>
                            </div>
                            
                            <!-- Volunteer Button -->
                            <div class="ep-volunteer-cta">
                                <button class="ep-volunteer-btn" onclick="showVolunteerForm()">
                                    <i class="dashicons dashicons-heart"></i>
                                    <?php _e('Volunteer for This Project', 'environmental-platform-core'); ?>
                                </button>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Project Gallery -->
                    <?php
                    $gallery_images = get_post_meta(get_the_ID(), '_ep_gallery_images', true);
                    if ($gallery_images && is_array($gallery_images)):
                    ?>
                        <section class="ep-project-gallery">
                            <h2><?php _e('Project Gallery', 'environmental-platform-core'); ?></h2>
                            <div class="ep-gallery-grid">
                                <?php foreach ($gallery_images as $image_id): ?>
                                    <div class="ep-gallery-item">
                                        <a href="<?php echo wp_get_attachment_url($image_id); ?>" data-lightbox="project-gallery">
                                            <?php echo wp_get_attachment_image($image_id, 'medium'); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>

                <!-- Project Sidebar -->
                <aside class="ep-project-sidebar">
                    <!-- Project Manager -->
                    <?php
                    $project_manager = get_post_meta(get_the_ID(), '_ep_project_manager', true);
                    if ($project_manager):
                    ?>
                        <div class="ep-sidebar-widget ep-project-manager">
                            <h3><?php _e('Project Manager', 'environmental-platform-core'); ?></h3>
                            <div class="ep-manager-info">
                                <div class="ep-manager-avatar">
                                    <?php echo get_avatar($project_manager['email'], 60); ?>
                                </div>
                                <div class="ep-manager-details">
                                    <h4><?php echo esc_html($project_manager['name']); ?></h4>
                                    <p><?php echo esc_html($project_manager['role']); ?></p>
                                    <a href="mailto:<?php echo esc_attr($project_manager['email']); ?>" class="ep-contact-btn">
                                        <i class="dashicons dashicons-email"></i>
                                        <?php _e('Contact', 'environmental-platform-core'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Budget Information -->
                    <?php
                    $budget = get_post_meta(get_the_ID(), '_ep_budget', true);
                    $funding_source = get_post_meta(get_the_ID(), '_ep_funding_source', true);
                    if ($budget || $funding_source):
                    ?>
                        <div class="ep-sidebar-widget ep-project-budget">
                            <h3><?php _e('Project Funding', 'environmental-platform-core'); ?></h3>
                            <?php if ($budget): ?>
                                <div class="ep-budget-item">
                                    <span class="ep-budget-label"><?php _e('Total Budget:', 'environmental-platform-core'); ?></span>
                                    <span class="ep-budget-amount">$<?php echo number_format($budget); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($funding_source): ?>
                                <div class="ep-budget-item">
                                    <span class="ep-budget-label"><?php _e('Funding Source:', 'environmental-platform-core'); ?></span>
                                    <span class="ep-funding-source"><?php echo esc_html($funding_source); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Project Stats -->
                    <div class="ep-sidebar-widget ep-project-stats">
                        <h3><?php _e('Project Statistics', 'environmental-platform-core'); ?></h3>
                        <div class="ep-stats-grid">
                            <div class="ep-stat-item">
                                <div class="ep-stat-number"><?php echo get_post_meta(get_the_ID(), '_ep_volunteers_count', true) ?: '0'; ?></div>
                                <div class="ep-stat-label"><?php _e('Volunteers', 'environmental-platform-core'); ?></div>
                            </div>
                            <div class="ep-stat-item">
                                <div class="ep-stat-number"><?php echo get_post_meta(get_the_ID(), '_ep_trees_planted', true) ?: '0'; ?></div>
                                <div class="ep-stat-label"><?php _e('Trees Planted', 'environmental-platform-core'); ?></div>
                            </div>
                            <div class="ep-stat-item">
                                <div class="ep-stat-number"><?php echo get_post_meta(get_the_ID(), '_ep_waste_collected', true) ?: '0'; ?></div>
                                <div class="ep-stat-label"><?php _e('Waste (kg)', 'environmental-platform-core'); ?></div>
                            </div>
                            <div class="ep-stat-item">
                                <div class="ep-stat-number"><?php echo get_post_meta(get_the_ID(), '_ep_co2_saved', true) ?: '0'; ?></div>
                                <div class="ep-stat-label"><?php _e('CO₂ Saved (kg)', 'environmental-platform-core'); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Updates -->
                    <?php
                    $updates = get_post_meta(get_the_ID(), '_ep_project_updates', true);
                    if ($updates && is_array($updates)):
                    ?>
                        <div class="ep-sidebar-widget ep-project-updates">
                            <h3><?php _e('Recent Updates', 'environmental-platform-core'); ?></h3>
                            <div class="ep-updates-list">
                                <?php foreach (array_slice($updates, 0, 3) as $update): ?>
                                    <div class="ep-update-item">
                                        <div class="ep-update-date"><?php echo date('M j', strtotime($update['date'])); ?></div>
                                        <div class="ep-update-content">
                                            <h4><?php echo esc_html($update['title']); ?></h4>
                                            <p><?php echo esc_html($update['content']); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Related Projects -->
                    <?php
                    $related_projects = new WP_Query(array(
                        'post_type' => 'env_project',
                        'posts_per_page' => 3,
                        'post__not_in' => array(get_the_ID())
                    ));
                    ?>
                    
                    <?php if ($related_projects->have_posts()) : ?>
                        <div class="ep-sidebar-widget ep-related-projects">
                            <h3><?php _e('Related Projects', 'environmental-platform-core'); ?></h3>
                            <div class="ep-related-list">
                                <?php while ($related_projects->have_posts()) : $related_projects->the_post(); ?>
                                    <div class="ep-related-project">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <div class="ep-related-thumbnail">
                                                <a href="<?php the_permalink(); ?>">
                                                    <?php the_post_thumbnail('thumbnail'); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <div class="ep-related-content">
                                            <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                            <p><?php echo wp_trim_words(get_the_excerpt(), 10); ?></p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        <?php wp_reset_postdata(); ?>
                    <?php endif; ?>
                </aside>
            </div>

            <!-- Volunteer Form Modal -->
            <div id="volunteerModal" class="ep-modal" style="display: none;">
                <div class="ep-modal-content">
                    <span class="ep-modal-close" onclick="hideVolunteerForm()">&times;</span>
                    <h2><?php _e('Volunteer for This Project', 'environmental-platform-core'); ?></h2>
                    <form id="volunteerForm" class="ep-volunteer-form">
                        <div class="ep-form-group">
                            <label for="volunteer_name"><?php _e('Full Name', 'environmental-platform-core'); ?></label>
                            <input type="text" id="volunteer_name" name="volunteer_name" required>
                        </div>
                        <div class="ep-form-group">
                            <label for="volunteer_email"><?php _e('Email Address', 'environmental-platform-core'); ?></label>
                            <input type="email" id="volunteer_email" name="volunteer_email" required>
                        </div>
                        <div class="ep-form-group">
                            <label for="volunteer_phone"><?php _e('Phone Number', 'environmental-platform-core'); ?></label>
                            <input type="tel" id="volunteer_phone" name="volunteer_phone">
                        </div>
                        <div class="ep-form-group">
                            <label for="volunteer_skills"><?php _e('Relevant Skills/Experience', 'environmental-platform-core'); ?></label>
                            <textarea id="volunteer_skills" name="volunteer_skills" rows="3"></textarea>
                        </div>
                        <div class="ep-form-group">
                            <label for="volunteer_availability"><?php _e('Availability', 'environmental-platform-core'); ?></label>
                            <select id="volunteer_availability" name="volunteer_availability">
                                <option value="weekdays"><?php _e('Weekdays', 'environmental-platform-core'); ?></option>
                                <option value="weekends"><?php _e('Weekends', 'environmental-platform-core'); ?></option>
                                <option value="both"><?php _e('Both', 'environmental-platform-core'); ?></option>
                                <option value="flexible"><?php _e('Flexible', 'environmental-platform-core'); ?></option>
                            </select>
                        </div>
                        <div class="ep-form-actions">
                            <button type="submit" class="ep-submit-btn"><?php _e('Submit Application', 'environmental-platform-core'); ?></button>
                            <button type="button" class="ep-cancel-btn" onclick="hideVolunteerForm()"><?php _e('Cancel', 'environmental-platform-core'); ?></button>
                        </div>
                    </form>
                </div>
            </div>

        </article>
    <?php endwhile; ?>
</div>

<style>
.ep-project-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.ep-project-single {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.ep-project-hero {
    position: relative;
    height: 400px;
    overflow: hidden;
}

.ep-project-hero img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ep-project-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(0,0,0,0.7), rgba(0,0,0,0.3));
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.ep-project-header-content {
    color: white;
}

.ep-project-title {
    font-size: 3em;
    margin: 0 0 20px 0;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.ep-project-header-simple {
    padding: 40px;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    text-align: center;
}

.ep-status-badge {
    display: inline-block;
    padding: 8px 20px;
    border-radius: 25px;
    font-size: 14px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-planning { background: #ffc107; color: #000; }
.status-active { background: #28a745; }
.status-completed { background: #007bff; }
.status-on-hold { background: #6c757d; }

.ep-project-info-bar {
    display: flex;
    justify-content: space-around;
    padding: 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    flex-wrap: wrap;
    gap: 10px;
}

.ep-info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
}

.ep-info-label {
    font-weight: 600;
    color: #495057;
}

.ep-info-value {
    color: #007cba;
    font-weight: bold;
}

.ep-project-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
    padding: 40px;
}

.ep-project-main section {
    margin-bottom: 40px;
}

.ep-project-main h2 {
    color: #28a745;
    border-bottom: 2px solid #28a745;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.ep-progress-bar {
    margin-bottom: 30px;
}

.ep-progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-weight: 600;
}

.ep-progress-track {
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}

.ep-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    transition: width 0.5s ease;
}

.ep-milestones-timeline {
    position: relative;
    padding-left: 30px;
}

.ep-milestone-item {
    position: relative;
    margin-bottom: 30px;
    padding-left: 40px;
}

.ep-milestone-item::before {
    content: '';
    position: absolute;
    left: -15px;
    top: 25px;
    bottom: -30px;
    width: 2px;
    background: #dee2e6;
}

.ep-milestone-item.completed::before {
    background: #28a745;
}

.ep-milestone-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 20px;
    height: 20px;
    background: #fff;
    border: 2px solid #dee2e6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ep-milestone-item.completed .ep-milestone-marker {
    border-color: #28a745;
    background: #28a745;
    color: white;
}

.ep-milestone-content h4 {
    margin: 0 0 5px 0;
    color: #495057;
}

.ep-milestone-date {
    color: #6c757d;
    font-size: 0.9em;
}

.ep-volunteer-cta {
    text-align: center;
    margin-top: 30px;
}

.ep-volunteer-btn {
    background: linear-gradient(45deg, #28a745, #20c997);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 25px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.ep-volunteer-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
}

.ep-gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

.ep-gallery-item {
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.ep-gallery-item:hover {
    transform: scale(1.05);
}

.ep-gallery-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.ep-project-sidebar {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.ep-sidebar-widget {
    background: #f8f9fa;
    padding: 25px;
    border-radius: 8px;
    border-left: 4px solid #28a745;
}

.ep-sidebar-widget h3 {
    margin: 0 0 20px 0;
    color: #28a745;
    font-size: 1.2em;
}

.ep-manager-info {
    display: flex;
    gap: 15px;
    align-items: center;
}

.ep-manager-avatar img {
    border-radius: 50%;
}

.ep-manager-details h4 {
    margin: 0 0 5px 0;
}

.ep-manager-details p {
    margin: 0 0 10px 0;
    color: #6c757d;
    font-style: italic;
}

.ep-contact-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #28a745;
    color: white;
    padding: 8px 15px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 14px;
    transition: background 0.3s ease;
}

.ep-contact-btn:hover {
    background: #218838;
    color: white;
}

.ep-budget-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #dee2e6;
}

.ep-budget-amount {
    font-weight: bold;
    color: #28a745;
}

.ep-stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.ep-stat-item {
    text-align: center;
    background: white;
    padding: 20px;
    border-radius: 8px;
}

.ep-stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #28a745;
    margin-bottom: 5px;
}

.ep-stat-label {
    color: #6c757d;
    font-size: 0.9em;
}

.ep-updates-list {
    max-height: 300px;
    overflow-y: auto;
}

.ep-update-item {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #dee2e6;
}

.ep-update-date {
    background: #28a745;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.9em;
    font-weight: bold;
    height: fit-content;
}

.ep-update-content h4 {
    margin: 0 0 5px 0;
    font-size: 1em;
}

.ep-update-content p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9em;
}

.ep-related-project {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #dee2e6;
}

.ep-related-thumbnail {
    flex-shrink: 0;
}

.ep-related-thumbnail img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.ep-related-content h4 {
    margin: 0 0 5px 0;
    font-size: 0.9em;
}

.ep-related-content h4 a {
    color: #495057;
    text-decoration: none;
}

.ep-related-content h4 a:hover {
    color: #28a745;
}

.ep-related-content p {
    margin: 0;
    color: #6c757d;
    font-size: 0.8em;
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

.ep-volunteer-form {
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
.ep-form-group textarea,
.ep-form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.ep-form-group input:focus,
.ep-form-group textarea:focus,
.ep-form-group select:focus {
    outline: none;
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.ep-form-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 30px;
}

.ep-submit-btn {
    background: #28a745;
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
}

.ep-submit-btn:hover {
    background: #218838;
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
    .ep-project-container {
        padding: 10px;
    }
    
    .ep-project-content {
        grid-template-columns: 1fr;
        padding: 20px;
    }
    
    .ep-project-title {
        font-size: 2em;
    }
    
    .ep-project-info-bar {
        flex-direction: column;
        align-items: center;
    }
    
    .ep-gallery-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .ep-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .ep-manager-info {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .ep-gallery-grid {
        grid-template-columns: 1fr;
    }
    
    .ep-form-actions {
        flex-direction: column;
    }
}
</style>

<script>
function showVolunteerForm() {
    document.getElementById('volunteerModal').style.display = 'flex';
}

function hideVolunteerForm() {
    document.getElementById('volunteerModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('volunteerModal');
    if (event.target == modal) {
        hideVolunteerForm();
    }
}

// Handle form submission
document.getElementById('volunteerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Here you would typically send the form data via AJAX
    alert('Thank you for your interest! We will contact you soon.');
    hideVolunteerForm();
    this.reset();
});
</script>

<?php get_footer(); ?>
