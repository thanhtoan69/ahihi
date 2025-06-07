/**
 * PayPal Enhanced Gateway Frontend JavaScript
 * 
 * Handles PayPal Enhanced checkout process with environmental messaging
 * and carbon offset integration
 * 
 * @package EnvironmentalPaymentGateway
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    class EPGPayPalEnhanced {
        constructor() {
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.initPayPalButtons();
            this.setupEnvironmentalMessaging();
        }
        
        bindEvents() {
            $(document.body).on('updated_checkout', () => {
                this.initPayPalButtons();
            });
            
            $(document).on('change', '#payment_method_epg_paypal_enhanced', () => {
                this.showEnvironmentalImpact();
            });
            
            $(document).on('click', '.epg-carbon-offset-toggle', () => {
                this.toggleCarbonOffset();
            });
        }
        
        initPayPalButtons() {
            if (!window.paypal || !epg_paypal_params) {
                return;
            }
            
            const buttonContainer = '#epg-paypal-button-container';
            
            if (!$(buttonContainer).length) {
                return;
            }
            
            // Clear existing buttons
            $(buttonContainer).empty();
            
            paypal.Buttons({
                style: {
                    layout: 'vertical',
                    color: epg_paypal_params.button_color || 'gold',
                    shape: 'rect',
                    label: 'paypal',
                    tagline: false
                },
                
                createOrder: (data, actions) => {
                    return this.createPayPalOrder(actions);
                },
                
                onApprove: (data, actions) => {
                    return this.handlePayPalApproval(data, actions);
                },
                
                onError: (err) => {
                    this.handlePayPalError(err);
                },
                
                onCancel: (data) => {
                    this.handlePayPalCancel(data);
                }
            }).render(buttonContainer);
            
            // Add environmental messaging
            this.addEnvironmentalMessaging(buttonContainer);
        }
        
        createPayPalOrder(actions) {
            const cartTotal = this.getCartTotal();
            const carbonOffset = this.calculateCarbonOffset(cartTotal);
            
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: (cartTotal + carbonOffset).toFixed(2),
                        breakdown: {
                            item_total: {
                                value: cartTotal.toFixed(2),
                                currency_code: epg_paypal_params.currency
                            },
                            handling: carbonOffset > 0 ? {
                                value: carbonOffset.toFixed(2),
                                currency_code: epg_paypal_params.currency
                            } : undefined
                        }
                    },
                    description: 'Environmental Platform Purchase' + 
                                (carbonOffset > 0 ? ' (includes carbon offset)' : ''),
                    custom_id: this.getOrderId(),
                    items: this.getOrderItems(carbonOffset)
                }],
                application_context: {
                    brand_name: 'Environmental Platform',
                    locale: 'en-US',
                    landing_page: 'BILLING',
                    shipping_preference: 'NO_SHIPPING',
                    user_action: 'PAY_NOW'
                }
            });
        }
        
        handlePayPalApproval(data, actions) {
            return actions.order.capture().then((details) => {
                this.showSuccessMessage(details);
                this.redirectToThankYou(details);
            });
        }
        
        handlePayPalError(err) {
            console.error('PayPal Error:', err);
            this.showErrorMessage('Payment failed. Please try again.');
        }
        
        handlePayPalCancel(data) {
            this.showInfoMessage('Payment was cancelled.');
        }
        
        addEnvironmentalMessaging(container) {
            const messagingHtml = `
                <div class="epg-environmental-messaging">
                    <div class="epg-eco-badge">
                        <span class="epg-eco-icon">🌱</span>
                        <span class="epg-eco-text">Carbon Neutral Payment</span>
                    </div>
                    <p class="epg-eco-description">
                        Your payment helps fund environmental projects and carbon offsets.
                    </p>
                    <div class="epg-carbon-details" style="display: none;">
                        <p><strong>Environmental Impact:</strong></p>
                        <ul>
                            <li>Carbon footprint: <span id="epg-carbon-footprint">0.12 kg CO2</span></li>
                            <li>Offset contribution: $<span id="epg-offset-amount">0.50</span></li>
                            <li>Trees planted equivalent: <span id="epg-trees-equivalent">0.02</span></li>
                        </ul>
                    </div>
                    <button type="button" class="epg-show-details" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'">
                        Show Environmental Impact
                    </button>
                </div>
            `;
            
            $(container).after(messagingHtml);
        }
        
        setupEnvironmentalMessaging() {
            if (epg_paypal_params.environmental_messaging) {
                this.updateEnvironmentalData();
                this.bindEnvironmentalEvents();
            }
        }
        
        updateEnvironmentalData() {
            const cartTotal = this.getCartTotal();
            const carbonFootprint = cartTotal * 0.0005; // Estimated carbon footprint
            const offsetAmount = this.calculateCarbonOffset(cartTotal);
            const treesEquivalent = offsetAmount / 25; // Rough calculation
            
            $('#epg-carbon-footprint').text(carbonFootprint.toFixed(3) + ' kg CO2');
            $('#epg-offset-amount').text(offsetAmount.toFixed(2));
            $('#epg-trees-equivalent').text(treesEquivalent.toFixed(3));
        }
        
        bindEnvironmentalEvents() {
            $(document).on('click', '.epg-show-details', function() {
                $(this).siblings('.epg-carbon-details').slideToggle();
                $(this).text($(this).text() === 'Show Environmental Impact' ? 
                           'Hide Environmental Impact' : 'Show Environmental Impact');
            });
        }
        
        calculateCarbonOffset(amount) {
            if (!epg_paypal_params.carbon_offset_enabled) {
                return 0;
            }
            
            const offsetPercentage = parseFloat(epg_paypal_params.carbon_offset_percentage) || 2;
            return amount * (offsetPercentage / 100);
        }
        
        getCartTotal() {
            const totalElement = $('.order-total .amount, .cart-total .amount').last();
            const totalText = totalElement.text().replace(/[^\d.,]/g, '');
            return parseFloat(totalText) || 0;
        }
        
        getOrderId() {
            return epg_paypal_params.order_id || Math.random().toString(36).substr(2, 9);
        }
        
        getOrderItems(carbonOffset) {
            const items = [];
            
            // Add cart items
            $('.cart_item, .order_item').each(function() {
                const name = $(this).find('.product-name, .item-name').text().trim();
                const price = $(this).find('.amount').text().replace(/[^\d.,]/g, '');
                const quantity = $(this).find('.qty').text() || '1';
                
                if (name && price) {
                    items.push({
                        name: name.substring(0, 127),
                        unit_amount: {
                            value: parseFloat(price).toFixed(2),
                            currency_code: epg_paypal_params.currency
                        },
                        quantity: quantity.toString()
                    });
                }
            });
            
            // Add carbon offset as separate item
            if (carbonOffset > 0) {
                items.push({
                    name: 'Carbon Offset Contribution',
                    unit_amount: {
                        value: carbonOffset.toFixed(2),
                        currency_code: epg_paypal_params.currency
                    },
                    quantity: '1',
                    description: 'Environmental project funding'
                });
            }
            
            return items;
        }
        
        showEnvironmentalImpact() {
            const impactHtml = `
                <div class="epg-environmental-impact-summary">
                    <h4>🌍 Environmental Impact Summary</h4>
                    <div class="epg-impact-grid">
                        <div class="epg-impact-item">
                            <span class="epg-impact-label">Payment Method:</span>
                            <span class="epg-impact-value">PayPal (Digital)</span>
                        </div>
                        <div class="epg-impact-item">
                            <span class="epg-impact-label">Carbon Rating:</span>
                            <span class="epg-impact-value epg-rating-low">🌿 Low Impact</span>
                        </div>
                        <div class="epg-impact-item">
                            <span class="epg-impact-label">Offset Included:</span>
                            <span class="epg-impact-value">✅ Yes</span>
                        </div>
                    </div>
                </div>
            `;
            
            $('.payment_method_epg_paypal_enhanced').find('.epg-environmental-impact-summary').remove();
            $('.payment_method_epg_paypal_enhanced').append(impactHtml);
        }
        
        toggleCarbonOffset() {
            const isEnabled = $('.epg-carbon-offset-toggle').prop('checked');
            epg_paypal_params.carbon_offset_enabled = isEnabled;
            this.updateEnvironmentalData();
            this.initPayPalButtons(); // Refresh buttons with new pricing
        }
        
        showSuccessMessage(details) {
            const message = `
                <div class="epg-success-message">
                    <h3>🎉 Payment Successful!</h3>
                    <p>Transaction ID: ${details.id}</p>
                    <p>✅ Carbon offset contribution processed</p>
                    <p>🌱 Thank you for supporting environmental causes!</p>
                </div>
            `;
            
            this.showNotification(message, 'success');
        }
        
        showErrorMessage(message) {
            this.showNotification(`
                <div class="epg-error-message">
                    <h3>❌ Payment Failed</h3>
                    <p>${message}</p>
                </div>
            `, 'error');
        }
        
        showInfoMessage(message) {
            this.showNotification(`
                <div class="epg-info-message">
                    <h3>ℹ️ Information</h3>
                    <p>${message}</p>
                </div>
            `, 'info');
        }
        
        showNotification(html, type) {
            const notification = $(`
                <div class="epg-notification epg-notification-${type}">
                    ${html}
                    <button class="epg-notification-close">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            notification.fadeIn().delay(5000).fadeOut(() => {
                notification.remove();
            });
            
            notification.find('.epg-notification-close').on('click', () => {
                notification.fadeOut(() => notification.remove());
            });
        }
        
        redirectToThankYou(details) {
            setTimeout(() => {
                window.location.href = epg_paypal_params.return_url + 
                                     '&transaction_id=' + details.id;
            }, 2000);
        }
    }
    
    // Initialize when DOM is ready
    $(document).ready(() => {
        if (typeof epg_paypal_params !== 'undefined') {
            new EPGPayPalEnhanced();
        }
    });
    
})(jQuery);
