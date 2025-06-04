<?php
/**
 * Calendar Month View Template
 *
 * @package Environmental_Platform_Events
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get the current date from the parent template
$year = $current_year;
$month = $current_month;

// Calculate calendar grid
$first_day = new DateTime();
$first_day->setDate($year, $month, 1);
$last_day = new DateTime();
$last_day->setDate($year, $month + 1, 0); // Last day of current month

// Get first day of week (0 = Sunday, 1 = Monday, etc.)
$start_day_of_week = (int) $first_day->format('w');
$days_in_month = (int) $last_day->format('d');

// Calculate start date for calendar (may include days from previous month)
$calendar_start = clone $first_day;
$calendar_start->modify('-' . $start_day_of_week . ' days');

// Get events for this month
global $wpdb;
$events_table = $wpdb->prefix . 'ep_events';

$start_date = $calendar_start->format('Y-m-d');
$end_date = clone $last_day;
$end_date->modify('+7 days'); // Include a week after to cover full calendar grid
$end_date_str = $end_date->format('Y-m-d');

$events_query = $wpdb->prepare("
    SELECT e.*, 
           COALESCE(er.registered_count, 0) as registered_count,
           CASE 
               WHEN e.registration_deadline < NOW() THEN 'closed'
               WHEN COALESCE(er.registered_count, 0) >= e.capacity THEN 'full'
               ELSE 'open'
           END as registration_status
    FROM {$events_table} e
    LEFT JOIN (
        SELECT event_id, COUNT(*) as registered_count 
        FROM {$wpdb->prefix}ep_event_registrations 
        WHERE status = 'confirmed'
        GROUP BY event_id
    ) er ON e.id = er.event_id
    WHERE e.event_date BETWEEN %s AND %s
    AND e.status = 'published'
    ORDER BY e.event_date ASC, e.event_time ASC
", $start_date, $end_date_str);

$events = $wpdb->get_results($events_query);

// Group events by date
$events_by_date = [];
foreach ($events as $event) {
    $event_date = date('Y-m-d', strtotime($event->event_date));
    if (!isset($events_by_date[$event_date])) {
        $events_by_date[$event_date] = [];
    }
    $events_by_date[$event_date][] = $event;
}

// Day names
$day_names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
?>

<div class="ep-calendar-month">
    <!-- Day Headers -->
    <?php foreach ($day_names as $day_name): ?>
        <div class="ep-calendar-day-header"><?php echo esc_html($day_name); ?></div>
    <?php endforeach; ?>
    
    <!-- Calendar Days -->
    <?php
    $current_date = clone $calendar_start;
    $today = new DateTime();
    $today_str = $today->format('Y-m-d');
    
    // Generate 6 weeks (42 days) to ensure full month coverage
    for ($week = 0; $week < 6; $week++):
        for ($day = 0; $day < 7; $day++):
            $date_str = $current_date->format('Y-m-d');
            $day_number = (int) $current_date->format('d');
            $is_current_month = (int) $current_date->format('n') === $month;
            $is_today = $date_str === $today_str;
            $is_past = $current_date < $today;
            
            // CSS classes for the day
            $day_classes = ['ep-calendar-day'];
            if (!$is_current_month) $day_classes[] = 'other-month';
            if ($is_today) $day_classes[] = 'today';
            if ($is_past) $day_classes[] = 'past';
            
            // Get events for this day
            $day_events = isset($events_by_date[$date_str]) ? $events_by_date[$date_str] : [];
    ?>
    
    <div class="<?php echo esc_attr(implode(' ', $day_classes)); ?>" data-date="<?php echo esc_attr($date_str); ?>">
        <div class="ep-day-number"><?php echo esc_html($day_number); ?></div>
        
        <div class="ep-day-events">
            <?php if (!empty($day_events)): ?>
                <?php foreach ($day_events as $event): 
                    // Determine event type class
                    $event_type = get_post_meta($event->id, '_ep_event_type', true) ?: 'general';
                    $event_classes = ['ep-event-item', $event_type];
                    
                    // Add status classes
                    if ($event->registration_status === 'full') {
                        $event_classes[] = 'full';
                    } elseif ($event->registration_status === 'closed') {
                        $event_classes[] = 'closed';
                    }
                    
                    $event_time = $event->event_time ? date('H:i', strtotime($event->event_time)) : '';
                    $event_url = get_permalink($event->id);
                ?>
                    <div class="<?php echo esc_attr(implode(' ', $event_classes)); ?>" 
                         data-event-id="<?php echo esc_attr($event->id); ?>"
                         title="<?php echo esc_attr($event->title . ($event_time ? ' at ' . $event_time : '')); ?>">
                        <?php if ($event_time): ?>
                            <span class="ep-event-time"><?php echo esc_html($event_time); ?></span>
                        <?php endif; ?>
                        <span class="ep-event-title"><?php echo esc_html(wp_trim_words($event->title, 3)); ?></span>
                        
                        <?php if ($event->registration_status === 'full'): ?>
                            <span class="ep-event-status-indicator" title="Event is full">●</span>
                        <?php elseif ($event->registration_status === 'closed'): ?>
                            <span class="ep-event-status-indicator" title="Registration closed">○</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($is_current_month && !$is_past): ?>
            <div class="ep-day-add-event" style="display: none;">
                <button type="button" class="ep-add-event-btn" data-date="<?php echo esc_attr($date_str); ?>" title="Add event">+</button>
            </div>
        <?php endif; ?>
    </div>
    
    <?php
            $current_date->modify('+1 day');
        endfor;
    endfor;
    ?>
</div>

<!-- Event Quick View Modal -->
<div id="ep-event-quick-view" class="ep-modal" style="display: none;">
    <div class="ep-modal-overlay"></div>
    <div class="ep-modal-content">
        <div class="ep-modal-header">
            <h3 class="ep-modal-title">Event Details</h3>
            <button type="button" class="ep-modal-close">&times;</button>
        </div>
        <div class="ep-modal-body">
            <!-- Content will be loaded via AJAX -->
        </div>
    </div>
</div>

<style>
/* Month view specific styles */
.ep-calendar-month {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    border: 1px solid #dee2e6;
    border-radius: 0 0 8px 8px;
    overflow: hidden;
}

