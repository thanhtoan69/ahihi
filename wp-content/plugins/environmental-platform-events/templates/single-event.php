<?php
/**
 * Single Event Template
 *
 * @package Environmental_Platform_Events
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get the current event
global $post, $wpdb;
$event_id = get_the_ID();

// Get event meta data
$event_date = get_post_meta($event_id, '_ep_event_date', true);
$event_time = get_post_meta($event_id, '_ep_event_time', true);
$event_end_time = get_post_meta($event_id, '_ep_event_end_time', true);
$event_location = get_post_meta($event_id, '_ep_event_location', true);
$event_address = get_post_meta($event_id, '_ep_event_address', true);
$event_capacity = get_post_meta($event_id, '_ep_event_capacity', true);
$registration_deadline = get_post_meta($event_id, '_ep_registration_deadline', true);
$registration_fee = get_post_meta($event_id, '_ep_registration_fee', true);

// Environmental impact data
$carbon_footprint = get_post_meta($event_id, '_ep_carbon_footprint', true);
$sustainability_goals = get_post_meta($event_id, '_ep_sustainability_goals', true);
$eco_points = get_post_meta($event_id, '_ep_eco_points', true);

// Google Maps data
$map_latitude = get_post_meta($event_id, '_ep_map_latitude', true);
$map_longitude = get_post_meta($event_id, '_ep_map_longitude', true);

// Get registration count
$registration_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}ep_event_registrations 
     WHERE event_id = %d AND status = 'confirmed'",
    $event_id
));

// Check registration status
$is_registration_open = true;
$registration_message = '';

if ($registration_deadline && strtotime($registration_deadline) < time()) {
    $is_registration_open = false;
    $registration_message = 'Registration deadline has passed';
} elseif ($event_capacity && $registration_count >= $event_capacity) {
    $is_registration_open = false;
    $registration_message = 'Event is full';
} elseif ($event_date && strtotime($event_date) < time()) {
    $is_registration_open = false;
    $registration_message = 'Event has already occurred';
}

// Check if user is already registered
$user_registered = false;
$user_registration_id = null;
if (is_user_logged_in()) {
    $user_id = get_current_user_id();
    $user_registration = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ep_event_registrations 
         WHERE event_id = %d AND user_id = %d AND status = 'confirmed'",
        $event_id, $user_id
    ));
    if ($user_registration) {
        $user_registered = true;
        $user_registration_id = $user_registration->id;
    }
}

// Format dates and times
$formatted_date = $event_date ? date('F j, Y', strtotime($event_date)) : '';
$formatted_time = $event_time ? date('g:i A', strtotime($event_time)) : '';
$formatted_end_time = $event_end_time ? date('g:i A', strtotime($event_end_time)) : '';
$time_display = $formatted_time . ($formatted_end_time ? ' - ' . $formatted_end_time : '');

// Get event categories and tags
$categories = get_the_terms($event_id, 'event_category');
$tags = get_the_terms($event_id, 'event_tag');
$event_type = get_the_terms($event_id, 'event_type');
?>

<div id="ep-messages" class="ep-messages"></div>

<article class="ep-single-event">
    <!-- Event Hero Section -->
    <header class="ep-event-hero" style="<?php if (has_post_thumbnail()): ?>background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('<?php echo esc_url(get_the_post_thumbnail_url($event_id, 'large')); ?>'); background-size: cover; background-position: center;<?php endif; ?>">
        <div class="ep-event-hero-content">
            <h1 class="ep-event-title"><?php the_title(); ?></h1>
            <div class="ep-event-subtitle">
                <?php if ($formatted_date): ?>
                    <span class="ep-event-date-hero"><?php echo esc_html($formatted_date); ?></span>
                <?php endif; ?>
                <?php if ($time_display): ?>
                    <span class="ep-event-time-hero"><?php echo esc_html($time_display); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Event Content -->
    <div class="ep-event-content">
        <div class="ep-event-details">
            <!-- Main Content -->
            <div class="ep-event-info">
                <h3>About This Event</h3>
                <div class="ep-event-description">
                    <?php the_content(); ?>
                </div>

                <?php if ($sustainability_goals): ?>
                <div class="ep-environmental-impact">
                    <h3 class="ep-impact-title">Environmental Impact</h3>
                    <div class="ep-impact-content">
                        <p><?php echo wp_kses_post($sustainability_goals); ?></p>
                        
                        <div class="ep-impact-grid">
                            <?php if ($carbon_footprint): ?>
                            <div class="ep-impact-item">
                                <div class="ep-impact-value"><?php echo esc_html($carbon_footprint); ?></div>
                                <div class="ep-impact-label">CO₂ Reduction (kg)</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($eco_points): ?>
                            <div class="ep-impact-item">
                                <div class="ep-impact-value"><?php echo esc_html($eco_points); ?></div>
                                <div class="ep-impact-label">Eco Points Earned</div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="ep-impact-item">
                                <div class="ep-impact-value"><?php echo esc_html($registration_count); ?></div>
                                <div class="ep-impact-label">Participants Joined</div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Google Map -->
                <?php if ($map_latitude && $map_longitude): ?>
                <div class="ep-event-map-section">
                    <h3>Event Location</h3>
                    <div class="ep-event-map" 
                         data-lat="<?php echo esc_attr($map_latitude); ?>" 
                         data-lng="<?php echo esc_attr($map_longitude); ?>"
                         data-title="<?php echo esc_attr(get_the_title()); ?>"
                         style="height: 300px; border-radius: 8px; overflow: hidden;">
                        <p>Loading map...</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Event Meta Sidebar -->
            <aside class="ep-event-meta">
                <div class="ep-meta-card">
                    <h3>Event Details</h3>
                    
                    <?php if ($formatted_date): ?>
                    <div class="ep-meta-item">
                        <span class="ep-meta-icon">📅</span>
                        <span class="ep-meta-label">Date:</span>
                        <span class="ep-meta-value"><?php echo esc_html($formatted_date); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($time_display): ?>
                    <div class="ep-meta-item">
                        <span class="ep-meta-icon">🕐</span>
                        <span class="ep-meta-label">Time:</span>
                        <span class="ep-meta-value"><?php echo esc_html($time_display); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($event_location): ?>
                    <div class="ep-meta-item">
                        <span class="ep-meta-icon">📍</span>
                        <span class="ep-meta-label">Location:</span>
                        <span class="ep-meta-value"><?php echo esc_html($event_location); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($event_address): ?>
                    <div class="ep-meta-item">
                        <span class="ep-meta-icon">🏠</span>
                        <span class="ep-meta-label">Address:</span>
                        <span class="ep-meta-value"><?php echo esc_html($event_address); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($event_capacity): ?>
                    <div class="ep-meta-item">
                        <span class="ep-meta-icon">👥</span>
                        <span class="ep-meta-label">Capacity:</span>
                        <span class="ep-meta-value"><?php echo esc_html($registration_count . ' / ' . $event_capacity); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($registration_fee): ?>
                    <div class="ep-meta-item">
                        <span class="ep-meta-icon">💰</span>
                        <span class="ep-meta-label">Fee:</span>
                        <span class="ep-meta-value"><?php echo esc_html($registration_fee == '0' ? 'Free' : '$' . $registration_fee); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($registration_deadline): ?>
                    <div class="ep-meta-item">
                        <span class="ep-meta-icon">⏰</span>
                        <span class="ep-meta-label">Registration Deadline:</span>
                        <span class="ep-meta-value"><?php echo esc_html(date('F j, Y', strtotime($registration_deadline))); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Event Categories/Tags -->
                    <?php if ($categories): ?>
                    <div class="ep-meta-item">
                        <span class="ep-meta-icon">🏷️</span>
                        <span class="ep-meta-label">Category:</span>
                        <span class="ep-meta-value">
                            <?php foreach ($categories as $category): ?>
                                <span class="ep-category-tag"><?php echo esc_html($category->name); ?></span>
                            <?php endforeach; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- QR Code Section -->
                <?php if ($user_registered): ?>
                <div class="ep-qr-section">
                    <h4 class="ep-qr-title">Your Event QR Code</h4>
                    <div class="ep-qr-container">
                        <div id="qr-code-<?php echo esc_attr($event_id); ?>" class="ep-qr-code"></div>
                        <p class="ep-qr-instructions">
                            Show this QR code at the event for quick check-in
                        </p>
                    </div>
                    <button type="button" class="ep-generate-qr ep-button-secondary" 
                            data-event-id="<?php echo esc_attr($event_id); ?>"
                            data-registration-id="<?php echo esc_attr($user_registration_id); ?>">
                        Generate QR Code
                    </button>
                </div>
                <?php endif; ?>
            </aside>
        </div>

        <!-- Registration Section -->
        <section class="ep-registration-section">
            <?php if ($user_registered): ?>
                <!-- User is already registered -->
                <div class="ep-registration-confirmed">
                    <h3 class="ep-registration-title">✅ You're Registered!</h3>
                    <p>You have successfully registered for this event. You'll receive a confirmation email with all the details.</p>
                    
                    <div class="ep-registration-actions">
                        <button type="button" class="ep-cancel-registration ep-button-danger" 
                                data-event-id="<?php echo esc_attr($event_id); ?>"
                                data-registration-id="<?php echo esc_attr($user_registration_id); ?>">
                            Cancel Registration
                        </button>
                        <a href="<?php echo esc_url(home_url('/events/my-registrations')); ?>" class="ep-button-secondary">
                            View My Registrations
                        </a>
                    </div>
                </div>
                
            <?php elseif (!$is_registration_open): ?>
                <!-- Registration is closed -->
                <div class="ep-registration-closed">
                    <h3 class="ep-registration-title">Registration Closed</h3>
                    <p class="ep-registration-message"><?php echo esc_html($registration_message); ?></p>
                </div>
                
            <?php else: ?>
                <!-- Registration form -->
                <h3 class="ep-registration-title">Register for This Event</h3>
                
                <?php if (!is_user_logged_in()): ?>
                    <div class="ep-login-notice">
                        <p>Please <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">log in</a> or <a href="<?php echo esc_url(wp_registration_url()); ?>">register</a> to sign up for this event.</p>
                    </div>
                <?php else: ?>
                    <form class="ep-registration-form" data-event-id="<?php echo esc_attr($event_id); ?>">
                        <?php wp_nonce_field('ep_register_event', 'ep_registration_nonce'); ?>
                        
                        <div class="ep-form-row">
                            <div class="ep-form-group">
                                <label for="participant_name" class="ep-form-label">Full Name *</label>
                                <input type="text" 
                                       id="participant_name" 
                                       name="participant_name" 
                                       class="ep-form-input" 
                                       value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="ep-form-row">
                            <div class="ep-form-group">
                                <label for="participant_email" class="ep-form-label">Email Address *</label>
                                <input type="email" 
                                       id="participant_email" 
                                       name="participant_email" 
                                       class="ep-form-input" 
                                       value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" 
                                       required>
                            </div>
                        </div>
                        
                        <div class="ep-form-row">
                            <div class="ep-form-group">
                                <label for="participant_phone" class="ep-form-label">Phone Number</label>
                                <input type="tel" 
                                       id="participant_phone" 
                                       name="participant_phone" 
                                       class="ep-form-input">
                            </div>
                        </div>
                        
                        <div class="ep-form-row">
                            <div class="ep-form-group">
                                <label for="dietary_requirements" class="ep-form-label">Dietary Requirements</label>
                                <textarea id="dietary_requirements" 
                                          name="dietary_requirements" 
                                          class="ep-form-input ep-form-textarea" 
                                          rows="3" 
                                          placeholder="Please specify any dietary restrictions or allergies..."></textarea>
                            </div>
                        </div>
                        
                        <div class="ep-form-row">
                            <div class="ep-form-group">
                                <label for="special_needs" class="ep-form-label">Special Accessibility Needs</label>
                                <textarea id="special_needs" 
                                          name="special_needs" 
                                          class="ep-form-input ep-form-textarea" 
                                          rows="3" 
                                          placeholder="Please describe any accessibility requirements..."></textarea>
                            </div>
                        </div>
                        
                        <div class="ep-form-row">
                            <div class="ep-form-group">
                                <label class="ep-checkbox-label">
                                    <input type="checkbox" name="terms_accepted" required>
                                    I agree to the <a href="#" target="_blank">Terms and Conditions</a> and <a href="#" target="_blank">Privacy Policy</a>
                                </label>
                            </div>
                        </div>
                        
                        <div class="ep-form-actions">
                            <button type="submit" class="ep-register-btn">
                                Register for Event
                                <span class="ep-loading" style="display: none;"></span>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</article>

<!-- Include QR Code library -->
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>

<script>
jQuery(document).ready(function($) {
    // Initialize Google Maps if available
    if (typeof google !== 'undefined' && $('.ep-event-map').length > 0) {
        initEventMap();
    }
    
    // Handle QR code generation
    $('.ep-generate-qr').on('click', function() {
        const button = $(this);
        const eventId = button.data('event-id');
        const registrationId = button.data('registration-id');
        const container = $('#qr-code-' + eventId);
        
        if (container.children().length > 0) {
            container.toggle();
            return;
        }
        
        // Generate QR code data
        const qrData = window.location.origin + '/events/checkin/' + eventId + '/' + registrationId;
        
        if (typeof QRCode !== 'undefined') {
            QRCode.toCanvas(container[0], qrData, {
                width: 200,
                height: 200,
                colorDark: '#000000',
                colorLight: '#ffffff'
            }, function(error) {
                if (error) {
                    console.error('QR Code generation failed:', error);
                    container.html('<p>QR Code generation failed</p>');
                } else {
                    container.show();
                    button.text('Hide QR Code');
                }
            });
        } else {
            container.html('<p>QR Code library not available</p>');
        }
    });
    
    function initEventMap() {
        $('.ep-event-map').each(function() {
            const mapEl = $(this)[0];
            const lat = parseFloat($(this).data('lat'));
            const lng = parseFloat($(this).data('lng'));
            const title = $(this).data('title');
            
            if (lat && lng) {
                const map = new google.maps.Map(mapEl, {
                    zoom: 15,
                    center: { lat: lat, lng: lng },
                    styles: [
                        {
                            featureType: 'poi',
                            elementType: 'labels',
                            stylers: [{ visibility: 'off' }]
                        }
                    ]
                });
                
                new google.maps.Marker({
                    position: { lat: lat, lng: lng },
                    map: map,
                    title: title,
                    icon: {
                        url: 'data:image/svg+xml;base64,' + btoa(`
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="#28a745">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                        `),
                        scaledSize: new google.maps.Size(32, 32)
                    }
                });
            }
        });
    }
});
</script>

<style>
/* Additional single event styles */
.ep-meta-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
}

