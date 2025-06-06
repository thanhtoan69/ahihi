/**
 * Environmental Platform Theme Customizer Live Preview
 */

(function($) {
    'use strict';

    // Site title and description
    wp.customize('blogname', function(value) {
        value.bind(function(to) {
            $('.site-title').text(to);
        });
    });

    wp.customize('blogdescription', function(value) {
        value.bind(function(to) {
            $('.site-description').text(to);
        });
    });

    // Hero section settings
    wp.customize('hero_title', function(value) {
        value.bind(function(to) {
            $('.hero-title').text(to);
        });
    });

    wp.customize('hero_subtitle', function(value) {
        value.bind(function(to) {
            $('.hero-subtitle').text(to);
        });
    });

    wp.customize('hero_button_primary_text', function(value) {
        value.bind(function(to) {
            $('.hero-button-primary').text(to);
        });
    });

    // Footer about text
    wp.customize('footer_about_text', function(value) {
        value.bind(function(to) {
            $('.footer-about-text').text(to);
        });
    });

    // Color changes
    wp.customize('primary_green_color', function(value) {
        value.bind(function(to) {
            updateCSSVariable('--primary-green', to);
        });
    });

    wp.customize('secondary_green_color', function(value) {
        value.bind(function(to) {
            updateCSSVariable('--secondary-green', to);
        });
    });

    wp.customize('earth_brown_color', function(value) {
        value.bind(function(to) {
            updateCSSVariable('--earth-brown', to);
        });
    });

    // Typography changes
    wp.customize('base_font_size', function(value) {
        value.bind(function(to) {
            updateCSSVariable('--font-size-base', to + 'px');
        });
    });

    // Helper function to update CSS variables
    function updateCSSVariable(property, value) {
        document.documentElement.style.setProperty(property, value);
    }

})(jQuery);
