<?php
/**
 * Events Archive Template
 *
 * @package Environmental_Platform_Events
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get filtering parameters
$category_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
$type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
$month_filter = isset($_GET['month']) ? sanitize_text_field($_GET['month']) : '';
$search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Get available filter options
$categories = get_terms([
    'taxonomy' => 'event_category',
    'hide_empty' => true
]);

$event_types = get_terms([
    'taxonomy' => 'event_type',
    'hide_empty' => true
]);

// Build query arguments
$query_args = [
    'post_type' => 'ep_event',
    'post_status' => 'publish',
    'posts_per_page' => 12,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
    'meta_query' => [
        [
            'key' => '_ep_event_date',
            'value' => date('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE'
        ]
    ],
    'meta_key' => '_ep_event_date',
    'orderby' => 'meta_value',
    'order' => 'ASC'
];

// Add taxonomy filters
$tax_query = [];
if ($category_filter) {
    $tax_query[] = [
        'taxonomy' => 'event_category',
        'field' => 'slug',
        'terms' => $category_filter
    ];
}
if ($type_filter) {
    $tax_query[] = [
        'taxonomy' => 'event_type',
        'field' => 'slug',
        'terms' => $type_filter
    ];
}
if (!empty($tax_query)) {
    $query_args['tax_query'] = $tax_query;
}

// Add date filter
if ($month_filter) {
    $month_year = explode('-', $month_filter);
    if (count($month_year) === 2) {
        $query_args['meta_query'][] = [
            'key' => '_ep_event_date',
            'value' => [$month_filter . '-01', $month_filter . '-31'],
            'compare' => 'BETWEEN',
            'type' => 'DATE'
        ];
    }
}

// Add search filter
if ($search_query) {
    $query_args['s'] = $search_query;
}

// Execute query
$events_query = new WP_Query($query_args);

// Get current page URL for filters
$base_url = get_post_type_archive_link('ep_event');
?>

<div class="ep-events-archive">
    <!-- Archive Header -->
    <header class="ep-events-header">
        <h1>Environmental Events</h1>
        <p>Join us in making a positive impact on our environment through community action and education.</p>
    </header>

    <!-- Event Filters -->
    <div class="ep-events-filters">
        <form method="get" class="ep-filters-form">
            <div class="ep-filter-group">
                <label for="search" class="ep-filter-label">Search:</label>
                <input type="text" 
                       id="search" 
                       name="search" 
                       value="<?php echo esc_attr($search_query); ?>" 
                       placeholder="Search events..."
                       class="ep-filter-input">
            </div>

            <div class="ep-filter-group">
                <label for="category" class="ep-filter-label">Category:</label>
                <select id="category" name="category" class="ep-filter-select">
                    <option value="">All Categories</option>
                    <?php if ($categories): ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo esc_attr($category->slug); ?>" 
                                    <?php selected($category_filter, $category->slug); ?>>
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="ep-filter-group">
                <label for="type" class="ep-filter-label">Type:</label>
                <select id="type" name="type" class="ep-filter-select">
                    <option value="">All Types</option>
                    <?php if ($event_types): ?>
                        <?php foreach ($event_types as $type): ?>
                            <option value="<?php echo esc_attr($type->slug); ?>" 
                                    <?php selected($type_filter, $type->slug); ?>>
                                <?php echo esc_html($type->name); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="ep-filter-group">
                <label for="month" class="ep-filter-label">Month:</label>
                <select id="month" name="month" class="ep-filter-select">
                    <option value="">All Months</option>
                    <?php
                    $current_year = date('Y');
                    $next_year = $current_year + 1;
                    for ($year = $current_year; $year <= $next_year; $year++) {
                        for ($month = 1; $month <= 12; $month++) {
                            $month_value = sprintf('%d-%02d', $year, $month);
                            $month_name = date('F Y', mktime(0, 0, 0, $month, 1, $year));
                            echo sprintf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($month_value),
                                selected($month_filter, $month_value, false),
                                esc_html($month_name)
                            );
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="ep-filter-actions">
                <button type="submit" class="ep-filter-apply">Apply Filters</button>
                <a href="<?php echo esc_url($base_url); ?>" class="ep-filter-reset">Reset</a>
            </div>
        </form>
    </div>

    <!-- Events Grid -->
    <?php if ($events_query->have_posts()): ?>
        <div class="ep-events-grid">
            <?php while ($events_query->have_posts()): $events_query->the_post(); 
                $event_id = get_the_ID();
                
                // Get event meta
                $event_date = get_post_meta($event_id, '_ep_event_date', true);
                $event_time = get_post_meta($event_id, '_ep_event_time', true);
                $event_location = get_post_meta($event_id, '_ep_event_location', true);
                $event_capacity = get_post_meta($event_id, '_ep_event_capacity', true);
                $registration_deadline = get_post_meta($event_id, '_ep_registration_deadline', true);
                
                // Get registration count
                global $wpdb;
                $registration_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}ep_event_registrations 
                     WHERE event_id = %d AND status = 'confirmed'",
                    $event_id
                ));
                
                // Determine registration status
                $registration_status = 'open';
                if ($registration_deadline && strtotime($registration_deadline) < time()) {
                    $registration_status = 'closed';
                } elseif ($event_capacity && $registration_count >= $event_capacity) {
                    $registration_status = 'full';
                }
                
                // Format date and time
                $formatted_date = $event_date ? date('M j, Y', strtotime($event_date)) : '';
                $formatted_time = $event_time ? date('g:i A', strtotime($event_time)) : '';
                
                // Get categories and type
                $categories = get_the_terms($event_id, 'event_category');
                $event_type = get_the_terms($event_id, 'event_type');
                $primary_type = $event_type && !is_wp_error($event_type) ? $event_type[0]->slug : 'general';
            ?>
                <article class="ep-event-card" data-event-id="<?php echo esc_attr($event_id); ?>">
                    <header class="ep-event-card-header">
                        <?php if ($formatted_date): ?>
                            <div class="ep-event-date"><?php echo esc_html($formatted_date); ?></div>
                        <?php endif; ?>
                        
                        <h3 class="ep-event-card-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        
                        <?php if ($event_type && !is_wp_error($event_type)): ?>
                            <span class="ep-event-type ep-type-<?php echo esc_attr($event_type[0]->slug); ?>">
                                <?php echo esc_html($event_type[0]->name); ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if (has_post_thumbnail()): ?>
                            <div class="ep-event-card-image">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('medium', ['class' => 'ep-event-thumbnail']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </header>

                    <div class="ep-event-card-body">
                        <div class="ep-event-excerpt">
                            <?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?>
                        </div>

                        <div class="ep-event-card-meta">
                            <?php if ($formatted_time): ?>
                                <div class="ep-card-meta-item">
                                    <i class="ep-icon-time">🕐</i>
                                    <?php echo esc_html($formatted_time); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($event_location): ?>
                                <div class="ep-card-meta-item">
                                    <i class="ep-icon-location">📍</i>
                                    <?php echo esc_html(wp_trim_words($event_location, 3)); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($event_capacity): ?>
                                <div class="ep-card-meta-item">
                                    <i class="ep-icon-people">👥</i>
                                    <?php echo esc_html($registration_count . '/' . $event_capacity); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($categories && !is_wp_error($categories)): ?>
                            <div class="ep-event-categories">
                                <?php foreach (array_slice($categories, 0, 2) as $category): ?>
                                    <span class="ep-category-badge"><?php echo esc_html($category->name); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <footer class="ep-event-card-footer">
                        <a href="<?php the_permalink(); ?>" class="ep-view-event-btn">
                            View Details
                        </a>
                        
                        <span class="ep-registration-status ep-status-<?php echo esc_attr($registration_status); ?>">
                            <?php
                            switch ($registration_status) {
                                case 'open':
                                    echo 'Registration Open';
                                    break;
                                case 'full':
                                    echo 'Event Full';
                                    break;
                                case 'closed':
                                    echo 'Registration Closed';
                                    break;
                            }
                            ?>
                        </span>
                    </footer>
                </article>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($events_query->max_num_pages > 1): ?>
            <nav class="ep-pagination">
                <?php
                echo paginate_links([
                    'base' => get_pagenum_link(1) . '%_%',
                    'format' => 'page/%#%/',
                    'current' => max(1, get_query_var('paged')),
                    'total' => $events_query->max_num_pages,
                    'prev_text' => '← Previous',
                    'next_text' => 'Next →',
                    'class' => 'ep-pagination-link'
                ]);
                ?>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <!-- No Events Found -->
        <div class="ep-no-events">
            <div class="ep-no-events-content">
                <h3>No Events Found</h3>
                <p>There are currently no upcoming events matching your criteria.</p>
                
                <?php if ($search_query || $category_filter || $type_filter || $month_filter): ?>
                    <p><a href="<?php echo esc_url($base_url); ?>" class="ep-view-event-btn">View All Events</a></p>
                <?php endif; ?>
                
                <div class="ep-no-events-actions">
                    <a href="<?php echo esc_url(home_url('/events/calendar')); ?>" class="ep-button-secondary">
                        View Calendar
                    </a>
                    <a href="<?php echo esc_url(home_url('/contact')); ?>" class="ep-button-secondary">
                        Suggest an Event
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>
</div>

<!-- Quick View Modal -->
<div id="ep-event-quick-view" class="ep-modal" style="display: none;">
    <div class="ep-modal-overlay"></div>
    <div class="ep-modal-content">
        <div class="ep-modal-header">
            <h3 class="ep-modal-title">Event Preview</h3>
            <button type="button" class="ep-modal-close">&times;</button>
        </div>
        <div class="ep-modal-body">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<style>
/* Archive-specific styles */
.ep-filters-form {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: end;
}

