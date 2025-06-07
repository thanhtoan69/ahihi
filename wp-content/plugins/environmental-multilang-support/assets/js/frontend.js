/**
 * Environmental Multi-language Support - Frontend JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize frontend functionality
        EMS_Frontend.init();
    });

    var EMS_Frontend = {
        init: function() {
            this.bindEvents();
            this.initLanguageSwitcher();
            this.initPageTransition();
            this.detectUserLanguage();
        },

        bindEvents: function() {
            // Language switcher change event
            $(document).on('change', '.ems-language-switcher select', this.handleLanguageChange);
            $(document).on('click', '.ems-language-switcher a', this.handleLanguageClick);
            
            // Form submission with language
            $(document).on('submit', 'form', this.handleFormSubmission);
            
            // AJAX requests with language
            $(document).ajaxSend(this.addLanguageToAjax);
        },

        initLanguageSwitcher: function() {
            // Initialize dropdown language switchers
            $('.ems-language-switcher.ems-style-dropdown').each(function() {
                var $switcher = $(this);
                var $select = $switcher.find('select');
                
                // Add custom styling if needed
                if ($select.hasClass('ems-custom-select')) {
                    EMS_Frontend.customizeSelect($select);
                }
            });

            // Initialize flag-based switchers
            $('.ems-language-switcher.ems-style-flags').each(function() {
                var $switcher = $(this);
                EMS_Frontend.initFlagSwitcher($switcher);
            });
        },

        initPageTransition: function() {
            // Add page transition effect for language switching
            $('body').addClass('ems-page-transition loaded');
        },

        detectUserLanguage: function() {
            // Auto-detect user language preference if enabled
            if (ems_frontend_vars.auto_detect && !ems_frontend_vars.language_detected) {
                var detectedLang = this.getPreferredLanguage();
                if (detectedLang && detectedLang !== ems_frontend_vars.current_language) {
                    this.suggestLanguageSwitch(detectedLang);
                }
            }
        },

        handleLanguageChange: function(e) {
            var $select = $(this);
            var selectedLang = $select.val();
            var currentUrl = window.location.href;
            
            if (selectedLang && selectedLang !== ems_frontend_vars.current_language) {
                EMS_Frontend.switchLanguage(selectedLang, currentUrl);
            }
        },

        handleLanguageClick: function(e) {
            var $link = $(this);
            
            // Check if it's a language switcher link
            if ($link.closest('.ems-language-switcher').length) {
                e.preventDefault();
                
                var targetLang = $link.data('lang');
                var targetUrl = $link.attr('href');
                
                if (targetLang && targetLang !== ems_frontend_vars.current_language) {
                    EMS_Frontend.switchLanguage(targetLang, targetUrl);
                }
            }
        },

        handleFormSubmission: function(e) {
            var $form = $(this);
            
            // Add current language to form data if not already present
            if (!$form.find('input[name="ems_language"]').length) {
                $form.append('<input type="hidden" name="ems_language" value="' + ems_frontend_vars.current_language + '">');
            }
        },

        addLanguageToAjax: function(event, jqXHR, ajaxOptions) {
            // Add language parameter to AJAX requests
            if (ajaxOptions.data && typeof ajaxOptions.data === 'string') {
                ajaxOptions.data += '&ems_language=' + ems_frontend_vars.current_language;
            } else if (ajaxOptions.data && typeof ajaxOptions.data === 'object') {
                ajaxOptions.data.ems_language = ems_frontend_vars.current_language;
            }
        },

        switchLanguage: function(targetLang, targetUrl) {
            // Show loading state
            $('body').removeClass('loaded').addClass('ems-loading');
            $('.ems-language-switcher').addClass('ems-loading');
            
            // Update language preference
            this.setLanguagePreference(targetLang);
            
            // Navigate to target URL
            if (targetUrl) {
                window.location.href = targetUrl;
            } else {
                // Generate URL for target language
                var newUrl = this.generateLanguageUrl(targetLang, window.location.href);
                window.location.href = newUrl;
            }
        },

        generateLanguageUrl: function(language, currentUrl) {
            var url = new URL(currentUrl);
            
            switch (ems_frontend_vars.url_structure) {
                case 'query':
                    url.searchParams.set('lang', language);
                    break;
                    
                case 'directory':
                    var pathParts = url.pathname.split('/').filter(Boolean);
                    var currentLang = ems_frontend_vars.current_language;
                    
                    // Remove current language from path
                    if (pathParts[0] === currentLang) {
                        pathParts.shift();
                    }
                    
                    // Add new language to path
                    if (language !== ems_frontend_vars.default_language) {
                        pathParts.unshift(language);
                    }
                    
                    url.pathname = '/' + pathParts.join('/');
                    break;
                    
                case 'subdomain':
                    var hostParts = url.hostname.split('.');
                    var currentLang = ems_frontend_vars.current_language;
                    
                    // Remove current language subdomain
                    if (hostParts[0] === currentLang) {
                        hostParts.shift();
                    }
                    
                    // Add new language subdomain
                    if (language !== ems_frontend_vars.default_language) {
                        hostParts.unshift(language);
                    }
                    
                    url.hostname = hostParts.join('.');
                    break;
            }
            
            return url.toString();
        },

        setLanguagePreference: function(language) {
            // Set cookie for language preference
            this.setCookie('ems_language', language, 365);
            
            // Update session storage
            if (typeof Storage !== 'undefined') {
                sessionStorage.setItem('ems_language', language);
            }
            
            // Send AJAX request to update user preference
            if (ems_frontend_vars.user_logged_in) {
                $.post(ems_frontend_vars.ajax_url, {
                    action: 'ems_set_user_language',
                    nonce: ems_frontend_vars.nonce,
                    language: language
                });
            }
        },

        getPreferredLanguage: function() {
            // Check multiple sources for language preference
            var sources = [
                this.getCookie('ems_language'),
                sessionStorage.getItem('ems_language'),
                this.getBrowserLanguage(),
            ];
            
            for (var i = 0; i < sources.length; i++) {
                var lang = sources[i];
                if (lang && this.isLanguageSupported(lang)) {
                    return lang;
                }
            }
            
            return null;
        },

        getBrowserLanguage: function() {
            var browserLang = navigator.language || navigator.userLanguage;
            if (browserLang) {
                // Extract language code (e.g., 'en' from 'en-US')
                return browserLang.substring(0, 2).toLowerCase();
            }
            return null;
        },

        isLanguageSupported: function(language) {
            return ems_frontend_vars.supported_languages.indexOf(language) !== -1;
        },

        suggestLanguageSwitch: function(suggestedLang) {
            // Show language suggestion banner
            var $banner = $('<div class="ems-language-suggestion">')
                .html(
                    '<div class="ems-suggestion-content">' +
                    '<span class="ems-suggestion-text">' + 
                    ems_frontend_vars.strings.language_suggestion.replace('%s', this.getLanguageName(suggestedLang)) +
                    '</span>' +
                    '<button class="ems-suggestion-accept" data-lang="' + suggestedLang + '">' + 
                    ems_frontend_vars.strings.accept + 
                    '</button>' +
                    '<button class="ems-suggestion-dismiss">' + 
                    ems_frontend_vars.strings.dismiss + 
                    '</button>' +
                    '</div>'
                );
            
            $('body').prepend($banner);
            
            // Handle suggestion actions
            $banner.on('click', '.ems-suggestion-accept', function() {
                var lang = $(this).data('lang');
                EMS_Frontend.switchLanguage(lang);
            });
            
            $banner.on('click', '.ems-suggestion-dismiss', function() {
                $banner.fadeOut(function() {
                    $(this).remove();
                });
                // Remember dismissal
                EMS_Frontend.setCookie('ems_suggestion_dismissed', '1', 7);
            });
        },

        getLanguageName: function(langCode) {
            var languages = ems_frontend_vars.language_names || {};
            return languages[langCode] || langCode.toUpperCase();
        },

        customizeSelect: function($select) {
            // Custom select styling for better cross-browser compatibility
            var $wrapper = $('<div class="ems-select-wrapper">');
            var $customSelect = $('<div class="ems-custom-select">');
            var $current = $('<div class="ems-select-current">');
            var $options = $('<div class="ems-select-options">');
            
            // Build custom select
            $select.find('option').each(function() {
                var $option = $(this);
                var $customOption = $('<div class="ems-select-option" data-value="' + $option.val() + '">');
                $customOption.html($option.html());
                
                if ($option.is(':selected')) {
                    $current.html($option.html());
                    $customOption.addClass('selected');
                }
                
                $options.append($customOption);
            });
            
            $customSelect.append($current).append($options);
            $wrapper.append($customSelect);
            
            // Hide original select and add wrapper
            $select.hide().after($wrapper);
            
            // Handle interactions
            $current.on('click', function() {
                $customSelect.toggleClass('open');
            });
            
            $options.on('click', '.ems-select-option', function() {
                var value = $(this).data('value');
                $select.val(value).trigger('change');
                $current.html($(this).html());
                $options.find('.selected').removeClass('selected');
                $(this).addClass('selected');
                $customSelect.removeClass('open');
            });
            
            // Close on outside click
            $(document).on('click', function(e) {
                if (!$customSelect.is(e.target) && $customSelect.has(e.target).length === 0) {
                    $customSelect.removeClass('open');
                }
            });
        },

        initFlagSwitcher: function($switcher) {
            // Add tooltips to flag links
            $switcher.find('a').each(function() {
                var $link = $(this);
                var lang = $link.data('lang');
                var langName = EMS_Frontend.getLanguageName(lang);
                
                if (langName) {
                    $link.attr('title', langName);
                }
            });
        },

        setCookie: function(name, value, days) {
            var expires = '';
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = name + '=' + (value || '') + expires + '; path=/';
        },

        getCookie: function(name) {
            var nameEQ = name + '=';
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }
    };

    // Export for use in other scripts
    window.EMS_Frontend = EMS_Frontend;

})(jQuery);
