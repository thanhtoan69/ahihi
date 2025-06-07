/**
 * Environmental Payment Gateway - Coinbase Commerce Frontend
 * 
 * Handles Coinbase Commerce cryptocurrency payments
 * Includes environmental impact tracking and carbon offset options
 */

class EPGCoinbaseGateway {
    constructor() {
        this.chargeId = null;
        this.currentOrder = null;
        this.paymentInProgress = false;
        this.checkInterval = null;
        this.supportedCurrencies = {
            'BTC': {
                name: 'Bitcoin',
                symbol: 'BTC',
                environmental_rating: 'very_high',
                carbon_per_tx: 707.0
            },
            'ETH': {
                name: 'Ethereum',
                symbol: 'ETH',
                environmental_rating: 'high',
                carbon_per_tx: 60.0
            },
            'LTC': {
                name: 'Litecoin',
                symbol: 'LTC',
                environmental_rating: 'medium',
                carbon_per_tx: 15.0
            },
            'BCH': {
                name: 'Bitcoin Cash',
                symbol: 'BCH',
                environmental_rating: 'high',
                carbon_per_tx: 55.0
            },
            'USDC': {
                name: 'USD Coin',
                symbol: 'USDC',
                environmental_rating: 'high',
                carbon_per_tx: 60.0
            },
            'DAI': {
                name: 'Dai',
                symbol: 'DAI',
                environmental_rating: 'high',
                carbon_per_tx: 60.0
            }
        };
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadCoinbaseSDK();
        this.displayEnvironmentalInfo();
    }

    setupEventListeners() {
        // Payment Method Selection
        jQuery(document).on('change', '.epg-coinbase-currency-select', (e) => {
            this.handleCurrencyChange(e.target.value);
        });

        // Carbon Offset Toggle
        jQuery(document).on('change', '.epg-coinbase-carbon-offset', (e) => {
            this.updateCarbonOffset(e.target.checked);
        });

        // Payment Button
        jQuery(document).on('click', '.epg-coinbase-pay-button', (e) => {
            e.preventDefault();
            this.initializePayment();
        });

        // Eco-friendly Alternative
        jQuery(document).on('click', '.epg-coinbase-eco-alternative', (e) => {
            e.preventDefault();
            this.showEcoAlternatives();
        });

        // QR Code Toggle
        jQuery(document).on('click', '.epg-coinbase-qr-toggle', (e) => {
            e.preventDefault();
            this.toggleQRCode();
        });

        // Payment Status Check
        jQuery(document).on('click', '.epg-coinbase-check-payment', (e) => {
            e.preventDefault();
            this.checkPaymentStatus();
        });
    }

    async loadCoinbaseSDK() {
        // Load Coinbase Commerce SDK if not already loaded
        if (typeof CoinbaseCommerce === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://commerce.coinbase.com/v1/commerce.js';
            script.onload = () => {
                this.coinbaseReady = true;
                this.initializeCoinbaseCommerce();
            };
            document.head.appendChild(script);
        } else {
            this.coinbaseReady = true;
            this.initializeCoinbaseCommerce();
        }
    }

    initializeCoinbaseCommerce() {
        if (typeof CoinbaseCommerce !== 'undefined' && epg_coinbase_vars.api_key) {
            CoinbaseCommerce.init({
                apiKey: epg_coinbase_vars.api_key,
                environment: epg_coinbase_vars.environment || 'production'
            });
        }
    }

    handleCurrencyChange(currency) {
        const currencyData = this.supportedCurrencies[currency];
        if (!currencyData) return;

        this.updateCurrencyInfo(currencyData);
        this.updateEnvironmentalImpact(currencyData);
        this.calculateTotal();
    }

    updateCurrencyInfo(currency) {
        const currencyInfo = jQuery('.epg-coinbase-currency-info');
        currencyInfo.html(`
            <div class="currency-details">
                <div class="currency-header">
                    <img src="${epg_coinbase_vars.plugin_url}/assets/images/${currency.symbol.toLowerCase()}-logo.png" 
                         alt="${currency.name}" class="currency-logo">
                    <div class="currency-name">
                        <h4>${currency.name}</h4>
                        <span class="symbol">${currency.symbol}</span>
                    </div>
                </div>
                <div class="environmental-rating rating-${currency.environmental_rating}">
                    <span class="rating-label">Environmental Impact:</span>
                    <span class="rating-value">${currency.environmental_rating.replace('_', ' ').toUpperCase()}</span>
                </div>
            </div>
        `).show();
    }

