<?php
/**
 * Custom widgets for Environmental Platform theme
 *
 * @package Environmental_Platform
 * @since 1.0.0
 */

/**
 * Register widget area.
 */
function environmental_platform_widgets_init() {
    register_sidebar(array(
        'name'          => esc_html__('Sidebar', 'environmental-platform'),
        'id'            => 'sidebar-1',
        'description'   => esc_html__('Add widgets here.', 'environmental-platform'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Footer 1', 'environmental-platform'),
        'id'            => 'footer-1',
        'description'   => esc_html__('Add widgets here for the first footer column.', 'environmental-platform'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Footer 2', 'environmental-platform'),
        'id'            => 'footer-2',
        'description'   => esc_html__('Add widgets here for the second footer column.', 'environmental-platform'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));

    register_sidebar(array(
        'name'          => esc_html__('Footer 3', 'environmental-platform'),
        'id'            => 'footer-3',
        'description'   => esc_html__('Add widgets here for the third footer column.', 'environmental-platform'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ));
}
add_action('widgets_init', 'environmental_platform_widgets_init');

/**
 * Environmental Statistics Widget
 */
class Environmental_Platform_Stats_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'environmental_stats_widget',
            __('Environmental Statistics', 'environmental-platform'),
            array('description' => __('Display environmental platform statistics.', 'environmental-platform'))
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        global $wpdb;
        
        // Get statistics
        $total_users = $wpdb->get_var("SELECT COUNT(*) FROM users WHERE status = 'active'") ?: 0;
        $carbon_saved = $wpdb->get_var("SELECT SUM(carbon_impact_kg) FROM user_activities_comprehensive WHERE carbon_impact_kg > 0") ?: 0;
        $waste_reports = $wpdb->get_var("SELECT COUNT(*) FROM waste_items WHERE status = 'classified'") ?: 0;
        
        echo '<div class="environmental-stats-widget">';
        echo '<div class="stat-item">';
        echo '<span class="stat-number">' . number_format($total_users) . '</span>';
        echo '<span class="stat-label">' . __('Active Users', 'environmental-platform') . '</span>';
        echo '</div>';
        
        echo '<div class="stat-item">';
        echo '<span class="stat-number">' . number_format($carbon_saved, 1) . 'kg</span>';
        echo '<span class="stat-label">' . __('CO₂ Saved', 'environmental-platform') . '</span>';
        echo '</div>';
        
        echo '<div class="stat-item">';
        echo '<span class="stat-number">' . number_format($waste_reports) . '</span>';
        echo '<span class="stat-label">' . __('Items Classified', 'environmental-platform') . '</span>';
        echo '</div>';
        echo '</div>';

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Environmental Impact', 'environmental-platform');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

/**
 * Environmental Tip Widget
 */
class Environmental_Platform_Tip_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'environmental_tip_widget',
            __('Environmental Tip of the Day', 'environmental-platform'),
            array('description' => __('Display daily environmental tips.', 'environmental-platform'))
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        $tip = environmental_platform_get_environmental_tip_of_day();
        
        echo '<div class="environmental-tip-widget">';
        echo '<div class="tip-icon">💡</div>';
        echo '<div class="tip-content">' . esc_html($tip) . '</div>';
        echo '</div>';

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Eco Tip of the Day', 'environmental-platform');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

/**
 * User Environmental Progress Widget
 */
class Environmental_Platform_User_Progress_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'environmental_user_progress_widget',
            __('My Environmental Progress', 'environmental-platform'),
            array('description' => __('Display current user environmental progress.', 'environmental-platform'))
        );
    }

    public function widget($args, $instance) {
        if (!is_user_logged_in()) {
            return;
        }

        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        $user_level = environmental_platform_get_user_environmental_level();
        
        echo '<div class="user-progress-widget">';
        echo '<div class="level-info">';
        echo '<div class="level-badge">';
        echo '<span class="level-number">' . $user_level['level'] . '</span>';
        echo '</div>';
        echo '<div class="level-details">';
        echo '<h4>' . esc_html($user_level['title']) . '</h4>';
        echo '<div class="progress-bar">';
        echo '<div class="progress-fill" style="width: ' . $user_level['progress'] . '%"></div>';
        echo '</div>';
        echo '<p class="progress-text">' . sprintf(__('%d points to next level', 'environmental-platform'), $user_level['points_needed']) . '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('My Progress', 'environmental-platform');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

/**
 * Register custom widgets
 */
function environmental_platform_register_widgets() {
    register_widget('Environmental_Platform_Stats_Widget');
    register_widget('Environmental_Platform_Tip_Widget');
    register_widget('Environmental_Platform_User_Progress_Widget');
}
add_action('widgets_init', 'environmental_platform_register_widgets');
