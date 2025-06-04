<?php
/**
 * Template for displaying event calendar
 *
 * @package Environmental_Platform_Events
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current date parameters
$current_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$current_month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$current_view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'month';

// Validate parameters
$current_year = max(2020, min(2030, $current_year));
$current_month = max(1, min(12, $current_month));
$current_view = in_array($current_view, ['month', 'week', 'list']) ? $current_view : 'month';

// Create date object for current month
$current_date = new DateTime();
$current_date->setDate($current_year, $current_month, 1);

// Get month name
$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$month_name = $month_names[$current_month];

// Get navigation URLs
$prev_month = $current_month == 1 ? 12 : $current_month - 1;
$prev_year = $current_month == 1 ? $current_year - 1 : $current_year;
$next_month = $current_month == 12 ? 1 : $current_month + 1;
$next_year = $current_month == 12 ? $current_year + 1 : $current_year;

$base_url = get_permalink();
$prev_url = add_query_arg(['year' => $prev_year, 'month' => $prev_month, 'view' => $current_view], $base_url);
$next_url = add_query_arg(['year' => $next_year, 'month' => $next_month, 'view' => $current_view], $base_url);
$today_url = add_query_arg(['year' => date('Y'), 'month' => date('n'), 'view' => $current_view], $base_url);

// View URLs
$month_view_url = add_query_arg(['year' => $current_year, 'month' => $current_month, 'view' => 'month'], $base_url);
$week_view_url = add_query_arg(['year' => $current_year, 'month' => $current_month, 'view' => 'week'], $base_url);
$list_view_url = add_query_arg(['year' => $current_year, 'month' => $current_month, 'view' => 'list'], $base_url);
?>

<div id="ep-messages" class="ep-messages"></div>

<div class="ep-events-calendar" data-year="<?php echo esc_attr($current_year); ?>" data-month="<?php echo esc_attr($current_month); ?>" data-view="<?php echo esc_attr($current_view); ?>">
    <!-- Calendar Header -->
    <div class="ep-calendar-header">
        <h2 class="ep-calendar-title"><?php echo esc_html($month_name . ' ' . $current_year); ?></h2>
        
        <div class="ep-calendar-controls">
            <!-- Navigation -->
            <div class="ep-calendar-nav">
                <a href="<?php echo esc_url($prev_url); ?>" class="ep-nav-btn" data-action="prev">
                    ← Previous
                </a>
                <a href="<?php echo esc_url($today_url); ?>" class="ep-nav-btn" data-action="today">
                    Today
                </a>
                <a href="<?php echo esc_url($next_url); ?>" class="ep-nav-btn" data-action="next">
                    Next →
                </a>
            </div>
            
            <!-- View Switcher -->
            <div class="ep-calendar-views">
                <a href="<?php echo esc_url($month_view_url); ?>" 
                   class="ep-view-btn <?php echo $current_view === 'month' ? 'active' : ''; ?>" 
                   data-view="month">Month</a>
                <a href="<?php echo esc_url($week_view_url); ?>" 
                   class="ep-view-btn <?php echo $current_view === 'week' ? 'active' : ''; ?>" 
                   data-view="week">Week</a>
                <a href="<?php echo esc_url($list_view_url); ?>" 
                   class="ep-view-btn <?php echo $current_view === 'list' ? 'active' : ''; ?>" 
                   data-view="list">List</a>
            </div>
        </div>
    </div>
    
    <!-- Calendar Content -->
    <div class="ep-calendar-content">
        <?php if ($current_view === 'month'): ?>
            <?php include 'calendar-month-view.php'; ?>
        <?php elseif ($current_view === 'week'): ?>
            <?php include 'calendar-week-view.php'; ?>
        <?php else: ?>
            <?php include 'calendar-list-view.php'; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Event Popup Template -->
<script type="text/template" id="ep-event-popup-template">
    <div class="ep-event-popup-overlay">
        <div class="ep-event-popup">
            <div class="ep-popup-header">
                <h3 class="ep-popup-title"></h3>
                <button class="ep-close-popup" type="button">&times;</button>
            </div>
            <div class="ep-popup-content">
                <div class="ep-popup-meta"></div>
                <div class="ep-popup-description"></div>
                <div class="ep-popup-actions">
                    <a href="#" class="ep-view-event-btn">View Full Details</a>
                    <button type="button" class="ep-quick-register-btn">Quick Register</button>
                </div>
            </div>
        </div>
    </div>
</script>

<style>
/* Additional calendar-specific styles */
.ep-calendar-controls {
    display: flex;
    gap: 20px;
    align-items: center;
}

.ep-nav-btn {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 8px 15px;
    border-radius: 4px;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 500;
}

.ep-nav-btn:hover {
    background: rgba(255,255,255,0.3);
    color: white;
    text-decoration: none;
}

.ep-event-popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
}

.ep-event-popup {
    background: white;
    border-radius: 12px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: ep-popup-in 0.3s ease-out;
}

@keyframes ep-popup-in {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.ep-popup-header {
    background: linear-gradient(135deg, #2e8b57, #228b22);
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 12px 12px 0 0;
}

.ep-popup-title {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
}

.ep-close-popup {
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.2s ease;
}

.ep-close-popup:hover {
    background: rgba(255,255,255,0.2);
}

.ep-popup-content {
    padding: 25px;
}

.ep-popup-meta {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    border-left: 4px solid #28a745;
}

.ep-popup-meta p {
    margin: 5px 0;
    font-size: 14px;
}

.ep-popup-description {
    margin-bottom: 25px;
    line-height: 1.6;
    color: #495057;
}

.ep-popup-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.ep-quick-register-btn {
    background: #ffc107;
    color: #212529;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.ep-quick-register-btn:hover {
    background: #e0a800;
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .ep-calendar-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
    
    .ep-calendar-controls {
        flex-direction: column;
        gap: 15px;
        width: 100%;
    }
    
    .ep-calendar-nav,
    .ep-calendar-views {
        justify-content: center;
    }
    
    .ep-event-popup {
        width: 95%;
        margin: 20px;
    }
    
    .ep-popup-content {
        padding: 20px;
    }
    
    .ep-popup-actions {
        flex-direction: column;
    }
}
</style>

<script>
// Initialize calendar with current parameters
jQuery(document).ready(function($) {
    if (typeof EP_Events !== 'undefined') {
        EP_Events.config.currentDate = new Date(<?php echo $current_year; ?>, <?php echo $current_month - 1; ?>, 1);
        EP_Events.config.currentView = '<?php echo $current_view; ?>';
        EP_Events.loadCalendarEvents();
    }
});
</script>