    updateEnvironmentalImpact(currency) {
        const impactContainer = jQuery('.epg-coinbase-environmental-impact');
        const carbonFootprint = currency.carbon_per_tx;
        const offsetCost = carbonFootprint * 0.02; // $0.02 per kg CO₂
        
        let impactClass = 'impact-' + currency.environmental_rating;
        let warningHtml = '';
        let recommendationHtml = '';
        
        if (currency.environmental_rating === 'very_high' || currency.environmental_rating === 'high') {
            warningHtml = `
                <div class="environmental-warning">
                    <span class="warning-icon">⚠️</span>
                    <span class="warning-text">High environmental impact cryptocurrency</span>
                </div>
            `;
            
            recommendationHtml = `
                <div class="environmental-recommendation">
                    <h4>🌱 Consider Eco-Friendly Alternatives</h4>
                    <p>This cryptocurrency has high energy consumption. Consider:</p>
                    <ul>
                        <li>Using Litecoin (LTC) - 75% less energy than Bitcoin</li>
                        <li>Enabling automatic carbon offset (+$${offsetCost.toFixed(2)})</li>
                        <li>Exploring renewable energy powered mining pools</li>
                    </ul>
                    <button class="epg-coinbase-eco-alternative">View Eco-Friendly Options</button>
                </div>
            `;
        }
        
        impactContainer.html(`
            <div class="impact-summary ${impactClass}">
                ${warningHtml}
                <div class="carbon-footprint">
                    <span class="icon">🌍</span>
                    <div class="footprint-details">
                        <span class="amount">${carbonFootprint} kg CO₂</span>
                        <span class="description">per transaction</span>
                    </div>
                </div>
                <div class="offset-option">
                    <label class="offset-checkbox">
                        <input type="checkbox" class="epg-coinbase-carbon-offset" 
                               ${carbonFootprint > 50 ? 'checked' : ''}>
                        <span class="checkmark"></span>
                        <span class="offset-text">
                            Offset carbon footprint 
                            <span class="offset-cost">(+$${offsetCost.toFixed(2)})</span>
                        </span>
                    </label>
                </div>
                <div class="impact-comparison">
                    <small>
                        Equivalent to ${(carbonFootprint / 0.4).toFixed(0)} km of car driving
                    </small>
                </div>
            </div>
            ${recommendationHtml}
        `).show();
    }