.ep-category-tag {
    background: #e9ecef;
    color: #495057;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    margin-right: 5px;
}

.ep-registration-confirmed {
    text-align: center;
    padding: 30px;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 8px;
}

.ep-registration-closed {
    text-align: center;
    padding: 30px;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 8px;
}

.ep-login-notice {
    text-align: center;
    padding: 20px;
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    border-radius: 8px;
    margin-bottom: 20px;
}

.ep-form-row {
    margin-bottom: 20px;
}

.ep-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 14px;
    line-height: 1.5;
}

.ep-checkbox-label input[type="checkbox"] {
    margin-top: 2px;
}

.ep-form-actions {
    text-align: center;
    margin-top: 30px;
}

.ep-registration-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 20px;
}

.ep-button-secondary {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    display: inline-block;
    transition: all 0.3s ease;
}

.ep-button-secondary:hover {
    background: #545b62;
    color: white;
    text-decoration: none;
}

.ep-button-danger {
    background: #dc3545;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.ep-button-danger:hover {
    background: #c82333;
}

.ep-event-map-section {
    margin: 30px 0;
}

.ep-event-map-section h3 {
    margin-bottom: 15px;
    color: #2e8b57;
}

.ep-qr-container {
    text-align: center;
    margin: 15px 0;
}

.ep-qr-container canvas {
    border: 1px solid #dee2e6;
    border-radius: 8px;
}
</style>
