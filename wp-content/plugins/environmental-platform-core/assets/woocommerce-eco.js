/**
 * WooCommerce Integration JavaScript
 * 
 * Phase 32: E-commerce Integration (WooCommerce)
 * JavaScript for eco-friendly features and interactive elements
 * 
 * @package EnvironmentalPlatform
 * @subpackage Assets
 * @since 1.0.0
 */

jQuery(document).ready(function($) {
    'use strict';

    // Initialize WooCommerce Eco Features
    var WooCommerceEco = {
        
        init: function() {
            this.bindEvents();
            this.initEcoCalculator();
            this.initSustainabilityTooltips();
            this.initCarbonOffsetCalculator();
            this.initEcoFiltering();
            this.initProgressBars();
        },

        bindEvents: function() {
            // Update cart environmental impact when quantities change
            $(document.body).on('updated_cart_totals updated_checkout', this.updateEnvironmentalImpact);
            
            // Carbon offset checkbox handler
            $(document).on('change', '#billing_carbon_offset', this.handleCarbonOffsetChange);
            
            // Eco packaging change handler
            $(document).on('change', '#shipping_eco_packaging', this.handlePackagingChange);
            
            // Eco product filter
            $(document).on('change', '.eco-product-filter', this.handleEcoFilter);
            
            // Sustainability score hover
            $(document).on('mouseenter', '.sustainability-score', this.showScoreTooltip);
            $(document).on('mouseleave', '.sustainability-score', this.hideScoreTooltip);
        },

        initEcoCalculator: function() {
            // Real-time calculation of environmental impact in cart
            var cartItems = $('.woocommerce-cart-form .cart_item');
            if (cartItems.length > 0) {
                this.calculateCartImpact();
            }
        },

        initSustainabilityTooltips: function() {
            // Initialize tooltips for sustainability elements
            $('.ep-eco-badge, .sustainability-score, .carbon-footprint').each(function() {
                var $this = $(this);
                var tooltip = $this.data('tooltip');
                
                if (tooltip) {
                    $this.attr('title', tooltip);
                }
            });
        },

        initCarbonOffsetCalculator: function() {
            var $carbonCheckbox = $('#billing_carbon_offset');
            if ($carbonCheckbox.length > 0) {
                var carbonAmount = $carbonCheckbox.data('carbon-amount') || 0;
                var offsetCost = carbonAmount * 0.02; // $0.02 per kg CO2
                
                // Update label with real-time calculation
                this.updateCarbonOffsetLabel(carbonAmount, offsetCost);
            }
        },

        initEcoFiltering: function() {
            // Initialize eco-friendly product filtering
            var $filterSelect = $('.eco-product-filter select');
            if ($filterSelect.length > 0) {
                $filterSelect.on('change', function() {
                    var filterValue = $(this).val();
                    window.location.href = this.addFilterToUrl(filterValue);
                }.bind(this));
            }
        },

        initProgressBars: function() {
            // Animate sustainability score progress bars
            $('.score-bar .score-fill').each(function() {
                var $fill = $(this);
                var targetWidth = $fill.data('score') || $fill.css('width');
                
                $fill.css('width', '0%');
                setTimeout(function() {
                    $fill.css('width', targetWidth);
                }, 500);
            });
        },

        updateEnvironmentalImpact: function() {
            // Update environmental impact display in cart/checkout
            var totalCarbon = 0;
            var totalSustainability = 0;
            var itemCount = 0;

            $('.cart_item').each(function() {
                var $item = $(this);
                var carbonFootprint = parseFloat($item.data('carbon-footprint') || 0);
                var sustainabilityScore = parseInt($item.data('sustainability-score') || 0);
                var quantity = parseInt($item.find('.qty').val() || 1);

                totalCarbon += carbonFootprint * quantity;
                if (sustainabilityScore > 0) {
                    totalSustainability += sustainabilityScore * quantity;
                    itemCount += quantity;
                }
            });

            // Update display
            $('.carbon-footprint-total .amount').text(totalCarbon.toFixed(2) + ' kg CO2');
            
            if (itemCount > 0) {
                var avgSustainability = totalSustainability / itemCount;
                $('.sustainability-score-total .amount').text(avgSustainability.toFixed(1) + '/100');
            }

            // Update carbon offset calculator
            WooCommerceEco.updateCarbonOffsetLabel(totalCarbon, totalCarbon * 0.02);
        },

        handleCarbonOffsetChange: function() {
            var $checkbox = $(this);
            var $infoDiv = $('.carbon-offset-info');
            
            if ($checkbox.is(':checked')) {
                if ($infoDiv.length === 0) {
                    var carbonAmount = $checkbox.data('carbon-amount') || 0;
                    var offsetCost = (carbonAmount * 0.02).toFixed(2);
                    
                    var infoHtml = '<div class="carbon-offset-info">' +
                        '<p><strong>🌱 ' + wp.i18n.__('Carbon Offset Details', 'environmental-platform-core') + '</strong></p>' +
                        '<p>' + wp.i18n.sprintf(
                            wp.i18n.__('Your $%s contribution will fund verified carbon reduction projects equivalent to %s kg CO2.', 'environmental-platform-core'),
                            offsetCost,
                            carbonAmount.toFixed(2)
                        ) + '</p>' +
                        '</div>';
                    
                    $checkbox.closest('.form-row').after(infoHtml);
                }
            } else {
                $infoDiv.remove();
            }
        },

        handlePackagingChange: function() {
            var $select = $(this);
            var selectedOption = $select.val();
            var $infoDiv = $('.packaging-info');
            
            $infoDiv.remove(); // Remove existing info
            
            if (selectedOption) {
                var infoText = '';
                switch (selectedOption) {
                    case 'minimal':
                        infoText = wp.i18n.__('Minimal packaging reduces waste by up to 30%.', 'environmental-platform-core');
                        break;
                    case 'recyclable':
                        infoText = wp.i18n.__('100% recyclable materials help close the loop.', 'environmental-platform-core');
                        break;
                    case 'biodegradable':
                        infoText = wp.i18n.__('Biodegradable packaging naturally decomposes.', 'environmental-platform-core');
                        break;
                    case 'reusable':
                        infoText = wp.i18n.__('Reusable packaging can be returned for a discount on your next order.', 'environmental-platform-core');
                        break;
                }
                
                if (infoText) {
                    var infoHtml = '<div class="packaging-info"><p>ℹ️ ' + infoText + '</p></div>';
                    $select.closest('.form-row').after(infoHtml);
                }
            }
        },

        handleEcoFilter: function() {
            var $filter = $(this);
            var filterValue = $filter.val();
            var currentUrl = window.location.href;
            var newUrl = WooCommerceEco.addFilterToUrl(currentUrl, 'eco_filter', filterValue);
            
            // Show loading state
            $filter.prop('disabled', true);
            $('<div class="ep-loading-eco-data"></div>').insertAfter($filter);
            
            // Navigate to filtered results
            window.location.href = newUrl;
        },

        showScoreTooltip: function() {
            var $element = $(this);
            var score = $element.data('score') || 0;
            var tooltipText = WooCommerceEco.getScoreDescription(score);
            
            if (tooltipText) {
                var $tooltip = $('<div class="ep-score-tooltip">' + tooltipText + '</div>');
                $('body').append($tooltip);
                
                var offset = $element.offset();
                $tooltip.css({
                    position: 'absolute',
                    top: offset.top - $tooltip.outerHeight() - 5,
                    left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2),
                    zIndex: 9999
                });
            }
        },

        hideScoreTooltip: function() {
            $('.ep-score-tooltip').remove();
        },

        calculateCartImpact: function() {
            // Calculate total environmental impact for cart
            var impact = {
                totalCarbon: 0,
                avgSustainability: 0,
                ecoProducts: 0,
                totalProducts: 0
            };

            $('.cart_item').each(function() {
                var $item = $(this);
                var quantity = parseInt($item.find('.qty').val() || 1);
                var carbonFootprint = parseFloat($item.data('carbon-footprint') || 0);
                var sustainabilityScore = parseInt($item.data('sustainability-score') || 0);
                var isEcoFriendly = $item.data('eco-friendly') === 'yes';

                impact.totalCarbon += carbonFootprint * quantity;
                if (sustainabilityScore > 0) {
                    impact.avgSustainability += sustainabilityScore * quantity;
                }
                if (isEcoFriendly) {
                    impact.ecoProducts += quantity;
                }
                impact.totalProducts += quantity;
            });

            if (impact.totalProducts > 0) {
                impact.avgSustainability = impact.avgSustainability / impact.totalProducts;
                impact.ecoPercentage = (impact.ecoProducts / impact.totalProducts) * 100;
            }

            return impact;
        },

        updateCarbonOffsetLabel: function(carbonAmount, offsetCost) {
            var $checkbox = $('#billing_carbon_offset');
            if ($checkbox.length > 0 && carbonAmount > 0) {
                var labelText = wp.i18n.sprintf(
                    wp.i18n.__('Purchase carbon offset (+$%s to neutralize %s kg CO2)', 'environmental-platform-core'),
                    offsetCost.toFixed(2),
                    carbonAmount.toFixed(2)
                );
                $checkbox.next('label').text(labelText);
                $checkbox.data('carbon-amount', carbonAmount);
            }
        },

        getScoreDescription: function(score) {
            if (score >= 90) return wp.i18n.__('Excellent environmental performance', 'environmental-platform-core');
            if (score >= 80) return wp.i18n.__('Very good environmental impact', 'environmental-platform-core');
            if (score >= 70) return wp.i18n.__('Good sustainability practices', 'environmental-platform-core');
            if (score >= 60) return wp.i18n.__('Moderate environmental impact', 'environmental-platform-core');
            if (score >= 50) return wp.i18n.__('Basic sustainability measures', 'environmental-platform-core');
            return wp.i18n.__('Limited environmental consideration', 'environmental-platform-core');
        },

        addFilterToUrl: function(url, param, value) {
            var newUrl = new URL(url);
            if (value) {
                newUrl.searchParams.set(param, value);
            } else {
                newUrl.searchParams.delete(param);
            }
            return newUrl.toString();
        },

        // AJAX helpers for eco data
        loadEcoData: function(productId, callback) {
            $.ajax({
                url: ep_wc_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_product_eco_data',
                    product_id: productId,
                    nonce: ep_wc_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        callback(response.data);
                    }
                },
                error: function() {
                    console.log('Failed to load eco data');
                }
            });
        },

        // Eco points display animation
        animateEcoPointsEarned: function(points) {
            if (points > 0) {
                var $notification = $('<div class="eco-points-notification">' +
                    '<span class="icon">🌱</span>' +
                    '<span class="text">' + wp.i18n.sprintf(wp.i18n.__('You earned %d eco points!', 'environmental-platform-core'), points) + '</span>' +
                    '</div>');

                $('body').append($notification);

                setTimeout(function() {
                    $notification.addClass('show');
                }, 100);

                setTimeout(function() {
                    $notification.removeClass('show');
                    setTimeout(function() {
                        $notification.remove();
                    }, 300);
                }, 3000);
            }
        }
    };

    // Initialize on document ready
    WooCommerceEco.init();

    // Make functions available globally for other scripts
    window.WooCommerceEco = WooCommerceEco;

    // Handle product page eco-friendly information toggle
    $(document).on('click', '.sustainability-info-toggle', function(e) {
        e.preventDefault();
        var $content = $(this).next('.sustainability-content');
        $content.slideToggle();
        $(this).find('.dashicons').toggleClass('dashicons-arrow-down dashicons-arrow-up');
    });

    // Eco product comparison (if multiple products are selected)
    $('.compare-eco-impact').on('click', function() {
        var selectedProducts = [];
        $('.product-checkbox:checked').each(function() {
            selectedProducts.push($(this).val());
        });

        if (selectedProducts.length > 1) {
            // Show comparison modal or redirect to comparison page
            var comparisonUrl = ep_wc_ajax.home_url + '/eco-compare/?products=' + selectedProducts.join(',');
            window.open(comparisonUrl, '_blank');
        } else {
            alert(wp.i18n.__('Please select at least 2 products to compare.', 'environmental-platform-core'));
        }
    });
});