    showEcoAlternatives() {
        const modal = jQuery('.epg-coinbase-eco-modal');
        
        if (modal.length === 0) {
            jQuery('body').append(`
                <div class="epg-coinbase-eco-modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>🌱 Eco-Friendly Cryptocurrency Options</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="eco-comparison">
                                <div class="crypto-option recommended" data-currency="LTC">
                                    <div class="crypto-header">
                                        <img src="${epg_coinbase_vars.plugin_url}/assets/images/ltc-logo.png" 
                                             alt="Litecoin" class="crypto-logo">
                                        <div class="crypto-info">
                                            <h4>Litecoin (LTC)</h4>
                                            <span class="eco-badge">🌱 RECOMMENDED</span>
                                        </div>
                                    </div>
                                    <div class="crypto-stats">
                                        <div class="stat">⚡ Energy: 75% less than Bitcoin</div>
                                        <div class="stat">🌍 CO₂: 15kg per transaction</div>
                                        <div class="stat">⏱️ Speed: 2.5 minutes</div>
                                    </div>
                                    <button class="select-crypto">Select Litecoin</button>
                                </div>
                                
                                <div class="crypto-option" data-currency="ETH">
                                    <div class="crypto-header">
                                        <img src="${epg_coinbase_vars.plugin_url}/assets/images/eth-logo.png" 
                                             alt="Ethereum" class="crypto-logo">
                                        <div class="crypto-info">
                                            <h4>Ethereum (ETH)</h4>
                                            <span class="eco-badge">🟡 MODERATE</span>
                                        </div>
                                    </div>
                                    <div class="crypto-stats">
                                        <div class="stat">⚡ Energy: Proof of Stake</div>
                                        <div class="stat">🌍 CO₂: 60kg per transaction</div>
                                        <div class="stat">⏱️ Speed: 15 seconds</div>
                                    </div>
                                    <button class="select-crypto">Select Ethereum</button>
                                </div>
                            </div>
                            
                            <div class="environmental-tips">
                                <h4>💡 Environmental Tips</h4>
                                <ul>
                                    <li>Choose cryptocurrencies with Proof of Stake consensus</li>
                                    <li>Enable carbon offset for high-impact payments</li>
                                    <li>Consider timing transactions during renewable energy peaks</li>
                                    <li>Support mining operations powered by renewable energy</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            // Modal event listeners
            jQuery('.modal-close, .epg-coinbase-eco-modal').on('click', (e) => {
                if (e.target === e.currentTarget) {
                    jQuery('.epg-coinbase-eco-modal').remove();
                }
            });
            
            jQuery('.select-crypto').on('click', (e) => {
                const currency = jQuery(e.target).closest('.crypto-option').data('currency');
                jQuery('.epg-coinbase-currency-select').val(currency).trigger('change');
                jQuery('.epg-coinbase-eco-modal').remove();
            });
        }
        
        modal.show();
    }

    updateCarbonOffset(enabled) {
        const selectedCurrency = jQuery('.epg-coinbase-currency-select').val();
        const currency = this.supportedCurrencies[selectedCurrency];
        
        if (!currency) return;
        
        const offsetCost = enabled ? currency.carbon_per_tx * 0.02 : 0;
        this.calculateTotal(offsetCost);
        
        // Update offset display
        const offsetDisplay = jQuery('.epg-coinbase-offset-summary');
        if (enabled) {
            offsetDisplay.html(`
                <div class="offset-active">
                    <span class="offset-icon">🌱</span>
                    <span class="offset-text">
                        Carbon offset enabled: ${currency.carbon_per_tx}kg CO₂ 
                        <span class="offset-cost">(+$${offsetCost.toFixed(2)})</span>
                    </span>
                </div>
            `).show();
        } else {
            offsetDisplay.hide();
        }
    }

    calculateTotal(offsetCost = 0) {
        if (!this.currentOrder) return;
        
        const baseAmount = parseFloat(this.currentOrder.amount);
        const total = baseAmount + offsetCost;
        
        const totalDisplay = jQuery('.epg-coinbase-payment-total');
        totalDisplay.html(`
            <div class="total-breakdown">
                <div class="line-item">
                    <span class="item-label">Order Amount:</span>
                    <span class="item-value">$${baseAmount.toFixed(2)}</span>
                </div>
                ${offsetCost > 0 ? `
                <div class="line-item offset-item">
                    <span class="item-label">Carbon Offset:</span>
                    <span class="item-value">+$${offsetCost.toFixed(2)}</span>
                </div>
                ` : ''}
                <div class="line-item total-item">
                    <span class="item-label"><strong>Total:</strong></span>
                    <span class="item-value"><strong>$${total.toFixed(2)}</strong></span>
                </div>
            </div>
        `);
    }

    async initializePayment() {
        if (this.paymentInProgress) return;
        
        const selectedCurrency = jQuery('.epg-coinbase-currency-select').val();
        if (!selectedCurrency) {
            alert('Please select a cryptocurrency');
            return;
        }
        
        this.paymentInProgress = true;
        this.updatePaymentStatus('creating');
        
        try {
            const carbonOffset = jQuery('.epg-coinbase-carbon-offset').is(':checked');
            const currency = this.supportedCurrencies[selectedCurrency];
            const offsetCost = carbonOffset ? currency.carbon_per_tx * 0.02 : 0;
            const totalAmount = parseFloat(this.currentOrder.amount) + offsetCost;
            
            // Create charge via backend
            const chargeData = await this.createCharge({
                amount: totalAmount,
                currency: 'USD',
                crypto_currency: selectedCurrency,
                carbon_offset: carbonOffset,
                order_id: this.currentOrder.id
            });
            
            this.chargeId = chargeData.charge_id;
            this.displayPaymentInterface(chargeData);
            this.startPaymentMonitoring();
            
        } catch (error) {
            console.error('Payment initialization failed:', error);
            this.updatePaymentStatus('failed');
            this.paymentInProgress = false;
        }
    }

    async createCharge(chargeData) {
        try {
            const response = await fetch(epg_coinbase_vars.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'epg_create_coinbase_charge',
                    nonce: epg_coinbase_vars.nonce,
                    charge_data: JSON.stringify(chargeData)
                })
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.data);
            }
            
            return result.data;
            
        } catch (error) {
            console.error('Charge creation failed:', error);
            throw error;
        }
    }

    displayPaymentInterface(chargeData) {
        const paymentInterface = jQuery('.epg-coinbase-payment-interface');
        
        paymentInterface.html(`
            <div class="payment-details">
                <div class="payment-header">
                    <h3>Complete Your Payment</h3>
                    <div class="payment-amount">
                        <span class="crypto-amount">${chargeData.crypto_amount} ${chargeData.crypto_currency}</span>
                        <span class="usd-amount">($${chargeData.usd_amount})</span>
                    </div>
                </div>
                
                <div class="payment-methods">
                    <div class="payment-method active" id="qr-method">
                        <div class="method-header">
                            <span class="method-icon">📱</span>
                            <span class="method-title">Scan QR Code</span>
                        </div>
                        <div class="qr-code-container">
                            <img src="${chargeData.qr_code_url}" alt="Payment QR Code" class="qr-code">
                            <p class="qr-instructions">
                                Scan this QR code with your ${chargeData.crypto_currency} wallet
                            </p>
                        </div>
                    </div>
                    
                    <div class="payment-method" id="address-method">
                        <div class="method-header">
                            <span class="method-icon">💳</span>
                            <span class="method-title">Manual Transfer</span>
                        </div>
                        <div class="address-container">
                            <div class="payment-address">
                                <label>Send ${chargeData.crypto_currency} to:</label>
                                <div class="address-field">
                                    <input type="text" value="${chargeData.address}" readonly class="address-input">
                                    <button class="copy-address" data-address="${chargeData.address}">Copy</button>
                                </div>
                            </div>
                            <div class="payment-amount-field">
                                <label>Exact Amount:</label>
                                <div class="amount-field">
                                    <input type="text" value="${chargeData.crypto_amount}" readonly class="amount-input">
                                    <button class="copy-amount" data-amount="${chargeData.crypto_amount}">Copy</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="payment-status">
                    <div class="status-indicator waiting">
                        <span class="status-icon">⏳</span>
                        <span class="status-text">Waiting for payment...</span>
                    </div>
                    <div class="payment-timer">
                        <span>Payment expires in: </span>
                        <span class="timer-countdown">${chargeData.expires_in}</span>
                    </div>
                </div>
                
                <div class="payment-actions">
                    <button class="epg-coinbase-check-payment">Check Payment Status</button>
                    <button class="epg-coinbase-qr-toggle">Toggle QR/Address</button>
                </div>
            </div>
        `).show();
        
        this.setupPaymentInterface();
        this.startPaymentTimer(chargeData.expires_in);
    }

    setupPaymentInterface() {
        // Copy address functionality
        jQuery('.copy-address, .copy-amount').on('click', function() {
            const textToCopy = jQuery(this).data('address') || jQuery(this).data('amount');
            navigator.clipboard.writeText(textToCopy).then(() => {
                jQuery(this).text('Copied!').addClass('copied');
                setTimeout(() => {
                    jQuery(this).text('Copy').removeClass('copied');
                }, 2000);
            });
        });
    }

    toggleQRCode() {
        const qrMethod = jQuery('#qr-method');
        const addressMethod = jQuery('#address-method');
        
        if (qrMethod.hasClass('active')) {
            qrMethod.removeClass('active');
            addressMethod.addClass('active');
        } else {
            addressMethod.removeClass('active');
            qrMethod.addClass('active');
        }
    }

    startPaymentTimer(expiresIn) {
        let timeLeft = expiresIn;
        const timerElement = jQuery('.timer-countdown');
        
        const timer = setInterval(() => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            timerElement.text(`${minutes}:${seconds.toString().padStart(2, '0')}`);
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                this.handlePaymentExpired();
            }
            
            timeLeft--;
        }, 1000);
    }

    startPaymentMonitoring() {
        this.checkInterval = setInterval(() => {
            this.checkPaymentStatus();
        }, 10000); // Check every 10 seconds
    }

    async checkPaymentStatus() {
        if (!this.chargeId) return;
        
        try {
            const response = await fetch(epg_coinbase_vars.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'epg_check_coinbase_payment',
                    nonce: epg_coinbase_vars.nonce,
                    charge_id: this.chargeId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                const status = result.data.status;
                this.updatePaymentStatus(status);
                
                if (status === 'completed' || status === 'confirmed') {
                    this.handlePaymentSuccess(result.data);
                } else if (status === 'failed' || status === 'expired') {
                    this.handlePaymentFailure(status);
                }
            }
            
        } catch (error) {
            console.error('Payment status check failed:', error);
        }
    }

    updatePaymentStatus(status) {
        const statusIndicator = jQuery('.status-indicator');
        const statusIcon = statusIndicator.find('.status-icon');
        const statusText = statusIndicator.find('.status-text');
        
        const statusConfig = {
            'waiting': { icon: '⏳', text: 'Waiting for payment...', class: 'waiting' },
            'pending': { icon: '🔄', text: 'Payment detected, confirming...', class: 'pending' },
            'confirmed': { icon: '✅', text: 'Payment confirmed!', class: 'confirmed' },
            'completed': { icon: '✅', text: 'Payment completed successfully!', class: 'completed' },
            'failed': { icon: '❌', text: 'Payment failed', class: 'failed' },
            'expired': { icon: '⏰', text: 'Payment expired', class: 'expired' }
        };
        
        const config = statusConfig[status] || statusConfig['waiting'];
        
        statusIndicator.removeClass('waiting pending confirmed completed failed expired')
                      .addClass(config.class);
        statusIcon.text(config.icon);
        statusText.text(config.text);
    }

    handlePaymentSuccess(paymentData) {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
        
        this.paymentInProgress = false;
        
        // Show success message
        jQuery('.epg-coinbase-payment-interface').append(`
            <div class="payment-success">
                <div class="success-icon">🎉</div>
                <h3>Payment Successful!</h3>
                <div class="success-details">
                    <p>Transaction Hash: <code>${paymentData.transaction_hash}</code></p>
                    <p>Amount: ${paymentData.crypto_amount} ${paymentData.crypto_currency}</p>
                    ${paymentData.carbon_offset ? '<p>✅ Carbon footprint has been offset</p>' : ''}
                </div>
                <p>Redirecting to confirmation page...</p>
            </div>
        `);
        
        // Redirect after delay
        setTimeout(() => {
            window.location.href = this.currentOrder.return_url;
        }, 3000);
    }

    handlePaymentFailure(status) {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
        
        this.paymentInProgress = false;
        
        const failureMessage = status === 'expired' ? 
            'Payment time has expired. Please try again.' :
            'Payment failed. Please try again or contact support.';
        
        jQuery('.epg-coinbase-payment-interface').append(`
            <div class="payment-failure">
                <div class="failure-icon">❌</div>
                <h3>Payment ${status === 'expired' ? 'Expired' : 'Failed'}</h3>
                <p>${failureMessage}</p>
                <button class="retry-payment" onclick="location.reload()">Try Again</button>
            </div>
        `);
    }

    handlePaymentExpired() {
        this.handlePaymentFailure('expired');
    }

    displayEnvironmentalInfo() {
        const envInfo = jQuery('.epg-coinbase-environmental-info');
        envInfo.html(`
            <div class="environmental-header">
                <h3>🌍 Environmental Impact Information</h3>
                <p>Make informed choices about cryptocurrency environmental impact</p>
            </div>
            <div class="environmental-facts">
                <div class="fact-card">
                    <span class="fact-icon">⚡</span>
                    <div class="fact-content">
                        <h4>Energy Consumption</h4>
                        <p>Different cryptocurrencies have varying energy requirements</p>
                    </div>
                </div>
                <div class="fact-card">
                    <span class="fact-icon">🌱</span>
                    <div class="fact-content">
                        <h4>Carbon Offsetting</h4>
                        <p>We partner with verified carbon offset programs</p>
                    </div>
                </div>
                <div class="fact-card">
                    <span class="fact-icon">🔄</span>
                    <div class="fact-content">
                        <h4>Renewable Energy</h4>
                        <p>Supporting mining operations powered by clean energy</p>
                    </div>
                </div>
            </div>
        `).show();
    }

    // Initialize order data
    setOrderData(orderData) {
        this.currentOrder = orderData;
        this.calculateTotal();
    }
}

// Initialize when document is ready
jQuery(document).ready(function() {
    if (typeof epg_coinbase_vars !== 'undefined') {
        window.epgCoinbaseGateway = new EPGCoinbaseGateway();
        
        // Set order data if available
        if (typeof epg_coinbase_order !== 'undefined') {
            window.epgCoinbaseGateway.setOrderData(epg_coinbase_order);
        }
    }
});
