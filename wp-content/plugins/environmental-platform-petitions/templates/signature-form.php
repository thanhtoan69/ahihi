<?php
/**
 * Petition Signature Form Template
 * 
 * @package Environmental_Platform_Petitions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get petition data
$petition_id = get_the_ID();
$goal = get_post_meta($petition_id, 'petition_goal', true) ?: 1000;
$current_signatures = Environmental_Platform_Petitions_Database::get_signature_count($petition_id);
$progress_percentage = min(100, ($current_signatures / $goal) * 100);

// Get petition settings
$verification_required = get_post_meta($petition_id, 'petition_verification_required', true);
$deadline = get_post_meta($petition_id, 'petition_deadline', true);
$allow_anonymous = get_post_meta($petition_id, 'petition_allow_anonymous', true);
$collect_phone = get_post_meta($petition_id, 'petition_collect_phone', true);
$collect_address = get_post_meta($petition_id, 'petition_collect_address', true);

// Check if user already signed
$user_signed = false;
if (is_user_logged_in()) {
    $user_signed = Environmental_Platform_Petitions_Database::has_user_signed($petition_id, get_current_user_id());
}

// Check deadline
$deadline_passed = false;
if ($deadline && strtotime($deadline) < time()) {
    $deadline_passed = true;
}
?>

<div class="petition-signature-form-container" data-petition-id="<?php echo esc_attr($petition_id); ?>">
    <!-- Progress Section -->
    <div class="petition-progress-section">
        <div class="progress-stats">
            <div class="signature-count">
                <span class="count-number"><?php echo number_format($current_signatures); ?></span>
                <span class="count-label">signatures</span>
            </div>
            <div class="goal-info">
                <span class="goal-text">of <?php echo number_format($goal); ?> goal</span>
                <?php if ($deadline && !$deadline_passed): ?>
                    <span class="deadline-info">by <?php echo date('M d, Y', strtotime($deadline)); ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="petition-progress-bar">
            <div class="progress-fill" style="width: <?php echo $progress_percentage; ?>%">
                <span class="progress-text"><?php echo round($progress_percentage, 1); ?>%</span>
            </div>
        </div>
        
        <div class="progress-milestones">
            <?php
            $milestones = [25, 50, 75, 100];
            foreach ($milestones as $milestone):
                $milestone_count = ($goal * $milestone) / 100;
                $achieved = $current_signatures >= $milestone_count;
            ?>
                <div class="milestone <?php echo $achieved ? 'achieved' : ''; ?>">
                    <div class="milestone-marker"></div>
                    <div class="milestone-label"><?php echo number_format($milestone_count); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($deadline_passed): ?>
        <!-- Deadline Passed Message -->
        <div class="petition-status-message deadline-passed">
            <div class="status-icon">⏰</div>
            <div class="status-content">
                <h3>Signature Period Ended</h3>
                <p>The signature collection period for this petition ended on <?php echo date('F d, Y', strtotime($deadline)); ?>.</p>
                <p>Thank you to all <?php echo number_format($current_signatures); ?> supporters who signed this petition!</p>
            </div>
        </div>
        
    <?php elseif ($user_signed): ?>
        <!-- Already Signed Message -->
        <div class="petition-status-message already-signed">
            <div class="status-icon">✅</div>
            <div class="status-content">
                <h3>Thank You for Your Support!</h3>
                <p>You've already signed this petition. Your voice has been heard!</p>
                <div class="share-encouragement">
                    <p><strong>Help us reach our goal by sharing:</strong></p>
                    <div class="quick-share-buttons">
                        <?php echo do_shortcode('[petition_share petition_id="' . $petition_id . '" style="compact"]'); ?>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Signature Form -->
        <div class="petition-signature-form">
            <div class="form-header">
                <h3>
                    <span class="form-icon">✍️</span>
                    Sign This Petition
                </h3>
                <p class="form-description">Add your voice to this important environmental cause</p>
            </div>
            
            <form id="petition-signature-form" class="signature-form" method="post">
                <?php wp_nonce_field('petition_signature', 'petition_signature_nonce'); ?>
                <input type="hidden" name="petition_id" value="<?php echo esc_attr($petition_id); ?>">
                
                <div class="form-row">
                    <div class="form-group half-width">
                        <label for="signer_first_name">
                            First Name <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="signer_first_name" 
                               name="signer_first_name" 
                               required 
                               autocomplete="given-name"
                               class="form-control">
                    </div>
                    
                    <div class="form-group half-width">
                        <label for="signer_last_name">
                            Last Name <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="signer_last_name" 
                               name="signer_last_name" 
                               required 
                               autocomplete="family-name"
                               class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="signer_email">
                        Email Address <span class="required">*</span>
                    </label>
                    <input type="email" 
                           id="signer_email" 
                           name="signer_email" 
                           required 
                           autocomplete="email"
                           class="form-control">
                    <?php if ($verification_required): ?>
                        <small class="field-note">We'll send you a verification email</small>
                    <?php endif; ?>
                </div>
                
                <?php if ($collect_phone): ?>
                <div class="form-group">
                    <label for="signer_phone">
                        Phone Number
                        <?php if (get_post_meta($petition_id, 'petition_phone_required', true)): ?>
                            <span class="required">*</span>
                        <?php endif; ?>
                    </label>
                    <input type="tel" 
                           id="signer_phone" 
                           name="signer_phone" 
                           autocomplete="tel"
                           class="form-control"
                           <?php echo get_post_meta($petition_id, 'petition_phone_required', true) ? 'required' : ''; ?>>
                </div>
                <?php endif; ?>
                
                <?php if ($collect_address): ?>
                <div class="address-section">
                    <h4>Address Information</h4>
                    
                    <div class="form-group">
                        <label for="signer_city">City</label>
                        <input type="text" 
                               id="signer_city" 
                               name="signer_city" 
                               autocomplete="address-level2"
                               class="form-control">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group half-width">
                            <label for="signer_state">State/Province</label>
                            <input type="text" 
                                   id="signer_state" 
                                   name="signer_state" 
                                   autocomplete="address-level1"
                                   class="form-control">
                        </div>
                        
                        <div class="form-group half-width">
                            <label for="signer_country">Country</label>
                            <select id="signer_country" name="signer_country" autocomplete="country" class="form-control">
                                <option value="">Select Country</option>
                                <option value="US">United States</option>
                                <option value="CA">Canada</option>
                                <option value="GB">United Kingdom</option>
                                <option value="AU">Australia</option>
                                <option value="DE">Germany</option>
                                <option value="FR">France</option>
                                <option value="JP">Japan</option>
                                <option value="VN">Vietnam</option>
                                <!-- Add more countries as needed -->
                            </select>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="signer_comment">
                        Your Comment (Optional)
                    </label>
                    <textarea id="signer_comment" 
                              name="signer_comment" 
                              rows="3" 
                              class="form-control"
                              placeholder="Why is this petition important to you?"></textarea>
                    <small class="field-note">Share why you support this cause (visible to other supporters)</small>
                </div>
                
                <div class="form-options">
                    <?php if (!$allow_anonymous): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="display_name" value="1" checked>
                        <span class="checkmark"></span>
                        Display my name publicly with other supporters
                    </label>
                    <?php else: ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="sign_anonymously" value="1">
                        <span class="checkmark"></span>
                        Sign anonymously (your name won't be displayed)
                    </label>
                    <?php endif; ?>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="receive_updates" value="1" checked>
                        <span class="checkmark"></span>
                        Receive email updates about this petition's progress
                    </label>
                    
                    <label class="checkbox-label required-checkbox">
                        <input type="checkbox" name="agree_terms" value="1" required>
                        <span class="checkmark"></span>
                        I agree to the <a href="<?php echo get_privacy_policy_url(); ?>" target="_blank">Privacy Policy</a> 
                        and <a href="#" target="_blank">Terms of Service</a> <span class="required">*</span>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="petition-sign-button" id="petition-sign-btn">
                        <span class="button-icon">✍️</span>
                        <span class="button-text">Sign This Petition</span>
                        <span class="button-loading" style="display: none;">
                            <span class="spinner"></span>
                            Signing...
                        </span>
                    </button>
                </div>
                
                <div class="form-footer">
                    <div class="signatures-today">
                        <span class="today-count"><?php 
                            echo Environmental_Platform_Petitions_Database::get_signatures_count_by_period($petition_id, 'today'); 
                        ?></span> people signed today
                    </div>
                    <?php if ($verification_required): ?>
                        <div class="verification-note">
                            <small>📧 You'll receive an email to verify your signature</small>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    <?php endif; ?>
    
    <!-- Recent Signatures -->
    <div class="recent-signatures-section">
        <h4>Recent Supporters</h4>
        <div class="recent-signatures-list" id="recent-signatures">
            <!-- Will be populated by JavaScript -->
        </div>
        <button class="load-more-signatures" id="load-more-signatures" style="display: none;">
            Load More Signatures
        </button>
    </div>
</div>

<!-- Success Modal -->
<div id="signature-success-modal" class="petition-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <span class="modal-close">&times;</span>
            <h3>🎉 Thank You!</h3>
        </div>
        <div class="modal-body">
            <div class="success-message">
                <div class="success-icon">✅</div>
                <p>Your signature has been recorded!</p>
                <?php if ($verification_required): ?>
                    <div class="verification-info">
                        <p><strong>📧 Please check your email</strong></p>
                        <p>We've sent you a verification link to complete your signature.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="share-section">
                <h4>Help us reach our goal!</h4>
                <p>Share this petition with your friends and family:</p>
                <div class="modal-share-buttons">
                    <!-- Share buttons will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize the signature form when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (typeof PetitionForm !== 'undefined') {
        PetitionForm.init();
    }
});
</script>
