<?php
/**
 * Petition Share Buttons Template
 * 
 * @package Environmental_Platform_Petitions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get petition data
$petition_id = isset($petition_id) ? $petition_id : get_the_ID();
$petition_title = get_the_title($petition_id);
$petition_url = get_permalink($petition_id);
$petition_excerpt = get_the_excerpt($petition_id);

// Get current signatures for social proof
$current_signatures = Environmental_Platform_Petitions_Database::get_signature_count($petition_id);

// Prepare share content
$share_title = html_entity_decode($petition_title, ENT_QUOTES, 'UTF-8');
$share_description = $petition_excerpt ? html_entity_decode($petition_excerpt, ENT_QUOTES, 'UTF-8') : 
                    'Join ' . number_format($current_signatures) . ' people who have signed this important environmental petition.';
$share_url = $petition_url;

// Get display style from shortcode attributes
$style = isset($style) ? $style : 'default'; // default, compact, minimal, floating
$show_counts = isset($show_counts) ? $show_counts : true;
$platforms = isset($platforms) ? explode(',', $platforms) : ['facebook', 'twitter', 'linkedin', 'whatsapp', 'telegram', 'email'];

// Encode URLs for sharing
$encoded_url = urlencode($share_url);
$encoded_title = urlencode($share_title);
$encoded_description = urlencode($share_description);

// Prepare platform-specific share URLs
$share_urls = [
    'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url,
    'twitter' => 'https://twitter.com/intent/tweet?url=' . $encoded_url . '&text=' . $encoded_title . '&hashtags=environment,petition,climate',
    'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encoded_url,
    'whatsapp' => 'https://wa.me/?text=' . $encoded_title . '%20' . $encoded_url,
    'telegram' => 'https://t.me/share/url?url=' . $encoded_url . '&text=' . $encoded_title,
    'email' => 'mailto:?subject=' . $encoded_title . '&body=' . $encoded_description . '%0A%0A' . $encoded_url,
    'reddit' => 'https://reddit.com/submit?url=' . $encoded_url . '&title=' . $encoded_title,
    'pinterest' => 'https://pinterest.com/pin/create/button/?url=' . $encoded_url . '&description=' . $encoded_title
];

// Platform configurations
$platform_config = [
    'facebook' => [
        'name' => 'Facebook',
        'icon' => '📘',
        'color' => '#3b5998',
        'class' => 'facebook'
    ],
    'twitter' => [
        'name' => 'Twitter',
        'icon' => '🐦',
        'color' => '#1da1f2',
        'class' => 'twitter'
    ],
    'linkedin' => [
        'name' => 'LinkedIn',
        'icon' => '💼',
        'color' => '#0077b5',
        'class' => 'linkedin'
    ],
    'whatsapp' => [
        'name' => 'WhatsApp',
        'icon' => '💬',
        'color' => '#25d366',
        'class' => 'whatsapp'
    ],
    'telegram' => [
        'name' => 'Telegram',
        'icon' => '✈️',
        'color' => '#0088cc',
        'class' => 'telegram'
    ],
    'email' => [
        'name' => 'Email',
        'icon' => '📧',
        'color' => '#666666',
        'class' => 'email'
    ],
    'reddit' => [
        'name' => 'Reddit',
        'icon' => '🤖',
        'color' => '#ff4500',
        'class' => 'reddit'
    ],
    'pinterest' => [
        'name' => 'Pinterest',
        'icon' => '📌',
        'color' => '#bd081c',
        'class' => 'pinterest'
    ]
];
?>

<div class="petition-share-container style-<?php echo esc_attr($style); ?>" data-petition-id="<?php echo esc_attr($petition_id); ?>">
    
    <?php if ($style !== 'minimal'): ?>
    <div class="share-header">
        <?php if ($style === 'default'): ?>
            <h4 class="share-title">
                <span class="share-icon">📤</span>
                Share This Petition
            </h4>
            <p class="share-description">
                Help us reach more people by sharing this petition with your network!
            </p>
        <?php elseif ($style === 'compact'): ?>
            <div class="share-title-compact">
                <span class="share-icon">📤</span>
                <span>Share</span>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="share-buttons-wrapper">
        <div class="share-buttons <?php echo $style === 'floating' ? 'floating-buttons' : ''; ?>">
            <?php foreach ($platforms as $platform): 
                $platform = trim($platform);
                if (!isset($share_urls[$platform]) || !isset($platform_config[$platform])) continue;
                
                $config = $platform_config[$platform];
                $share_count = 0; // You can implement share counting if needed
                ?>
                
                <div class="share-button-container">
                    <a href="<?php echo esc_url($share_urls[$platform]); ?>" 
                       class="share-button <?php echo esc_attr($config['class']); ?>"
                       data-platform="<?php echo esc_attr($platform); ?>"
                       data-petition-id="<?php echo esc_attr($petition_id); ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       title="Share on <?php echo esc_attr($config['name']); ?>"
                       style="--platform-color: <?php echo esc_attr($config['color']); ?>">
                        
                        <span class="button-icon">
                            <?php echo $config['icon']; ?>
                        </span>
                        
                        <?php if ($style !== 'minimal' && $style !== 'compact'): ?>
                        <span class="button-text">
                            <?php echo esc_html($config['name']); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($show_counts && $share_count > 0): ?>
                        <span class="share-count">
                            <?php echo number_format($share_count); ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    
                    <?php if ($style === 'default' && $show_counts): ?>
                    <div class="share-count-display">
                        <span class="count-number" id="share-count-<?php echo $platform; ?>">0</span>
                        <span class="count-label">shares</span>
                    </div>
                    <?php endif; ?>
                </div>
                
            <?php endforeach; ?>
            
            <!-- Copy Link Button -->
            <div class="share-button-container">
                <button class="share-button copy-link" 
                        data-url="<?php echo esc_attr($share_url); ?>"
                        data-petition-id="<?php echo esc_attr($petition_id); ?>"
                        title="Copy Link">
                    <span class="button-icon">🔗</span>
                    <?php if ($style !== 'minimal' && $style !== 'compact'): ?>
                    <span class="button-text">Copy Link</span>
                    <?php endif; ?>
                </button>
            </div>
        </div>
    </div>
    
    <?php if ($style === 'default'): ?>
    <div class="share-stats">
        <div class="total-shares">
            <span class="stats-icon">📊</span>
            <span class="stats-label">Total Shares:</span>
            <span class="stats-number" id="total-shares-count">
                <?php echo number_format(Environmental_Platform_Petitions_Database::get_total_shares($petition_id)); ?>
            </span>
        </div>
        
        <div class="share-impact">
            <small class="impact-text">
                🌟 Shared petitions get <strong>3x more signatures</strong> on average
            </small>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($style === 'default'): ?>
    <!-- Custom Share Message -->
    <div class="custom-share-section">
        <h5>Customize Your Message</h5>
        <div class="custom-message-container">
            <textarea id="custom-share-message" 
                      class="custom-message-input"
                      rows="3"
                      placeholder="Add your personal message to make sharing more impactful..."><?php echo esc_textarea($share_title . "\n\n" . $share_description); ?></textarea>
            
            <div class="message-actions">
                <button class="update-message-btn" id="update-share-message">
                    Update Message
                </button>
                <button class="reset-message-btn" id="reset-share-message">
                    Reset
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Share Success Toast -->
<div id="share-success-toast" class="share-toast" style="display: none;">
    <div class="toast-content">
        <span class="toast-icon">✅</span>
        <span class="toast-message">Link copied to clipboard!</span>
    </div>
</div>

<!-- Share Analytics (Hidden) -->
<div class="share-analytics" style="display: none;">
    <input type="hidden" id="petition-share-url" value="<?php echo esc_attr($share_url); ?>">
    <input type="hidden" id="petition-share-title" value="<?php echo esc_attr($share_title); ?>">
    <input type="hidden" id="petition-share-description" value="<?php echo esc_attr($share_description); ?>">
</div>

<?php if ($style === 'floating'): ?>
<!-- Floating Share Toggle -->
<button class="floating-share-toggle" id="floating-share-toggle">
    <span class="toggle-icon">📤</span>
    <span class="toggle-text">Share</span>
</button>
<?php endif; ?>

<script>
// Initialize share functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (typeof PetitionShare !== 'undefined') {
        PetitionShare.init({
            petitionId: <?php echo $petition_id; ?>,
            style: '<?php echo $style; ?>',
            showCounts: <?php echo $show_counts ? 'true' : 'false'; ?>
        });
    }
});
</script>

<style>
/* Additional CSS for specific share styles */
<?php if ($style === 'floating'): ?>
.petition-share-container.style-floating {
    position: fixed;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1000;
}

.floating-buttons {
    flex-direction: column;
    gap: 10px;
    opacity: 0;
    transform: translateX(100px);
    transition: all 0.3s ease;
}

.floating-buttons.active {
    opacity: 1;
    transform: translateX(0);
}

.floating-share-toggle {
    position: fixed;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1001;
    background: linear-gradient(135deg, #2E8B57, #228B22);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 12px 16px;
    font-size: 14px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}

.floating-share-toggle:hover {
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
    .petition-share-container.style-floating,
    .floating-share-toggle {
        right: 10px;
    }
}
<?php endif; ?>

<?php if ($style === 'compact'): ?>
.petition-share-container.style-compact .share-buttons {
    justify-content: center;
    gap: 8px;
}

.petition-share-container.style-compact .share-button {
    padding: 8px 12px;
    font-size: 14px;
    min-width: auto;
}
<?php endif; ?>

<?php if ($style === 'minimal'): ?>
.petition-share-container.style-minimal .share-buttons {
    justify-content: center;
    gap: 5px;
}

.petition-share-container.style-minimal .share-button {
    width: 40px;
    height: 40px;
    padding: 0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.petition-share-container.style-minimal .button-icon {
    font-size: 18px;
}
<?php endif; ?>
</style>
