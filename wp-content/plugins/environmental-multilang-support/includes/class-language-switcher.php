<?php
/**
 * Language Switcher Component
 * 
 * Handles the display and functionality of language switching interface
 */

if (!defined('ABSPATH')) {
    exit;
}

class EMS_Language_Switcher {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_ems_switch_language', array($this, 'ajax_switch_language'));
        add_action('wp_ajax_nopriv_ems_switch_language', array($this, 'ajax_switch_language'));
        add_shortcode('ems_language_switcher', array($this, 'shortcode_callback'));
        add_action('widgets_init', array($this, 'register_widget'));
    }

    /**
     * Render language switcher
     */
    public function render($args = array()) {
        $defaults = array(
            'type' => 'dropdown', // dropdown, flags, text, buttons
            'show_flags' => true,
            'show_names' => true,
            'show_current' => true,
            'class' => 'ems-language-switcher',
            'current_class' => 'current',
            'link_class' => 'lang-link',
            'before' => '',
            'after' => '',
            'separator' => ' | '
        );

        $args = wp_parse_args($args, $defaults);
        
        $languages = $this->get_available_languages();
        $current_lang = $this->get_current_language();
        
        if (empty($languages)) {
            return '';
        }

        $output = $args['before'];
        
        switch ($args['type']) {
            case 'dropdown':
                $output .= $this->render_dropdown($languages, $current_lang, $args);
                break;
            case 'flags':
                $output .= $this->render_flags($languages, $current_lang, $args);
                break;
            case 'buttons':
                $output .= $this->render_buttons($languages, $current_lang, $args);
                break;
            case 'text':
            default:
                $output .= $this->render_text_links($languages, $current_lang, $args);
                break;
        }
        
        $output .= $args['after'];
        
        return $output;
    }

    /**
     * Render dropdown style switcher
     */
    private function render_dropdown($languages, $current_lang, $args) {
        $output = '<div class="' . esc_attr($args['class']) . ' ems-dropdown">';
        $output .= '<select id="ems-language-dropdown" onchange="emsHandleLanguageSwitch(this.value)">';
        
        foreach ($languages as $lang_code => $lang_data) {
            $selected = ($lang_code === $current_lang) ? ' selected="selected"' : '';
            $flag_html = $args['show_flags'] ? $this->get_flag_html($lang_data['flag']) . ' ' : '';
            $name = $args['show_names'] ? $lang_data['native_name'] : $lang_code;
            
            $output .= sprintf(
                '<option value="%s"%s>%s%s</option>',
                esc_attr($lang_code),
                $selected,
                $flag_html,
                esc_html($name)
            );
        }
        
        $output .= '</select>';
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Render flags style switcher
     */
    private function render_flags($languages, $current_lang, $args) {
        $output = '<div class="' . esc_attr($args['class']) . ' ems-flags">';
        
        foreach ($languages as $lang_code => $lang_data) {
            $is_current = ($lang_code === $current_lang);
            $url = $this->get_language_url($lang_code);
            
            $class = $args['link_class'];
            if ($is_current && $args['show_current']) {
                $class .= ' ' . $args['current_class'];
            }
            
            if ($is_current && !$args['show_current']) {
                continue;
            }
            
            $flag_html = $this->get_flag_html($lang_data['flag'], $lang_data['native_name']);
            $title = sprintf(__('Switch to %s', 'environmental-multilang-support'), $lang_data['native_name']);
            
            if ($is_current) {
                $output .= sprintf(
                    '<span class="%s" title="%s">%s</span>',
                    esc_attr($class),
                    esc_attr($title),
                    $flag_html
                );
            } else {
                $output .= sprintf(
                    '<a href="%s" class="%s" title="%s" data-lang="%s">%s</a>',
                    esc_url($url),
                    esc_attr($class),
                    esc_attr($title),
                    esc_attr($lang_code),
                    $flag_html
                );
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Render buttons style switcher
     */
    private function render_buttons($languages, $current_lang, $args) {
        $output = '<div class="' . esc_attr($args['class']) . ' ems-buttons">';
        
        foreach ($languages as $lang_code => $lang_data) {
            $is_current = ($lang_code === $current_lang);
            $url = $this->get_language_url($lang_code);
            
            $class = $args['link_class'] . ' ems-button';
            if ($is_current && $args['show_current']) {
                $class .= ' ' . $args['current_class'] . ' active';
            }
            
            if ($is_current && !$args['show_current']) {
                continue;
            }
            
            $flag_html = $args['show_flags'] ? $this->get_flag_html($lang_data['flag']) . ' ' : '';
            $name = $args['show_names'] ? $lang_data['native_name'] : strtoupper($lang_code);
            $title = sprintf(__('Switch to %s', 'environmental-multilang-support'), $lang_data['native_name']);
            
            if ($is_current) {
                $output .= sprintf(
                    '<span class="%s" title="%s">%s%s</span>',
                    esc_attr($class),
                    esc_attr($title),
                    $flag_html,
                    esc_html($name)
                );
            } else {
                $output .= sprintf(
                    '<a href="%s" class="%s" title="%s" data-lang="%s">%s%s</a>',
                    esc_url($url),
                    esc_attr($class),
                    esc_attr($title),
                    esc_attr($lang_code),
                    $flag_html,
                    esc_html($name)
                );
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Render text links style switcher
     */
    private function render_text_links($languages, $current_lang, $args) {
        $output = '<div class="' . esc_attr($args['class']) . ' ems-text-links">';
        $links = array();
        
        foreach ($languages as $lang_code => $lang_data) {
            $is_current = ($lang_code === $current_lang);
            $url = $this->get_language_url($lang_code);
            
            $class = $args['link_class'];
            if ($is_current && $args['show_current']) {
                $class .= ' ' . $args['current_class'];
            }
            
            if ($is_current && !$args['show_current']) {
                continue;
            }
            
            $flag_html = $args['show_flags'] ? $this->get_flag_html($lang_data['flag']) . ' ' : '';
            $name = $args['show_names'] ? $lang_data['native_name'] : strtoupper($lang_code);
            $title = sprintf(__('Switch to %s', 'environmental-multilang-support'), $lang_data['native_name']);
            
            if ($is_current) {
                $links[] = sprintf(
                    '<span class="%s" title="%s">%s%s</span>',
                    esc_attr($class),
                    esc_attr($title),
                    $flag_html,
                    esc_html($name)
                );
            } else {
                $links[] = sprintf(
                    '<a href="%s" class="%s" title="%s" data-lang="%s">%s%s</a>',
                    esc_url($url),
                    esc_attr($class),
                    esc_attr($title),
                    esc_attr($lang_code),
                    $flag_html,
                    esc_html($name)
                );
            }
        }
        
        $output .= implode($args['separator'], $links);
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Get flag HTML
     */
    private function get_flag_html($flag_code, $alt = '') {
        $flag_url = EMS_PLUGIN_URL . 'assets/images/flags/' . $flag_code . '.png';
        
        // Check if custom flag exists, otherwise use default
        if (!file_exists(EMS_PLUGIN_DIR . 'assets/images/flags/' . $flag_code . '.png')) {
            $flag_url = EMS_PLUGIN_URL . 'assets/images/flags/default.png';
        }
        
        return sprintf(
            '<img src="%s" alt="%s" class="ems-flag" width="16" height="12">',
            esc_url($flag_url),
            esc_attr($alt)
        );
    }

    /**
     * AJAX language switch handler
     */
    public function ajax_switch_language() {
        check_ajax_referer('ems_nonce', 'nonce');
        
        $lang_code = sanitize_text_field($_POST['lang']);
        
        if (!$this->is_valid_language($lang_code)) {
            wp_die(__('Invalid language code', 'environmental-multilang-support'));
        }
        
        // Set session language
        $this->set_session_language($lang_code);
        
        // Update user preference if logged in
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'ems_language_preference', $lang_code);
        }
        
        $response = array(
            'success' => true,
            'language' => $lang_code,
            'redirect_url' => $this->get_language_url($lang_code),
            'message' => sprintf(__('Language switched to %s', 'environmental-multilang-support'), $this->get_language_name($lang_code))
        );
        
        wp_send_json_success($response);
    }

    /**
     * Shortcode callback
     */
    public function shortcode_callback($atts) {
        $atts = shortcode_atts(array(
            'type' => 'dropdown',
            'show_flags' => 'true',
            'show_names' => 'true',
            'show_current' => 'true',
            'class' => 'ems-language-switcher'
        ), $atts, 'ems_language_switcher');
        
        // Convert string booleans
        $atts['show_flags'] = filter_var($atts['show_flags'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_names'] = filter_var($atts['show_names'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_current'] = filter_var($atts['show_current'], FILTER_VALIDATE_BOOLEAN);
        
        return $this->render($atts);
    }

    /**
     * Register widget
     */
    public function register_widget() {
        register_widget('EMS_Language_Switcher_Widget');
    }

    /**
     * Helper methods
     */
    private function get_available_languages() {
        $ems = Environmental_Multilang_Support::get_instance();
        return $ems->get_available_languages();
    }

    private function get_current_language() {
        $ems = Environmental_Multilang_Support::get_instance();
        return $ems->get_current_language();
    }

    private function get_language_url($lang_code) {
        $ems = Environmental_Multilang_Support::get_instance();
        return $ems->get_language_url($lang_code);
    }

    private function is_valid_language($lang_code) {
        $ems = Environmental_Multilang_Support::get_instance();
        return $ems->is_valid_language($lang_code);
    }

    private function get_language_name($lang_code) {
        $languages = $this->get_available_languages();
        return isset($languages[$lang_code]['native_name']) ? $languages[$lang_code]['native_name'] : $lang_code;
    }

    private function set_session_language($lang_code) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['ems_language'] = $lang_code;
    }
}

/**
 * Language Switcher Widget
 */
class EMS_Language_Switcher_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'ems_language_switcher_widget',
            __('Language Switcher', 'environmental-multilang-support'),
            array(
                'description' => __('Display a language switcher for multilingual content', 'environmental-multilang-support')
            )
        );
    }

    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);
        $type = isset($instance['type']) ? $instance['type'] : 'dropdown';
        $show_flags = isset($instance['show_flags']) ? (bool) $instance['show_flags'] : true;
        $show_names = isset($instance['show_names']) ? (bool) $instance['show_names'] : true;

        echo $args['before_widget'];

        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $switcher_args = array(
            'type' => $type,
            'show_flags' => $show_flags,
            'show_names' => $show_names,
            'class' => 'ems-widget-switcher'
        );

        $switcher = new EMS_Language_Switcher();
        echo $switcher->render($switcher_args);

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : __('Language', 'environmental-multilang-support');
        $type = isset($instance['type']) ? $instance['type'] : 'dropdown';
        $show_flags = isset($instance['show_flags']) ? (bool) $instance['show_flags'] : true;
        $show_names = isset($instance['show_names']) ? (bool) $instance['show_names'] : true;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'environmental-multilang-support'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('type'); ?>"><?php _e('Display Type:', 'environmental-multilang-support'); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('type'); ?>" name="<?php echo $this->get_field_name('type'); ?>">
                <option value="dropdown" <?php selected($type, 'dropdown'); ?>><?php _e('Dropdown', 'environmental-multilang-support'); ?></option>
                <option value="flags" <?php selected($type, 'flags'); ?>><?php _e('Flags', 'environmental-multilang-support'); ?></option>
                <option value="buttons" <?php selected($type, 'buttons'); ?>><?php _e('Buttons', 'environmental-multilang-support'); ?></option>
                <option value="text" <?php selected($type, 'text'); ?>><?php _e('Text Links', 'environmental-multilang-support'); ?></option>
            </select>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_flags); ?> id="<?php echo $this->get_field_id('show_flags'); ?>" name="<?php echo $this->get_field_name('show_flags'); ?>">
            <label for="<?php echo $this->get_field_id('show_flags'); ?>"><?php _e('Show Flags', 'environmental-multilang-support'); ?></label>
        </p>
        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_names); ?> id="<?php echo $this->get_field_id('show_names'); ?>" name="<?php echo $this->get_field_name('show_names'); ?>">
            <label for="<?php echo $this->get_field_id('show_names'); ?>"><?php _e('Show Language Names', 'environmental-multilang-support'); ?></label>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['type'] = (!empty($new_instance['type'])) ? strip_tags($new_instance['type']) : 'dropdown';
        $instance['show_flags'] = !empty($new_instance['show_flags']);
        $instance['show_names'] = !empty($new_instance['show_names']);
        return $instance;
    }
}