// Eco-friendly checkout validation
jQuery(document).on('checkout_place_order', function() {
    var carbonOffset = jQuery('#billing_carbon_offset').is(':checked');
    var ecoPackaging = jQuery('#shipping_eco_packaging').val();
    
    // Track eco-friendly choices for analytics
    if (carbonOffset || ecoPackaging) {
        // Send tracking data
        jQuery.post(ep_wc_ajax.ajax_url, {
            action: 'track_eco_checkout_choice',
            carbon_offset: carbonOffset,
            eco_packaging: ecoPackaging,
            nonce: ep_wc_ajax.nonce
        });
    }
    
    return true; // Allow checkout to proceed
});

// CSS for JavaScript-generated elements
var ecoStyles = `
<style>
.ep-score-tooltip {
    background: rgba(0,0,0,0.9);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
    max-width: 200px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.eco-points-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: linear-gradient(135deg, #4CAF50, #66BB6A);
    color: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 10000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}

.eco-points-notification.show {
    transform: translateX(0);
}

.eco-points-notification .icon {
    font-size: 20px;
}

.carbon-offset-info,
.packaging-info {
    background: #e8f5e8;
    padding: 10px;
    border-radius: 6px;
    margin: 10px 0;
    font-size: 12px;
    color: #2E7D32;
    border-left: 4px solid #4CAF50;
}
</style>
`;

jQuery('head').append(ecoStyles);
