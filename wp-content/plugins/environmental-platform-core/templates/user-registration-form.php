<?php
/**
 * User Registration Form Template
 * Template for environmental platform user registration
 * 
 * @package Environmental_Platform_Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="ep-user-registration-form-container">
    <div class="ep-form-header">
        <h2><?php _e('Join the Environmental Platform', 'environmental-platform-core'); ?></h2>
        <p><?php _e('Create your account and start making a positive environmental impact today!', 'environmental-platform-core'); ?></p>
    </div>

    <!-- Social Login Options -->
    <div class="ep-social-login-section">
        <h3><?php _e('Quick Registration with Social Media', 'environmental-platform-core'); ?></h3>
        <div class="ep-social-buttons">
            <?php echo do_shortcode('[ep_social_login_buttons action="register"]'); ?>
        </div>
        <div class="ep-divider">
            <span><?php _e('or register with email', 'environmental-platform-core'); ?></span>
        </div>
    </div>

    <!-- Registration Form -->
    <form id="ep-registration-form" class="ep-user-form" method="post">
        <?php wp_nonce_field('ep_user_registration', 'ep_registration_nonce'); ?>
        
        <div class="ep-form-row">
            <div class="ep-form-group ep-half-width">
                <label for="ep_first_name"><?php _e('First Name', 'environmental-platform-core'); ?> <span class="required">*</span></label>
                <input type="text" id="ep_first_name" name="first_name" required>
            </div>
            <div class="ep-form-group ep-half-width">
                <label for="ep_last_name"><?php _e('Last Name', 'environmental-platform-core'); ?> <span class="required">*</span></label>
                <input type="text" id="ep_last_name" name="last_name" required>
            </div>
        </div>

        <div class="ep-form-group">
            <label for="ep_username"><?php _e('Username', 'environmental-platform-core'); ?> <span class="required">*</span></label>
            <input type="text" id="ep_username" name="username" required>
            <small class="ep-help-text"><?php _e('Choose a unique username for your profile', 'environmental-platform-core'); ?></small>
        </div>

        <div class="ep-form-group">
            <label for="ep_email"><?php _e('Email Address', 'environmental-platform-core'); ?> <span class="required">*</span></label>
            <input type="email" id="ep_email" name="email" required>
        </div>

        <div class="ep-form-row">
            <div class="ep-form-group ep-half-width">
                <label for="ep_password"><?php _e('Password', 'environmental-platform-core'); ?> <span class="required">*</span></label>
                <input type="password" id="ep_password" name="password" required minlength="8">
                <small class="ep-help-text"><?php _e('Minimum 8 characters', 'environmental-platform-core'); ?></small>
            </div>
            <div class="ep-form-group ep-half-width">
                <label for="ep_confirm_password"><?php _e('Confirm Password', 'environmental-platform-core'); ?> <span class="required">*</span></label>
                <input type="password" id="ep_confirm_password" name="confirm_password" required>
            </div>
        </div>

        <!-- Environmental Platform Specific Fields -->
        <div class="ep-environmental-fields">
            <h3><?php _e('Environmental Preferences', 'environmental-platform-core'); ?></h3>
            
            <div class="ep-form-group">
                <label for="ep_user_type"><?php _e('I am a', 'environmental-platform-core'); ?></label>
                <select id="ep_user_type" name="user_type">
                    <option value="eco_user"><?php _e('Individual Environmental Enthusiast', 'environmental-platform-core'); ?></option>
                    <option value="organization_member"><?php _e('Organization Member', 'environmental-platform-core'); ?></option>
                    <option value="business_partner"><?php _e('Business Partner', 'environmental-platform-core'); ?></option>
                    <option value="content_creator"><?php _e('Content Creator/Educator', 'environmental-platform-core'); ?></option>
                </select>
            </div>

            <div class="ep-form-group">
                <label for="ep_location"><?php _e('Location (City, Country)', 'environmental-platform-core'); ?></label>
                <input type="text" id="ep_location" name="location" placeholder="<?php _e('e.g., Ho Chi Minh City, Vietnam', 'environmental-platform-core'); ?>">
            </div>

            <div class="ep-form-group">
                <label><?php _e('Environmental Interests', 'environmental-platform-core'); ?></label>
                <div class="ep-checkbox-group">
                    <label class="ep-checkbox-label">
                        <input type="checkbox" name="interests[]" value="waste_reduction">
                        <?php _e('Waste Reduction & Recycling', 'environmental-platform-core'); ?>
                    </label>
                    <label class="ep-checkbox-label">
                        <input type="checkbox" name="interests[]" value="renewable_energy">
                        <?php _e('Renewable Energy', 'environmental-platform-core'); ?>
                    </label>
                    <label class="ep-checkbox-label">
                        <input type="checkbox" name="interests[]" value="sustainable_living">
                        <?php _e('Sustainable Living', 'environmental-platform-core'); ?>
                    </label>
                    <label class="ep-checkbox-label">
                        <input type="checkbox" name="interests[]" value="conservation">
                        <?php _e('Wildlife & Nature Conservation', 'environmental-platform-core'); ?>
                    </label>
                    <label class="ep-checkbox-label">
                        <input type="checkbox" name="interests[]" value="climate_action">
                        <?php _e('Climate Action', 'environmental-platform-core'); ?>
                    </label>
                    <label class="ep-checkbox-label">
                        <input type="checkbox" name="interests[]" value="green_technology">
                        <?php _e('Green Technology', 'environmental-platform-core'); ?>
                    </label>
                </div>
            </div>
        </div>

        <!-- Terms and Privacy -->
        <div class="ep-form-group ep-terms-section">
            <label class="ep-checkbox-label">
                <input type="checkbox" id="ep_terms" name="accept_terms" required>
                <?php printf(
                    __('I agree to the <a href="%s" target="_blank">Terms of Service</a> and <a href="%s" target="_blank">Privacy Policy</a>', 'environmental-platform-core'),
                    esc_url(get_privacy_policy_url()),
                    esc_url(get_privacy_policy_url())
                ); ?>
            </label>
        </div>

        <div class="ep-form-group ep-newsletter-section">
            <label class="ep-checkbox-label">
                <input type="checkbox" id="ep_newsletter" name="subscribe_newsletter" checked>
                <?php _e('Subscribe to our environmental newsletter for tips, updates, and impact stories', 'environmental-platform-core'); ?>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="ep-form-group ep-submit-section">
            <button type="submit" id="ep-register-btn" class="ep-btn ep-btn-primary">
                <span class="ep-btn-text"><?php _e('Create Account', 'environmental-platform-core'); ?></span>
                <span class="ep-btn-loading" style="display: none;">
                    <i class="ep-icon-spinner"></i> <?php _e('Creating...', 'environmental-platform-core'); ?>
                </span>
            </button>
        </div>

        <!-- Login Link -->
        <div class="ep-form-footer">
            <p><?php printf(
                __('Already have an account? <a href="%s">Sign in here</a>', 'environmental-platform-core'),
                esc_url(wp_login_url())
            ); ?></p>
        </div>
    </form>

    <!-- Success/Error Messages -->
    <div id="ep-registration-messages" class="ep-messages" style="display: none;">
        <div class="ep-message ep-success" style="display: none;">
            <i class="ep-icon-check"></i>
            <span class="ep-message-text"></span>
        </div>
        <div class="ep-message ep-error" style="display: none;">
            <i class="ep-icon-warning"></i>
            <span class="ep-message-text"></span>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize registration form
    if (typeof EP_UserManagement !== 'undefined') {
        EP_UserManagement.initRegistrationForm();
    }
});
</script>
