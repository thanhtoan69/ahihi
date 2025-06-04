<?php
/**
 * Archive Environmental Events Template
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

get_header(); ?>

<div class="env-events-archive">
    <div class="container">
        <!-- Header Section -->
        <header class="archive-header">
            <div class="header-content">
                <h1 class="archive-title">
                    <i class="fa fa-calendar"></i>
                    <?php _e('Environmental Events', 'environmental-platform-core'); ?>
                </h1>
                <p class="archive-description">
                    <?php _e('Join our community events to make a positive environmental impact. From cleanups to educational workshops, there\'s something for everyone.', 'environmental-platform-core'); ?>
                </p>
                
                <!-- Event Statistics -->
                <div class="event-stats">
                    <?php
                    $total_events = wp_count_posts('env_event')->publish;
                    $upcoming_count = get_posts(array(
                        'post_type' => 'env_event',
                        'numberposts' => -1,
                        'meta_query' => array(
                            array(
                                'key' => '_event_date',
                                'value' => current_time('Y-m-d'),
                                'compare' => '>='
                            )
                        ),
                        'fields' => 'ids'
                    ));
                    $upcoming_events = count($upcoming_count);
                    ?>
                    
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_events; ?></span>
                        <span class="stat-label"><?php _e('Total Events', 'environmental-platform-core'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $upcoming_events; ?></span>
                        <span class="stat-label"><?php _e('Upcoming', 'environmental-platform-core'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_events - $upcoming_events; ?></span>
                        <span class="stat-label"><?php _e('Past Events', 'environmental-platform-core'); ?></span>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Filters Section -->
        <div class="events-filters">
            <div class="filter-section">
                <h3><?php _e('Filter Events', 'environmental-platform-core'); ?></h3>
                
                <div class="filter-row">
                    <!-- Event Status Filter -->
                    <div class="filter-group">
                        <label for="event-status-filter"><?php _e('Status', 'environmental-platform-core'); ?></label>
                        <select id="event-status-filter" class="filter-select">
                            <option value=""><?php _e('All Events', 'environmental-platform-core'); ?></option>
                            <option value="upcoming"><?php _e('Upcoming', 'environmental-platform-core'); ?></option>
                            <option value="ongoing"><?php _e('Ongoing', 'environmental-platform-core'); ?></option>
                            <option value="past"><?php _e('Past', 'environmental-platform-core'); ?></option>
                        </select>
                    </div>
                    
                    <!-- Event Type Filter -->
                    <div class="filter-group">
                        <label for="event-type-filter"><?php _e('Type', 'environmental-platform-core'); ?></label>
                        <select id="event-type-filter" class="filter-select">
                            <option value=""><?php _e('All Types', 'environmental-platform-core'); ?></option>
                            <?php
                            $event_types = get_terms(array(
                                'taxonomy' => 'event_type',
                                'hide_empty' => true
                            ));
                            foreach ($event_types as $type) :
                            ?>
                                <option value="<?php echo esc_attr($type->slug); ?>">
                                    <?php echo esc_html($type->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Location Filter -->
                    <div class="filter-group">
                        <label for="location-filter"><?php _e('Location', 'environmental-platform-core'); ?></label>
                        <input type="text" id="location-filter" class="filter-input" placeholder="<?php _e('Enter location...', 'environmental-platform-core'); ?>">
                    </div>
                    
                    <!-- Date Range Filter -->
                    <div class="filter-group">
                        <label for="date-from"><?php _e('Date Range', 'environmental-platform-core'); ?></label>
                        <div class="date-range">
                            <input type="date" id="date-from" class="filter-input">
                            <span><?php _e('to', 'environmental-platform-core'); ?></span>
                            <input type="date" id="date-to" class="filter-input">
                        </div>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="button" id="apply-filters" class="btn btn-primary">
                        <i class="fa fa-filter"></i>
                        <?php _e('Apply Filters', 'environmental-platform-core'); ?>
                    </button>
                    <button type="button" id="clear-filters" class="btn btn-secondary">
                        <i class="fa fa-times"></i>
                        <?php _e('Clear', 'environmental-platform-core'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- View Toggle -->
        <div class="view-controls">
            <div class="view-toggle">
                <button type="button" class="view-btn active" data-view="grid">
                    <i class="fa fa-th"></i>
                    <?php _e('Grid', 'environmental-platform-core'); ?>
                </button>
                <button type="button" class="view-btn" data-view="list">
                    <i class="fa fa-list"></i>
                    <?php _e('List', 'environmental-platform-core'); ?>
                </button>
                <button type="button" class="view-btn" data-view="calendar">
                    <i class="fa fa-calendar"></i>
                    <?php _e('Calendar', 'environmental-platform-core'); ?>
                </button>
            </div>
            
            <div class="sort-options">
                <select id="sort-events" class="filter-select">
                    <option value="date-asc"><?php _e('Date (Earliest First)', 'environmental-platform-core'); ?></option>
                    <option value="date-desc"><?php _e('Date (Latest First)', 'environmental-platform-core'); ?></option>
                    <option value="title-asc"><?php _e('Title (A-Z)', 'environmental-platform-core'); ?></option>
                    <option value="title-desc"><?php _e('Title (Z-A)', 'environmental-platform-core'); ?></option>
                </select>
            </div>
        </div>
        
        <!-- Events Grid -->
        <div id="events-container" class="events-grid view-grid">
            <?php if (have_posts()) : ?>
                <?php while (have_posts()) : the_post(); ?>
                    <article class="event-card" data-event-id="<?php the_ID(); ?>">
                        <!-- Event Date Badge -->
                        <div class="event-date-badge">
                            <?php 
                            $event_date = get_post_meta(get_the_ID(), '_event_date', true);
                            if ($event_date) {
                                $date = new DateTime($event_date);
                                echo '<span class="month">' . $date->format('M') . '</span>';
                                echo '<span class="day">' . $date->format('d') . '</span>';
                            }
                            ?>
                        </div>
                        
                        <!-- Event Image -->
                        <div class="event-image">
                            <?php if (has_post_thumbnail()) : ?>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium', array('class' => 'img-responsive')); ?>
                                </a>
                            <?php else : ?>
                                <div class="default-image">
                                    <i class="fa fa-calendar"></i>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Event Status -->
                            <?php 
                            $event_status = get_post_meta(get_the_ID(), '_event_status', true);
                            $is_upcoming = $event_date && strtotime($event_date) >= current_time('timestamp');
                            $actual_status = $event_status ? $event_status : ($is_upcoming ? 'upcoming' : 'past');
                            ?>
                            <span class="event-status status-<?php echo esc_attr(strtolower($actual_status)); ?>">
                                <?php echo esc_html(ucfirst($actual_status)); ?>
                            </span>
                        </div>
                        
                        <!-- Event Content -->
                        <div class="event-content">
                            <header class="event-header">
                                <h3 class="event-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                
                                <div class="event-meta">
                                    <?php if (has_term('', 'event_type')) : ?>
                                        <span class="event-type">
                                            <?php echo get_the_term_list(get_the_ID(), 'event_type', '', ', '); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </header>
                            
                            <!-- Event Details -->
                            <div class="event-details">
                                <?php 
                                $event_time = get_post_meta(get_the_ID(), '_event_time', true);
                                $event_location = get_post_meta(get_the_ID(), '_event_location', true);
                                ?>
                                
                                <?php if ($event_date) : ?>
                                    <div class="detail-item">
                                        <i class="fa fa-calendar"></i>
                                        <span><?php echo date('F j, Y', strtotime($event_date)); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($event_time) : ?>
                                    <div class="detail-item">
                                        <i class="fa fa-clock-o"></i>
                                        <span><?php echo esc_html($event_time); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($event_location) : ?>
                                    <div class="detail-item">
                                        <i class="fa fa-map-marker"></i>
                                        <span><?php echo esc_html($event_location); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Event Excerpt -->
                            <div class="event-excerpt">
                                <?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?>
                            </div>
                            
                            <!-- Event Participation -->
                            <?php 
                            $max_participants = get_post_meta(get_the_ID(), '_max_participants', true);
                            $current_participants = get_post_meta(get_the_ID(), '_current_participants', true);
                            ?>
                            
                            <?php if ($max_participants && $current_participants) : ?>
                                <div class="participation-info">
                                    <?php $percentage = ($current_participants / $max_participants) * 100; ?>
                                    <div class="participation-bar">
                                        <div class="participation-fill" style="width: <?php echo min($percentage, 100); ?>%;"></div>
                                    </div>
                                    <span class="participation-text">
                                        <?php echo esc_html($current_participants); ?> / <?php echo esc_html($max_participants); ?> 
                                        <?php _e('participants', 'environmental-platform-core'); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Action Buttons -->
                            <div class="event-actions">
                                <a href="<?php the_permalink(); ?>" class="btn btn-primary">
                                    <?php _e('View Details', 'environmental-platform-core'); ?>
                                </a>
                                
                                <?php if ($is_upcoming) : ?>
                                    <?php $registration_url = get_post_meta(get_the_ID(), '_registration_url', true); ?>
                                    <?php if ($registration_url) : ?>
                                        <a href="<?php echo esc_url($registration_url); ?>" class="btn btn-success" target="_blank">
                                            <?php _e('Register', 'environmental-platform-core'); ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-outline share-event" data-event-id="<?php the_ID(); ?>">
                                    <i class="fa fa-share"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
                
                <!-- Pagination -->
                <div class="events-pagination">
                    <?php
                    the_posts_pagination(array(
                        'mid_size' => 2,
                        'prev_text' => '<i class="fa fa-chevron-left"></i> ' . __('Previous', 'environmental-platform-core'),
                        'next_text' => __('Next', 'environmental-platform-core') . ' <i class="fa fa-chevron-right"></i>',
                        'class' => 'pagination-list'
                    ));
                    ?>
                </div>
                
            <?php else : ?>
                <div class="no-events">
                    <div class="no-events-content">
                        <i class="fa fa-calendar-o"></i>
                        <h3><?php _e('No Events Found', 'environmental-platform-core'); ?></h3>
                        <p><?php _e('There are currently no events matching your criteria. Please check back later or adjust your filters.', 'environmental-platform-core'); ?></p>
                        <button type="button" id="clear-all-filters" class="btn btn-primary">
                            <?php _e('Show All Events', 'environmental-platform-core'); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Calendar View (Hidden by default) -->
        <div id="calendar-view" class="calendar-container" style="display: none;">
            <div id="event-calendar"></div>
        </div>
        
        <!-- Newsletter Signup -->
        <div class="newsletter-section">
            <div class="newsletter-content">
                <h3><?php _e('Stay Updated', 'environmental-platform-core'); ?></h3>
                <p><?php _e('Subscribe to receive notifications about new environmental events in your area.', 'environmental-platform-core'); ?></p>
                
                <form id="event-newsletter-form" class="newsletter-form">
                    <div class="form-group">
                        <input type="email" id="newsletter-email" placeholder="<?php _e('Enter your email address', 'environmental-platform-core'); ?>" required>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-bell"></i>
                            <?php _e('Subscribe', 'environmental-platform-core'); ?>
                        </button>
                    </div>
                    <div class="form-options">
                        <label>
                            <input type="checkbox" id="location-events" value="location">
                            <?php _e('Events in my area', 'environmental-platform-core'); ?>
                        </label>
                        <label>
                            <input type="checkbox" id="weekly-digest" value="weekly">
                            <?php _e('Weekly digest', 'environmental-platform-core'); ?>
                        </label>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div id="share-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php _e('Share Event', 'environmental-platform-core'); ?></h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div class="share-options">
                <button type="button" class="share-btn facebook" data-platform="facebook">
                    <i class="fa fa-facebook"></i>
                    <?php _e('Facebook', 'environmental-platform-core'); ?>
                </button>
                <button type="button" class="share-btn twitter" data-platform="twitter">
                    <i class="fa fa-twitter"></i>
                    <?php _e('Twitter', 'environmental-platform-core'); ?>
                </button>
                <button type="button" class="share-btn linkedin" data-platform="linkedin">
                    <i class="fa fa-linkedin"></i>
                    <?php _e('LinkedIn', 'environmental-platform-core'); ?>
                </button>
                <button type="button" class="share-btn email" data-platform="email">
                    <i class="fa fa-envelope"></i>
                    <?php _e('Email', 'environmental-platform-core'); ?>
                </button>
            </div>
            <div class="copy-link">
                <input type="text" id="event-link" readonly>
                <button type="button" id="copy-link-btn" class="btn btn-outline">
                    <i class="fa fa-copy"></i>
                    <?php _e('Copy Link', 'environmental-platform-core'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.env-events-archive {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px 0;
}

.archive-header {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 40px;
    margin-bottom: 30px;
    color: white;
    text-align: center;
}

.archive-title {
    font-size: 3em;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.archive-description {
    font-size: 1.2em;
    margin-bottom: 30px;
    opacity: 0.9;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
}

.event-stats {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
}

.stat-item {
    text-align: center;
    background: rgba(255,255,255,0.1);
    padding: 20px;
    border-radius: 15px;
    min-width: 120px;
    backdrop-filter: blur(5px);
}

.stat-number {
    display: block;
    font-size: 2.5em;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-label {
    display: block;
    font-size: 14px;
    opacity: 0.9;
}

.events-filters {
    background: white;
    border-radius: 15px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.filter-section h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.5em;
}

.filter-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.filter-group label {
    display: block;
    margin-bottom: 8px;
    color: #555;
    font-weight: 600;
}

.filter-select,
.filter-input {
    width: 100%;
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.filter-select:focus,
.filter-input:focus {
    outline: none;
    border-color: #667eea;
}

.date-range {
    display: flex;
    align-items: center;
    gap: 10px;
}

.date-range span {
    color: #666;
    font-size: 14px;
}

.filter-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102,126,234,0.4);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-outline {
    background: transparent;
    border: 2px solid #667eea;
    color: #667eea;
}

.view-controls {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.view-toggle {
    display: flex;
    gap: 10px;
}

.view-btn {
    padding: 10px 20px;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.view-btn.active,
.view-btn:hover {
    background: #667eea;
    border-color: #667eea;
    color: white;
}

.events-grid {
    display: grid;
    gap: 30px;
    margin-bottom: 40px;
}

.view-grid .events-grid {
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
}

.view-list .events-grid {
    grid-template-columns: 1fr;
}

.event-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    position: relative;
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
}

.event-date-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: rgba(102,126,234,0.9);
    color: white;
    padding: 10px;
    border-radius: 10px;
    text-align: center;
    z-index: 2;
    backdrop-filter: blur(5px);
}

.event-date-badge .month {
    display: block;
    font-size: 12px;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.event-date-badge .day {
    display: block;
    font-size: 20px;
    font-weight: bold;
}

.event-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.event-card:hover .event-image img {
    transform: scale(1.05);
}

.default-image {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3em;
    color: white;
}

.event-status {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-upcoming { background: #28a745; color: white; }
.status-ongoing { background: #ffc107; color: #212529; }
.status-past { background: #6c757d; color: white; }
.status-cancelled { background: #dc3545; color: white; }

.event-content {
    padding: 25px;
}

.event-title {
    margin-bottom: 10px;
    font-size: 1.3em;
    line-height: 1.3;
}

.event-title a {
    color: #2c3e50;
    text-decoration: none;
    transition: color 0.3s;
}

.event-title a:hover {
    color: #667eea;
}

.event-meta {
    margin-bottom: 15px;
}

.event-type {
    background: #f8f9fa;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 12px;
    color: #666;
}

.event-details {
    margin-bottom: 15px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 14px;
    color: #666;
}

.detail-item i {
    color: #667eea;
    width: 16px;
}

.event-excerpt {
    color: #666;
    line-height: 1.6;
    margin-bottom: 15px;
}

.participation-info {
    margin-bottom: 20px;
}

.participation-bar {
    background: #e9ecef;
    height: 6px;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 5px;
}

.participation-fill {
    background: linear-gradient(90deg, #667eea, #764ba2);
    height: 100%;
    transition: width 0.3s;
}

.participation-text {
    font-size: 12px;
    color: #666;
}

.event-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.event-actions .btn {
    padding: 8px 16px;
    font-size: 14px;
}

.share-event {
    width: 40px;
    height: 40px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin-left: auto;
}

.no-events {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.no-events-content i {
    font-size: 4em;
    color: #ddd;
    margin-bottom: 20px;
}

.no-events-content h3 {
    color: #2c3e50;
    margin-bottom: 15px;
}

.events-pagination {
    margin-top: 40px;
    text-align: center;
}

.pagination-list {
    display: inline-flex;
    gap: 10px;
    list-style: none;
    padding: 0;
    margin: 0;
}

.pagination-list li a,
.pagination-list li span {
    padding: 12px 16px;
    background: white;
    border-radius: 8px;
    text-decoration: none;
    color: #2c3e50;
    transition: all 0.3s;
}

.pagination-list li a:hover,
.pagination-list li.current span {
    background: #667eea;
    color: white;
}

.newsletter-section {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 40px;
    margin-top: 40px;
    text-align: center;
    color: white;
}

.newsletter-section h3 {
    font-size: 2em;
    margin-bottom: 15px;
}

.newsletter-form {
    max-width: 500px;
    margin: 0 auto;
}

.newsletter-form .form-group {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.newsletter-form input[type="email"] {
    flex: 1;
    padding: 15px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
}

.form-options {
    display: flex;
    justify-content: center;
    gap: 30px;
    flex-wrap: wrap;
}

.form-options label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(5px);
}

.modal-content {
    background: white;
    margin: 10% auto;
    padding: 0;
    border-radius: 15px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px 30px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.close:hover {
    color: #000;
}

.modal-body {
    padding: 30px;
}

.share-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 30px;
}

.share-btn {
    padding: 15px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-weight: 600;
}

.share-btn.facebook { background: #3b5998; color: white; }
.share-btn.twitter { background: #1da1f2; color: white; }
.share-btn.linkedin { background: #0077b5; color: white; }
.share-btn.email { background: #666; color: white; }

.copy-link {
    display: flex;
    gap: 10px;
}

.copy-link input {
    flex: 1;
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
}

/* Calendar View */
.calendar-container {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .archive-title {
        font-size: 2em;
    }
    
    .event-stats {
        gap: 20px;
    }
    
    .stat-item {
        min-width: 100px;
        padding: 15px;
    }
    
    .filter-row {
        grid-template-columns: 1fr;
    }
    
    .view-controls {
        flex-direction: column;
        gap: 20px;
    }
    
    .events-grid {
        grid-template-columns: 1fr !important;
    }
    
    .newsletter-form .form-group {
        flex-direction: column;
    }
    
    .form-options {
        flex-direction: column;
        gap: 15px;
    }
    
    .share-options {
        grid-template-columns: 1fr;
    }
    
    .copy-link {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Filter functionality
    $('#apply-filters').on('click', function() {
        applyFilters();
    });
    
    $('#clear-filters').on('click', function() {
        clearFilters();
    });
    
    $('#clear-all-filters').on('click', function() {
        clearFilters();
        applyFilters();
    });
    
    // View toggle
    $('.view-btn').on('click', function() {
        const view = $(this).data('view');
        $('.view-btn').removeClass('active');
        $(this).addClass('active');
        
        if (view === 'calendar') {
            $('#events-container').hide();
            $('#calendar-view').show();
            initCalendar();
        } else {
            $('#calendar-view').hide();
            $('#events-container').show().removeClass('view-grid view-list').addClass('view-' + view);
        }
    });
    
    // Sort functionality
    $('#sort-events').on('change', function() {
        sortEvents($(this).val());
    });
    
    // Share functionality
    $('.share-event').on('click', function() {
        const eventId = $(this).data('event-id');
        showShareModal(eventId);
    });
    
    // Modal functionality
    $('.close').on('click', function() {
        $('.modal').hide();
    });
    
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('modal')) {
            $('.modal').hide();
        }
    });
    
    // Newsletter form
    $('#event-newsletter-form').on('submit', function(e) {
        e.preventDefault();
        subscribeToNewsletter();
    });
    
    // Copy link functionality
    $('#copy-link-btn').on('click', function() {
        const linkInput = $('#event-link')[0];
        linkInput.select();
        document.execCommand('copy');
        
        $(this).html('<i class="fa fa-check"></i> Copied!');
        setTimeout(() => {
            $(this).html('<i class="fa fa-copy"></i> Copy Link');
        }, 2000);
    });
    
    function applyFilters() {
        const filters = {
            status: $('#event-status-filter').val(),
            type: $('#event-type-filter').val(),
            location: $('#location-filter').val(),
            dateFrom: $('#date-from').val(),
            dateTo: $('#date-to').val()
        };
        
        $('.event-card').each(function() {
            const $card = $(this);
            let show = true;
            
            // Apply filters logic here
            // This would typically be handled via AJAX for better performance
            
            if (show) {
                $card.show();
            } else {
                $card.hide();
            }
        });
        
        updateResultsCount();
    }
    
    function clearFilters() {
        $('#event-status-filter').val('');
        $('#event-type-filter').val('');
        $('#location-filter').val('');
        $('#date-from').val('');
        $('#date-to').val('');
        $('.event-card').show();
        updateResultsCount();
    }
    
    function sortEvents(sortBy) {
        const $container = $('#events-container');
        const $events = $container.find('.event-card').detach();
        
        $events.sort(function(a, b) {
            // Sorting logic based on sortBy value
            // This is a simplified version
            const aTitle = $(a).find('.event-title a').text();
            const bTitle = $(b).find('.event-title a').text();
            
            if (sortBy === 'title-asc') {
                return aTitle.localeCompare(bTitle);
            } else if (sortBy === 'title-desc') {
                return bTitle.localeCompare(aTitle);
            }
            
            return 0;
        });
        
        $container.append($events);
    }
    
    function updateResultsCount() {
        const visibleCount = $('.event-card:visible').length;
        // Update results count display
    }
    
    function showShareModal(eventId) {
        // Get event data and populate share modal
        const eventUrl = window.location.origin + '/event/' + eventId;
        $('#event-link').val(eventUrl);
        $('#share-modal').show();
    }
    
    function subscribeToNewsletter() {
        const email = $('#newsletter-email').val();
        const preferences = [];
        
        if ($('#location-events').is(':checked')) {
            preferences.push('location');
        }
        if ($('#weekly-digest').is(':checked')) {
            preferences.push('weekly');
        }
        
        // AJAX call to subscribe
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'subscribe_event_newsletter',
                email: email,
                preferences: preferences,
                nonce: '<?php echo wp_create_nonce('event_newsletter_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Successfully subscribed to event updates!');
                    $('#event-newsletter-form')[0].reset();
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    }
    
    function initCalendar() {
        // Initialize calendar view (would typically use a library like FullCalendar)
        $('#event-calendar').html('<p style="text-align: center; padding: 40px; color: #666;">Calendar view coming soon...</p>');
    }
});
</script>

<?php get_footer(); ?>
