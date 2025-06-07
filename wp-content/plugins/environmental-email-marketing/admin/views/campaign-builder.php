<?php
/**
 * Environmental Email Marketing - Campaign Builder Template
 * Step-by-step campaign creation interface
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get campaign data if editing
$campaign_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
$is_editing = $campaign_id > 0;
$campaign_data = $is_editing ? $this->get_campaign($campaign_id) : array();

// Default values
$campaign_data = wp_parse_args($campaign_data, array(
    'name' => '',
    'subject' => '',
    'content' => '',
    'type' => 'regular',
    'status' => 'draft',
    'template_id' => 0,
    'lists' => array(),
    'segments' => array(),
    'environmental_theme' => 'nature',
    'ab_testing' => false,
    'scheduled_at' => '',
    'environmental_settings' => array()
));

// Get lists and templates
$lists = $this->get_subscriber_lists();
$templates = $this->get_email_templates();
$segments = $this->get_subscriber_segments();
?>

<div class="eem-admin-wrap eem-campaign-builder">
    <div class="eem-builder-header">
        <div class="eem-builder-title">
            <h1>
                <?php echo $is_editing ? 'Edit Campaign' : 'Create New Campaign'; ?>
                <?php if ($is_editing): ?>
                    <span class="eem-campaign-id">#<?php echo esc_html($campaign_id); ?></span>
                <?php endif; ?>
            </h1>
            <div class="eem-builder-subtitle">
                Build engaging environmental email campaigns with our step-by-step wizard
            </div>
        </div>
        
        <div class="eem-builder-actions">
            <button class="eem-btn eem-btn-secondary eem-save-draft">
                <span class="dashicons dashicons-cloud"></span>
                Save Draft
            </button>
            <button class="eem-btn eem-btn-outline eem-preview-btn">
                <span class="dashicons dashicons-visibility"></span>
                Preview
            </button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=eem-campaigns')); ?>" class="eem-btn eem-btn-link">
                Cancel
            </a>
        </div>
    </div>

    <!-- Progress Steps -->
    <div class="eem-builder-progress">
        <div class="eem-progress-container">
            <div class="eem-progress-bar" style="width: 20%;"></div>
        </div>
        <div class="eem-steps-nav">
            <button class="eem-step-nav active" data-step="1">
                <span class="eem-step-number">1</span>
                <span class="eem-step-label">Campaign Details</span>
            </button>
            <button class="eem-step-nav" data-step="2">
                <span class="eem-step-number">2</span>
                <span class="eem-step-label">Recipients</span>
            </button>
            <button class="eem-step-nav" data-step="3">
                <span class="eem-step-number">3</span>
                <span class="eem-step-label">Content</span>
            </button>
            <button class="eem-step-nav" data-step="4">
                <span class="eem-step-number">4</span>
                <span class="eem-step-label">Settings</span>
            </button>
            <button class="eem-step-nav" data-step="5">
                <span class="eem-step-number">5</span>
                <span class="eem-step-label">Review & Send</span>
            </button>
        </div>
    </div>

    <form id="eem-campaign-builder-form" method="post" action="">
        <?php wp_nonce_field('eem_save_campaign', 'eem_campaign_nonce'); ?>
        <input type="hidden" name="action" value="save_campaign">
        <input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign_id); ?>">

        <!-- Step 1: Campaign Details -->
        <div class="eem-step eem-step-1 active" id="eem-step-1">
            <div class="eem-step-content">
                <h2>Campaign Details</h2>
                <p class="eem-step-description">Set up the basic information for your environmental email campaign.</p>

                <div class="eem-form-grid">
                    <div class="eem-form-section">
                        <div class="eem-form-group">
                            <label for="campaign_name" class="eem-form-label">
                                Campaign Name <span class="eem-required">*</span>
                            </label>
                            <input type="text" id="campaign_name" name="campaign_name" class="eem-form-control" 
                                   value="<?php echo esc_attr($campaign_data['name']); ?>" 
                                   placeholder="e.g., Earth Day Newsletter 2024" required>
                            <div class="eem-form-help">Internal name for your campaign - not visible to subscribers</div>
                        </div>

                        <div class="eem-form-group">
                            <label for="campaign_subject" class="eem-form-label">
                                Email Subject Line <span class="eem-required">*</span>
                            </label>
                            <input type="text" id="campaign_subject" name="campaign_subject" class="eem-form-control" 
                                   value="<?php echo esc_attr($campaign_data['subject']); ?>" 
                                   placeholder="e.g., 🌍 Join Us for Earth Day - Make a Difference Today!" required>
                            <div class="eem-subject-tools">
                                <button type="button" class="eem-btn eem-btn-small eem-subject-emoji">Add Emoji</button>
                                <button type="button" class="eem-btn eem-btn-small eem-subject-preview">Preview</button>
                                <div class="eem-subject-length">
                                    <span id="eem-subject-count">0</span>/50 characters (recommended)
                                </div>
                            </div>
                        </div>

                        <div class="eem-form-group">
                            <label for="campaign_type" class="eem-form-label">Campaign Type</label>
                            <div class="eem-radio-group">
                                <label class="eem-radio-option">
                                    <input type="radio" name="campaign_type" value="regular" <?php checked($campaign_data['type'], 'regular'); ?>>
                                    <span class="eem-radio-label">
                                        <strong>Regular Campaign</strong>
                                        <span class="eem-radio-description">Send once to your selected audience</span>
                                    </span>
                                </label>
                                <label class="eem-radio-option">
                                    <input type="radio" name="campaign_type" value="a_b_test" <?php checked($campaign_data['type'], 'a_b_test'); ?>>
                                    <span class="eem-radio-label">
                                        <strong>A/B Test</strong>
                                        <span class="eem-radio-description">Test different versions to optimize performance</span>
                                    </span>
                                </label>
                                <label class="eem-radio-option">
                                    <input type="radio" name="campaign_type" value="automated" <?php checked($campaign_data['type'], 'automated'); ?>>
                                    <span class="eem-radio-label">
                                        <strong>Automated</strong>
                                        <span class="eem-radio-description">Trigger-based emails for environmental actions</span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- A/B Test Options -->
                        <div class="eem-ab-test-options" style="display: none;">
                            <div class="eem-form-group">
                                <label class="eem-form-label">A/B Test Configuration</label>
                                <div class="eem-checkbox-group">
                                    <label>
                                        <input type="checkbox" name="ab_test_subject" value="1">
                                        Test different subject lines
                                    </label>
                                    <label>
                                        <input type="checkbox" name="ab_test_content" value="1">
                                        Test different email content
                                    </label>
                                    <label>
                                        <input type="checkbox" name="ab_test_send_time" value="1">
                                        Test different send times
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="eem-form-section">
                        <div class="eem-environmental-settings">
                            <h3>
                                <span class="eem-environmental-icon">🌱</span>
                                Environmental Settings
                            </h3>
                            
                            <div class="eem-form-group">
                                <label for="environmental_theme" class="eem-form-label">Environmental Theme</label>
                                <select id="environmental_theme" name="environmental_theme" class="eem-form-control">
                                    <option value="nature" <?php selected($campaign_data['environmental_theme'], 'nature'); ?>>🌿 Nature Green</option>
                                    <option value="earth" <?php selected($campaign_data['environmental_theme'], 'earth'); ?>>🌍 Earth Blue</option>
                                    <option value="climate" <?php selected($campaign_data['environmental_theme'], 'climate'); ?>>🔥 Climate Action Red</option>
                                    <option value="sustainable" <?php selected($campaign_data['environmental_theme'], 'sustainable'); ?>>🌾 Sustainable Brown</option>
                                    <option value="clean" <?php selected($campaign_data['environmental_theme'], 'clean'); ?>>☁️ Clean White</option>
                                </select>
                            </div>

                            <div class="eem-form-group">
                                <label class="eem-form-label">Environmental Features</label>
                                <div class="eem-checkbox-group">
                                    <label>
                                        <input type="checkbox" name="include_carbon_footer" value="1" <?php checked(!empty($campaign_data['environmental_settings']['include_carbon_footer'])); ?>>
                                        Include carbon footprint information
                                    </label>
                                    <label>
                                        <input type="checkbox" name="optimize_images" value="1" <?php checked(!empty($campaign_data['environmental_settings']['optimize_images'])); ?>>
                                        Optimize images for reduced data usage
                                    </label>
                                    <label>
                                        <input type="checkbox" name="track_environmental_actions" value="1" <?php checked(!empty($campaign_data['environmental_settings']['track_environmental_actions'])); ?>>
                                        Track environmental action clicks
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Recipients -->
        <div class="eem-step eem-step-2" id="eem-step-2">
            <div class="eem-step-content">
                <h2>Select Recipients</h2>
                <p class="eem-step-description">Choose who will receive your environmental email campaign.</p>

                <div class="eem-recipients-options">
                    <div class="eem-recipient-tabs">
                        <button type="button" class="eem-tab-btn active" data-tab="lists">
                            <span class="dashicons dashicons-groups"></span>
                            Subscriber Lists
                        </button>
                        <button type="button" class="eem-tab-btn" data-tab="segments">
                            <span class="dashicons dashicons-filter"></span>
                            Segments
                        </button>
                        <button type="button" class="eem-tab-btn" data-tab="custom">
                            <span class="dashicons dashicons-admin-users"></span>
                            Custom
                        </button>
                    </div>

                    <!-- Lists Tab -->
                    <div class="eem-tab-content eem-tab-lists active">
                        <div class="eem-lists-grid">
                            <?php foreach ($lists as $list): ?>
                                <div class="eem-list-option">
                                    <label class="eem-list-checkbox">
                                        <input type="checkbox" name="selected_lists[]" value="<?php echo esc_attr($list['id']); ?>" 
                                               <?php checked(in_array($list['id'], (array)$campaign_data['lists'])); ?>>
                                        <div class="eem-list-info">
                                            <div class="eem-list-name"><?php echo esc_html($list['name']); ?></div>
                                            <div class="eem-list-meta">
                                                <?php echo esc_html(number_format($list['subscriber_count'])); ?> subscribers
                                                <?php if ($list['environmental_focus']): ?>
                                                    <span class="eem-environmental-badge">🌱 Environmental</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="eem-list-description"><?php echo esc_html($list['description']); ?></div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Segments Tab -->
                    <div class="eem-tab-content eem-tab-segments">
                        <div class="eem-segments-grid">
                            <?php foreach ($segments as $segment): ?>
                                <div class="eem-segment-option">
                                    <label class="eem-segment-checkbox">
                                        <input type="checkbox" name="selected_segments[]" value="<?php echo esc_attr($segment['id']); ?>" 
                                               <?php checked(in_array($segment['id'], (array)$campaign_data['segments'])); ?>>
                                        <div class="eem-segment-info">
                                            <div class="eem-segment-name"><?php echo esc_html($segment['name']); ?></div>
                                            <div class="eem-segment-meta">
                                                ~<?php echo esc_html(number_format($segment['estimated_size'])); ?> subscribers
                                                <span class="eem-environmental-score">🌱 Avg: <?php echo esc_html($segment['avg_environmental_score']); ?></span>
                                            </div>
                                            <div class="eem-segment-criteria"><?php echo esc_html($segment['criteria_description']); ?></div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Custom Tab -->
                    <div class="eem-tab-content eem-tab-custom">
                        <div class="eem-custom-recipients">
                            <div class="eem-form-group">
                                <label class="eem-form-label">Environmental Interest Filter</label>
                                <div class="eem-checkbox-group">
                                    <label><input type="checkbox" name="interests[]" value="climate_change"> Climate Change</label>
                                    <label><input type="checkbox" name="interests[]" value="renewable_energy"> Renewable Energy</label>
                                    <label><input type="checkbox" name="interests[]" value="conservation"> Conservation</label>
                                    <label><input type="checkbox" name="interests[]" value="sustainable_living"> Sustainable Living</label>
                                    <label><input type="checkbox" name="interests[]" value="environmental_education"> Environmental Education</label>
                                </div>
                            </div>

                            <div class="eem-form-group">
                                <label class="eem-form-label">Environmental Score Range</label>
                                <div class="eem-range-inputs">
                                    <input type="number" name="env_score_min" placeholder="Min" class="eem-form-control" min="0" max="1000">
                                    <span>to</span>
                                    <input type="number" name="env_score_max" placeholder="Max" class="eem-form-control" min="0" max="1000">
                                </div>
                            </div>

                            <div class="eem-form-group">
                                <label class="eem-form-label">Engagement Level</label>
                                <select name="engagement_level" class="eem-form-control">
                                    <option value="">All subscribers</option>
                                    <option value="high">High engagement (opens regularly)</option>
                                    <option value="medium">Medium engagement</option>
                                    <option value="low">Low engagement (re-engagement)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="eem-recipients-summary">
                    <div class="eem-summary-card">
                        <h4>Campaign Reach</h4>
                        <div class="eem-reach-stats">
                            <div class="eem-reach-stat">
                                <div class="eem-reach-number" id="eem-total-recipients">0</div>
                                <div class="eem-reach-label">Total Recipients</div>
                            </div>
                            <div class="eem-reach-stat">
                                <div class="eem-reach-number" id="eem-environmental-subscribers">0</div>
                                <div class="eem-reach-label">Environmental Subscribers</div>
                            </div>
                            <div class="eem-reach-stat">
                                <div class="eem-reach-number" id="eem-avg-engagement">0%</div>
                                <div class="eem-reach-label">Avg Engagement</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Content -->
        <div class="eem-step eem-step-3" id="eem-step-3">
            <div class="eem-step-content">
                <h2>Email Content</h2>
                <p class="eem-step-description">Create compelling content for your environmental email campaign.</p>

                <div class="eem-content-builder">
                    <div class="eem-content-toolbar">
                        <button type="button" class="eem-btn eem-btn-secondary" onclick="eemOpenTemplateLibrary()">
                            <span class="dashicons dashicons-book"></span>
                            Template Library
                        </button>
                        <button type="button" class="eem-btn eem-btn-outline eem-import-content">
                            <span class="dashicons dashicons-download"></span>
                            Import Content
                        </button>
                        <button type="button" class="eem-btn eem-btn-outline" onclick="eemPreviewCampaign()">
                            <span class="dashicons dashicons-visibility"></span>
                            Preview
                        </button>
                    </div>

                    <div class="eem-editor-container">
                        <?php
                        wp_editor($campaign_data['content'], 'campaign_content', array(
                            'textarea_name' => 'campaign_content',
                            'textarea_rows' => 20,
                            'media_buttons' => true,
                            'tinymce' => array(
                                'toolbar1' => 'formatselect,bold,italic,underline,strikethrough,|,bullist,numlist,|,link,unlink,|,image,|,alignleft,aligncenter,alignright,|,undo,redo',
                                'toolbar2' => 'forecolor,backcolor,|,hr,|,charmap,|,pastetext,removeformat,|,outdent,indent,|,wp_more,|,spellchecker,fullscreen,wp_adv',
                                'content_css' => plugin_dir_url(dirname(__FILE__)) . 'assets/css/editor.css'
                            )
                        ));
                        ?>
                    </div>

                    <div class="eem-content-sidebar">
                        <div class="eem-sidebar-section">
                            <h4>Environmental Variables</h4>
                            <div class="eem-variable-list">
                                <button type="button" class="eem-variable-btn" data-variable="{{subscriber_name}}">
                                    Subscriber Name
                                </button>
                                <button type="button" class="eem-variable-btn" data-variable="{{environmental_score}}">
                                    Environmental Score
                                </button>
                                <button type="button" class="eem-variable-btn" data-variable="{{carbon_footprint}}">
                                    Carbon Footprint
                                </button>
                                <button type="button" class="eem-variable-btn" data-variable="{{green_actions_count}}">
                                    Green Actions Count
                                </button>
                                <button type="button" class="eem-variable-btn" data-variable="{{eco_tips}}">
                                    Personalized Eco Tips
                                </button>
                                <button type="button" class="eem-variable-btn" data-variable="{{local_events}}">
                                    Local Environmental Events
                                </button>
                            </div>
                        </div>

                        <div class="eem-sidebar-section">
                            <h4>Content Blocks</h4>
                            <div class="eem-block-list">
                                <div class="eem-content-block" data-block="environmental_tip">
                                    <div class="eem-block-icon">💡</div>
                                    <div class="eem-block-name">Environmental Tip</div>
                                </div>
                                <div class="eem-content-block" data-block="carbon_calculator">
                                    <div class="eem-block-icon">📊</div>
                                    <div class="eem-block-name">Carbon Calculator</div>
                                </div>
                                <div class="eem-content-block" data-block="green_action_cta">
                                    <div class="eem-block-icon">🎯</div>
                                    <div class="eem-block-name">Green Action CTA</div>
                                </div>
                                <div class="eem-content-block" data-block="sustainability_progress">
                                    <div class="eem-block-icon">📈</div>
                                    <div class="eem-block-name">Sustainability Progress</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 4: Settings -->
        <div class="eem-step eem-step-4" id="eem-step-4">
            <div class="eem-step-content">
                <h2>Campaign Settings</h2>
                <p class="eem-step-description">Configure delivery and tracking settings for your campaign.</p>

                <div class="eem-settings-grid">
                    <div class="eem-settings-section">
                        <h3>Delivery Settings</h3>
                        
                        <div class="eem-form-group">
                            <label class="eem-form-label">Send Option</label>
                            <div class="eem-radio-group">
                                <label class="eem-radio-option">
                                    <input type="radio" name="send_option" value="now" checked>
                                    <span class="eem-radio-label">Send immediately</span>
                                </label>
                                <label class="eem-radio-option">
                                    <input type="radio" name="send_option" value="scheduled">
                                    <span class="eem-radio-label">Schedule for later</span>
                                </label>
                            </div>
                        </div>

                        <div class="eem-schedule-options" style="display: none;">
                            <div class="eem-form-group">
                                <label for="scheduled_date" class="eem-form-label">Schedule Date & Time</label>
                                <div class="eem-datetime-inputs">
                                    <input type="date" name="scheduled_date" class="eem-form-control">
                                    <input type="time" name="scheduled_time" class="eem-form-control">
                                </div>
                            </div>

                            <div class="eem-form-group">
                                <label for="timezone" class="eem-form-label">Timezone</label>
                                <select name="timezone" class="eem-form-control">
                                    <option value="UTC">UTC</option>
                                    <option value="America/New_York">Eastern Time</option>
                                    <option value="America/Chicago">Central Time</option>
                                    <option value="America/Denver">Mountain Time</option>
                                    <option value="America/Los_Angeles">Pacific Time</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="eem-settings-section">
                        <h3>Tracking & Analytics</h3>
                        
                        <div class="eem-checkbox-group">
                            <label>
                                <input type="checkbox" name="track_opens" value="1" checked>
                                Track email opens
                            </label>
                            <label>
                                <input type="checkbox" name="track_clicks" value="1" checked>
                                Track link clicks
                            </label>
                            <label>
                                <input type="checkbox" name="track_environmental_actions" value="1" checked>
                                Track environmental action clicks
                            </label>
                            <label>
                                <input type="checkbox" name="google_analytics" value="1">
                                Enable Google Analytics tracking
                            </label>
                        </div>
                    </div>

                    <div class="eem-settings-section">
                        <h3>Environmental Impact</h3>
                        
                        <div class="eem-checkbox-group">
                            <label>
                                <input type="checkbox" name="calculate_carbon_offset" value="1" checked>
                                Calculate carbon offset vs print
                            </label>
                            <label>
                                <input type="checkbox" name="optimize_delivery" value="1" checked>
                                Optimize delivery for minimal energy use
                            </label>
                            <label>
                                <input type="checkbox" name="include_sustainability_footer" value="1" checked>
                                Include sustainability information in footer
                            </label>
                        </div>

                        <div class="eem-environmental-goals">
                            <h4>Environmental Goals</h4>
                            <div class="eem-form-group">
                                <label>Target environmental actions from this campaign:</label>
                                <input type="number" name="target_environmental_actions" class="eem-form-control" min="0" placeholder="e.g., 100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 5: Review & Send -->
        <div class="eem-step eem-step-5" id="eem-step-5">
            <div class="eem-step-content">
                <h2>Review & Send</h2>
                <p class="eem-step-description">Review your campaign details before sending to your environmental community.</p>

                <div class="eem-review-sections">
                    <!-- Campaign Summary -->
                    <div class="eem-review-section">
                        <h3>Campaign Summary</h3>
                        <div class="eem-summary-grid">
                            <div class="eem-summary-item">
                                <label>Campaign Name:</label>
                                <span id="review-campaign-name">—</span>
                            </div>
                            <div class="eem-summary-item">
                                <label>Subject Line:</label>
                                <span id="review-subject">—</span>
                            </div>
                            <div class="eem-summary-item">
                                <label>Campaign Type:</label>
                                <span id="review-type">—</span>
                            </div>
                            <div class="eem-summary-item">
                                <label>Environmental Theme:</label>
                                <span id="review-theme">—</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recipients Summary -->
                    <div class="eem-review-section">
                        <h3>Recipients</h3>
                        <div class="eem-recipients-preview">
                            <div class="eem-recipient-stats">
                                <div class="eem-stat">
                                    <div class="eem-stat-number" id="review-total-recipients">0</div>
                                    <div class="eem-stat-label">Total Recipients</div>
                                </div>
                                <div class="eem-stat">
                                    <div class="eem-stat-number" id="review-estimated-opens">0</div>
                                    <div class="eem-stat-label">Est. Opens</div>
                                </div>
                                <div class="eem-stat">
                                    <div class="eem-stat-number" id="review-estimated-clicks">0</div>
                                    <div class="eem-stat-label">Est. Clicks</div>
                                </div>
                                <div class="eem-stat">
                                    <div class="eem-stat-number" id="review-environmental-impact">0</div>
                                    <div class="eem-stat-label">Est. Environmental Actions</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Environmental Impact Prediction -->
                    <div class="eem-review-section eem-environmental-impact">
                        <h3>
                            <span class="eem-environmental-icon">🌍</span>
                            Environmental Impact Prediction
                        </h3>
                        <div class="eem-impact-predictions">
                            <div class="eem-impact-item">
                                <div class="eem-impact-icon">🌱</div>
                                <div class="eem-impact-content">
                                    <div class="eem-impact-value" id="review-carbon-saved">0kg</div>
                                    <div class="eem-impact-label">CO₂ saved vs print</div>
                                </div>
                            </div>
                            <div class="eem-impact-item">
                                <div class="eem-impact-icon">💧</div>
                                <div class="eem-impact-content">
                                    <div class="eem-impact-value" id="review-water-saved">0L</div>
                                    <div class="eem-impact-label">Water saved</div>
                                </div>
                            </div>
                            <div class="eem-impact-item">
                                <div class="eem-impact-icon">⚡</div>
                                <div class="eem-impact-content">
                                    <div class="eem-impact-value" id="review-energy-saved">0kWh</div>
                                    <div class="eem-impact-label">Energy saved</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Final Actions -->
                    <div class="eem-review-section">
                        <h3>Final Steps</h3>
                        <div class="eem-final-actions">
                            <div class="eem-test-email">
                                <h4>Send Test Email</h4>
                                <p>Send a test email to yourself before launching the campaign.</p>
                                <div class="eem-test-form">
                                    <input type="email" id="test-email-address" placeholder="your-email@example.com" class="eem-form-control">
                                    <button type="button" class="eem-btn eem-btn-secondary eem-send-test">Send Test</button>
                                </div>
                            </div>

                            <div class="eem-launch-campaign">
                                <h4>Launch Campaign</h4>
                                <p>Ready to send your environmental email campaign?</p>
                                <div class="eem-launch-buttons">
                                    <button type="button" class="eem-btn eem-btn-secondary eem-save-draft">
                                        Save as Draft
                                    </button>
                                    <button type="button" class="eem-btn eem-btn-primary eem-send-campaign" id="eem-send-now">
                                        <span class="dashicons dashicons-email-alt"></span>
                                        Send Campaign Now
                                    </button>
                                    <button type="button" class="eem-btn eem-btn-environmental eem-schedule-campaign" style="display: none;">
                                        <span class="dashicons dashicons-clock"></span>
                                        Schedule Campaign
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="eem-builder-navigation">
            <button type="button" class="eem-btn eem-btn-secondary eem-btn-prev" style="display: none;">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                Previous
            </button>
            
            <div class="eem-nav-spacer"></div>
            
            <div class="eem-last-saved">
                Last saved: Never
            </div>
            
            <button type="button" class="eem-btn eem-btn-primary eem-btn-next">
                Next
                <span class="dashicons dashicons-arrow-right-alt"></span>
            </button>
        </div>
    </form>
</div>

<!-- Template Library Modal -->
<div id="eem-template-library-modal" class="eem-modal eem-modal-large" tabindex="-1">
    <div class="eem-modal-dialog">
        <div class="eem-modal-content">
            <div class="eem-modal-header">
                <h5 class="eem-modal-title">Environmental Email Templates</h5>
                <button type="button" class="eem-modal-close" data-dismiss="modal">&times;</button>
            </div>
            <div class="eem-modal-body">
                <div class="eem-template-grid">
                    <?php foreach ($templates as $template): ?>
                        <div class="eem-template-option" data-template-id="<?php echo esc_attr($template['id']); ?>">
                            <div class="eem-template-preview">
                                <img src="<?php echo esc_url($template['preview_image']); ?>" alt="<?php echo esc_attr($template['name']); ?>">
                            </div>
                            <div class="eem-template-info">
                                <h4><?php echo esc_html($template['name']); ?></h4>
                                <p><?php echo esc_html($template['description']); ?></p>
                                <div class="eem-template-meta">
                                    <span class="eem-template-theme"><?php echo esc_html($template['theme']); ?></span>
                                    <span class="eem-template-type"><?php echo esc_html($template['type']); ?></span>
                                </div>
                                <button class="eem-btn eem-btn-primary" onclick="eemSelectTemplate(<?php echo esc_attr($template['id']); ?>)">
                                    Use Template
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
// Campaign builder specific variables
var eem_admin_vars = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('eem_admin_nonce'); ?>',
    new_campaign_url: '<?php echo admin_url('admin.php?page=eem-campaigns&action=new'); ?>',
    analytics_url: '<?php echo admin_url('admin.php?page=eem-analytics'); ?>'
};

// Initialize campaign builder when document is ready
jQuery(document).ready(function($) {
    // Subject line character counter
    $('#campaign_subject').on('input', function() {
        const length = $(this).val().length;
        $('#eem-subject-count').text(length);
        
        // Color coding for optimal length
        const counter = $('#eem-subject-count').parent();
        counter.removeClass('optimal warning danger');
        if (length <= 50) {
            counter.addClass('optimal');
        } else if (length <= 70) {
            counter.addClass('warning');
        } else {
            counter.addClass('danger');
        }
    });
    
    // Campaign type change handler
    $('input[name="campaign_type"]').on('change', function() {
        const type = $(this).val();
        $('.eem-ab-test-options').toggle(type === 'a_b_test');
    });
    
    // Tab switching
    $('.eem-tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.eem-tab-btn').removeClass('active');
        $('.eem-tab-content').removeClass('active');
        $(this).addClass('active');
        $('.eem-tab-' + tab).addClass('active');
        
        // Update recipient count
        updateRecipientCount();
    });
    
    // Update recipient count when selections change
    $('input[name="selected_lists[]"], input[name="selected_segments[]"]').on('change', updateRecipientCount);
    
    // Send option change
    $('input[name="send_option"]').on('change', function() {
        $('.eem-schedule-options').toggle($(this).val() === 'scheduled');
        $('#eem-send-now').toggle($(this).val() === 'now');
        $('.eem-schedule-campaign').toggle($(this).val() === 'scheduled');
    });
    
    function updateRecipientCount() {
        // This would normally make an AJAX call to get actual counts
        // For now, we'll simulate it
        let totalRecipients = 0;
        
        $('input[name="selected_lists[]"]:checked').each(function() {
            // Add list subscriber counts (would come from data attributes or AJAX)
            totalRecipients += parseInt($(this).closest('.eem-list-option').find('.eem-list-meta').text().match(/\d+/)[0] || 0);
        });
        
        $('#eem-total-recipients').text(totalRecipients.toLocaleString());
        $('#review-total-recipients').text(totalRecipients.toLocaleString());
        
        // Update estimates
        const estimatedOpens = Math.round(totalRecipients * 0.25);
        const estimatedClicks = Math.round(totalRecipients * 0.03);
        const environmentalActions = Math.round(totalRecipients * 0.05);
        
        $('#review-estimated-opens').text(estimatedOpens.toLocaleString());
        $('#review-estimated-clicks').text(estimatedClicks.toLocaleString());
        $('#review-environmental-impact').text(environmentalActions.toLocaleString());
        
        // Update environmental impact
        updateEnvironmentalImpact(totalRecipients);
    }
    
    function updateEnvironmentalImpact(recipients) {
        // Rough calculations for environmental impact
        const carbonSaved = (recipients * 0.02).toFixed(1); // 20g per email vs print
        const waterSaved = (recipients * 0.5).toFixed(0); // 0.5L per email vs print
        const energySaved = (recipients * 0.001).toFixed(2); // 1Wh per email vs print
        
        $('#review-carbon-saved').text(carbonSaved + 'kg');
        $('#review-water-saved').text(waterSaved + 'L');
        $('#review-energy-saved').text(energySaved + 'kWh');
    }
    
    // Initialize
    updateRecipientCount();
});
</script>
