<?php
/**
 * Hero Section Template Part
 * Displays the main hero section on the front page
 *
 * @package Environmental_Platform
 * @since 1.0.0
 */

// Get hero content from customizer
$hero_title = get_theme_mod('hero_title', __('Build a Sustainable Future Together', 'environmental-platform'));
$hero_subtitle = get_theme_mod('hero_subtitle', __('Join our environmental platform to track your impact, learn sustainable practices, and connect with like-minded individuals working towards a greener planet.', 'environmental-platform'));
$hero_background = get_theme_mod('hero_background');
$hero_cta_text = get_theme_mod('hero_cta_text', __('Get Started Today', 'environmental-platform'));
$hero_cta_url = get_theme_mod('hero_cta_url', home_url('/register'));
?>

<section class="env-hero" role="banner" 
         style="<?php echo $hero_background ? 'background-image: url(' . esc_url($hero_background) . ');' : ''; ?>">
    <div class="container">
        <div class="env-hero-content">
            <h1 class="env-hero-title">
                <?php echo esc_html($hero_title); ?>
            </h1>
            
            <p class="env-hero-subtitle">
                <?php echo esc_html($hero_subtitle); ?>
            </p>
            
            <div class="env-hero-actions">
                <a href="<?php echo esc_url($hero_cta_url); ?>" class="env-button env-button-primary env-button-large">
                    <?php echo esc_html($hero_cta_text); ?>
                </a>
                
                <a href="#learn-more" class="env-button env-button-secondary env-button-large">
                    <?php esc_html_e('Learn More', 'environmental-platform'); ?>
                </a>
            </div>
            
            <!-- Environmental Stats -->
            <div class="env-hero-stats">
                <?php
                global $wpdb;
                
                // Get platform statistics
                $total_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");
                $total_activities = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}environmental_activities");
                $total_impact = $wpdb->get_var("SELECT SUM(environmental_score) FROM {$wpdb->prefix}environmental_activities");
                ?>
                
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($total_users ?: 0); ?></span>
                    <span class="stat-label"><?php esc_html_e('Community Members', 'environmental-platform'); ?></span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($total_activities ?: 0); ?></span>
                    <span class="stat-label"><?php esc_html_e('Environmental Actions', 'environmental-platform'); ?></span>
                </div>
                
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($total_impact ?: 0); ?></span>
                    <span class="stat-label"><?php esc_html_e('Impact Points Generated', 'environmental-platform'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Scroll indicator -->
        <div class="scroll-indicator">
            <span class="scroll-text"><?php esc_html_e('Scroll to explore', 'environmental-platform'); ?></span>
            <div class="scroll-arrow">↓</div>
        </div>
    </div>
    
    <!-- Background elements -->
    <div class="hero-bg-elements">
        <div class="floating-leaf leaf-1">🍃</div>
        <div class="floating-leaf leaf-2">🌿</div>
        <div class="floating-leaf leaf-3">🍀</div>
    </div>
</section>

<style>
/* Hero Section Specific Styles */
.env-hero {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-green) 100%);
    color: white;
    overflow: hidden;
}

.env-hero-content {
    text-align: center;
    z-index: 2;
    position: relative;
}

.env-hero-title {
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 700;
    margin-bottom: 1.5rem;
    line-height: 1.2;
}

.env-hero-subtitle {
    font-size: clamp(1.1rem, 2vw, 1.3rem);
    margin-bottom: 3rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
    opacity: 0.95;
    line-height: 1.6;
}

.env-hero-actions {
    display: flex;
    gap: 1.5rem;
    justify-content: center;
    align-items: center;
    margin-bottom: 4rem;
    flex-wrap: wrap;
}

.env-button-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.env-hero-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    backdrop-filter: blur(10px);
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.scroll-indicator {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    text-align: center;
    animation: bounce 2s infinite;
}

.scroll-text {
    display: block;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    opacity: 0.8;
}

.scroll-arrow {
    font-size: 1.5rem;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0) translateX(-50%);
    }
    40% {
        transform: translateY(-10px) translateX(-50%);
    }
    60% {
        transform: translateY(-5px) translateX(-50%);
    }
}

.hero-bg-elements {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
}

.floating-leaf {
    position: absolute;
    font-size: 2rem;
    opacity: 0.3;
    animation: float 20s infinite linear;
}

.leaf-1 {
    top: 20%;
    left: 10%;
    animation-delay: 0s;
}

.leaf-2 {
    top: 60%;
    right: 15%;
    animation-delay: 7s;
}

.leaf-3 {
    top: 40%;
    left: 80%;
    animation-delay: 14s;
}

@keyframes float {
    0% {
        transform: translateY(0px) rotate(0deg);
    }
    33% {
        transform: translateY(-20px) rotate(120deg);
    }
    66% {
        transform: translateY(10px) rotate(240deg);
    }
    100% {
        transform: translateY(0px) rotate(360deg);
    }
}

/* Mobile responsive */
@media (max-width: 768px) {
    .env-hero {
        min-height: 80vh;
        padding: 4rem 0;
    }
    
    .env-hero-actions {
        flex-direction: column;
        gap: 1rem;
    }
    
    .env-button-large {
        width: 100%;
        max-width: 300px;
    }
    
    .env-hero-stats {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        padding: 1.5rem;
    }
    
    .stat-number {
        font-size: 2rem;
    }
}
</style>
