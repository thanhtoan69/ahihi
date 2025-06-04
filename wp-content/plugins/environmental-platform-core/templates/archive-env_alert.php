<?php
/**
 * Archive Environmental Alerts Template
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

get_header(); ?>

<div class="env-alerts-archive">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <header class="archive-header">
                    <h1 class="archive-title">
                        <i class="fa fa-exclamation-triangle"></i>
                        <?php _e('Environmental Alerts', 'environmental-platform-core'); ?>
                    </h1>
                    <p class="archive-description">
                        <?php _e('Stay informed about urgent environmental issues and emergency situations in your area.', 'environmental-platform-core'); ?>
                    </p>
                </header>
                
                <!-- Alert Status Summary -->
                <div class="alert-summary-stats">
                    <?php
                    $active_alerts = new WP_Query(array(
                        'post_type' => 'env_alert',
                        'meta_query' => array(
                            array(
                                'key' => '_alert_status',
                                'value' => 'active',
                                'compare' => '='
                            )
                        ),
                        'posts_per_page' => -1
                    ));
                    
                    $resolved_alerts = new WP_Query(array(
                        'post_type' => 'env_alert',
                        'meta_query' => array(
                            array(
                                'key' => '_alert_status',
                                'value' => 'resolved',
                                'compare' => '='
                            )
                        ),
                        'posts_per_page' => -1
                    ));
                    ?>
                    
                    <div class="stats-grid">
                        <div class="stat-item active">
                            <i class="fa fa-warning"></i>
                            <span class="stat-number"><?php echo $active_alerts->found_posts; ?></span>
                            <span class="stat-label"><?php _e('Active Alerts', 'environmental-platform-core'); ?></span>
                        </div>
                        <div class="stat-item resolved">
                            <i class="fa fa-check-circle"></i>
                            <span class="stat-number"><?php echo $resolved_alerts->found_posts; ?></span>
                            <span class="stat-label"><?php _e('Resolved', 'environmental-platform-core'); ?></span>
                        </div>
                        <div class="stat-item total">
                            <i class="fa fa-list"></i>
                            <span class="stat-number"><?php echo wp_count_posts('env_alert')->publish; ?></span>
                            <span class="stat-label"><?php _e('Total Alerts', 'environmental-platform-core'); ?></span>
                        </div>
                    </div>
                    
                    <?php wp_reset_postdata(); ?>
                </div>
                
                <!-- Filter Controls -->
                <div class="archive-filters">
                    <div class="filter-group">
                        <label for="priority-filter"><?php _e('Priority Level:', 'environmental-platform-core'); ?></label>
                        <select id="priority-filter" class="form-control">
                            <option value=""><?php _e('All Priorities', 'environmental-platform-core'); ?></option>
                            <option value="emergency" <?php selected(isset($_GET['priority']) && $_GET['priority'] == 'emergency'); ?>><?php _e('Emergency', 'environmental-platform-core'); ?></option>
                            <option value="high" <?php selected(isset($_GET['priority']) && $_GET['priority'] == 'high'); ?>><?php _e('High', 'environmental-platform-core'); ?></option>
                            <option value="medium" <?php selected(isset($_GET['priority']) && $_GET['priority'] == 'medium'); ?>><?php _e('Medium', 'environmental-platform-core'); ?></option>
                            <option value="low" <?php selected(isset($_GET['priority']) && $_GET['priority'] == 'low'); ?>><?php _e('Low', 'environmental-platform-core'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="status-filter"><?php _e('Alert Status:', 'environmental-platform-core'); ?></label>
                        <select id="status-filter" class="form-control">
                            <option value=""><?php _e('All Status', 'environmental-platform-core'); ?></option>
                            <option value="active" <?php selected(isset($_GET['status']) && $_GET['status'] == 'active'); ?>><?php _e('Active', 'environmental-platform-core'); ?></option>
                            <option value="monitoring" <?php selected(isset($_GET['status']) && $_GET['status'] == 'monitoring'); ?>><?php _e('Monitoring', 'environmental-platform-core'); ?></option>
                            <option value="resolved" <?php selected(isset($_GET['status']) && $_GET['status'] == 'resolved'); ?>><?php _e('Resolved', 'environmental-platform-core'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="location-filter"><?php _e('Location:', 'environmental-platform-core'); ?></label>
                        <select id="location-filter" class="form-control">
                            <option value=""><?php _e('All Locations', 'environmental-platform-core'); ?></option>
                            <?php
                            $locations = get_terms(array(
                                'taxonomy' => 'env_location',
                                'hide_empty' => true
                            ));
                            foreach ($locations as $location) {
                                $selected = (isset($_GET['env_location']) && $_GET['env_location'] == $location->slug) ? 'selected' : '';
                                echo '<option value="' . esc_attr($location->slug) . '" ' . $selected . '>' . esc_html($location->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="sort-filter"><?php _e('Sort by:', 'environmental-platform-core'); ?></label>
                        <select id="sort-filter" class="form-control">
                            <option value="date"><?php _e('Latest First', 'environmental-platform-core'); ?></option>
                            <option value="priority"><?php _e('Priority Level', 'environmental-platform-core'); ?></option>
                            <option value="title"><?php _e('Title A-Z', 'environmental-platform-core'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- Alerts Grid -->
                <div class="alerts-grid" id="alerts-container">
                    <?php if (have_posts()) : ?>
                        <?php while (have_posts()) : the_post(); ?>
                            <?php
                            $priority = get_post_meta(get_the_ID(), '_alert_priority', true);
                            $status = get_post_meta(get_the_ID(), '_alert_status', true);
                            $location = get_post_meta(get_the_ID(), '_alert_location', true);
                            $affected_area = get_post_meta(get_the_ID(), '_affected_area', true);
                            $contact_info = get_post_meta(get_the_ID(), '_emergency_contact', true);
                            ?>
                            
                            <article class="alert-card priority-<?php echo esc_attr($priority); ?> status-<?php echo esc_attr($status); ?>">
                                <!-- Priority & Status Indicators -->
                                <div class="alert-indicators">
                                    <span class="priority-badge priority-<?php echo esc_attr($priority); ?>">
                                        <?php if ($priority == 'emergency') : ?>
                                            <i class="fa fa-warning"></i>
                                        <?php elseif ($priority == 'high') : ?>
                                            <i class="fa fa-exclamation-triangle"></i>
                                        <?php elseif ($priority == 'medium') : ?>
                                            <i class="fa fa-info-circle"></i>
                                        <?php else : ?>
                                            <i class="fa fa-bell"></i>
                                        <?php endif; ?>
                                        <?php echo esc_html(ucfirst($priority)); ?>
                                    </span>
                                    
                                    <span class="status-badge status-<?php echo esc_attr($status); ?>">
                                        <?php echo esc_html(ucfirst($status)); ?>
                                    </span>
                                </div>
                                
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="alert-image">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('medium', array('class' => 'img-responsive')); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="alert-content">
                                    <div class="alert-meta">
                                        <span class="date">
                                            <i class="fa fa-calendar"></i>
                                            <?php echo get_the_date(); ?>
                                        </span>
                                        
                                        <?php if ($location) : ?>
                                            <span class="location">
                                                <i class="fa fa-map-marker"></i>
                                                <?php echo esc_html($location); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h3 class="alert-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    
                                    <div class="alert-excerpt">
                                        <?php echo wp_trim_words(get_the_excerpt(), 25); ?>
                                    </div>
                                    
                                    <?php if ($affected_area) : ?>
                                        <div class="affected-area">
                                            <i class="fa fa-area-chart"></i>
                                            <strong><?php _e('Affected Area:', 'environmental-platform-core'); ?></strong>
                                            <?php echo esc_html($affected_area); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="alert-actions">
                                        <a href="<?php the_permalink(); ?>" class="view-details-btn btn-primary">
                                            <?php _e('View Details', 'environmental-platform-core'); ?>
                                            <i class="fa fa-arrow-right"></i>
                                        </a>
                                        
                                        <?php if ($contact_info && $status == 'active') : ?>
                                            <button class="emergency-contact-btn btn-warning" data-contact="<?php echo esc_attr($contact_info); ?>">
                                                <i class="fa fa-phone"></i>
                                                <?php _e('Emergency Contact', 'environmental-platform-core'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endwhile; ?>
                        
                        <!-- Pagination -->
                        <div class="archive-pagination">
                            <?php
                            echo paginate_links(array(
                                'total' => $wp_query->max_num_pages,
                                'current' => max(1, get_query_var('paged')),
                                'format' => '?paged=%#%',
                                'show_all' => false,
                                'end_size' => 2,
                                'mid_size' => 2,
                                'prev_next' => true,
                                'prev_text' => '<i class="fa fa-chevron-left"></i> ' . __('Previous', 'environmental-platform-core'),
                                'next_text' => __('Next', 'environmental-platform-core') . ' <i class="fa fa-chevron-right"></i>',
                                'type' => 'list'
                            ));
                            ?>
                        </div>
                    <?php else : ?>
                        <div class="no-alerts-message">
                            <i class="fa fa-check-circle"></i>
                            <h3><?php _e('No Active Alerts', 'environmental-platform-core'); ?></h3>
                            <p><?php _e('Great news! There are currently no environmental alerts in your area.', 'environmental-platform-core'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-md-4">
                <aside class="archive-sidebar">
                    <!-- Emergency Preparedness Widget -->
                    <div class="sidebar-widget emergency-preparedness">
                        <h4><i class="fa fa-shield"></i> <?php _e('Emergency Preparedness', 'environmental-platform-core'); ?></h4>
                        <div class="preparedness-checklist">
                            <ul>
                                <li><i class="fa fa-check"></i> <?php _e('Emergency contact numbers saved', 'environmental-platform-core'); ?></li>
                                <li><i class="fa fa-check"></i> <?php _e('Emergency kit prepared', 'environmental-platform-core'); ?></li>
                                <li><i class="fa fa-check"></i> <?php _e('Evacuation plan ready', 'environmental-platform-core'); ?></li>
                                <li><i class="fa fa-check"></i> <?php _e('Local alerts subscribed', 'environmental-platform-core'); ?></li>
                            </ul>
                            <a href="#" class="btn btn-primary btn-sm" id="emergency-guide-btn">
                                <?php _e('View Emergency Guide', 'environmental-platform-core'); ?>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Alert Subscription Widget -->
                    <div class="sidebar-widget alert-subscription">
                        <h4><i class="fa fa-bell"></i> <?php _e('Alert Notifications', 'environmental-platform-core'); ?></h4>
                        <p><?php _e('Get instant notifications about environmental alerts in your area.', 'environmental-platform-core'); ?></p>
                        <form class="subscription-form" id="alert-subscription-form">
                            <div class="form-group">
                                <input type="email" class="form-control" placeholder="<?php _e('Your email address', 'environmental-platform-core'); ?>" required>
                            </div>
                            <div class="form-group">
                                <select class="form-control" name="location_preference">
                                    <option value=""><?php _e('Select your location', 'environmental-platform-core'); ?></option>
                                    <?php
                                    $locations = get_terms(array('taxonomy' => 'env_location', 'hide_empty' => false));
                                    foreach ($locations as $location) {
                                        echo '<option value="' . esc_attr($location->slug) . '">' . esc_html($location->name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <?php _e('Subscribe to Alerts', 'environmental-platform-core'); ?>
                            </button>
                        </form>
                    </div>
                    
                    <!-- Recent Updates Widget -->
                    <div class="sidebar-widget recent-updates">
                        <h4><i class="fa fa-clock-o"></i> <?php _e('Recent Updates', 'environmental-platform-core'); ?></h4>
                        <?php
                        $recent_alerts = new WP_Query(array(
                            'post_type' => 'env_alert',
                            'posts_per_page' => 3,
                            'meta_key' => '_alert_status',
                            'meta_value' => 'active'
                        ));
                        
                        if ($recent_alerts->have_posts()) :
                            while ($recent_alerts->have_posts()) : $recent_alerts->the_post();
                                $priority = get_post_meta(get_the_ID(), '_alert_priority', true);
                        ?>
                                <div class="recent-alert-item">
                                    <span class="priority-indicator priority-<?php echo esc_attr($priority); ?>"></span>
                                    <div class="alert-info">
                                        <h6><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h6>
                                        <span class="alert-date"><?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ' . __('ago', 'environmental-platform-core'); ?></span>
                                    </div>
                                </div>
                        <?php
                            endwhile;
                            wp_reset_postdata();
                        endif;
                        ?>
                    </div>
                    
                    <!-- Contact Information Widget -->
                    <div class="sidebar-widget contact-info">
                        <h4><i class="fa fa-support"></i> <?php _e('Emergency Contacts', 'environmental-platform-core'); ?></h4>
                        <div class="emergency-contacts">
                            <div class="contact-item">
                                <i class="fa fa-fire"></i>
                                <div class="contact-details">
                                    <strong><?php _e('Fire Emergency', 'environmental-platform-core'); ?></strong>
                                    <span>911</span>
                                </div>
                            </div>
                            <div class="contact-item">
                                <i class="fa fa-medkit"></i>
                                <div class="contact-details">
                                    <strong><?php _e('Medical Emergency', 'environmental-platform-core'); ?></strong>
                                    <span>911</span>
                                </div>
                            </div>
                            <div class="contact-item">
                                <i class="fa fa-leaf"></i>
                                <div class="contact-details">
                                    <strong><?php _e('Environmental Hotline', 'environmental-platform-core'); ?></strong>
                                    <span>1-800-ENV-HELP</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<!-- Emergency Contact Modal -->
<div class="modal fade" id="emergencyContactModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-phone"></i>
                    <?php _e('Emergency Contact Information', 'environmental-platform-core'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="contact-details" id="modal-contact-details">
                    <!-- Contact details will be populated by JavaScript -->
                </div>
                <div class="emergency-actions">
                    <button class="btn btn-danger btn-lg" onclick="window.location.href='tel:911'">
                        <i class="fa fa-phone"></i>
                        <?php _e('Call 911', 'environmental-platform-core'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.env-alerts-archive {
    padding: 40px 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    min-height: 100vh;
}

.archive-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.archive-title {
    font-size: 2.5em;
    color: #2c3e50;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.archive-title i {
    color: #e74c3c;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.archive-description {
    font-size: 1.1em;
    color: #7f8c8d;
    margin: 0;
}

.alert-summary-stats {
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.stat-item {
    background: white;
    padding: 25px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-5px);
}

.stat-item i {
    font-size: 2em;
    margin-bottom: 10px;
    display: block;
}

.stat-item.active i { color: #e74c3c; }
.stat-item.resolved i { color: #27ae60; }
.stat-item.total i { color: #3498db; }

.stat-number {
    display: block;
    font-size: 2em;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    color: #7f8c8d;
    font-size: 0.9em;
}

.archive-filters {
    background: white;
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 30px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.filter-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.alerts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.alert-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border-left: 5px solid #bdc3c7;
    position: relative;
}

.alert-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.alert-card.priority-emergency {
    border-left-color: #e74c3c;
    animation: emergencyPulse 3s infinite;
}

.alert-card.priority-high { border-left-color: #f39c12; }
.alert-card.priority-medium { border-left-color: #f1c40f; }
.alert-card.priority-low { border-left-color: #3498db; }

@keyframes emergencyPulse {
    0%, 100% { box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    50% { box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3); }
}

.alert-indicators {
    position: absolute;
    top: 15px;
    right: 15px;
    z-index: 2;
    display: flex;
    gap: 10px;
}

.priority-badge, .status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.priority-badge.priority-emergency {
    background: #e74c3c;
    color: white;
}

.priority-badge.priority-high {
    background: #f39c12;
    color: white;
}

.priority-badge.priority-medium {
    background: #f1c40f;
    color: #2c3e50;
}

.priority-badge.priority-low {
    background: #3498db;
    color: white;
}

.status-badge.status-active {
    background: #e74c3c;
    color: white;
}

.status-badge.status-monitoring {
    background: #f39c12;
    color: white;
}

.status-badge.status-resolved {
    background: #27ae60;
    color: white;
}

.alert-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.alert-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.alert-card:hover .alert-image img {
    transform: scale(1.05);
}

.alert-content {
    padding: 25px;
}

.alert-meta {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
    font-size: 0.9em;
    color: #7f8c8d;
}

.alert-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.alert-title {
    margin: 0 0 15px 0;
    font-size: 1.3em;
    line-height: 1.4;
}

.alert-title a {
    color: #2c3e50;
    text-decoration: none;
    transition: color 0.3s ease;
}

.alert-title a:hover {
    color: #3498db;
}

.alert-excerpt {
    color: #7f8c8d;
    line-height: 1.6;
    margin-bottom: 15px;
}

.affected-area {
    background: #ecf0f1;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 0.9em;
}

.affected-area i {
    color: #e74c3c;
    margin-right: 8px;
}

.alert-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.view-details-btn, .emergency-contact-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9em;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

.btn-warning {
    background: #f39c12;
    color: white;
}

.btn-warning:hover {
    background: #e67e22;
    transform: translateY(-2px);
}

.archive-sidebar {
    padding-left: 20px;
}

.sidebar-widget {
    background: white;
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}

.sidebar-widget h4 {
    color: #2c3e50;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.sidebar-widget h4 i {
    color: #3498db;
}

.preparedness-checklist ul {
    list-style: none;
    padding: 0;
    margin-bottom: 20px;
}

.preparedness-checklist li {
    padding: 8px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.preparedness-checklist li i {
    color: #27ae60;
}

.subscription-form .form-group {
    margin-bottom: 15px;
}

.btn-block {
    width: 100%;
}

.recent-alert-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #ecf0f1;
}

.recent-alert-item:last-child {
    border-bottom: none;
}

.priority-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.priority-indicator.priority-emergency { background: #e74c3c; }
.priority-indicator.priority-high { background: #f39c12; }
.priority-indicator.priority-medium { background: #f1c40f; }
.priority-indicator.priority-low { background: #3498db; }

.alert-info h6 {
    margin: 0 0 5px 0;
    font-size: 0.9em;
}

.alert-info h6 a {
    color: #2c3e50;
    text-decoration: none;
}

.alert-info h6 a:hover {
    color: #3498db;
}

.alert-date {
    font-size: 0.8em;
    color: #7f8c8d;
}

.emergency-contacts {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.contact-item i {
    font-size: 1.5em;
    color: #e74c3c;
    width: 30px;
    text-align: center;
}

.contact-details strong {
    display: block;
    color: #2c3e50;
    margin-bottom: 3px;
}

.contact-details span {
    color: #7f8c8d;
    font-size: 0.9em;
}

.no-alerts-message {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.no-alerts-message i {
    font-size: 4em;
    color: #27ae60;
    margin-bottom: 20px;
}

.no-alerts-message h3 {
    color: #2c3e50;
    margin-bottom: 15px;
}

.archive-pagination {
    margin-top: 40px;
    text-align: center;
}

.archive-pagination .page-numbers {
    display: inline-flex;
    list-style: none;
    padding: 0;
    margin: 0;
    gap: 10px;
}

.archive-pagination .page-numbers li {
    margin: 0;
}

.archive-pagination .page-numbers a,
.archive-pagination .page-numbers span {
    display: block;
    padding: 10px 15px;
    background: white;
    border: 2px solid #ecf0f1;
    border-radius: 8px;
    color: #2c3e50;
    text-decoration: none;
    transition: all 0.3s ease;
}

.archive-pagination .page-numbers a:hover,
.archive-pagination .page-numbers .current {
    background: #3498db;
    border-color: #3498db;
    color: white;
}

.modal-content {
    border-radius: 15px;
    border: none;
}

.modal-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 15px 15px 0 0;
}

.modal-title {
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-title i {
    color: #e74c3c;
}

.emergency-actions {
    text-align: center;
    margin-top: 20px;
}

.emergency-actions .btn {
    font-size: 1.1em;
    padding: 15px 30px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .archive-filters {
        grid-template-columns: 1fr;
    }
    
    .alerts-grid {
        grid-template-columns: 1fr;
    }
    
    .archive-title {
        font-size: 2em;
        flex-direction: column;
        gap: 10px;
    }
    
    .alert-actions {
        flex-direction: column;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .archive-sidebar {
        padding-left: 0;
        margin-top: 30px;
    }
    
    .alert-meta {
        flex-direction: column;
        gap: 10px;
    }
}

@media (max-width: 480px) {
    .alerts-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .alert-card {
        margin: 0 10px;
    }
    
    .alert-indicators {
        position: static;
        margin-bottom: 15px;
        justify-content: center;
    }
    
    .alert-image {
        height: 150px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Filter functionality
    $('#priority-filter, #status-filter, #location-filter, #sort-filter').on('change', function() {
        var priorityFilter = $('#priority-filter').val();
        var statusFilter = $('#status-filter').val();
        var locationFilter = $('#location-filter').val();
        var sortFilter = $('#sort-filter').val();
        
        var url = new URL(window.location);
        
        if (priorityFilter) {
            url.searchParams.set('priority', priorityFilter);
        } else {
            url.searchParams.delete('priority');
        }
        
        if (statusFilter) {
            url.searchParams.set('status', statusFilter);
        } else {
            url.searchParams.delete('status');
        }
        
        if (locationFilter) {
            url.searchParams.set('env_location', locationFilter);
        } else {
            url.searchParams.delete('env_location');
        }
        
        if (sortFilter && sortFilter !== 'date') {
            url.searchParams.set('orderby', sortFilter);
        } else {
            url.searchParams.delete('orderby');
        }
        
        window.location.href = url.toString();
    });
    
    // Emergency contact modal
    $('.emergency-contact-btn').on('click', function() {
        var contactInfo = $(this).data('contact');
        $('#modal-contact-details').html('<p><strong>' + contactInfo + '</strong></p>');
        $('#emergencyContactModal').modal('show');
    });
    
    // Alert subscription form
    $('#alert-subscription-form').on('submit', function(e) {
        e.preventDefault();
        
        // Here you would typically send the subscription data to your server
        alert('<?php _e("Thank you for subscribing to environmental alerts!", "environmental-platform-core"); ?>');
    });
    
    // Emergency preparedness guide
    $('#emergency-guide-btn').on('click', function(e) {
        e.preventDefault();
        
        // Open emergency guide modal or redirect to guide page
        alert('<?php _e("Emergency preparedness guide would open here.", "environmental-platform-core"); ?>');
    });
    
    // Animate alert cards on scroll
    function animateOnScroll() {
        $('.alert-card').each(function() {
            var elementTop = $(this).offset().top;
            var elementBottom = elementTop + $(this).outerHeight();
            var viewportTop = $(window).scrollTop();
            var viewportBottom = viewportTop + $(window).height();
            
            if (elementBottom > viewportTop && elementTop < viewportBottom) {
                $(this).addClass('animate-in');
            }
        });
    }
    
    $(window).on('scroll', animateOnScroll);
    animateOnScroll(); // Initial check
    
    // Auto-refresh for active alerts (every 5 minutes)
    if ($('.alert-card.status-active').length > 0) {
        setInterval(function() {
            location.reload();
        }, 300000); // 5 minutes
    }
});
</script>

<?php get_footer(); ?>
