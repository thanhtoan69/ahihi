    </div><!-- #content -->

    <footer id="colophon" class="site-footer">
        <div class="container">
            <div class="footer-content">
                
                <!-- About Section -->
                <div class="footer-section">
                    <h3><?php _e('About Environmental Platform', 'environmental-platform'); ?></h3>
                    <p><?php _e('Join our community of eco-warriors working together to create a sustainable future. Track your environmental impact, learn from experts, and make a difference.', 'environmental-platform'); ?></p>
                    
                    <!-- Social Links -->
                    <div class="social-links">
                        <?php
                        $social_links = array(
                            'facebook' => get_theme_mod('facebook_url'),
                            'twitter' => get_theme_mod('twitter_url'),
                            'instagram' => get_theme_mod('instagram_url'),
                            'youtube' => get_theme_mod('youtube_url'),
                        );
                        
                        foreach ($social_links as $platform => $url) :
                            if ($url) :
                        ?>
                            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener" class="social-link social-<?php echo $platform; ?>">
                                <span class="screen-reader-text"><?php printf(__('Follow us on %s', 'environmental-platform'), ucfirst($platform)); ?></span>
                                <?php echo environmental_platform_get_social_icon($platform); ?>
                            </a>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="footer-section">
                    <h3><?php _e('Quick Links', 'environmental-platform'); ?></h3>
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'footer',
                        'menu_class'     => 'footer-menu',
                        'container'      => false,
                        'depth'          => 1,
                        'fallback_cb'    => false,
                    ));
                    ?>
                    <?php if (!has_nav_menu('footer')) : ?>
                        <ul class="footer-menu">
                            <li><a href="<?php echo esc_url(home_url('/about')); ?>"><?php _e('About Us', 'environmental-platform'); ?></a></li>
                            <li><a href="<?php echo esc_url(home_url('/contact')); ?>"><?php _e('Contact', 'environmental-platform'); ?></a></li>
                            <li><a href="<?php echo esc_url(home_url('/blog')); ?>"><?php _e('Blog', 'environmental-platform'); ?></a></li>
                            <li><a href="<?php echo esc_url(home_url('/privacy-policy')); ?>"><?php _e('Privacy Policy', 'environmental-platform'); ?></a></li>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Environmental Categories -->
                <div class="footer-section">
                    <h3><?php _e('Environmental Topics', 'environmental-platform'); ?></h3>
                    <?php
                    $categories = get_categories(array(
                        'number' => 8,
                        'orderby' => 'count',
                        'order' => 'DESC',
                        'hide_empty' => true,
                    ));
                    
                    if ($categories) :
                    ?>
                        <ul class="environmental-categories">
                            <?php foreach ($categories as $category) : ?>
                                <li>
                                    <a href="<?php echo esc_url(get_category_link($category->term_id)); ?>">
                                        <?php echo esc_html($category->name); ?>
                                        <span class="category-count">(<?php echo $category->count; ?>)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <!-- Environmental Impact Widget -->
                <div class="footer-section">
                    <h3><?php _e('Our Impact Today', 'environmental-platform'); ?></h3>
                    <div class="daily-impact-widget">
                        <?php
                        global $wpdb;
                        
                        // Get today's environmental impact
                        $today = date('Y-m-d');
                        $daily_stats = $wpdb->get_row($wpdb->prepare(
                            "SELECT 
                                COUNT(DISTINCT user_id) as active_users,
                                SUM(CASE WHEN carbon_impact_kg > 0 THEN carbon_impact_kg ELSE 0 END) as carbon_saved,
                                COUNT(CASE WHEN activity_type = 'waste_report' THEN 1 END) as waste_reports,
                                COUNT(CASE WHEN activity_type = 'quiz_completion' THEN 1 END) as quizzes_completed
                            FROM user_activities_comprehensive 
                            WHERE DATE(created_at) = %s",
                            $today
                        ));
                        
                        if ($daily_stats) :
                        ?>
                            <div class="impact-stats">
                                <div class="impact-item">
                                    <span class="impact-number"><?php echo number_format($daily_stats->active_users); ?></span>
                                    <span class="impact-label"><?php _e('Active Users', 'environmental-platform'); ?></span>
                                </div>
                                <div class="impact-item">
                                    <span class="impact-number"><?php echo number_format($daily_stats->carbon_saved, 1); ?>kg</span>
                                    <span class="impact-label"><?php _e('CO₂ Saved', 'environmental-platform'); ?></span>
                                </div>
                                <div class="impact-item">
                                    <span class="impact-number"><?php echo number_format($daily_stats->waste_reports); ?></span>
                                    <span class="impact-label"><?php _e('Waste Reports', 'environmental-platform'); ?></span>
                                </div>
                                <div class="impact-item">
                                    <span class="impact-number"><?php echo number_format($daily_stats->quizzes_completed); ?></span>
                                    <span class="impact-label"><?php _e('Quizzes Completed', 'environmental-platform'); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Newsletter Signup -->
                    <div class="newsletter-signup">
                        <h4><?php _e('Stay Updated', 'environmental-platform'); ?></h4>
                        <form class="newsletter-form" action="#" method="post">
                            <div class="form-group">
                                <input type="email" name="newsletter_email" placeholder="<?php _e('Your email address', 'environmental-platform'); ?>" required>
                                <button type="submit" class="btn btn-secondary">
                                    <?php _e('Subscribe', 'environmental-platform'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <div class="copyright">
                        <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. <?php _e('All rights reserved.', 'environmental-platform'); ?></p>
                        <p class="environmental-commitment">
                            🌱 <?php _e('Committed to a sustainable future', 'environmental-platform'); ?>
                        </p>
                    </div>
                    
                    <div class="footer-credits">
                        <p>
                            <?php printf(
                                __('Powered by %1$s and %2$s', 'environmental-platform'),
                                '<a href="https://wordpress.org/">WordPress</a>',
                                '<a href="#">Environmental Platform</a>'
                            ); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

</div><!-- #page -->

<!-- Environmental Platform JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const primaryMenu = document.querySelector('#primary-menu');
    
    if (mobileToggle && primaryMenu) {
        mobileToggle.addEventListener('click', function() {
            const expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !expanded);
            primaryMenu.classList.toggle('menu-open');
        });
    }
    
    // Environmental alert banner close
    const alertClose = document.querySelector('.alert-close');
    if (alertClose) {
        alertClose.addEventListener('click', function() {
            this.closest('.environmental-alert-banner').style.display = 'none';
        });
    }
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add animation classes when elements come into view
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    document.querySelectorAll('.stat-item, .card, .environmental-impact-widget').forEach(el => {
        observer.observe(el);
    });
    
    // Newsletter form submission
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[name="newsletter_email"]').value;
            
            // Here you would typically send the email to your server
            // For now, we'll just show a success message
            alert('<?php _e("Thank you for subscribing to our environmental newsletter!", "environmental-platform"); ?>');
            this.reset();
        });
    }
});

// Environmental Platform theme custom functions
window.EnvironmentalPlatform = window.EnvironmentalPlatform || {};

EnvironmentalPlatform.theme = {
    // Update progress bars with animation
    updateProgressBar: function(selector, percentage) {
        const progressBar = document.querySelector(selector + ' .progress-fill');
        if (progressBar) {
            progressBar.style.width = percentage + '%';
        }
    },
    
    // Show environmental tip notification
    showEnvironmentalTip: function(message) {
        const tipDiv = document.createElement('div');
        tipDiv.className = 'environmental-tip-notification';
        tipDiv.innerHTML = '🌱 ' + message;
        document.body.appendChild(tipDiv);
        
        setTimeout(() => {
            tipDiv.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            tipDiv.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(tipDiv);
            }, 300);
        }, 5000);
    }
};
</script>

<?php wp_footer(); ?>

</body>
</html>
