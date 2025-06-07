/**
 * Wise Gateway Frontend JavaScript
 * 
 * Handles Wise international money transfer interface with
 * environmental features and currency conversion
 * 
 * @package EnvironmentalPaymentGateway
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    class EPGWiseGateway {
        constructor() {
            this.exchangeRates = {};
            this.environmentalData = {};
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.loadExchangeRates();
            this.setupCurrencyConverter();
            this.initEnvironmentalFeatures();
        }
        
        bindEvents() {
            $(document).on('change', '#payment_method_epg_wise', () => {
                this.showWiseInterface();
            });
            
            $(document).on('change', '.epg-wise-currency-select', () => {
                this.updateCurrencyConversion();
            });
            
            $(document).on('change', '.epg-wise-fee-option', () => {
                this.updateFeeCalculation();
            });
            
            $(document).on('click', '.epg-wise-green-routing', () => {
                this.toggleGreenRouting();
            });
            
            $(document).on('click', '.epg-wise-submit', () => {
                this.submitWisePayment();
            });
        }
        
        showWiseInterface() {
            const interfaceHtml = this.buildWiseInterface();
            $('.payment_method_epg_wise .payment_box').html(interfaceHtml);
            this.updateCurrencyConversion();
        }
        
        buildWiseInterface() {
            return `
                <div class="epg-wise-payment-interface">
                    <div class="epg-wise-header">
                        <h4>🌍 International Money Transfer</h4>
                        <p>Send money globally with transparent fees and great exchange rates</p>
                    </div>
                    
                    <div class="epg-wise-currency-section">
                        <div class="epg-currency-row">
                            <div class="epg-currency-from">
                                <label>You send</label>
                                <div class="epg-amount-input">
                                    <input type="text" id="epg-wise-amount" value="${this.getOrderTotal()}" readonly>
                                    <select id="epg-wise-from-currency">
                                        <option value="${epg_wise_params.base_currency}">${epg_wise_params.base_currency}</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="epg-currency-exchange">
                                <div class="epg-exchange-icon">⇄</div>
                                <div class="epg-exchange-rate">
                                    <span id="epg-wise-rate">Loading...</span>
                                </div>
                            </div>
                            
                            <div class="epg-currency-to">
                                <label>Recipient gets</label>
                                <div class="epg-amount-input">
                                    <input type="text" id="epg-wise-converted-amount" readonly>
                                    <select id="epg-wise-to-currency" class="epg-wise-currency-select">
                                        ${this.buildCurrencyOptions()}
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="epg-wise-fees-section">
                        <h5>💰 Fee Options</h5>
                        <div class="epg-fee-options">
                            <label class="epg-fee-option">
                                <input type="radio" name="wise_fee_option" value="customer" class="epg-wise-fee-option" checked>
                                <span>Customer pays fees</span>
                                <small>Most transparent option</small>
                            </label>
                            <label class="epg-fee-option">
                                <input type="radio" name="wise_fee_option" value="merchant" class="epg-wise-fee-option">
                                <span>Merchant absorbs fees</span>
                                <small>Simpler for customer</small>
                            </label>
                            <label class="epg-fee-option">
                                <input type="radio" name="wise_fee_option" value="markup" class="epg-wise-fee-option">
                                <span>Small markup (${epg_wise_params.markup_percentage}%)</span>
                                <small>Balanced approach</small>
                            </label>
                        </div>
                        
                        <div class="epg-fee-breakdown">
                            <div class="epg-fee-line">
                                <span>Transfer fee:</span>
                                <span id="epg-wise-transfer-fee">$0.00</span>
                            </div>
                            <div class="epg-fee-line">
                                <span>Exchange rate margin:</span>
                                <span id="epg-wise-rate-margin">0.35%</span>
                            </div>
                            <div class="epg-fee-line total">
                                <span>Total cost:</span>
                                <span id="epg-wise-total-cost">$0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="epg-wise-environmental-section">
                        <h5>🌱 Environmental Options</h5>
                        <div class="epg-environmental-options">
                            <label class="epg-env-option">
                                <input type="checkbox" class="epg-wise-green-routing" ${epg_wise_params.green_routing_default ? 'checked' : ''}>
                                <span>🌿 Green routing preference</span>
                                <small>Choose environmentally friendly transfer routes when available</small>
                            </label>
                            <label class="epg-env-option">
                                <input type="checkbox" class="epg-wise-carbon-neutral" checked>
                                <span>🌍 Carbon neutral transfer</span>
                                <small>Offset transfer emissions (${epg_wise_params.carbon_offset_percentage}% of amount)</small>
                            </label>
                        </div>
                        
                        <div class="epg-environmental-impact">
                            <div class="epg-impact-summary">
                                <div class="epg-impact-item">
                                    <span class="epg-impact-icon">🌍</span>
                                    <div class="epg-impact-details">
                                        <strong>Environmental Impact</strong>
                                        <p>Carbon footprint: <span id="epg-wise-carbon-footprint">0.02 kg CO2</span></p>
                                    </div>
                                </div>
                                <div class="epg-impact-item">
                                    <span class="epg-impact-icon">🌱</span>
                                    <div class="epg-impact-details">
                                        <strong>Offset Contribution</strong>
                                        <p>Amount: $<span id="epg-wise-offset-amount">0.50</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="epg-wise-delivery-section">
                        <h5>⚡ Delivery Speed</h5>
                        <div class="epg-delivery-options">
                            <label class="epg-delivery-option selected">
                                <input type="radio" name="wise_delivery" value="standard" checked>
                                <div class="epg-delivery-details">
                                    <strong>Standard Transfer</strong>
                                    <p>1-2 business days • Best rate</p>
                                    <span class="epg-delivery-price">No extra fee</span>
                                </div>
                            </label>
                            <label class="epg-delivery-option">
                                <input type="radio" name="wise_delivery" value="fast">
                                <div class="epg-delivery-details">
                                    <strong>Fast Transfer</strong>
                                    <p>Within hours • Higher cost</p>
                                    <span class="epg-delivery-price">+$5.00</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="epg-wise-actions">
                        <button type="button" class="epg-wise-submit button">
                            Continue with Wise
                        </button>
                        <p class="epg-wise-disclaimer">
                            <small>You'll be redirected to Wise to complete the transfer securely.</small>
                        </p>
                    </div>
                </div>
            `;
        }
        
        buildCurrencyOptions() {
            const currencies = epg_wise_params.supported_currencies || {};
            let options = '';
            
            Object.keys(currencies).forEach(code => {
                const currency = currencies[code];
                options += `<option value="${code}">${code} - ${currency.name}</option>`;
            });
            
            return options;
        }
        
        loadExchangeRates() {
            $.ajax({
                url: epg_wise_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'epg_get_wise_rates',
                    nonce: epg_wise_params.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.exchangeRates = response.data;
                        this.updateCurrencyConversion();
                    }
                },
                error: () => {
                    console.error('Failed to load exchange rates');
                }
            });
        }
        
        updateCurrencyConversion() {
            const fromCurrency = $('#epg-wise-from-currency').val();
            const toCurrency = $('#epg-wise-to-currency').val();
            const amount = parseFloat($('#epg-wise-amount').val()) || 0;
            
            if (!fromCurrency || !toCurrency || !this.exchangeRates[toCurrency]) {
                return;
            }
            
            const rate = this.exchangeRates[toCurrency].rate || 1;
            const convertedAmount = amount * rate;
            
            $('#epg-wise-converted-amount').val(convertedAmount.toFixed(2));
            $('#epg-wise-rate').text(`1 ${fromCurrency} = ${rate.toFixed(4)} ${toCurrency}`);
            
            this.updateFeeCalculation();
            this.updateEnvironmentalImpact();
        }
        
        updateFeeCalculation() {
            const amount = parseFloat($('#epg-wise-amount').val()) || 0;
            const feeOption = $('input[name="wise_fee_option"]:checked').val();
            const deliveryOption = $('input[name="wise_delivery"]:checked').val();
            
            let transferFee = this.calculateTransferFee(amount);
            let deliveryFee = deliveryOption === 'fast' ? 5.00 : 0;
            let totalCost = amount + transferFee + deliveryFee;
            
            if (feeOption === 'merchant') {
                transferFee = 0;
                totalCost = amount;
            } else if (feeOption === 'markup') {
                const markup = amount * (parseFloat(epg_wise_params.markup_percentage) / 100);
                transferFee = markup;
                totalCost = amount + markup + deliveryFee;
            }
            
            $('#epg-wise-transfer-fee').text('$' + transferFee.toFixed(2));
            $('#epg-wise-total-cost').text('$' + totalCost.toFixed(2));
        }
        
        calculateTransferFee(amount) {
            // Wise fee structure approximation
            if (amount <= 100) return 1.50;
            if (amount <= 500) return 3.00;
            if (amount <= 1000) return 5.00;
            return amount * 0.005; // 0.5% for larger amounts
        }
        
        updateEnvironmentalImpact() {
            const amount = parseFloat($('#epg-wise-amount').val()) || 0;
            const isGreenRouting = $('.epg-wise-green-routing').prop('checked');
            const isCarbonNeutral = $('.epg-wise-carbon-neutral').prop('checked');
            
            // Calculate environmental impact
            let carbonFootprint = amount * 0.0001; // Base carbon footprint
            if (isGreenRouting) {
                carbonFootprint *= 0.7; // 30% reduction with green routing
            }
            
            const offsetAmount = isCarbonNeutral ? 
                amount * (parseFloat(epg_wise_params.carbon_offset_percentage) / 100) : 0;
            
            $('#epg-wise-carbon-footprint').text(carbonFootprint.toFixed(4) + ' kg CO2');
            $('#epg-wise-offset-amount').text(offsetAmount.toFixed(2));
        }
        
        setupCurrencyConverter() {
            // Real-time currency conversion display
            $(document).on('input', '#epg-wise-amount', () => {
                this.updateCurrencyConversion();
            });
            
            $(document).on('change', 'input[name="wise_delivery"]', () => {
                this.updateFeeCalculation();
                $('.epg-delivery-option').removeClass('selected');
                $('input[name="wise_delivery"]:checked').closest('.epg-delivery-option').addClass('selected');
            });
        }
        
        initEnvironmentalFeatures() {
            $(document).on('change', '.epg-wise-green-routing, .epg-wise-carbon-neutral', () => {
                this.updateEnvironmentalImpact();
            });
        }
        
        toggleGreenRouting() {
            const isEnabled = $('.epg-wise-green-routing').prop('checked');
            
            if (isEnabled) {
                this.showGreenRoutingBenefits();
            }
            
            this.updateEnvironmentalImpact();
        }
        
        showGreenRoutingBenefits() {
            const benefitsHtml = `
                <div class="epg-green-benefits-popup">
                    <h4>🌿 Green Routing Benefits</h4>
                    <ul>
                        <li>✅ Lower carbon footprint</li>
                        <li>✅ Supports renewable energy infrastructure</li>
                        <li>✅ Partners with eco-friendly correspondent banks</li>
                        <li>✅ May qualify for green finance incentives</li>
                    </ul>
                    <button class="epg-close-popup">Got it!</button>
                </div>
            `;
            
            $('body').append(benefitsHtml);
            
            $('.epg-close-popup').on('click', () => {
                $('.epg-green-benefits-popup').fadeOut(() => {
                    $('.epg-green-benefits-popup').remove();
                });
            });
        }
        
        submitWisePayment() {
            const paymentData = this.collectPaymentData();
            
            if (!this.validatePaymentData(paymentData)) {
                return;
            }
            
            this.showProcessingIndicator();
            
            $.ajax({
                url: epg_wise_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'epg_create_wise_payment',
                    payment_data: paymentData,
                    nonce: epg_wise_params.nonce
                },
                success: (response) => {
                    this.hideProcessingIndicator();
                    
                    if (response.success) {
                        window.location.href = response.data.redirect_url;
                    } else {
                        this.showError(response.data.message || 'Payment creation failed');
                    }
                },
                error: () => {
                    this.hideProcessingIndicator();
                    this.showError('Network error. Please try again.');
                }
            });
        }
        
        collectPaymentData() {
            return {
                from_currency: $('#epg-wise-from-currency').val(),
                to_currency: $('#epg-wise-to-currency').val(),
                amount: $('#epg-wise-amount').val(),
                converted_amount: $('#epg-wise-converted-amount').val(),
                fee_option: $('input[name="wise_fee_option"]:checked').val(),
                delivery_option: $('input[name="wise_delivery"]:checked').val(),
                green_routing: $('.epg-wise-green-routing').prop('checked'),
                carbon_neutral: $('.epg-wise-carbon-neutral').prop('checked'),
                exchange_rate: $('#epg-wise-rate').text(),
                total_cost: $('#epg-wise-total-cost').text()
            };
        }
        
        validatePaymentData(data) {
            if (!data.to_currency || data.to_currency === data.from_currency) {
                this.showError('Please select a different target currency');
                return false;
            }
            
            if (parseFloat(data.amount) <= 0) {
                this.showError('Invalid payment amount');
                return false;
            }
            
            return true;
        }
        
        getOrderTotal() {
            const totalElement = $('.order-total .amount, .cart-total .amount').last();
            const totalText = totalElement.text().replace(/[^\d.,]/g, '');
            return parseFloat(totalText) || 0;
        }
        
        showProcessingIndicator() {
            $('.epg-wise-submit').prop('disabled', true).text('Processing...');
        }
        
        hideProcessingIndicator() {
            $('.epg-wise-submit').prop('disabled', false).text('Continue with Wise');
        }
        
        showError(message) {
            const errorHtml = `
                <div class="epg-wise-error">
                    <p><strong>Error:</strong> ${message}</p>
                </div>
            `;
            
            $('.epg-wise-payment-interface').prepend(errorHtml);
            
            setTimeout(() => {
                $('.epg-wise-error').fadeOut(() => {
                    $('.epg-wise-error').remove();
                });
            }, 5000);
        }
    }
    
    // Initialize when DOM is ready
    $(document).ready(() => {
        if (typeof epg_wise_params !== 'undefined') {
            new EPGWiseGateway();
        }
    });
    
})(jQuery);
