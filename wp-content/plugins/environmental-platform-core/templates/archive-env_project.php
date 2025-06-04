<?php
/**
 * Archive Environmental Projects Template
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

get_header(); ?>

<div class="env-projects-archive">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <header class="archive-header">
                    <h1 class="archive-title">
                        <i class="fa fa-seedling"></i>
                        <?php _e('Environmental Projects', 'environmental-platform-core'); ?>
                    </h1>
                    <p class="archive-description">
                        <?php _e('Join our community-driven environmental projects and make a positive impact on our planet.', 'environmental-platform-core'); ?>
                    </p>
                </header>
                
                <!-- Project Statistics -->
                <div class="project-stats-overview">
                    <?php
                    $active_projects = new WP_Query(array(
                        'post_type' => 'env_project',
                        'meta_query' => array(
                            array(
                                'key' => '_project_status',
                                'value' => 'active',
                                'compare' => '='
                            )
                        ),
                        'posts_per_page' => -1
                    ));
                    
                    $completed_projects = new WP_Query(array(
                        'post_type' => 'env_project',
                        'meta_query' => array(
                            array(
                                'key' => '_project_status',
                                'value' => 'completed',
                                'compare' => '='
                            )
                        ),
                        'posts_per_page' => -1
                    ));
                    
                    $total_volunteers = 0;
                    $total_funding = 0;
                    
                    $all_projects = new WP_Query(array(
                        'post_type' => 'env_project',
                        'posts_per_page' => -1
                    ));
                    
                    while ($all_projects->have_posts()) {
                        $all_projects->the_post();
                        $volunteers = get_post_meta(get_the_ID(), '_current_volunteers', true);
                        $funding = get_post_meta(get_the_ID(), '_current_funding', true);
                        $total_volunteers += intval($volunteers);
                        $total_funding += intval($funding);
                    }
                    wp_reset_postdata();
                    ?>
                    
                    <div class="stats-grid">
                        <div class="stat-item active">
                            <i class="fa fa-play-circle"></i>
                            <span class="stat-number"><?php echo $active_projects->found_posts; ?></span>
                            <span class="stat-label"><?php _e('Active Projects', 'environmental-platform-core'); ?></span>
                        </div>
                        <div class="stat-item completed">
                            <i class="fa fa-check-circle"></i>
                            <span class="stat-number"><?php echo $completed_projects->found_posts; ?></span>
                            <span class="stat-label"><?php _e('Completed', 'environmental-platform-core'); ?></span>
                        </div>
                        <div class="stat-item volunteers">
                            <i class="fa fa-users"></i>
                            <span class="stat-number"><?php echo number_format($total_volunteers); ?></span>
                            <span class="stat-label"><?php _e('Total Volunteers', 'environmental-platform-core'); ?></span>
                        </div>
                        <div class="stat-item funding">
                            <i class="fa fa-dollar-sign"></i>
                            <span class="stat-number">$<?php echo number_format($total_funding); ?></span>
                            <span class="stat-label"><?php _e('Total Funding', 'environmental-platform-core'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Controls -->
                <div class="archive-filters">
                    <div class="filter-group">
                        <label for="status-filter"><?php _e('Project Status:', 'environmental-platform-core'); ?></label>
                        <select id="status-filter" class="form-control">
                            <option value=""><?php _e('All Status', 'environmental-platform-core'); ?></option>
                            <option value="planning" <?php selected(isset($_GET['status']) && $_GET['status'] == 'planning'); ?>><?php _e('Planning', 'environmental-platform-core'); ?></option>
                            <option value="active" <?php selected(isset($_GET['status']) && $_GET['status'] == 'active'); ?>><?php _e('Active', 'environmental-platform-core'); ?></option>
                            <option value="completed" <?php selected(isset($_GET['status']) && $_GET['status'] == 'completed'); ?>><?php _e('Completed', 'environmental-platform-core'); ?></option>
                            <option value="on-hold" <?php selected(isset($_GET['status']) && $_GET['status'] == 'on-hold'); ?>><?php _e('On Hold', 'environmental-platform-core'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="category-filter"><?php _e('Project Type:', 'environmental-platform-core'); ?></label>
                        <select id="category-filter" class="form-control">
                            <option value=""><?php _e('All Types', 'environmental-platform-core'); ?></option>
                            <?php
                            $categories = get_terms(array(
                                'taxonomy' => 'project_category',
                                'hide_empty' => true
                            ));
                            foreach ($categories as $category) {
                                $selected = (isset($_GET['project_category']) && $_GET['project_category'] == $category->slug) ? 'selected' : '';
                                echo '<option value="' . esc_attr($category->slug) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                            }
                            ?>
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
                            <option value="progress"><?php _e('Progress', 'environmental-platform-core'); ?></option>
                            <option value="volunteers"><?php _e('Most Volunteers', 'environmental-platform-core'); ?></option>
                            <option value="funding"><?php _e('Funding Goal', 'environmental-platform-core'); ?></option>
                            <option value="title"><?php _e('Title A-Z', 'environmental-platform-core'); ?></option>
                        </select>
                    </div>
                </div>
                
                <!-- Projects Grid -->
                <div class="projects-grid" id="projects-container">
                    <?php if (have_posts()) : ?>
                        <?php while (have_posts()) : the_post(); ?>
                            <?php
                            $project_status = get_post_meta(get_the_ID(), '_project_status', true);
                            $progress_percentage = get_post_meta(get_the_ID(), '_progress_percentage', true);
                            $funding_goal = get_post_meta(get_the_ID(), '_funding_goal', true);
                            $current_funding = get_post_meta(get_the_ID(), '_current_funding', true);
                            $volunteer_goal = get_post_meta(get_the_ID(), '_volunteer_goal', true);
                            $current_volunteers = get_post_meta(get_the_ID(), '_current_volunteers', true);
                            $project_location = get_post_meta(get_the_ID(), '_project_location', true);
                            $start_date = get_post_meta(get_the_ID(), '_start_date', true);
                            $end_date = get_post_meta(get_the_ID(), '_end_date', true);
                            
                            $funding_percentage = $funding_goal ? min(100, ($current_funding / $funding_goal) * 100) : 0;
                            $volunteer_percentage = $volunteer_goal ? min(100, ($current_volunteers / $volunteer_goal) * 100) : 0;
                            ?>
                            
                            <article class="project-card status-<?php echo esc_attr($project_status); ?>">
                                <!-- Status Badge -->
                                <div class="project-status-badge">
                                    <span class="status-indicator status-<?php echo esc_attr($project_status); ?>">
                                        <?php if ($project_status == 'active') : ?>
                                            <i class="fa fa-play"></i>
                                        <?php elseif ($project_status == 'completed') : ?>
                                            <i class="fa fa-check"></i>
                                        <?php elseif ($project_status == 'planning') : ?>
                                            <i class="fa fa-clock"></i>
                                        <?php else : ?>
                                            <i class="fa fa-pause"></i>
                                        <?php endif; ?>
                                        <?php echo esc_html(ucfirst(str_replace('-', ' ', $project_status))); ?>
                                    </span>
                                </div>
                                
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="project-image">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('medium', array('class' => 'img-responsive')); ?>
                                        </a>
                                        
                                        <!-- Progress Overlay -->
                                        <?php if ($progress_percentage) : ?>
                                            <div class="progress-overlay">
                                                <div class="progress-circle">
                                                    <svg class="progress-ring" width="60" height="60">
                                                        <circle class="progress-ring-circle" stroke="#fff" stroke-width="4" fill="transparent" r="26" cx="30" cy="30"
                                                                stroke-dasharray="<?php echo 2 * pi() * 26; ?>"
                                                                stroke-dashoffset="<?php echo 2 * pi() * 26 * (1 - $progress_percentage / 100); ?>"/>
                                                    </svg>
                                                    <span class="progress-text"><?php echo intval($progress_percentage); ?>%</span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="project-content">
                                    <div class="project-meta">
                                        <?php if (has_term('', 'project_category')) : ?>
                                            <span class="category">
                                                <?php echo get_the_term_list(get_the_ID(), 'project_category', '', ', '); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($project_location) : ?>
                                            <span class="location">
                                                <i class="fa fa-map-marker"></i>
                                                <?php echo esc_html($project_location); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($start_date && $end_date) : ?>
                                            <span class="duration">
                                                <i class="fa fa-calendar"></i>
                                                <?php echo date('M j', strtotime($start_date)) . ' - ' . date('M j, Y', strtotime($end_date)); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h3 class="project-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    
                                    <div class="project-excerpt">
                                        <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                    </div>
                                    
                                    <!-- Project Progress Indicators -->
                                    <div class="project-indicators">
                                        <?php if ($progress_percentage) : ?>
                                            <div class="indicator-item">
                                                <span class="indicator-label"><?php _e('Overall Progress', 'environmental-platform-core'); ?></span>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo intval($progress_percentage); ?>%"></div>
                                                </div>
                                                <span class="indicator-value"><?php echo intval($progress_percentage); ?>%</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($funding_goal && $current_funding) : ?>
                                            <div class="indicator-item">
                                                <span class="indicator-label"><?php _e('Funding Progress', 'environmental-platform-core'); ?></span>
                                                <div class="progress-bar">
                                                    <div class="progress-fill funding" style="width: <?php echo intval($funding_percentage); ?>%"></div>
                                                </div>
                                                <span class="indicator-value">$<?php echo number_format($current_funding); ?> / $<?php echo number_format($funding_goal); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($volunteer_goal && $current_volunteers) : ?>
                                            <div class="indicator-item">
                                                <span class="indicator-label"><?php _e('Volunteer Progress', 'environmental-platform-core'); ?></span>
                                                <div class="progress-bar">
                                                    <div class="progress-fill volunteers" style="width: <?php echo intval($volunteer_percentage); ?>%"></div>
                                                </div>
                                                <span class="indicator-value"><?php echo intval($current_volunteers); ?> / <?php echo intval($volunteer_goal); ?> <?php _e('volunteers', 'environmental-platform-core'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="project-actions">
                                        <a href="<?php the_permalink(); ?>" class="view-project-btn btn-primary">
                                            <?php _e('View Project', 'environmental-platform-core'); ?>
                                            <i class="fa fa-arrow-right"></i>
                                        </a>
                                        
                                        <?php if ($project_status == 'active') : ?>
                                            <button class="volunteer-btn btn-success" data-project-id="<?php echo get_the_ID(); ?>">
                                                <i class="fa fa-hands-helping"></i>
                                                <?php _e('Volunteer', 'environmental-platform-core'); ?>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($funding_goal && $project_status != 'completed') : ?>
                                            <button class="donate-btn btn-warning" data-project-id="<?php echo get_the_ID(); ?>">
                                                <i class="fa fa-heart"></i>
                                                <?php _e('Donate', 'environmental-platform-core'); ?>
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
                        <div class="no-projects-message">
                            <i class="fa fa-seedling"></i>
                            <h3><?php _e('No Projects Found', 'environmental-platform-core'); ?></h3>
                            <p><?php _e('We\'re always working on new environmental projects. Check back soon or suggest a new project!', 'environmental-platform-core'); ?></p>
                            <a href="#" class="btn btn-primary" id="suggest-project-btn">
                                <?php _e('Suggest a Project', 'environmental-platform-core'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-md-4">
                <aside class="archive-sidebar">
                    <!-- Featured Project Widget -->
                    <?php
                    $featured_project = new WP_Query(array(
                        'post_type' => 'env_project',
                        'posts_per_page' => 1,
                        'meta_key' => '_featured_project',
                        'meta_value' => 'yes'
                    ));
                    
                    if ($featured_project->have_posts()) :
                        while ($featured_project->have_posts()) : $featured_project->the_post();
                            $progress = get_post_meta(get_the_ID(), '_progress_percentage', true);
                            $funding = get_post_meta(get_the_ID(), '_current_funding', true);
                            $funding_goal = get_post_meta(get_the_ID(), '_funding_goal', true);
                    ?>
                        <div class="sidebar-widget featured-project">
                            <h4><i class="fa fa-star"></i> <?php _e('Featured Project', 'environmental-platform-core'); ?></h4>
                            <div class="featured-content">
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="featured-image">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('medium', array('class' => 'img-responsive')); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <h5><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h5>
                                <p><?php echo wp_trim_words(get_the_excerpt(), 15); ?></p>
                                
                                <?php if ($progress) : ?>
                                    <div class="featured-progress">
                                        <span class="progress-label"><?php echo intval($progress); ?>% <?php _e('Complete', 'environmental-platform-core'); ?></span>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo intval($progress); ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <a href="<?php the_permalink(); ?>" class="btn btn-primary btn-sm btn-block">
                                    <?php _e('Learn More', 'environmental-platform-core'); ?>
                                </a>
                            </div>
                        </div>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    endif;
                    ?>
                    
                    <!-- Get Involved Widget -->
                    <div class="sidebar-widget get-involved">
                        <h4><i class="fa fa-hands-helping"></i> <?php _e('Get Involved', 'environmental-platform-core'); ?></h4>
                        <div class="involvement-options">
                            <div class="option-item">
                                <i class="fa fa-user-plus"></i>
                                <div class="option-content">
                                    <h6><?php _e('Become a Volunteer', 'environmental-platform-core'); ?></h6>
                                    <p><?php _e('Join our community of environmental champions.', 'environmental-platform-core'); ?></p>
                                </div>
                            </div>
                            
                            <div class="option-item">
                                <i class="fa fa-lightbulb"></i>
                                <div class="option-content">
                                    <h6><?php _e('Suggest a Project', 'environmental-platform-core'); ?></h6>
                                    <p><?php _e('Have an idea for an environmental project?', 'environmental-platform-core'); ?></p>
                                </div>
                            </div>
                            
                            <div class="option-item">
                                <i class="fa fa-donate"></i>
                                <div class="option-content">
                                    <h6><?php _e('Support Our Cause', 'environmental-platform-core'); ?></h6>
                                    <p><?php _e('Help fund environmental initiatives.', 'environmental-platform-core'); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <button class="btn btn-success btn-block" id="volunteer-signup-btn">
                            <i class="fa fa-heart"></i>
                            <?php _e('Join Our Community', 'environmental-platform-core'); ?>
                        </button>
                    </div>
                    
                    <!-- Project Categories Widget -->
                    <div class="sidebar-widget project-categories">
                        <h4><i class="fa fa-tags"></i> <?php _e('Project Categories', 'environmental-platform-core'); ?></h4>
                        <?php
                        $categories = get_terms(array(
                            'taxonomy' => 'project_category',
                            'hide_empty' => true
                        ));
                        
                        if ($categories && !is_wp_error($categories)) :
                        ?>
                            <ul class="category-list">
                                <?php foreach ($categories as $category) : ?>
                                    <li>
                                        <a href="<?php echo get_term_link($category); ?>" class="category-link">
                                            <span class="category-name"><?php echo esc_html($category->name); ?></span>
                                            <span class="category-count"><?php echo $category->count; ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Success Stories Widget -->
                    <div class="sidebar-widget success-stories">
                        <h4><i class="fa fa-trophy"></i> <?php _e('Success Stories', 'environmental-platform-core'); ?></h4>
                        <?php
                        $success_stories = new WP_Query(array(
                            'post_type' => 'env_project',
                            'posts_per_page' => 3,
                            'meta_key' => '_project_status',
                            'meta_value' => 'completed',
                            'orderby' => 'date',
                            'order' => 'DESC'
                        ));
                        
                        if ($success_stories->have_posts()) :
                            while ($success_stories->have_posts()) : $success_stories->the_post();
                                $impact = get_post_meta(get_the_ID(), '_environmental_impact', true);
                        ?>
                                <div class="success-story-item">
                                    <h6><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h6>
                                    <?php if ($impact) : ?>
                                        <p class="impact-summary"><?php echo esc_html($impact); ?></p>
                                    <?php endif; ?>
                                    <span class="completion-date"><?php _e('Completed:', 'environmental-platform-core'); ?> <?php echo get_the_date(); ?></span>
                                </div>
                        <?php
                            endwhile;
                            wp_reset_postdata();
                        endif;
                        ?>
                    </div>
                    
                    <!-- Newsletter Signup Widget -->
                    <div class="sidebar-widget newsletter-signup">
                        <h4><i class="fa fa-envelope"></i> <?php _e('Project Updates', 'environmental-platform-core'); ?></h4>
                        <p><?php _e('Stay updated on our latest environmental projects and impact.', 'environmental-platform-core'); ?></p>
                        <form class="newsletter-form" id="project-newsletter-form">
                            <div class="form-group">
                                <input type="email" class="form-control" placeholder="<?php _e('Your email address', 'environmental-platform-core'); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <?php _e('Subscribe', 'environmental-platform-core'); ?>
                            </button>
                        </form>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<!-- Volunteer Modal -->
<div class="modal fade" id="volunteerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-hands-helping"></i>
                    <?php _e('Volunteer for Project', 'environmental-platform-core'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="volunteer-form">
                    <div class="form-group">
                        <label><?php _e('Full Name', 'environmental-platform-core'); ?></label>
                        <input type="text" class="form-control" name="volunteer_name" required>
                    </div>
                    <div class="form-group">
                        <label><?php _e('Email Address', 'environmental-platform-core'); ?></label>
                        <input type="email" class="form-control" name="volunteer_email" required>
                    </div>
                    <div class="form-group">
                        <label><?php _e('Phone Number', 'environmental-platform-core'); ?></label>
                        <input type="tel" class="form-control" name="volunteer_phone">
                    </div>
                    <div class="form-group">
                        <label><?php _e('Experience & Skills', 'environmental-platform-core'); ?></label>
                        <textarea class="form-control" name="volunteer_experience" rows="3" placeholder="<?php _e('Tell us about your relevant experience or skills...', 'environmental-platform-core'); ?>"></textarea>
                    </div>
                    <div class="form-group">
                        <label><?php _e('Availability', 'environmental-platform-core'); ?></label>
                        <select class="form-control" name="volunteer_availability">
                            <option value="weekends"><?php _e('Weekends only', 'environmental-platform-core'); ?></option>
                            <option value="evenings"><?php _e('Weekday evenings', 'environmental-platform-core'); ?></option>
                            <option value="flexible"><?php _e('Flexible schedule', 'environmental-platform-core'); ?></option>
                            <option value="specific"><?php _e('Specific dates/times', 'environmental-platform-core'); ?></option>
                        </select>
                    </div>
                    <input type="hidden" name="project_id" id="volunteer-project-id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php _e('Cancel', 'environmental-platform-core'); ?></button>
                <button type="submit" form="volunteer-form" class="btn btn-success"><?php _e('Submit Application', 'environmental-platform-core'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Donation Modal -->
<div class="modal fade" id="donationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-heart"></i>
                    <?php _e('Support This Project', 'environmental-platform-core'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="donation-amounts">
                    <h6><?php _e('Select Amount', 'environmental-platform-core'); ?></h6>
                    <div class="amount-buttons">
                        <button class="amount-btn" data-amount="25">$25</button>
                        <button class="amount-btn" data-amount="50">$50</button>
                        <button class="amount-btn" data-amount="100">$100</button>
                        <button class="amount-btn" data-amount="250">$250</button>
                    </div>
                    <div class="custom-amount">
                        <label><?php _e('Custom Amount', 'environmental-platform-core'); ?></label>
                        <input type="number" class="form-control" id="custom-amount" placeholder="0.00" min="1">
                    </div>
                </div>
                <input type="hidden" id="donation-project-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php _e('Cancel', 'environmental-platform-core'); ?></button>
                <button type="button" class="btn btn-warning" id="proceed-donation"><?php _e('Proceed to Payment', 'environmental-platform-core'); ?></button>
            </div>
        </div>
    </div>
</div>

<style>
.env-projects-archive {
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
    color: #27ae60;
    animation: grow 3s infinite;
}

@keyframes grow {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.archive-description {
    font-size: 1.1em;
    color: #7f8c8d;
    margin: 0;
}

.project-stats-overview {
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

.stat-item.active i { color: #27ae60; }
.stat-item.completed i { color: #3498db; }
.stat-item.volunteers i { color: #e74c3c; }
.stat-item.funding i { color: #f39c12; }

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

.projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.project-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    position: relative;
}

.project-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.project-status-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    z-index: 2;
}

.status-indicator {
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.status-indicator.status-active {
    background: #27ae60;
    color: white;
}

.status-indicator.status-completed {
    background: #3498db;
    color: white;
}

.status-indicator.status-planning {
    background: #f39c12;
    color: white;
}

.status-indicator.status-on-hold {
    background: #95a5a6;
    color: white;
}

.project-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.project-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.project-card:hover .project-image img {
    transform: scale(1.05);
}

.progress-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0,0,0,0.7);
    border-radius: 50%;
    padding: 10px;
}

.progress-circle {
    position: relative;
    width: 60px;
    height: 60px;
}

.progress-ring {
    transform: rotate(-90deg);
}

.progress-ring-circle {
    transition: stroke-dashoffset 0.5s ease;
    stroke: #27ae60;
    stroke-linecap: round;
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-weight: bold;
    font-size: 0.8em;
}

.project-content {
    padding: 25px;
}

.project-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
    font-size: 0.9em;
    color: #7f8c8d;
}

.project-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.project-title {
    margin: 0 0 15px 0;
    font-size: 1.3em;
    line-height: 1.4;
}

.project-title a {
    color: #2c3e50;
    text-decoration: none;
    transition: color 0.3s ease;
}

.project-title a:hover {
    color: #3498db;
}

.project-excerpt {
    color: #7f8c8d;
    line-height: 1.6;
    margin-bottom: 20px;
}

.project-indicators {
    margin-bottom: 20px;
}

.indicator-item {
    margin-bottom: 15px;
}

.indicator-label {
    display: block;
    font-size: 0.9em;
    color: #2c3e50;
    margin-bottom: 5px;
    font-weight: 600;
}

.progress-bar {
    height: 8px;
    background: #ecf0f1;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 5px;
}

.progress-fill {
    height: 100%;
    background: #27ae60;
    border-radius: 4px;
    transition: width 0.5s ease;
}

.progress-fill.funding {
    background: #f39c12;
}

.progress-fill.volunteers {
    background: #e74c3c;
}

.indicator-value {
    font-size: 0.8em;
    color: #7f8c8d;
}

.project-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.view-project-btn, .volunteer-btn, .donate-btn {
    padding: 10px 15px;
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
    flex: 1;
    justify-content: center;
    min-width: 120px;
}

.btn-primary {
    background: #3498db;
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

.btn-success {
    background: #27ae60;
    color: white;
}

.btn-success:hover {
    background: #229954;
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

.featured-content .featured-image {
    margin-bottom: 15px;
}

.featured-content h5 {
    margin-bottom: 10px;
}

.featured-content h5 a {
    color: #2c3e50;
    text-decoration: none;
}

.featured-content h5 a:hover {
    color: #3498db;
}

.featured-progress {
    margin: 15px 0;
}

.progress-label {
    display: block;
    font-size: 0.9em;
    color: #2c3e50;
    margin-bottom: 5px;
    font-weight: 600;
}

.involvement-options {
    margin-bottom: 20px;
}

.option-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    margin-bottom: 20px;
}

.option-item i {
    font-size: 1.5em;
    color: #27ae60;
    margin-top: 5px;
}

.option-content h6 {
    margin-bottom: 5px;
    color: #2c3e50;
}

.option-content p {
    margin: 0;
    font-size: 0.9em;
    color: #7f8c8d;
}

.category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-list li {
    margin-bottom: 10px;
}

.category-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: #2c3e50;
    transition: all 0.3s ease;
}

.category-link:hover {
    background: #3498db;
    color: white;
    text-decoration: none;
}

.category-count {
    background: #ecf0f1;
    padding: 3px 8px;
    border-radius: 15px;
    font-size: 0.8em;
    color: #7f8c8d;
}

.category-link:hover .category-count {
    background: rgba(255,255,255,0.2);
    color: white;
}

.success-story-item {
    padding: 15px 0;
    border-bottom: 1px solid #ecf0f1;
}

.success-story-item:last-child {
    border-bottom: none;
}

.success-story-item h6 {
    margin-bottom: 5px;
}

.success-story-item h6 a {
    color: #2c3e50;
    text-decoration: none;
}

.success-story-item h6 a:hover {
    color: #3498db;
}

.impact-summary {
    font-size: 0.9em;
    color: #27ae60;
    margin-bottom: 5px;
}

.completion-date {
    font-size: 0.8em;
    color: #7f8c8d;
}

.newsletter-form .form-group {
    margin-bottom: 15px;
}

.btn-block {
    width: 100%;
}

.no-projects-message {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    grid-column: 1 / -1;
}

.no-projects-message i {
    font-size: 4em;
    color: #27ae60;
    margin-bottom: 20px;
}

.no-projects-message h3 {
    color: #2c3e50;
    margin-bottom: 15px;
}

.archive-pagination {
    margin-top: 40px;
    text-align: center;
    grid-column: 1 / -1;
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

/* Modal Styles */
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
    color: #27ae60;
}

