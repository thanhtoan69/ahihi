/*!
 * Environmental Platform Theme - Main JavaScript
 * Handles theme interactions, dark mode, and accessibility features
 */

(function($) {
    'use strict';

    class EnvironmentalPlatformTheme {
        constructor() {
            this.init();
        }

        init() {
            this.setupEventListeners();
            this.initThemeToggle();
            this.initMobileNavigation();
            this.initLazyLoading();
            this.initModals();
            this.initAccessibilityFeatures();
            this.initAnimations();
            this.initFormEnhancements();
        }

        /* ==========================================================================
           Theme Toggle (Dark/Light Mode)
           ========================================================================== */
          initThemeToggle() {
            // Create theme toggle button if it doesn't exist
            if (!document.querySelector('.env-theme-toggle')) {
                this.createThemeToggle();
            }
            
            // Set initial theme based on user preference or system preference
            this.setInitialTheme();
            
            // Listen for theme toggle clicks
            $(document).on('click', '.env-theme-toggle', (e) => {
                e.preventDefault();
                this.toggleTheme();
            });
            
            // Listen for system theme changes
            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                    if (!localStorage.getItem('environmental-theme-preference')) {
                        this.setTheme(e.matches ? 'dark' : 'light');
                    }
                });
            }
        }
        
        createThemeToggle() {
            const toggleHTML = `
                <button class="env-theme-toggle" aria-label="Toggle dark/light mode" title="Toggle Theme">
                    <span class="theme-icon theme-icon-light">☀️</span>
                    <span class="theme-icon theme-icon-dark">🌙</span>
                </button>
            `;
            
            // Add to header if it exists
            const header = document.querySelector('header, .site-header, .main-header');
            if (header) {
                header.insertAdjacentHTML('beforeend', toggleHTML);
            } else {
                // Create floating toggle button
                document.body.insertAdjacentHTML('beforeend', `
                    <div class="floating-theme-toggle">${toggleHTML}</div>
                `);
            }
        }
        
        setInitialTheme() {
            // Check for saved preference
            const savedTheme = localStorage.getItem('environmental-theme-preference');
            
            if (savedTheme) {
                this.setTheme(savedTheme);
            } else {
                // Check system preference
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                this.setTheme(prefersDark ? 'dark' : 'light');
            }
        }
        
        toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            this.setTheme(newTheme);
            
            // Save preference
            localStorage.setItem('environmental-theme-preference', newTheme);
            
            // Send to server if user is logged in
            if (environmentalPlatform.isUserLoggedIn) {
                $.ajax({
                    url: environmentalPlatform.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'toggle_dark_mode',
                        dark_mode: newTheme === 'dark',
                        nonce: environmentalPlatform.nonce
                    }
                });
            }
        }
        
        setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            document.body.classList.toggle('dark-mode', theme === 'dark');
            
            // Update toggle button appearance
            const toggle = document.querySelector('.env-theme-toggle');
            if (toggle) {
                toggle.setAttribute('aria-pressed', theme === 'dark');
                toggle.classList.toggle('dark-active', theme === 'dark');
            }
            
            // Announce theme change to screen readers
            this.announceThemeChange(theme);
        }
          announceThemeChange(theme) {
            const announcement = document.createElement('div');
            announcement.setAttribute('aria-live', 'polite');
            announcement.setAttribute('aria-atomic', 'true');
            announcement.style.cssText = 'position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;';
            announcement.textContent = `Theme changed to ${theme} mode`;
            
            document.body.appendChild(announcement);
            
            setTimeout(() => {
                document.body.removeChild(announcement);
            }, 1000);
        }

        createThemeToggle() {
            const toggleHTML = `
                <div class="env-theme-toggle" title="Toggle Dark/Light Mode" tabindex="0" role="button" aria-label="Toggle theme">
                    <div class="env-theme-toggle-inner">
                        <div class="env-theme-toggle-slider">
                            <span class="theme-icon">🌞</span>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(toggleHTML);
        }

        setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('environmental-platform-theme', theme);
            
            // Update toggle icon
            const icon = theme === 'dark' ? '🌙' : '🌞';
            $('.theme-icon').text(icon);
            
            // Announce to screen readers
            this.announceToScreenReader(`Switched to ${theme} mode`);
        }

        /* ==========================================================================
           Mobile Navigation
           ========================================================================== */
        
        initMobileNavigation() {
            // Create mobile toggle if it doesn't exist
            if (!document.querySelector('.env-nav-toggle')) {
                this.createMobileToggle();
            }

            // Mobile navigation toggle
            $(document).on('click', '.env-nav-toggle', () => {
                $('.env-nav-menu').toggleClass('active');
                $('.env-nav-toggle').toggleClass('active');
                
                // Update ARIA states
                const isOpen = $('.env-nav-menu').hasClass('active');
                $('.env-nav-toggle').attr('aria-expanded', isOpen);
                $('.env-nav-menu').attr('aria-hidden', !isOpen);
            });

            // Close mobile menu when clicking outside
            $(document).on('click', (e) => {
                if (!$(e.target).closest('.env-navigation').length) {
                    $('.env-nav-menu').removeClass('active');
                    $('.env-nav-toggle').removeClass('active').attr('aria-expanded', 'false');
                }
            });

            // Close mobile menu on escape key
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && $('.env-nav-menu').hasClass('active')) {
                    $('.env-nav-menu').removeClass('active');
                    $('.env-nav-toggle').removeClass('active').attr('aria-expanded', 'false').focus();
                }
            });
        }

        createMobileToggle() {
            const toggleHTML = `
                <button class="env-nav-toggle" aria-expanded="false" aria-controls="env-nav-menu" aria-label="Toggle navigation menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            `;
            $('.env-navigation .container').append(toggleHTML);
        }

        /* ==========================================================================
           Modal System
           ========================================================================== */
        
        initModals() {
            // Open modal
            $(document).on('click', '[data-modal-open]', (e) => {
                e.preventDefault();
                const modalId = $(e.currentTarget).data('modal-open');
                this.openModal(modalId);
            });

            // Close modal
            $(document).on('click', '.env-modal-close, .env-modal-overlay', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeModal();
                }
            });

            // Close modal on escape key
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && $('.env-modal-overlay.active').length) {
                    this.closeModal();
                }
            });
        }

        openModal(modalId) {
            const modal = $(`#${modalId}`);
            if (modal.length) {
                modal.addClass('active');
                
                // Focus management
                const firstFocusable = modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])').first();
                firstFocusable.focus();
                
                // Trap focus
                this.trapFocus(modal);
                
                // Prevent body scroll
                $('body').addClass('modal-open');
                
                this.announceToScreenReader('Modal opened');
            }
        }

        closeModal() {
            $('.env-modal-overlay.active').removeClass('active');
            $('body').removeClass('modal-open');
            this.announceToScreenReader('Modal closed');
        }

        trapFocus(modal) {
            const focusableElements = modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            const firstElement = focusableElements.first();
            const lastElement = focusableElements.last();

            modal.on('keydown.modal', (e) => {
                if (e.key === 'Tab') {
                    if (e.shiftKey) {
                        if (document.activeElement === firstElement[0]) {
                            e.preventDefault();
                            lastElement.focus();
                        }
                    } else {
                        if (document.activeElement === lastElement[0]) {
                            e.preventDefault();
                            firstElement.focus();
                        }
                    }
                }
            });
        }

        /* ==========================================================================
           Lazy Loading
           ========================================================================== */
        
        initLazyLoading() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('env-lazy-image');
                            img.classList.add('loaded');
                            observer.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('.env-lazy-image').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        }

        /* ==========================================================================
           Accessibility Features
           ========================================================================== */
        
        initAccessibilityFeatures() {
            // Skip links
            this.createSkipLinks();
            
            // Keyboard navigation
            this.initKeyboardNavigation();
            
            // ARIA live region for announcements
            this.createLiveRegion();
            
            // Focus management
            this.initFocusManagement();
        }

        createSkipLinks() {
            if (!document.querySelector('.skip-link')) {
                const skipLinksHTML = `
                    <a href="#main-content" class="skip-link">Skip to main content</a>
                    <a href="#env-nav-menu" class="skip-link">Skip to navigation</a>
                `;
                $('body').prepend(skipLinksHTML);
            }
        }

        createLiveRegion() {
            if (!document.querySelector('#env-live-region')) {
                $('body').append('<div id="env-live-region" aria-live="polite" aria-atomic="true" class="sr-only"></div>');
            }
        }

        announceToScreenReader(message) {
            $('#env-live-region').text(message);
        }

        initKeyboardNavigation() {
            // Enhanced keyboard navigation for custom elements
            $(document).on('keydown', '.env-theme-toggle', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(e.currentTarget).click();
                }
            });

            // Dropdown navigation with arrow keys
            $(document).on('keydown', '.env-nav-menu a', (e) => {
                const menuItems = $('.env-nav-menu a');
                const currentIndex = menuItems.index(e.currentTarget);
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const nextIndex = (currentIndex + 1) % menuItems.length;
                    menuItems.eq(nextIndex).focus();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prevIndex = currentIndex === 0 ? menuItems.length - 1 : currentIndex - 1;
                    menuItems.eq(prevIndex).focus();
                }
            });
        }

        initFocusManagement() {
            // Focus outline for keyboard users only
            $(document).on('mousedown', () => {
                $('body').addClass('using-mouse');
            });

            $(document).on('keydown', (e) => {
                if (e.key === 'Tab') {
                    $('body').removeClass('using-mouse');
                }
            });
        }

        /* ==========================================================================
           Animations
           ========================================================================== */
        
        initAnimations() {
            // Respect user's motion preference
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }

            // Scroll animations
            this.initScrollAnimations();
            
            // Loading animations
            this.initLoadingAnimations();
        }

        initScrollAnimations() {
            if ('IntersectionObserver' in window) {
                const animationObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('animate-in');
                        }
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                });

                document.querySelectorAll('.env-card, .env-hero-content').forEach(el => {
                    el.classList.add('animate-on-scroll');
                    animationObserver.observe(el);
                });
            }
        }

        initLoadingAnimations() {
            // Staggered card animations
            $('.env-card').each((index, card) => {
                $(card).css('animation-delay', `${index * 0.1}s`);
            });
        }

        /* ==========================================================================
           Form Enhancements
           ========================================================================== */
        
        initFormEnhancements() {
            // Form validation
            this.initFormValidation();
            
            // Enhanced form interactions
            this.initFormInteractions();
            
            // Auto-save functionality
            this.initAutoSave();
        }

        initFormValidation() {
            $(document).on('submit', '.env-form', (e) => {
                const form = $(e.currentTarget);
                let isValid = true;

                // Remove previous error states
                form.find('.env-form-error').removeClass('env-form-error');
                form.find('.error-message').remove();

                // Validate required fields
                form.find('[required]').each((index, field) => {
                    const $field = $(field);
                    if (!field.value.trim()) {
                        isValid = false;
                        $field.addClass('env-form-error');
                        $field.after('<div class="error-message">This field is required</div>');
                    }
                });

                // Validate email fields
                form.find('input[type="email"]').each((index, field) => {
                    const $field = $(field);
                    if (field.value && !this.isValidEmail(field.value)) {
                        isValid = false;
                        $field.addClass('env-form-error');
                        $field.after('<div class="error-message">Please enter a valid email address</div>');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    
                    // Focus first error field
                    form.find('.env-form-error').first().focus();
                    
                    this.announceToScreenReader('Form contains errors. Please review and correct.');
                }
            });
        }

        initFormInteractions() {
            // Floating labels
            $(document).on('focus blur', '.env-form-input, .env-form-textarea', function() {
                const $this = $(this);
                const $label = $this.siblings('.env-form-label');
                
                if (this.value || $(this).is(':focus')) {
                    $label.addClass('floating');
                } else {
                    $label.removeClass('floating');
                }
            });

            // Character count for textareas
            $(document).on('input', '.env-form-textarea[maxlength]', function() {
                const $this = $(this);
                const maxLength = $this.attr('maxlength');
                const currentLength = this.value.length;
                
                let $counter = $this.siblings('.character-counter');
                if (!$counter.length) {
                    $counter = $('<div class="character-counter"></div>');
                    $this.after($counter);
                }
                
                $counter.text(`${currentLength}/${maxLength}`);
                
                if (currentLength > maxLength * 0.9) {
                    $counter.addClass('warning');
                } else {
                    $counter.removeClass('warning');
                }
            });
        }

        initAutoSave() {
            // Auto-save form data to localStorage
            $(document).on('input', '.env-form[data-autosave]', function() {
                const form = $(this);
                const formId = form.data('autosave');
                const formData = form.serialize();
                
                localStorage.setItem(`env-form-${formId}`, formData);
                
                // Show save indicator
                clearTimeout(this.autoSaveTimeout);
                this.autoSaveTimeout = setTimeout(() => {
                    form.find('.auto-save-indicator').remove();
                    form.append('<div class="auto-save-indicator">Saved</div>');
                    
                    setTimeout(() => {
                        form.find('.auto-save-indicator').fadeOut();
                    }, 2000);
                }, 1000);
            });

            // Restore form data on page load
            $('.env-form[data-autosave]').each(function() {
                const form = $(this);
                const formId = form.data('autosave');
                const savedData = localStorage.getItem(`env-form-${formId}`);
                
                if (savedData) {
                    const params = new URLSearchParams(savedData);
                    params.forEach((value, key) => {
                        form.find(`[name="${key}"]`).val(value);
                    });
                }
            });
        }

        /* ==========================================================================
           Utility Functions
           ========================================================================== */
        
        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        setupEventListeners() {
            // Window resize handler
            $(window).on('resize', this.debounce(() => {
                this.handleResize();
            }, 250));

            // Scroll handler
            $(window).on('scroll', this.debounce(() => {
                this.handleScroll();
            }, 16));
        }

        handleResize() {
            // Close mobile menu on resize to desktop
            if (window.innerWidth > 768) {
                $('.env-nav-menu').removeClass('active');
                $('.env-nav-toggle').removeClass('active').attr('aria-expanded', 'false');
            }
        }

        handleScroll() {
            // Add shadow to navigation on scroll
            if (window.scrollY > 100) {
                $('.env-navigation').addClass('scrolled');
            } else {
                $('.env-navigation').removeClass('scrolled');
            }
        }
    }

    /* ==========================================================================
       Initialize Theme
       ========================================================================== */
    
    $(document).ready(() => {
        new EnvironmentalPlatformTheme();
    });

    /* ==========================================================================
       Custom Events for Third-Party Integration
       ========================================================================== */
    
    window.EnvironmentalPlatform = {
        theme: {
            setTheme: function(theme) {
                const themeInstance = new EnvironmentalPlatformTheme();
                themeInstance.setTheme(theme);
            },
            openModal: function(modalId) {
                const themeInstance = new EnvironmentalPlatformTheme();
                themeInstance.openModal(modalId);
            },
            closeModal: function() {
                const themeInstance = new EnvironmentalPlatformTheme();
                themeInstance.closeModal();
            },
            announceToScreenReader: function(message) {
                const themeInstance = new EnvironmentalPlatformTheme();
                themeInstance.announceToScreenReader(message);
            }
        }
    };

})(jQuery);
