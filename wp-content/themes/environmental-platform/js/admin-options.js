jQuery(document).ready(function($) {
    'use strict';

    // Logo upload functionality
    $('.upload-logo-button').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var logoUrlField = button.siblings('.logo-url-field');
        var logoPreview = button.siblings('.logo-preview');
        
        // Create the media frame
        var frame = wp.media({
            title: 'Select or Upload Logo',
            button: {
                text: 'Use This Logo'
            },
            multiple: false
        });
        
        // When an image is selected
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            
            // Set the URL in the input field
            logoUrlField.val(attachment.url);
            
            // Update or create preview
            if (logoPreview.length) {
                logoPreview.find('img').attr('src', attachment.url);
            } else {
                button.after('<div class="logo-preview"><img src="' + attachment.url + '" alt="Logo Preview" style="max-width: 200px; height: auto; margin-top: 10px;" /></div>');
            }
        });
        
        // Open the media frame
        frame.open();
    });

    // Environmental impact statistics animation
    function animateStats() {
        $('.stat-number').each(function() {
            var $this = $(this);
            var target = parseInt($this.text().replace(/,/g, ''));
            var current = 0;
            var increment = target / 100;
            
            var timer = setInterval(function() {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                
                // Format number with commas
                var formattedNumber = Math.floor(current).toLocaleString();
                $this.text(formattedNumber);
            }, 20);
        });
    }

    // Trigger animation when options page loads
    setTimeout(animateStats, 500);

    // Form validation
    $('form').on('submit', function(e) {
        var isValid = true;
        var errorMessages = [];

        // Validate URLs
        $('input[type="url"]').each(function() {
            var url = $(this).val();
            if (url && !isValidUrl(url)) {
                isValid = false;
                var fieldLabel = $(this).closest('tr').find('th label').text();
                errorMessages.push(fieldLabel + ' must be a valid URL');
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });

        // Validate numeric fields
        $('input[name*="daily_"]').each(function() {
            var value = $(this).val();
            if (value && !$.isNumeric(value.replace(/,/g, ''))) {
                isValid = false;
                var fieldLabel = $(this).closest('tr').find('th label').text();
                errorMessages.push(fieldLabel + ' must be a valid number');
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Please fix the following errors:\n' + errorMessages.join('\n'));
        }
    });

    // URL validation helper
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    // Add visual feedback for form fields
    $('input, select, textarea').on('focus', function() {
        $(this).closest('tr').addClass('focused');
    }).on('blur', function() {
        $(this).closest('tr').removeClass('focused');
    });

    // Auto-format number fields
    $('input[name*="daily_"]').on('input', function() {
        var value = $(this).val().replace(/,/g, '');
        if ($.isNumeric(value)) {
            $(this).val(parseInt(value).toLocaleString());
        }
    });

    // Social media URL helpers
    var socialPlatforms = {
        'facebook_url': {
            icon: 'fab fa-facebook-f',
            color: '#1877f2',
            placeholder: 'https://facebook.com/your-page'
        },
        'twitter_url': {
            icon: 'fab fa-twitter',
            color: '#1da1f2',
            placeholder: 'https://twitter.com/your-handle'
        },
        'instagram_url': {
            icon: 'fab fa-instagram',
            color: '#e4405f',
            placeholder: 'https://instagram.com/your-profile'
        },
        'linkedin_url': {
            icon: 'fab fa-linkedin-in',
            color: '#0077b5',
            placeholder: 'https://linkedin.com/company/your-company'
        }
    };

    // Enhance social media fields
    $.each(socialPlatforms, function(fieldName, platform) {
        var field = $('input[name="environmental_platform_options[' + fieldName + ']"]');
        if (field.length) {
            field.attr('placeholder', platform.placeholder);
            
            // Add icon before the field
            field.before('<i class="' + platform.icon + '" style="color: ' + platform.color + '; margin-right: 10px; font-size: 1.2em;"></i>');
            
            // Validate platform-specific URLs
            field.on('blur', function() {
                var url = $(this).val();
                if (url && !url.includes(fieldName.replace('_url', '').replace('_', ''))) {
                    $(this).addClass('warning');
                    $(this).after('<span class="url-warning" style="color: orange; font-size: 0.9em; margin-left: 10px;">This doesn\'t look like a ' + fieldName.replace('_url', '').replace('_', ' ') + ' URL</span>');
                } else {
                    $(this).removeClass('warning');
                    $(this).siblings('.url-warning').remove();
                }
            });
        }
    });

    // Add theme preview functionality
    if ($('#environmental-theme-preview').length === 0) {
        $('form').after('<div id="environmental-theme-preview" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 10px;"><h3>Theme Preview</h3><p>Changes will be reflected on your live site after saving.</p></div>');
    }

    // Add save confirmation
    $('input[type="submit"]').on('click', function() {
        $(this).val('Saving Environmental Settings...');
        $(this).prop('disabled', true);
        
        setTimeout(function() {
            $('input[type="submit"]').val('Save Environmental Settings');
            $('input[type="submit"]').prop('disabled', false);
        }, 2000);
    });

    // Add tooltips for better UX
    $('[data-tooltip]').each(function() {
        var tooltip = $(this).data('tooltip');
        $(this).attr('title', tooltip);
    });

    // Environmental impact calculator preview
    function updateImpactPreview() {
        var trees = parseInt($('input[name="environmental_platform_options[daily_trees_planted]"]').val().replace(/,/g, '')) || 0;
        var waste = parseInt($('input[name="environmental_platform_options[daily_waste_recycled]"]').val().replace(/,/g, '')) || 0;
        var energy = parseInt($('input[name="environmental_platform_options[daily_energy_saved]"]').val().replace(/,/g, '')) || 0;
        
        // Calculate estimated environmental impact
        var co2Reduced = (trees * 48) + (waste * 0.5) + (energy * 0.7); // Rough calculation
        var waterSaved = (trees * 150) + (waste * 2); // Rough calculation
        
        if ($('#impact-preview').length === 0) {
            $('.options-widget').last().after(
                '<div class="options-widget" id="impact-preview">' +
                '<h3>Estimated Daily Impact</h3>' +
                '<div class="impact-calculation">' +
                '<div class="calc-item"><strong>CO₂ Reduced:</strong> <span id="co2-calc">' + co2Reduced.toFixed(1) + ' kg</span></div>' +
                '<div class="calc-item"><strong>Water Saved:</strong> <span id="water-calc">' + waterSaved.toFixed(0) + ' L</span></div>' +
                '</div>' +
                '</div>'
            );
        } else {
            $('#co2-calc').text(co2Reduced.toFixed(1) + ' kg');
            $('#water-calc').text(waterSaved.toFixed(0) + ' L');
        }
    }

    // Update impact preview when values change
    $('input[name*="daily_"]').on('input', updateImpactPreview);
    updateImpactPreview(); // Initial calculation

    console.log('Environmental Platform Theme Options loaded successfully');
});
