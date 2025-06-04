<?php
/**
 * User Login Form Template
 * Template for environmental platform user login
 * 
 * @package Environmental_Platform_Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="ep-user-login-form-container">
    <div class="ep-form-header">
        <h2><?php _e('Welcome Back!', 'environmental-platform-core'); ?></h2>
        <p><?php _e('Sign in to continue your environmental journey', 'environmental-platform-core'); ?></p>
    </div>

    <!-- Social Login Options -->
    <div class="ep-social-login-section">
        <h3><?php _e('Quick Sign In', 'environmental-platform-core'); ?></h3>
        <div class="ep-social-buttons">
            <?php echo do_shortcode('[ep_social_login_buttons action="login"]'); ?>
        </div>
        <div class="ep-divider">
            <span><?php _e('or sign in with your account', 'environmental-platform-core'); ?></span>
        </div>
    </div>

    <!-- Login Form -->
    <form id="ep-login-form" class="ep-user-form" method="post">
        <?php wp_nonce_field('ep_user_login', 'ep_login_nonce'); ?>
        
        <div class="ep-form-group">
            <label for="ep_login_email"><?php _e('Email or Username', 'environmental-platform-core'); ?></label>
            <input type="text" id="ep_login_email" name="login" required autocomplete="username">
        </div>

        <div class="ep-form-group">
            <label for="ep_login_password"><?php _e('Password', 'environmental-platform-core'); ?></label>
            <input type="password" id="ep_login_password" name="password" required autocomplete="current-password">
        </div>

        <div class="ep-form-row ep-login-options">
            <div class="ep-form-group ep-remember-me">
                <label class="ep-checkbox-label">
                    <input type="checkbox" id="ep_remember_me" name="remember_me" value="1">
                    <?php _e('Remember me', 'environmental-platform-core'); ?>
                </label>
            </div>
            <div class="ep-form-group ep-forgot-password">
                <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="ep-link">
                    <?php _e('Forgot password?', 'environmental-platform-core'); ?>
                </a>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="ep-form-group ep-submit-section">
            <button type="submit" id="ep-login-btn" class="ep-btn ep-btn-primary">
                <span class="ep-btn-text"><?php _e('Sign In', 'environmental-platform-core'); ?></span>
                <span class="ep-btn-loading" style="display: none;">
                    <i class="ep-icon-spinner"></i> <?php _e('Signing in...', 'environmental-platform-core'); ?>
                </span>
            </button>
        </div>

        <!-- Registration Link -->
        <div class="ep-form-footer">
            <p><?php printf(
                __('Don\'t have an account? <a href="%s">Create one here</a>', 'environmental-platform-core'),
                esc_url(wp_registration_url())
            ); ?></p>
        </div>
    </form>

    <!-- Success/Error Messages -->
    <div id="ep-login-messages" class="ep-messages" style="display: none;">
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
    // Initialize login form
    if (typeof EP_UserManagement !== 'undefined') {
        EP_UserManagement.initLoginForm();
    }
});
</script>