.donation-amounts {
    margin-bottom: 20px;
}

.amount-buttons {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin-bottom: 15px;
}

.amount-btn {
    padding: 15px;
    border: 2px solid #ecf0f1;
    background: white;
    border-radius: 8px;
    font-weight: 600;
    color: #2c3e50;
    cursor: pointer;
    transition: all 0.3s ease;
}

.amount-btn:hover,
.amount-btn.selected {
    border-color: #f39c12;
    background: #f39c12;
    color: white;
}

.custom-amount label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #2c3e50;
}

/* Responsive Design */
@media (max-width: 768px) {
    .archive-filters {
        grid-template-columns: 1fr;
    }
    
    .projects-grid {
        grid-template-columns: 1fr;
    }
    
    .archive-title {
        font-size: 2em;
        flex-direction: column;
        gap: 10px;
    }
    
    .project-actions {
        flex-direction: column;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .archive-sidebar {
        padding-left: 0;
        margin-top: 30px;
    }
    
    .project-meta {
        flex-direction: column;
        gap: 10px;
    }
    
    .amount-buttons {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .project-card {
        margin: 0 10px;
    }
    
    .project-status-badge {
        position: static;
        margin-bottom: 15px;
        display: flex;
        justify-content: center;
    }
    
    .project-image {
        height: 150px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Filter functionality
    $('#status-filter, #category-filter, #location-filter, #sort-filter').on('change', function() {
        var statusFilter = $('#status-filter').val();
        var categoryFilter = $('#category-filter').val();
        var locationFilter = $('#location-filter').val();
        var sortFilter = $('#sort-filter').val();
        
        var url = new URL(window.location);
        
        if (statusFilter) {
            url.searchParams.set('status', statusFilter);
        } else {
            url.searchParams.delete('status');
        }
        
        if (categoryFilter) {
            url.searchParams.set('project_category', categoryFilter);
        } else {
            url.searchParams.delete('project_category');
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
    
    // Volunteer modal
    $('.volunteer-btn').on('click', function() {
        var projectId = $(this).data('project-id');
        $('#volunteer-project-id').val(projectId);
        $('#volunteerModal').modal('show');
    });
    
    // Volunteer form submission
    $('#volunteer-form').on('submit', function(e) {
        e.preventDefault();
        
        // Here you would typically send the volunteer data to your server
        alert('<?php _e("Thank you for volunteering! We will contact you soon.", "environmental-platform-core"); ?>');
        $('#volunteerModal').modal('hide');
        $(this)[0].reset();
    });
    
    // Donation modal
    $('.donate-btn').on('click', function() {
        var projectId = $(this).data('project-id');
        $('#donation-project-id').val(projectId);
        $('#donationModal').modal('show');
    });
    
    // Amount selection
    $('.amount-btn').on('click', function() {
        $('.amount-btn').removeClass('selected');
        $(this).addClass('selected');
        $('#custom-amount').val('');
    });
    
    $('#custom-amount').on('input', function() {
        $('.amount-btn').removeClass('selected');
    });
    
    // Proceed to donation
    $('#proceed-donation').on('click', function() {
        var amount = $('.amount-btn.selected').data('amount') || $('#custom-amount').val();
        var projectId = $('#donation-project-id').val();
        
        if (!amount || amount <= 0) {
            alert('<?php _e("Please select or enter a donation amount.", "environmental-platform-core"); ?>');
            return;
        }
        
        // Here you would typically redirect to a payment processor
        alert('<?php _e("Redirecting to secure payment processor...", "environmental-platform-core"); ?>');
        $('#donationModal').modal('hide');
    });
    
    // Newsletter subscription
    $('#project-newsletter-form').on('submit', function(e) {
        e.preventDefault();
        
        // Here you would typically send the subscription data to your server
        alert('<?php _e("Thank you for subscribing to project updates!", "environmental-platform-core"); ?>');
        $(this)[0].reset();
    });
    
    // Volunteer signup
    $('#volunteer-signup-btn').on('click', function() {
        $('#volunteerModal').modal('show');
    });
    
    // Suggest project
    $('#suggest-project-btn').on('click', function(e) {
        e.preventDefault();
        
        // Here you would typically open a project suggestion form
        alert('<?php _e("Project suggestion form would open here.", "environmental-platform-core"); ?>');
    });
    
    // Animate project cards on scroll
    function animateOnScroll() {
        $('.project-card').each(function() {
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
    
    // Progress bar animations
    function animateProgressBars() {
        $('.progress-fill').each(function() {
            var width = $(this).attr('style').match(/width:\s*(\d+)%/);
            if (width) {
                $(this).css('width', '0%').animate({
                    width: width[1] + '%'
                }, 1000);
            }
        });
    }
    
    // Trigger progress bar animations when visible
    $(window).on('scroll', function() {
        $('.progress-bar').each(function() {
            var elementTop = $(this).offset().top;
            var elementBottom = elementTop + $(this).outerHeight();
            var viewportTop = $(window).scrollTop();
            var viewportBottom = viewportTop + $(window).height();
            
            if (elementBottom > viewportTop && elementTop < viewportBottom && !$(this).hasClass('animated')) {
                $(this).addClass('animated');
                $(this).find('.progress-fill').each(function() {
                    var targetWidth = $(this).css('width');
                    $(this).css('width', '0%').animate({
                        width: targetWidth
                    }, 1000);
                });
            }
        });
    });
});
</script>

<?php get_footer(); ?>