.ep-calendar-day {
    border-right: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
    min-height: 120px;
    padding: 8px;
    background: white;
    position: relative;
    transition: background-color 0.2s ease;
}

.ep-calendar-day:nth-child(7n) {
    border-right: none;
}

.ep-calendar-day:hover {
    background: #f8f9fa;
}

.ep-calendar-day.other-month {
    background: #f8f9fa;
    color: #adb5bd;
}

.ep-calendar-day.today {
    background: #fff3cd;
    border-color: #ffeaa7;
}

.ep-calendar-day.past {
    background: #f1f3f4;
}

.ep-day-number {
    font-weight: 600;
    margin-bottom: 5px;
    font-size: 14px;
    color: #495057;
}

.ep-calendar-day.today .ep-day-number {
    background: #28a745;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.ep-day-events {
    display: flex;
    flex-direction: column;
    gap: 2px;
    height: calc(100% - 25px);
    overflow: hidden;
}

.ep-event-item {
    background: #28a745;
    color: white;
    padding: 3px 6px;
    border-radius: 3px;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.2s ease;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    position: relative;
    display: flex;
    align-items: center;
    gap: 4px;
}

.ep-event-item:hover {
    background: #218838;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    z-index: 10;
    white-space: normal;
    min-height: auto;
}

/* Event type colors */
.ep-event-item.workshop { background: #007bff; }
.ep-event-item.cleanup { background: #28a745; }
.ep-event-item.education { background: #ffc107; color: #212529; }
.ep-event-item.campaign { background: #dc3545; }
.ep-event-item.conference { background: #6f42c1; }
.ep-event-item.volunteer { background: #20c997; }

/* Event status indicators */
.ep-event-item.full { opacity: 0.7; border-left: 3px solid #dc3545; }
.ep-event-item.closed { opacity: 0.6; border-left: 3px solid #6c757d; }

.ep-event-time {
    font-weight: 600;
    font-size: 10px;
}

.ep-event-title {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ep-event-status-indicator {
    font-size: 8px;
    margin-left: auto;
}

.ep-day-add-event {
    position: absolute;
    bottom: 4px;
    right: 4px;
}

.ep-add-event-btn {
    background: #28a745;
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0.7;
    transition: opacity 0.2s ease;
}

.ep-calendar-day:hover .ep-add-event-btn {
    opacity: 1;
}

/* Modal styles */
.ep-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ep-modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.ep-modal-content {
    background: white;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.ep-modal-header {
    background: #2e8b57;
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 8px 8px 0 0;
}

.ep-modal-title {
    margin: 0;
    font-size: 18px;
}

.ep-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
}

.ep-modal-body {
    padding: 20px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .ep-calendar-day {
        min-height: 80px;
        padding: 4px;
    }
    
    .ep-day-number {
        font-size: 12px;
        margin-bottom: 3px;
    }
    
    .ep-event-item {
        font-size: 10px;
        padding: 2px 4px;
    }
    
    .ep-event-time {
        display: none; /* Hide time on mobile to save space */
    }
}

@media (max-width: 480px) {
    .ep-calendar-day {
        min-height: 60px;
        padding: 2px;
    }
    
    .ep-event-item {
        font-size: 9px;
        padding: 1px 3px;
    }
    
    .ep-day-events {
        gap: 1px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle event item clicks
    $(document).on('click', '.ep-event-item', function(e) {
        e.preventDefault();
        const eventId = $(this).data('event-id');
        showEventQuickView(eventId);
    });
    
    // Handle modal close
    $(document).on('click', '.ep-modal-close, .ep-modal-overlay', function(e) {
        e.preventDefault();
        $('#ep-event-quick-view').fadeOut(300);
    });
    
    // Show event quick view
    function showEventQuickView(eventId) {
        const modal = $('#ep-event-quick-view');
        const modalBody = modal.find('.ep-modal-body');
        
        // Show loading
        modalBody.html('<div class="ep-loading-center"><div class="ep-loading"></div><p>Loading event details...</p></div>');
        modal.fadeIn(300);
        
        // Load event details
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
                modalBody.html('<div class="ep-error-message">An error occurred while loading event details.</div>');
            }
        });
    }
    
    // Handle more events indicator click
    $(document).on('click', '.ep-more-events', function(e) {
        e.preventDefault();
        const date = $(this).data('date');
        // Could show a popup with all events for that day
        console.log('Show all events for date:', date);
    });
});
</script>
