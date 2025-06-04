<?php
/**
 * Single Environmental Event Template
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

get_header(); ?>

<div class="env-event-container">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <?php while (have_posts()) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class('env-event'); ?>>
                        <header class="event-header">
                            <div class="event-date-badge">
                                <?php 
                                $event_date = get_post_meta(get_the_ID(), '_event_date', true);
                                if ($event_date) {
                                    $date = new DateTime($event_date);
                                    echo '<span class="month">' . $date->format('M') . '</span>';
                                    echo '<span class="day">' . $date->format('d') . '</span>';
                                    echo '<span class="year">' . $date->format('Y') . '</span>';
                                }
                                ?>
                            </div>
                            
                            <div class="event-title-section">
                                <h1 class="event-title"><?php the_title(); ?></h1>
                                
                                <div class="event-meta">
                                    <?php if (has_term('', 'event_type')) : ?>
                                        <span class="event-type">
                                            <i class="fa fa-tag"></i>
                                            <?php echo get_the_term_list(get_the_ID(), 'event_type', '', ', '); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $event_status = get_post_meta(get_the_ID(), '_event_status', true);
                                    if ($event_status) : ?>
                                        <span class="event-status status-<?php echo esc_attr(strtolower($event_status)); ?>">
                                            <i class="fa fa-circle"></i>
                                            <?php echo esc_html($event_status); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    $organizer = get_post_meta(get_the_ID(), '_event_organizer', true);
                                    if ($organizer) : ?>
                                        <span class="organizer">
                                            <i class="fa fa-user"></i>
                                            <?php _e('Organized by', 'environmental-platform-core'); ?> <?php echo esc_html($organizer); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </header>
                        
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="event-featured-image">
                                <?php the_post_thumbnail('large', array('class' => 'img-responsive')); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Event Information Grid -->
                        <div class="event-info-grid">
                            <?php 
                            $event_date = get_post_meta(get_the_ID(), '_event_date', true);
                            $event_time = get_post_meta(get_the_ID(), '_event_time', true);
                            $event_location = get_post_meta(get_the_ID(), '_event_location', true);
                            $event_address = get_post_meta(get_the_ID(), '_event_address', true);
                            $registration_required = get_post_meta(get_the_ID(), '_registration_required', true);
                            $max_participants = get_post_meta(get_the_ID(), '_max_participants', true);
                            $current_participants = get_post_meta(get_the_ID(), '_current_participants', true);
                            ?>
                            
                            <div class="event-info-card">
                                <h3><i class="fa fa-calendar"></i> <?php _e('Date & Time', 'environmental-platform-core'); ?></h3>
                                <div class="info-content">
                                    <?php if ($event_date) : ?>
                                        <p><strong><?php _e('Date:', 'environmental-platform-core'); ?></strong> <?php echo date('F j, Y', strtotime($event_date)); ?></p>
                                    <?php endif; ?>
                                    <?php if ($event_time) : ?>
                                        <p><strong><?php _e('Time:', 'environmental-platform-core'); ?></strong> <?php echo esc_html($event_time); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="event-info-card">
                                <h3><i class="fa fa-map-marker"></i> <?php _e('Location', 'environmental-platform-core'); ?></h3>
                                <div class="info-content">
                                    <?php if ($event_location) : ?>
                                        <p><strong><?php echo esc_html($event_location); ?></strong></p>
                                    <?php endif; ?>
                                    <?php if ($event_address) : ?>
                                        <p><?php echo esc_html($event_address); ?></p>
                                        <a href="https://maps.google.com/maps?q=<?php echo urlencode($event_address); ?>" target="_blank" class="directions-link">
                                            <i class="fa fa-external-link"></i> <?php _e('Get Directions', 'environmental-platform-core'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($registration_required || $max_participants) : ?>
                                <div class="event-info-card">
                                    <h3><i class="fa fa-users"></i> <?php _e('Registration', 'environmental-platform-core'); ?></h3>
                                    <div class="info-content">
                                        <?php if ($registration_required) : ?>
                                            <p class="registration-required">
                                                <i class="fa fa-exclamation-circle"></i>
                                                <?php _e('Registration Required', 'environmental-platform-core'); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if ($max_participants) : ?>
                                            <div class="participant-info">
                                                <p><strong><?php _e('Capacity:', 'environmental-platform-core'); ?></strong> <?php echo esc_html($max_participants); ?> <?php _e('participants', 'environmental-platform-core'); ?></p>
                                                
                                                <?php if ($current_participants) : ?>
                                                    <div class="participation-progress">
                                                        <div class="progress-bar">
                                                            <?php $percentage = ($current_participants / $max_participants) * 100; ?>
                                                            <div class="progress-fill" style="width: <?php echo min($percentage, 100); ?>%;"></div>
                                                        </div>
                                                        <p class="progress-text">
                                                            <?php echo esc_html($current_participants); ?> / <?php echo esc_html($max_participants); ?> 
                                                            (<?php echo round($percentage, 1); ?>% <?php _e('full', 'environmental-platform-core'); ?>)
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Event Description -->
                        <div class="event-content">
                            <h2><?php _e('About This Event', 'environmental-platform-core'); ?></h2>
                            <?php the_content(); ?>
                        </div>
                        
                        <!-- Environmental Impact -->
                        <?php 
                        $environmental_focus = get_post_meta(get_the_ID(), '_environmental_focus', true);
                        $expected_impact = get_post_meta(get_the_ID(), '_expected_impact', true);
                        $sustainability_goals = get_post_meta(get_the_ID(), '_sustainability_goals', true);
                        ?>
                        
                        <?php if ($environmental_focus || $expected_impact || $sustainability_goals) : ?>
                            <div class="environmental-impact-section">
                                <h2><?php _e('Environmental Impact', 'environmental-platform-core'); ?></h2>
                                <div class="impact-grid">
                                    <?php if ($environmental_focus) : ?>
                                        <div class="impact-item">
                                            <h3><i class="fa fa-leaf"></i> <?php _e('Environmental Focus', 'environmental-platform-core'); ?></h3>
                                            <p><?php echo esc_html($environmental_focus); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($expected_impact) : ?>
                                        <div class="impact-item">
                                            <h3><i class="fa fa-chart-line"></i> <?php _e('Expected Impact', 'environmental-platform-core'); ?></h3>
                                            <p><?php echo esc_html($expected_impact); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($sustainability_goals) : ?>
                                        <div class="impact-item">
                                            <h3><i class="fa fa-bullseye"></i> <?php _e('Sustainability Goals', 'environmental-platform-core'); ?></h3>
                                            <p><?php echo esc_html($sustainability_goals); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Registration/Action Section -->
                        <div class="event-action-section">
                            <?php 
                            $registration_url = get_post_meta(get_the_ID(), '_registration_url', true);
                            $event_date = get_post_meta(get_the_ID(), '_event_date', true);
                            $is_past_event = $event_date && strtotime($event_date) < current_time('timestamp');
                            ?>
                            
                            <?php if (!$is_past_event) : ?>
                                <div class="action-buttons">
                                    <?php if ($registration_url) : ?>
                                        <a href="<?php echo esc_url($registration_url); ?>" class="register-btn" target="_blank">
                                            <i class="fa fa-calendar-plus"></i>
                                            <?php _e('Register for Event', 'environmental-platform-core'); ?>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="add-calendar-btn" onclick="addToCalendar()">
                                        <i class="fa fa-calendar"></i>
                                        <?php _e('Add to Calendar', 'environmental-platform-core'); ?>
                                    </button>
                                    
                                    <button type="button" class="share-event-btn" onclick="shareEvent()">
                                        <i class="fa fa-share"></i>
                                        <?php _e('Share Event', 'environmental-platform-core'); ?>
                                    </button>
                                </div>
                            <?php else : ?>
                                <div class="past-event-notice">
                                    <i class="fa fa-clock-o"></i>
                                    <h3><?php _e('This event has already taken place', 'environmental-platform-core'); ?></h3>
                                    <p><?php _e('Thank you to everyone who participated! Check out our upcoming events below.', 'environmental-platform-core'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Event Tags -->
                        <?php if (has_term('', 'env_tag')) : ?>
                            <div class="event-tags">
                                <h3><?php _e('Tags', 'environmental-platform-core'); ?></h3>
                                <div class="tag-list">
                                    <?php echo get_the_term_list(get_the_ID(), 'env_tag', '', ''); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
                
                <!-- Navigation -->
                <nav class="event-navigation">
                    <?php
                    $prev_event = get_previous_post();
                    $next_event = get_next_post();
                    ?>
                    
                    <?php if ($prev_event) : ?>
                        <div class="nav-previous">
                            <a href="<?php echo get_permalink($prev_event); ?>">
                                <span class="nav-subtitle"><?php _e('Previous Event', 'environmental-platform-core'); ?></span>
                                <span class="nav-title"><?php echo get_the_title($prev_event); ?></span>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($next_event) : ?>
                        <div class="nav-next">
                            <a href="<?php echo get_permalink($next_event); ?>">
                                <span class="nav-subtitle"><?php _e('Next Event', 'environmental-platform-core'); ?></span>
                                <span class="nav-title"><?php echo get_the_title($next_event); ?></span>
                            </a>
                        </div>
                    <?php endif; ?>
                </nav>
            </div>
            
            <!-- Sidebar -->
            <aside class="col-md-4">
                <div class="event-sidebar">
                    <!-- Upcoming Events -->
                    <?php
                    $upcoming_events = new WP_Query(array(
                        'post_type' => 'env_event',
                        'posts_per_page' => 5,
                        'post__not_in' => array(get_the_ID()),
                        'meta_query' => array(
                            array(
                                'key' => '_event_date',
                                'value' => current_time('Y-m-d'),
                                'compare' => '>='
                            )
                        ),
                        'meta_key' => '_event_date',
                        'orderby' => 'meta_value',
                        'order' => 'ASC'
                    ));
                    ?>
                    
                    <?php if ($upcoming_events->have_posts()) : ?>
                        <div class="sidebar-widget upcoming-events">
                            <h3><?php _e('Upcoming Events', 'environmental-platform-core'); ?></h3>
                            <div class="events-list">
                                <?php while ($upcoming_events->have_posts()) : $upcoming_events->the_post(); ?>
                                    <div class="event-item">
                                        <div class="event-date">
                                            <?php 
                                            $event_date = get_post_meta(get_the_ID(), '_event_date', true);
                                            if ($event_date) {
                                                $date = new DateTime($event_date);
                                                echo '<span class="month">' . $date->format('M') . '</span>';
                                                echo '<span class="day">' . $date->format('d') . '</span>';
                                            }
                                            ?>
                                        </div>
                                        <div class="event-info">
                                            <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                                            <?php 
                                            $event_location = get_post_meta(get_the_ID(), '_event_location', true);
                                            if ($event_location) : ?>
                                                <span class="location"><?php echo esc_html($event_location); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Event Categories -->
                    <?php
                    $event_types = get_terms(array(
                        'taxonomy' => 'event_type',
                        'hide_empty' => true
                    ));
                    ?>
                    
                    <?php if (!empty($event_types)) : ?>
                        <div class="sidebar-widget event-categories">
                            <h3><?php _e('Event Types', 'environmental-platform-core'); ?></h3>
                            <ul class="category-list">
                                <?php foreach ($event_types as $type) : ?>
                                    <li>
                                        <a href="<?php echo get_term_link($type); ?>">
                                            <?php echo esc_html($type->name); ?>
                                            <span class="count">(<?php echo $type->count; ?>)</span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Contact Organizer -->
                    <?php 
                    $organizer = get_post_meta(get_the_ID(), '_event_organizer', true);
                    $organizer_email = get_post_meta(get_the_ID(), '_organizer_email', true);
                    $organizer_phone = get_post_meta(get_the_ID(), '_organizer_phone', true);
                    ?>
                    
                    <?php if ($organizer || $organizer_email || $organizer_phone) : ?>
                        <div class="sidebar-widget contact-organizer">
                            <h3><?php _e('Contact Organizer', 'environmental-platform-core'); ?></h3>
                            <div class="organizer-info">
                                <?php if ($organizer) : ?>
                                    <p><strong><?php echo esc_html($organizer); ?></strong></p>
                                <?php endif; ?>
                                
                                <?php if ($organizer_email) : ?>
                                    <p>
                                        <i class="fa fa-envelope"></i>
                                        <a href="mailto:<?php echo esc_attr($organizer_email); ?>"><?php echo esc_html($organizer_email); ?></a>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($organizer_phone) : ?>
                                    <p>
                                        <i class="fa fa-phone"></i>
                                        <a href="tel:<?php echo esc_attr($organizer_phone); ?>"><?php echo esc_html($organizer_phone); ?></a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</div>

<style>
.env-event-container {
    padding: 20px 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.env-event {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    overflow: hidden;
    margin-bottom: 30px;
}

.event-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px;
    display: flex;
    align-items: center;
    gap: 30px;
}

.event-date-badge {
    background: rgba(255,255,255,0.2);
    border-radius: 15px;
    padding: 20px;
    text-align: center;
    min-width: 120px;
    backdrop-filter: blur(10px);
}

.event-date-badge .month {
    display: block;
    font-size: 14px;
    text-transform: uppercase;
    margin-bottom: 5px;
    opacity: 0.9;
}

.event-date-badge .day {
    display: block;
    font-size: 36px;
    font-weight: bold;
    line-height: 1;
    margin-bottom: 5px;
}

.event-date-badge .year {
    display: block;
    font-size: 16px;
    opacity: 0.9;
}

.event-title-section {
    flex: 1;
}

.event-title {
    font-size: 2.5em;
    margin-bottom: 15px;
    line-height: 1.2;
}

.event-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    font-size: 14px;
}

.event-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
    background: rgba(255,255,255,0.1);
    padding: 5px 12px;
    border-radius: 20px;
    backdrop-filter: blur(5px);
}

.event-status.status-upcoming { background: rgba(46, 204, 113, 0.8); }
.event-status.status-ongoing { background: rgba(241, 196, 15, 0.8); }
.event-status.status-completed { background: rgba(149, 165, 166, 0.8); }
.event-status.status-cancelled { background: rgba(231, 76, 60, 0.8); }

.event-featured-image {
    position: relative;
    overflow: hidden;
    height: 400px;
}

.event-featured-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 30px;
    background: #f8f9fa;
}

.event-info-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-left: 4px solid #667eea;
}

.event-info-card h3 {
    color: #2c3e50;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 18px;
}

.event-info-card h3 i {
    color: #667eea;
}

.info-content p {
    margin-bottom: 10px;
    line-height: 1.6;
}

.directions-link {
    color: #667eea;
    text-decoration: none;
    font-weight: bold;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 10px;
}

.directions-link:hover {
    color: #5a67d8;
}

.registration-required {
    color: #e74c3c;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 5px;
}

.participation-progress {
    margin-top: 15px;
}

.progress-bar {
    background: #e9ecef;
    border-radius: 10px;
    height: 8px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-fill {
    background: linear-gradient(90deg, #667eea, #764ba2);
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s;
}

.progress-text {
    font-size: 14px;
    color: #666;
    margin: 0;
}

.event-content {
    padding: 30px;
    line-height: 1.8;
    font-size: 16px;
    color: #333;
}

.event-content h2 {
    color: #2c3e50;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #667eea;
}

.environmental-impact-section {
    background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
    border: 1px solid #4caf50;
    border-radius: 10px;
    padding: 30px;
    margin: 30px;
}

.environmental-impact-section h2 {
    color: #1b5e20;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 2px solid #4caf50;
}

.impact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.impact-item {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #4caf50;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.impact-item h3 {
    color: #1b5e20;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
}

.impact-item i {
    color: #4caf50;
}

.event-action-section {
    padding: 30px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
}

.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    justify-content: center;
}

.action-buttons a,
.action-buttons button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 25px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
    font-size: 14px;
}

.register-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.register-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.add-calendar-btn {
    background: linear-gradient(135deg, #4caf50, #45a049);
    color: white;
}

.add-calendar-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
}

.share-event-btn {
    background: linear-gradient(135deg, #ff9800, #f57c00);
    color: white;
}

.share-event-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(255, 152, 0, 0.3);
}

.past-event-notice {
    text-align: center;
    padding: 30px;
    background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
    border-radius: 10px;
    color: #666;
}

.past-event-notice i {
    font-size: 48px;
    margin-bottom: 15px;
    color: #bbb;
}

.past-event-notice h3 {
    color: #555;
    margin-bottom: 10px;
}

.event-tags {
    padding: 30px;
    border-top: 1px solid #e9ecef;
}

.event-tags h3 {
    color: #2c3e50;
    margin-bottom: 15px;
}

.tag-list a {
    display: inline-block;
    background: #667eea;
    color: white;
    padding: 5px 12px;
    border-radius: 15px;
    text-decoration: none;
    font-size: 12px;
    margin: 0 5px 5px 0;
    transition: background 0.3s;
}

.tag-list a:hover {
    background: #5a67d8;
}

.event-navigation {
    margin-top: 30px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.event-navigation a {
    display: block;
    padding: 20px;
    background: white;
    border-radius: 10px;
    text-decoration: none;
    color: #2c3e50;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s;
}

.event-navigation a:hover {
    transform: translateY(-3px);
}

.nav-subtitle {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    display: block;
    margin-bottom: 5px;
}

.nav-title {
    font-weight: bold;
    font-size: 16px;
    line-height: 1.3;
}

.event-sidebar {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

.sidebar-widget {
    padding: 25px;
    border-bottom: 1px solid #f0f0f0;
}

.sidebar-widget:last-child {
    border-bottom: none;
}

.sidebar-widget h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 18px;
}

.events-list {
    space-y: 15px;
}

.event-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 15px;
    transition: background 0.3s;
}

.event-item:hover {
    background: #e9ecef;
}

.event-item .event-date {
    background: #667eea;
    color: white;
    border-radius: 8px;
    padding: 10px;
    text-align: center;
    min-width: 60px;
}

.event-item .event-date .month {
    display: block;
    font-size: 10px;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.event-item .event-date .day {
    display: block;
    font-size: 18px;
    font-weight: bold;
}

.event-info h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
}

.event-info a {
    color: #2c3e50;
    text-decoration: none;
    transition: color 0.3s;
}

.event-info a:hover {
    color: #667eea;
}

.event-info .location {
    font-size: 12px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 5px;
}

.category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-list li {
    margin-bottom: 8px;
}

.category-list a {
    text-decoration: none;
    color: #2c3e50;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 5px;
    transition: all 0.3s;
}

.category-list a:hover {
    background: #667eea;
    color: white;
}

.category-list .count {
    font-size: 12px;
    background: white;
    color: #666;
    padding: 2px 8px;
    border-radius: 10px;
}

.category-list a:hover .count {
    background: rgba(255,255,255,0.2);
    color: white;
}

.organizer-info p {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.organizer-info a {
    color: #667eea;
    text-decoration: none;
}

.organizer-info a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .event-header {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .event-info-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .action-buttons a,
    .action-buttons button {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }
    
    .event-navigation {
        grid-template-columns: 1fr;
    }
    
    .impact-grid {
        grid-template-columns: 1fr;
    }
    
    .event-item {
        flex-direction: column;
        text-align: center;
    }
    
    .event-item .event-date {
        align-self: center;
    }
}
</style>

<script>
function addToCalendar() {
    <?php 
    $event_date = get_post_meta(get_the_ID(), '_event_date', true);
    $event_time = get_post_meta(get_the_ID(), '_event_time', true);
    $event_location = get_post_meta(get_the_ID(), '_event_location', true);
    ?>
    
    var eventData = {
        title: <?php echo json_encode(get_the_title()); ?>,
        start: '<?php echo $event_date; ?>',
        time: '<?php echo $event_time; ?>',
        location: '<?php echo $event_location; ?>',
        description: <?php echo json_encode(wp_trim_words(get_the_content(), 50)); ?>
    };
    
    // Google Calendar URL
    var googleUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE' +
        '&text=' + encodeURIComponent(eventData.title) +
        '&dates=' + eventData.start.replace(/-/g, '') + '/' + eventData.start.replace(/-/g, '') +
        '&details=' + encodeURIComponent(eventData.description) +
        '&location=' + encodeURIComponent(eventData.location);
    
    window.open(googleUrl, '_blank');
}

function shareEvent() {
    if (navigator.share) {
        navigator.share({
            title: <?php echo json_encode(get_the_title()); ?>,
            text: <?php echo json_encode(wp_trim_words(get_the_content(), 30)); ?>,
            url: window.location.href
        });
    } else {
        // Fallback - copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(function() {
            alert('<?php _e('Event link copied to clipboard!', 'environmental-platform-core'); ?>');
        });
    }
}
</script>

<?php get_footer(); ?>