.ep-filter-input {
    padding: 8px 12px;
    border: 2px solid #dee2e6;
    border-radius: 4px;
    min-width: 200px;
}

.ep-filter-actions {
    display: flex;
    gap: 10px;
}

.ep-filter-apply {
    background: #28a745;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s ease;
}

.ep-filter-apply:hover {
    background: #218838;
}

.ep-filter-reset {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    transition: background 0.3s ease;
}

.ep-filter-reset:hover {
    background: #545b62;
    color: white;
    text-decoration: none;
}

.ep-event-card-image {
    margin: 15px 0;
    overflow: hidden;
    border-radius: 6px;
}

.ep-event-thumbnail {
    width: 100%;
    height: 180px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.ep-event-card:hover .ep-event-thumbnail {
    transform: scale(1.05);
}

.ep-event-categories {
    margin-top: 10px;
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.ep-category-badge {
    background: #e9ecef;
    color: #495057;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.ep-no-events {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 40px 0;
}

.ep-no-events-content h3 {
    color: #6c757d;
    margin-bottom: 15px;
    font-size: 24px;
}

.ep-no-events-actions {
    margin-top: 30px;
    display: flex;
    gap: 15px;
    justify-content: center;
}

.ep-pagination {
    margin: 40px 0;
    text-align: center;
}

.ep-pagination .page-numbers {
    display: inline-block;
    padding: 8px 12px;
    margin: 0 4px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    text-decoration: none;
    color: #495057;
    transition: all 0.3s ease;
}

.ep-pagination .page-numbers:hover,
.ep-pagination .page-numbers.current {
    background: #28a745;
    color: white;
    border-color: #28a745;
    text-decoration: none;
}

/* Event type color variations */
.ep-type-workshop { background: #007bff; }
.ep-type-cleanup { background: #28a745; }
.ep-type-education { background: #ffc107; color: #212529; }
.ep-type-campaign { background: #dc3545; }
.ep-type-conference { background: #6f42c1; }
.ep-type-volunteer { background: #20c997; }

/* Registration status colors */
.ep-status-open { background: #d4edda; color: #155724; }
.ep-status-full { background: #f8d7da; color: #721c24; }
.ep-status-closed { background: #d1ecf1; color: #0c5460; }

@media (max-width: 768px) {
    .ep-filters-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .ep-filter-group {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .ep-filter-input,
    .ep-filter-select {
        min-width: auto;
        flex: 1;
        margin-left: 10px;
    }
    
    .ep-filter-actions {
        justify-content: center;
        margin-top: 10px;
    }
    
    .ep-no-events-actions {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Auto-submit form on filter change
    $('.ep-filter-select').on('change', function() {
        $(this).closest('form').submit();
    });
    
    // Handle event card hover for quick preview
    $('.ep-event-card').on('mouseenter', function() {
        $(this).addClass('ep-card-hover');
    }).on('mouseleave', function() {
        $(this).removeClass('ep-card-hover');
    });
    
    // Quick view functionality (optional)
    $('.ep-event-card-title a').on('click', function(e) {
        if (e.ctrlKey || e.metaKey) {
            return; // Allow opening in new tab
        }
        
        if ($(window).width() > 768) {
            // Show quick view on desktop
            e.preventDefault();
            const eventId = $(this).closest('.ep-event-card').data('event-id');
            showEventQuickView(eventId);
        }
    });
    
    function showEventQuickView(eventId) {
        const modal = $('#ep-event-quick-view');
        const modalBody = modal.find('.ep-modal-body');
        
        modalBody.html('<div class="ep-loading-center"><div class="ep-loading"></div><p>Loading...</p></div>');
        modal.fadeIn(300);
        
        $.ajax({
            url: ep_events_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ep_get_event_quick_view',
                nonce: ep_events_ajax.nonce,
                event_id: eventId
            },
            success: function(response) {
                if (response.success) {
                    modalBody.html(response.data.html);
                } else {
                    modalBody.html('<div class="ep-error-message">Failed to load event details.</div>');
                }
            },
            error: function() {
                modalBody.html('<div class="ep-error-message">An error occurred.</div>');
            }
        });
    }
    
    // Close modal
    $(document).on('click', '.ep-modal-close, .ep-modal-overlay', function(e) {
        e.preventDefault();
        $('#ep-event-quick-view').fadeOut(300);
    });
});
</script>
