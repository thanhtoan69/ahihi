<?php
/**
 * Environmental Email Marketing - Template Engine
 *
 * Handles email template creation, management, and rendering
 *
 * @package Environmental_Email_Marketing
 * @subpackage Templates
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EEM_Template_Engine {

    /**
     * Database manager instance
     *
     * @var EEM_Database_Manager
     */
    private $db_manager;

    /**
     * Template cache
     *
     * @var array
     */
    private $template_cache = array();

    /**
     * Available themes
     *
     * @var array
     */
    private $themes = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->db_manager = new EEM_Database_Manager();
        $this->init_themes();
    }

    /**
     * Initialize available themes
     */
    private function init_themes() {
        $this->themes = array(
            'nature_green' => array(
                'name' => 'Nature Green',
                'description' => 'Fresh green theme inspired by nature',
                'primary_color' => '#2E7D32',
                'secondary_color' => '#4CAF50',
                'accent_color' => '#81C784',
                'text_color' => '#2C3E2D',
                'background_color' => '#F1F8E9',
                'header_image' => 'nature-header.jpg'
            ),
            'earth_blue' => array(
                'name' => 'Earth Blue',
                'description' => 'Ocean-inspired blue theme',
                'primary_color' => '#0277BD',
                'secondary_color' => '#03A9F4',
                'accent_color' => '#4FC3F7',
                'text_color' => '#1B2631',
                'background_color' => '#E3F2FD',
                'header_image' => 'ocean-header.jpg'
            ),
            'climate_red' => array(
                'name' => 'Climate Action Red',
                'description' => 'Urgent red theme for climate action',
                'primary_color' => '#C62828',
                'secondary_color' => '#F44336',
                'accent_color' => '#EF5350',
                'text_color' => '#2C1810',
                'background_color' => '#FFEBEE',
                'header_image' => 'climate-header.jpg'
            ),
            'sustainable_brown' => array(
                'name' => 'Sustainable Earth',
                'description' => 'Earthy brown theme for sustainability',
                'primary_color' => '#5D4037',
                'secondary_color' => '#8D6E63',
                'accent_color' => '#A1887F',
                'text_color' => '#3E2723',
                'background_color' => '#EFEBE9',
                'header_image' => 'earth-header.jpg'
            ),
            'clean_white' => array(
                'name' => 'Clean Energy White',
                'description' => 'Clean, minimal white theme',
                'primary_color' => '#37474F',
                'secondary_color' => '#607D8B',
                'accent_color' => '#90A4AE',
                'text_color' => '#263238',
                'background_color' => '#FFFFFF',
                'header_image' => 'clean-header.jpg'
            )
        );
    }

    /**
     * Create new template
     *
     * @param array $template_data Template data
     * @return int|false Template ID or false on failure
     */
    public function create_template($template_data) {
        global $wpdb;
        
        $templates_table = $this->db_manager->get_table_name('email_templates');
        
        // Validate template data
        if (empty($template_data['name']) || empty($template_data['content'])) {
            return false;
        }
        
        // Prepare template data
        $insert_data = array(
            'name' => sanitize_text_field($template_data['name']),
            'description' => sanitize_textarea_field($template_data['description'] ?? ''),
            'content' => wp_kses_post($template_data['content']),
            'theme' => sanitize_key($template_data['theme'] ?? 'nature_green'),
            'category' => sanitize_key($template_data['category'] ?? 'general'),
            'variables' => json_encode($template_data['variables'] ?? array()),
            'environmental_theme' => sanitize_key($template_data['environmental_theme'] ?? 'nature_green'),
            'is_active' => isset($template_data['is_active']) ? (bool) $template_data['is_active'] : true,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($templates_table, $insert_data);
        
        if ($result) {
            $template_id = $wpdb->insert_id;
            
            // Clear cache
            $this->clear_template_cache();
            
            // Create default preview
            $this->generate_template_preview($template_id);
            
            return $template_id;
        }
        
        return false;
    }

    /**
     * Update template
     *
     * @param int $template_id Template ID
     * @param array $template_data Updated template data
     * @return bool Success status
     */
    public function update_template($template_id, $template_data) {
        global $wpdb;
        
        $templates_table = $this->db_manager->get_table_name('email_templates');
        
        // Prepare update data
        $update_data = array(
            'updated_at' => current_time('mysql')
        );
        
        if (isset($template_data['name'])) {
            $update_data['name'] = sanitize_text_field($template_data['name']);
        }
        
        if (isset($template_data['description'])) {
            $update_data['description'] = sanitize_textarea_field($template_data['description']);
        }
        
        if (isset($template_data['content'])) {
            $update_data['content'] = wp_kses_post($template_data['content']);
        }
        
        if (isset($template_data['theme'])) {
            $update_data['theme'] = sanitize_key($template_data['theme']);
        }
        
        if (isset($template_data['category'])) {
            $update_data['category'] = sanitize_key($template_data['category']);
        }
        
        if (isset($template_data['variables'])) {
            $update_data['variables'] = json_encode($template_data['variables']);
        }
        
        if (isset($template_data['environmental_theme'])) {
            $update_data['environmental_theme'] = sanitize_key($template_data['environmental_theme']);
        }
        
        if (isset($template_data['is_active'])) {
            $update_data['is_active'] = (bool) $template_data['is_active'];
        }
        
        $result = $wpdb->update(
            $templates_table,
            $update_data,
            array('id' => $template_id)
        );
        
        if ($result !== false) {
            // Clear cache
            $this->clear_template_cache($template_id);
            
            // Regenerate preview
            $this->generate_template_preview($template_id);
            
            return true;
        }
        
        return false;
    }

    /**
     * Get template by ID
     *
     * @param int $template_id Template ID
     * @return object|null Template object
     */
    public function get_template($template_id) {
        // Check cache first
        if (isset($this->template_cache[$template_id])) {
            return $this->template_cache[$template_id];
        }
        
        global $wpdb;
        
        $templates_table = $this->db_manager->get_table_name('email_templates');
        
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$templates_table} WHERE id = %d",
            $template_id
        ));
        
        if ($template) {
            // Parse JSON fields
            $template->variables = json_decode($template->variables, true) ?? array();
            
            // Cache template
            $this->template_cache[$template_id] = $template;
        }
        
        return $template;
    }

    /**
     * Get templates by category
     *
     * @param string $category Template category
     * @param bool $active_only Whether to return only active templates
     * @return array Array of template objects
     */
    public function get_templates_by_category($category = '', $active_only = true) {
        global $wpdb;
        
        $templates_table = $this->db_manager->get_table_name('email_templates');
        
        $where_conditions = array();
        $where_values = array();
        
        if ($active_only) {
            $where_conditions[] = 'is_active = %d';
            $where_values[] = 1;
        }
        
        if (!empty($category)) {
            $where_conditions[] = 'category = %s';
            $where_values[] = $category;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT * FROM {$templates_table} {$where_clause} ORDER BY name ASC";
        
        if (!empty($where_values)) {
            $templates = $wpdb->get_results($wpdb->prepare($query, $where_values));
        } else {
            $templates = $wpdb->get_results($query);
        }
        
        // Parse JSON fields
        foreach ($templates as $template) {
            $template->variables = json_decode($template->variables, true) ?? array();
        }
        
        return $templates;
    }

    /**
     * Render template with data
     *
     * @param int $template_id Template ID
     * @param array $data Data for template variables
     * @param array $options Rendering options
     * @return string Rendered HTML
     */
    public function render_template($template_id, $data = array(), $options = array()) {
        $template = $this->get_template($template_id);
        
        if (!$template) {
            return '';
        }
        
        // Get theme data
        $theme = $this->get_theme_data($template->environmental_theme);
        
        // Prepare template variables
        $template_vars = array_merge(
            $this->get_default_variables($theme),
            $template->variables,
            $data
        );
        
        // Process template content
        $content = $template->content;
        
        // Replace variables
        $content = $this->replace_template_variables($content, $template_vars);
        
        // Apply theme styling
        $content = $this->apply_theme_styling($content, $theme);
        
        // Add tracking pixels if enabled
        if (!empty($options['add_tracking'])) {
            $content = $this->add_tracking_pixels($content, $options['tracking_data'] ?? array());
        }
        
        // Inline CSS if requested
        if (!empty($options['inline_css'])) {
            $content = $this->inline_css($content);
        }
        
        // Wrap in base template
        $content = $this->wrap_in_base_template($content, $theme, $template_vars);
        
        return $content;
    }

    /**
     * Get theme data
     *
     * @param string $theme_key Theme key
     * @return array Theme data
     */
    public function get_theme_data($theme_key) {
        return $this->themes[$theme_key] ?? $this->themes['nature_green'];
    }

    /**
     * Get default template variables
     *
     * @param array $theme Theme data
     * @return array Default variables
     */
    private function get_default_variables($theme) {
        return array(
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'site_description' => get_bloginfo('description'),
            'current_year' => date('Y'),
            'current_date' => date('F j, Y'),
            'unsubscribe_url' => '{{unsubscribe_url}}',
            'view_online_url' => '{{view_online_url}}',
            'primary_color' => $theme['primary_color'],
            'secondary_color' => $theme['secondary_color'],
            'accent_color' => $theme['accent_color'],
            'text_color' => $theme['text_color'],
            'background_color' => $theme['background_color'],
            'header_image_url' => $this->get_header_image_url($theme['header_image']),
            'contact_address' => get_option('eem_contact_address', ''),
            'contact_phone' => get_option('eem_contact_phone', ''),
            'contact_email' => get_option('admin_email'),
            'social_facebook' => get_option('eem_social_facebook', ''),
            'social_twitter' => get_option('eem_social_twitter', ''),
            'social_instagram' => get_option('eem_social_instagram', ''),
            'social_linkedin' => get_option('eem_social_linkedin', '')
        );
    }

    /**
     * Replace template variables
     *
     * @param string $content Template content
     * @param array $variables Variables to replace
     * @return string Content with variables replaced
     */
    private function replace_template_variables($content, $variables) {
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, $value, $content);
        }
        
        // Handle conditional blocks
        $content = $this->process_conditional_blocks($content, $variables);
        
        // Handle loops
        $content = $this->process_loops($content, $variables);
        
        return $content;
    }

    /**
     * Process conditional blocks
     *
     * @param string $content Template content
     * @param array $variables Variables
     * @return string Processed content
     */
    private function process_conditional_blocks($content, $variables) {
        // Pattern: {{#if variable_name}}content{{/if}}
        $pattern = '/\{\{#if\s+([^}]+)\}\}(.*?)\{\{\/if\}\}/s';
        
        return preg_replace_callback($pattern, function($matches) use ($variables) {
            $condition = trim($matches[1]);
            $block_content = $matches[2];
            
            // Check if condition is true
            if (isset($variables[$condition]) && !empty($variables[$condition])) {
                return $block_content;
            }
            
            return '';
        }, $content);
    }

    /**
     * Process loops
     *
     * @param string $content Template content
     * @param array $variables Variables
     * @return string Processed content
     */
    private function process_loops($content, $variables) {
        // Pattern: {{#each array_name}}content{{/each}}
        $pattern = '/\{\{#each\s+([^}]+)\}\}(.*?)\{\{\/each\}\}/s';
        
        return preg_replace_callback($pattern, function($matches) use ($variables) {
            $array_name = trim($matches[1]);
            $loop_content = $matches[2];
            
            if (!isset($variables[$array_name]) || !is_array($variables[$array_name])) {
                return '';
            }
            
            $output = '';
            foreach ($variables[$array_name] as $index => $item) {
                $item_content = $loop_content;
                
                // Replace item variables
                if (is_array($item)) {
                    foreach ($item as $key => $value) {
                        $item_content = str_replace('{{' . $key . '}}', $value, $item_content);
                    }
                } else {
                    $item_content = str_replace('{{this}}', $item, $item_content);
                }
                
                // Replace index
                $item_content = str_replace('{{@index}}', $index, $item_content);
                
                $output .= $item_content;
            }
            
            return $output;
        }, $content);
    }

    /**
     * Apply theme styling
     *
     * @param string $content Content
     * @param array $theme Theme data
     * @return string Styled content
     */
    private function apply_theme_styling($content, $theme) {
        // Add theme-specific CSS classes and styling
        $theme_styles = $this->get_theme_styles($theme);
        
        // Inject styles into content
        if (strpos($content, '<style>') === false && strpos($content, '</head>') !== false) {
            $content = str_replace('</head>', "<style>{$theme_styles}</style></head>", $content);
        } elseif (strpos($content, '<style>') !== false) {
            $content = str_replace('<style>', "<style>{$theme_styles}", $content);
        } else {
            $content = "<style>{$theme_styles}</style>" . $content;
        }
        
        return $content;
    }

    /**
     * Get theme-specific CSS styles
     *
     * @param array $theme Theme data
     * @return string CSS styles
     */
    private function get_theme_styles($theme) {
        return "
        /* Environmental Email Theme Styles */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: {$theme['background_color']};
            color: {$theme['text_color']};
            line-height: 1.6;
        }
        
        .email-header {
            background-color: {$theme['primary_color']};
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .email-content {
            padding: 30px 20px;
        }
        
        .email-footer {
            background-color: {$theme['secondary_color']};
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 14px;
        }
        
        .btn-primary {
            background-color: {$theme['primary_color']};
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            font-weight: bold;
        }
        
        .btn-secondary {
            background-color: {$theme['secondary_color']};
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
        }
        
        .highlight {
            background-color: {$theme['accent_color']};
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        
        .environmental-stat {
            background-color: {$theme['accent_color']};
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            text-align: center;
        }
        
        .social-links a {
            color: white;
            text-decoration: none;
            margin: 0 10px;
        }
        
        h1, h2, h3 {
            color: {$theme['primary_color']};
        }
        
        a {
            color: {$theme['primary_color']};
        }
        
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
            }
            
            .email-content {
                padding: 20px 15px !important;
            }
        }
        ";
    }

    /**
     * Add tracking pixels
     *
     * @param string $content Email content
     * @param array $tracking_data Tracking data
     * @return string Content with tracking pixels
     */
    private function add_tracking_pixels($content, $tracking_data) {
        $tracking_pixels = '';
        
        // Open tracking pixel
        if (!empty($tracking_data['open_tracking_url'])) {
            $tracking_pixels .= '<img src="' . esc_url($tracking_data['open_tracking_url']) . '" width="1" height="1" style="display:none;" alt="" />';
        }
        
        // Add tracking pixels before closing body tag
        if (strpos($content, '</body>') !== false) {
            $content = str_replace('</body>', $tracking_pixels . '</body>', $content);
        } else {
            $content .= $tracking_pixels;
        }
        
        return $content;
    }

    /**
     * Inline CSS for better email client compatibility
     *
     * @param string $content HTML content
     * @return string Content with inlined CSS
     */
    private function inline_css($content) {
        // Simple CSS inlining - in production, you might want to use a library like CSSTidy
        
        // Extract CSS from style tags
        preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $content, $style_matches);
        
        if (empty($style_matches[1])) {
            return $content;
        }
        
        $css = implode("\n", $style_matches[1]);
        
        // Parse CSS rules (basic implementation)
        preg_match_all('/([^{]+)\{([^}]+)\}/i', $css, $css_matches);
        
        $rules = array();
        for ($i = 0; $i < count($css_matches[1]); $i++) {
            $selector = trim($css_matches[1][$i]);
            $properties = trim($css_matches[2][$i]);
            $rules[$selector] = $properties;
        }
        
        // Apply styles to matching elements (basic implementation)
        foreach ($rules as $selector => $properties) {
            if (strpos($selector, '.') === 0) {
                $class = substr($selector, 1);
                $pattern = '/(<[^>]*class=["\'][^"\']*' . preg_quote($class) . '[^"\']*["\'][^>]*>)/i';
                
                $content = preg_replace_callback($pattern, function($matches) use ($properties) {
                    $tag = $matches[1];
                    
                    // Add or merge style attribute
                    if (strpos($tag, 'style=') !== false) {
                        $tag = preg_replace('/style=["\']([^"\']*)["\']/', 'style="$1; ' . $properties . '"', $tag);
                    } else {
                        $tag = str_replace('>', ' style="' . $properties . '">', $tag);
                    }
                    
                    return $tag;
                }, $content);
            }
        }
        
        // Remove style tags
        $content = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $content);
        
        return $content;
    }

    /**
     * Wrap content in base template
     *
     * @param string $content Content to wrap
     * @param array $theme Theme data
     * @param array $variables Template variables
     * @return string Wrapped content
     */
    private function wrap_in_base_template($content, $theme, $variables) {
        $base_template = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{{email_subject}}</title>
            <!--[if mso]>
            <noscript>
                <xml>
                    <o:OfficeDocumentSettings>
                        <o:PixelsPerInch>96</o:PixelsPerInch>
                    </o:OfficeDocumentSettings>
                </xml>
            </noscript>
            <![endif]-->
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f4f4;">
            <div class="email-container">
                ' . $content . '
                
                <div class="email-footer">
                    <p><strong>{{site_name}}</strong></p>
                    <p>{{contact_address}}</p>
                    
                    {{#if social_facebook}}
                    <div class="social-links">
                        <a href="{{social_facebook}}">Facebook</a>
                        {{#if social_twitter}}<a href="{{social_twitter}}">Twitter</a>{{/if}}
                        {{#if social_instagram}}<a href="{{social_instagram}}">Instagram</a>{{/if}}
                        {{#if social_linkedin}}<a href="{{social_linkedin}}">LinkedIn</a>{{/if}}
                    </div>
                    {{/if}}
                    
                    <p style="margin-top: 20px; font-size: 12px;">
                        <a href="{{unsubscribe_url}}" style="color: #cccccc;">Unsubscribe</a> | 
                        <a href="{{view_online_url}}" style="color: #cccccc;">View Online</a>
                    </p>
                    
                    <p style="font-size: 11px; color: #999999; margin-top: 15px;">
                        This email was sent to {{subscriber_email}}.<br>
                        {{site_name}} is committed to environmental sustainability.<br>
                        © {{current_year}} {{site_name}}. All rights reserved.
                    </p>
                </div>
            </div>
        </body>
        </html>';
        
        // Replace variables in base template
        return $this->replace_template_variables($base_template, $variables);
    }

    /**
     * Generate template preview
     *
     * @param int $template_id Template ID
     * @return string|false Preview HTML or false on failure
     */
    public function generate_template_preview($template_id) {
        $template = $this->get_template($template_id);
        
        if (!$template) {
            return false;
        }
        
        // Sample data for preview
        $preview_data = array(
            'subscriber_name' => 'John Doe',
            'subscriber_email' => 'john.doe@example.com',
            'email_subject' => 'Environmental Newsletter Preview',
            'campaign_name' => 'Monthly Environmental Update',
            'content_title' => 'Latest Environmental News',
            'content_excerpt' => 'Stay updated with the latest environmental news and sustainable living tips.',
            'environmental_score' => 85,
            'carbon_saved' => '2.5 kg CO2',
            'trees_planted' => 12,
            'recycling_rate' => '78%'
        );
        
        $preview_html = $this->render_template($template_id, $preview_data, array(
            'inline_css' => true
        ));
        
        // Save preview to database
        global $wpdb;
        $templates_table = $this->db_manager->get_table_name('email_templates');
        
        $wpdb->update(
            $templates_table,
            array('preview_html' => $preview_html),
            array('id' => $template_id)
        );
        
        return $preview_html;
    }

    /**
     * Get header image URL
     *
     * @param string $image_name Image filename
     * @return string Image URL
     */
    private function get_header_image_url($image_name) {
        $plugin_url = plugin_dir_url(dirname(__FILE__));
        return $plugin_url . 'assets/images/' . $image_name;
    }

    /**
     * Clear template cache
     *
     * @param int|null $template_id Specific template ID or null for all
     */
    private function clear_template_cache($template_id = null) {
        if ($template_id) {
            unset($this->template_cache[$template_id]);
        } else {
            $this->template_cache = array();
        }
    }

    /**
     * Delete template
     *
     * @param int $template_id Template ID
     * @return bool Success status
     */
    public function delete_template($template_id) {
        global $wpdb;
        
        $templates_table = $this->db_manager->get_table_name('email_templates');
        
        $result = $wpdb->delete($templates_table, array('id' => $template_id));
        
        if ($result) {
            $this->clear_template_cache($template_id);
            return true;
        }
        
        return false;
    }

    /**
     * Duplicate template
     *
     * @param int $template_id Original template ID
     * @param string $new_name New template name
     * @return int|false New template ID or false on failure
     */
    public function duplicate_template($template_id, $new_name) {
        $original_template = $this->get_template($template_id);
        
        if (!$original_template) {
            return false;
        }
        
        $new_template_data = array(
            'name' => $new_name,
            'description' => $original_template->description . ' (Copy)',
            'content' => $original_template->content,
            'theme' => $original_template->theme,
            'category' => $original_template->category,
            'variables' => $original_template->variables,
            'environmental_theme' => $original_template->environmental_theme,
            'is_active' => false // New templates start as inactive
        );
        
        return $this->create_template($new_template_data);
    }

    /**
     * Get available themes
     *
     * @return array Available themes
     */
    public function get_available_themes() {
        return $this->themes;
    }

    /**
     * Get template categories
     *
     * @return array Template categories
     */
    public function get_template_categories() {
        return array(
            'newsletter' => 'Newsletter',
            'welcome' => 'Welcome Series',
            'promotional' => 'Promotional',
            'educational' => 'Educational',
            'seasonal' => 'Seasonal',
            'petition' => 'Petition Follow-up',
            'event' => 'Event Notifications',
            'quiz' => 'Quiz Results',
            'general' => 'General'
        );
    }

    /**
     * Import template from file
     *
     * @param string $file_path Template file path
     * @return int|false Template ID or false on failure
     */
    public function import_template($file_path) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $template_data = json_decode(file_get_contents($file_path), true);
        
        if (!$template_data) {
            return false;
        }
        
        return $this->create_template($template_data);
    }

    /**
     * Export template to file
     *
     * @param int $template_id Template ID
     * @param string $file_path Export file path
     * @return bool Success status
     */
    public function export_template($template_id, $file_path) {
        $template = $this->get_template($template_id);
        
        if (!$template) {
            return false;
        }
        
        $export_data = array(
            'name' => $template->name,
            'description' => $template->description,
            'content' => $template->content,
            'theme' => $template->theme,
            'category' => $template->category,
            'variables' => $template->variables,
            'environmental_theme' => $template->environmental_theme
        );
        
        return file_put_contents($file_path, json_encode($export_data, JSON_PRETTY_PRINT)) !== false;
    }
}
