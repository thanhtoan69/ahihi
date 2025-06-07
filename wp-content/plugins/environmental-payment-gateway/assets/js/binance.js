/**
 * Environmental Payment Gateway - Binance Pay Frontend
 * 
 * Handles Binance Pay cryptocurrency payments
 * Includes environmental impact tracking and carbon offset options
 */

class EPGBinanceGateway {
    constructor() {
        this.orderId = null;
        this.currentOrder = null;
        this.paymentInProgress = false;
        this.checkInterval = null;
        this.supportedCurrencies = {
            'BTC': {
                name: 'Bitcoin',
                symbol: 'BTC',
                environmental_rating: 'very_high',
                carbon_per_tx: 707.0,
                icon: 'btc-logo.png'
            },
            'ETH': {
                name: 'Ethereum',
                symbol: 'ETH',
                environmental_rating: 'high',
                carbon_per_tx: 60.0,
                icon: 'eth-logo.png'
            },
            'BNB': {
                name: 'Binance Coin',
                symbol: 'BNB',
                environmental_rating: 'medium',
                carbon_per_tx: 8.5,
                icon: 'bnb-logo.png'
            },
            'USDT': {
                name: 'Tether',
                symbol: 'USDT',
                environmental_rating: 'high',
                carbon_per_tx: 60.0,
                icon: 'usdt-logo.png'
            },
            'USDC': {
                name: 'USD Coin',
                symbol: 'USDC',
                environmental_rating: 'high',
                carbon_per_tx: 60.0,
                icon: 'usdc-logo.png'
            },
            'BUSD': {
                name: 'Binance USD',
                symbol: 'BUSD',
                environmental_rating: 'medium',
                carbon_per_tx: 8.5,
                icon: 'busd-logo.png'
            },
            'ADA': {
                name: 'Cardano',
                symbol: 'ADA',
                environmental_rating: 'very_low',
                carbon_per_tx: 0.5,
                icon: 'ada-logo.png'
            },
            'DOT': {
                name: 'Polkadot',
                symbol: 'DOT',
                environmental_rating: 'low',
                carbon_per_tx: 2.0,
                icon: 'dot-logo.png'
            },
            'SOL': {
                name: 'Solana',
                symbol: 'SOL',
                environmental_rating: 'very_low',
                carbon_per_tx: 0.17,
                icon: 'sol-logo.png'
            },
            'MATIC': {
                name: 'Polygon',
                symbol: 'MATIC',
                environmental_rating: 'very_low',
                carbon_per_tx: 0.1,
                icon: 'matic-logo.png'
            }
        };
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.displayCurrencyOptions();
        this.displayEnvironmentalInfo();
    }

    setupEventListeners() {
        // Currency Selection
        jQuery(document).on('click', '.epg-binance-currency-option', (e) => {
            this.selectCurrency(e.currentTarget);
        });

        // Carbon Offset Toggle
        jQuery(document).on('change', '.epg-binance-carbon-offset', (e) => {
            this.updateCarbonOffset(e.target.checked);
        });

        // Payment Button
        jQuery(document).on('click', '.epg-binance-pay-button', (e) => {
            e.preventDefault();
            this.initializePayment();
        });

        // Eco-friendly Recommendations
        jQuery(document).on('click', '.epg-binance-eco-recommendation', (e) => {
            e.preventDefault();
            this.showEcoRecommendations();
        });

        // QR Code Toggle
        jQuery(document).on('click', '.epg-binance-qr-toggle', (e) => {
            e.preventDefault();
            this.toggleQRCode();
        });

        // Payment Status Check
        jQuery(document).on('click', '.epg-binance-check-payment', (e) => {
            e.preventDefault();
            this.checkPaymentStatus();
        });

        // Filter by Environmental Rating
        jQuery(document).on('change', '.epg-binance-eco-filter', (e) => {
            this.filterByEcoRating(e.target.value);
        });
    }

    displayCurrencyOptions() {
        const currencyContainer = jQuery('.epg-binance-currency-selection');
        
        let currencyOptionsHtml = `
            <div class="currency-header">
                <h3>Select Cryptocurrency</h3>
                <div class="eco-filter-container">
                    <label for="eco-filter">Filter by Environmental Impact:</label>
                    <select class="epg-binance-eco-filter" id="eco-filter">
                        <option value="all">All Currencies</option>
                        <option value="very_low">Very Low Impact</option>
                        <option value="low">Low Impact</option>
                        <option value="medium">Medium Impact</option>
                        <option value="high">High Impact</option>
                        <option value="very_high">Very High Impact</option>
                    </select>
                </div>
            </div>
            <div class="currency-grid">
        `;
        
        Object.entries(this.supportedCurrencies).forEach(([symbol, currency]) => {
            const ecoClass = 'eco-' + currency.environmental_rating;
            const ecoLabel = currency.environmental_rating.replace('_', ' ').toUpperCase();
            
            currencyOptionsHtml += `
                <div class="epg-binance-currency-option ${ecoClass}" data-currency="${symbol}">
                    <div class="currency-card">
                        <div class="currency-icon">
                            <img src="${epg_binance_vars.plugin_url}/assets/images/${currency.icon}" 
                                 alt="${currency.name}" class="currency-logo">
                        </div>
                        <div class="currency-info">
                            <h4 class="currency-name">${currency.name}</h4>
                            <span class="currency-symbol">${currency.symbol}</span>
                        </div>
                        <div class="environmental-info">
                            <div class="eco-rating rating-${currency.environmental_rating}">
                                <span class="eco-label">${ecoLabel}</span>
                                <span class="eco-icon">${this.getEcoIcon(currency.environmental_rating)}</span>
                            </div>
                            <div class="carbon-footprint">
                                <span class="carbon-amount">${currency.carbon_per_tx}kg</span>
                                <span class="carbon-label">CO₂ per tx</span>
                            </div>
                        </div>
                        <div class="selection-indicator">
                            <span class="checkmark">✓</span>
                        </div>
                    </div>
                </div>
            `;
        });
        
        currencyOptionsHtml += '</div>';
        currencyContainer.html(currencyOptionsHtml);
    }

    getEcoIcon(rating) {
        const icons = {
            'very_low': '🌱',
            'low': '🟢',
            'medium': '🟡',
            'high': '🟠',
            'very_high': '🔴'
        };
        return icons[rating] || '⚪';
    }

    selectCurrency(element) {
        // Remove previous selection
        jQuery('.epg-binance-currency-option').removeClass('selected');
        
        // Add selection to clicked element
        jQuery(element).addClass('selected');
        
        const currency = jQuery(element).data('currency');
        const currencyData = this.supportedCurrencies[currency];
        
        this.updateSelectedCurrencyInfo(currencyData);
        this.updateEnvironmentalImpact(currencyData);
        this.calculateTotal();
        this.togglePaymentButton(true);
    }

    updateSelectedCurrencyInfo(currency) {
        const selectedInfo = jQuery('.epg-binance-selected-currency');
        selectedInfo.html(`
            <div class="selected-currency-display">
                <div class="currency-header">
                    <img src="${epg_binance_vars.plugin_url}/assets/images/${currency.icon}" 
                         alt="${currency.name}" class="selected-currency-logo">
                    <div class="selected-currency-details">
                        <h4>${currency.name}</h4>
                        <span class="symbol">${currency.symbol}</span>
                    </div>
                </div>
                <div class="environmental-summary">
                    <div class="eco-rating rating-${currency.environmental_rating}">
                        ${this.getEcoIcon(currency.environmental_rating)} ${currency.environmental_rating.replace('_', ' ').toUpperCase()} Impact
                    </div>
                    <div class="carbon-summary">
                        🌍 ${currency.carbon_per_tx}kg CO₂ per transaction
                    </div>
                </div>
            </div>
        `).show();
    }

    updateEnvironmentalImpact(currency) {
        const impactContainer = jQuery('.epg-binance-environmental-impact');
        const carbonFootprint = currency.carbon_per_tx;
        const offsetCost = carbonFootprint * 0.02; // $0.02 per kg CO₂
        
        let impactClass = 'impact-' + currency.environmental_rating;
        let recommendationHtml = '';
        let warningHtml = '';
        
        if (currency.environmental_rating === 'very_high' || currency.environmental_rating === 'high') {
            warningHtml = `
                <div class="environmental-warning">
                    <span class="warning-icon">⚠️</span>
                    <span class="warning-text">High environmental impact cryptocurrency</span>
                    <button class="epg-binance-eco-recommendation">View Eco-Friendly Alternatives</button>
                </div>
            `;
        }
        
        if (currency.environmental_rating !== 'very_low') {
            recommendationHtml = `
                <div class="environmental-recommendation">
                    <h4>🌱 Consider Lower Impact Options</h4>
                    <div class="eco-alternatives">
                        <div class="eco-alt" data-currency="ADA">
                            <span class="alt-name">Cardano (ADA)</span>
                            <span class="alt-impact">0.5kg CO₂</span>
                        </div>
                        <div class="eco-alt" data-currency="SOL">
                            <span class="alt-name">Solana (SOL)</span>
                            <span class="alt-impact">0.17kg CO₂</span>
                        </div>
                        <div class="eco-alt" data-currency="MATIC">
                            <span class="alt-name">Polygon (MATIC)</span>
                            <span class="alt-impact">0.1kg CO₂</span>
                        </div>
                    </div>
                </div>
            `;
        }
        
        impactContainer.html(`
            <div class="impact-analysis ${impactClass}">
                ${warningHtml}
                <div class="carbon-footprint-detailed">
                    <div class="footprint-main">
                        <span class="carbon-amount">${carbonFootprint}</span>
                        <span class="carbon-unit">kg CO₂</span>
                        <span class="carbon-label">per transaction</span>
                    </div>
                    <div class="footprint-comparison">
                        <small>
                            Equivalent to ${this.getCarbonComparison(carbonFootprint)}
                        </small>
                    </div>
                </div>
                
                <div class="offset-section">
                    <label class="offset-checkbox-container">
                        <input type="checkbox" class="epg-binance-carbon-offset" 
                               ${carbonFootprint > 10 ? 'checked' : ''}>
                        <span class="checkmark-custom"></span>
                        <div class="offset-text">
                            <span class="offset-main">Offset carbon footprint</span>
                            <span class="offset-cost">(+$${offsetCost.toFixed(2)})</span>
                            <small class="offset-description">
                                Support verified carbon reduction projects
                            </small>
                        </div>
                    </label>
                </div>
                
                ${recommendationHtml}
            </div>
        `).show();
        
        // Add click handlers for eco alternatives
        jQuery('.eco-alt').on('click', (e) => {
            const altCurrency = jQuery(e.currentTarget).data('currency');
            jQuery(`.epg-binance-currency-option[data-currency="${altCurrency}"]`).click();
        });
    }

    getCarbonComparison(carbonKg) {
        if (carbonKg < 1) {
            return `${(carbonKg * 2.5).toFixed(1)} km of car driving`;
        } else if (carbonKg < 50) {
            return `${(carbonKg * 2.5).toFixed(0)} km of car driving`;
        } else {
            return `${(carbonKg / 400).toFixed(1)} flights from NY to LA`;
        }
    }

    filterByEcoRating(rating) {
        const currencyOptions = jQuery('.epg-binance-currency-option');
        
        if (rating === 'all') {
            currencyOptions.show();
        } else {
            currencyOptions.hide();
            jQuery(`.epg-binance-currency-option.eco-${rating}`).show();
        }
    }

    showEcoRecommendations() {
        const modal = jQuery('.epg-binance-eco-modal');
        
        if (modal.length === 0) {
            const modalHtml = `
                <div class="epg-binance-eco-modal">
                    <div class="modal-overlay"></div>
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>🌱 Eco-Friendly Cryptocurrency Options</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="eco-comparison-grid">
                                ${this.generateEcoComparisonCards()}
                            </div>
                            <div class="environmental-education">
                                <h4>Why Choose Eco-Friendly Crypto?</h4>
                                <div class="edu-points">
                                    <div class="edu-point">
                                        <span class="edu-icon">⚡</span>
                                        <div class="edu-content">
                                            <h5>Lower Energy Consumption</h5>
                                            <p>Proof-of-Stake networks use 99% less energy than Proof-of-Work</p>
                                        </div>
                                    </div>
                                    <div class="edu-point">
                                        <span class="edu-icon">🌍</span>
                                        <div class="edu-content">
                                            <h5>Reduced Carbon Footprint</h5>
                                            <p>Help reduce the environmental impact of digital payments</p>
                                        </div>
                                    </div>
                                    <div class="edu-point">
                                        <span class="edu-icon">🚀</span>
                                        <div class="edu-content">
                                            <h5>Faster Transactions</h5>
                                            <p>Many eco-friendly networks also offer faster confirmation times</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            jQuery('body').append(modalHtml);
            
            // Modal event listeners
            jQuery('.modal-close, .modal-overlay').on('click', () => {
                jQuery('.epg-binance-eco-modal').remove();
            });
            
            jQuery('.select-eco-currency').on('click', (e) => {
                const currency = jQuery(e.target).data('currency');
                jQuery(`.epg-binance-currency-option[data-currency="${currency}"]`).click();
                jQuery('.epg-binance-eco-modal').remove();
            });
        }
        
        jQuery('.epg-binance-eco-modal').show();
    }

    generateEcoComparisonCards() {
        const ecoFriendly = ['ADA', 'SOL', 'MATIC', 'DOT'];
        let cardsHtml = '';
        
        ecoFriendly.forEach(symbol => {
            const currency = this.supportedCurrencies[symbol];
            cardsHtml += `
                <div class="eco-comparison-card">
                    <div class="card-header">
                        <img src="${epg_binance_vars.plugin_url}/assets/images/${currency.icon}" 
                             alt="${currency.name}" class="card-logo">
                        <div class="card-title">
                            <h4>${currency.name}</h4>
                            <span class="card-symbol">${currency.symbol}</span>
                        </div>
                        <div class="eco-badge eco-${currency.environmental_rating}">
                            ${this.getEcoIcon(currency.environmental_rating)} ECO-FRIENDLY
                        </div>
                    </div>
                    <div class="card-stats">
                        <div class="stat">
                            <span class="stat-label">CO₂ Impact:</span>
                            <span class="stat-value">${currency.carbon_per_tx}kg</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Energy Usage:</span>
                            <span class="stat-value">${currency.environmental_rating.replace('_', ' ')}</span>
                        </div>
                    </div>
                    <button class="select-eco-currency" data-currency="${symbol}">
                        Select ${currency.symbol}
                    </button>
                </div>
            `;
        });
        
        return cardsHtml;
    }

    updateCarbonOffset(enabled) {
        const selectedCurrency = jQuery('.epg-binance-currency-option.selected').data('currency');
        const currency = this.supportedCurrencies[selectedCurrency];
        
        if (!currency) return;
        
        const offsetCost = enabled ? currency.carbon_per_tx * 0.02 : 0;
        this.calculateTotal(offsetCost);
        
        // Update offset display
        const offsetSummary = jQuery('.epg-binance-offset-summary');
        if (enabled) {
            offsetSummary.html(`
                <div class="offset-active-display">
                    <div class="offset-header">
                        <span class="offset-icon">🌱</span>
                        <span class="offset-title">Carbon Offset Enabled</span>
                    </div>
                    <div class="offset-details">
                        <div class="offset-amount">${currency.carbon_per_tx}kg CO₂ will be offset</div>
                        <div class="offset-cost">Additional cost: $${offsetCost.toFixed(2)}</div>
                    </div>
                    <div class="offset-projects">
                        <small>Supporting: Reforestation & Renewable Energy Projects</small>
                    </div>
                </div>
            `).show();
        } else {
            offsetSummary.hide();
        }
    }

    calculateTotal(offsetCost = 0) {
        if (!this.currentOrder) return;
        
        const baseAmount = parseFloat(this.currentOrder.amount);
        const total = baseAmount + offsetCost;
        
        const totalDisplay = jQuery('.epg-binance-payment-total');
        totalDisplay.html(`
            <div class="total-calculation">
                <div class="total-line base-amount">
                    <span class="line-label">Order Amount:</span>
                    <span class="line-value">$${baseAmount.toFixed(2)}</span>
                </div>
                ${offsetCost > 0 ? `
                <div class="total-line offset-amount">
                    <span class="line-label">Carbon Offset:</span>
                    <span class="line-value">+$${offsetCost.toFixed(2)}</span>
                </div>
                ` : ''}
                <div class="total-line final-total">
                    <span class="line-label"><strong>Total:</strong></span>
                    <span class="line-value"><strong>$${total.toFixed(2)}</strong></span>
                </div>
            </div>
        `);
    }

    togglePaymentButton(enabled) {
        const payButton = jQuery('.epg-binance-pay-button');
        if (enabled) {
            payButton.prop('disabled', false).removeClass('disabled');
        } else {
            payButton.prop('disabled', true).addClass('disabled');
        }
    }

    async initializePayment() {
        if (this.paymentInProgress) return;
        
        const selectedCurrency = jQuery('.epg-binance-currency-option.selected').data('currency');
        if (!selectedCurrency) {
            alert('Please select a cryptocurrency');
            return;
        }
        
        this.paymentInProgress = true;
        this.updatePaymentStatus('creating');
        
        try {
            const carbonOffset = jQuery('.epg-binance-carbon-offset').is(':checked');
            const currency = this.supportedCurrencies[selectedCurrency];
            const offsetCost = carbonOffset ? currency.carbon_per_tx * 0.02 : 0;
            const totalAmount = parseFloat(this.currentOrder.amount) + offsetCost;
            
            // Create Binance Pay order via backend
            const orderData = await this.createBinanceOrder({
                amount: totalAmount,
                currency: 'USD',
                crypto_currency: selectedCurrency,
                carbon_offset: carbonOffset,
                order_id: this.currentOrder.id
            });
            
            this.orderId = orderData.order_id;
            this.displayPaymentInterface(orderData);
            this.startPaymentMonitoring();
            
        } catch (error) {
            console.error('Payment initialization failed:', error);
            this.updatePaymentStatus('failed');
            this.paymentInProgress = false;
        }
    }

    async createBinanceOrder(orderData) {
        try {
            const response = await fetch(epg_binance_vars.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'epg_create_binance_order',
                    nonce: epg_binance_vars.nonce,
                    order_data: JSON.stringify(orderData)
                })
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.data);
            }
            
            return result.data;
            
        } catch (error) {
            console.error('Binance order creation failed:', error);
            throw error;
        }
    }

    displayPaymentInterface(orderData) {
        const paymentInterface = jQuery('.epg-binance-payment-interface');
        
        paymentInterface.html(`
            <div class="binance-payment-container">
                <div class="payment-header">
                    <div class="binance-logo">
                        <img src="${epg_binance_vars.plugin_url}/assets/images/binance-pay-logo.png" 
                             alt="Binance Pay" class="binance-pay-logo">
                    </div>
                    <h3>Complete Payment with Binance Pay</h3>
                </div>
                
                <div class="payment-details">
                    <div class="amount-display">
                        <div class="crypto-amount">
                            ${orderData.crypto_amount} ${orderData.crypto_currency}
                        </div>
                        <div class="usd-equivalent">
                            ≈ $${orderData.usd_amount}
                        </div>
                    </div>
                </div>
                
                <div class="payment-methods-tabs">
                    <button class="tab-button active" data-tab="qr">QR Code</button>
                    <button class="tab-button" data-tab="deep-link">Binance App</button>
                </div>
                
                <div class="payment-method-content">
                    <div class="payment-tab active" id="qr-tab">
                        <div class="qr-payment-section">
                            <div class="qr-code-display">
                                <img src="${orderData.qr_code_url}" alt="Payment QR Code" class="payment-qr">
                            </div>
                            <div class="qr-instructions">
                                <h4>Scan with Binance App</h4>
                                <ol>
                                    <li>Open your Binance app</li>
                                    <li>Tap "Pay" on the bottom menu</li>
                                    <li>Scan this QR code</li>
                                    <li>Confirm the payment</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    
                    <div class="payment-tab" id="deep-link-tab">
                        <div class="deep-link-section">
                            <div class="deep-link-button-container">
                                <a href="${orderData.deep_link_url}" class="binance-deep-link">
                                    <img src="${epg_binance_vars.plugin_url}/assets/images/binance-icon.png" 
                                         alt="Binance" class="binance-icon">
                                    <span>Open Binance App</span>
                                </a>
                            </div>
                            <div class="deep-link-instructions">
                                <p>Clicking the button above will open the Binance app with your payment details pre-filled.</p>
                                <p><strong>Note:</strong> This only works if you have the Binance app installed.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="payment-status-section">
                    <div class="status-indicator waiting">
                        <div class="status-icon">⏳</div>
                        <div class="status-text">Waiting for payment confirmation...</div>
                    </div>
                    <div class="payment-timer">
                        <span class="timer-label">Payment expires in:</span>
                        <span class="timer-display">${orderData.expires_in}</span>
                    </div>
                </div>
                
                <div class="payment-actions">
                    <button class="action-button epg-binance-check-payment">
                        <span class="button-icon">🔄</span>
                        Check Payment Status
                    </button>
                    <button class="action-button secondary epg-binance-qr-toggle">
                        <span class="button-icon">📱</span>
                        Switch Payment Method
                    </button>
                </div>
                
                <div class="payment-security-info">
                    <div class="security-badge">
                        <span class="security-icon">🔒</span>
                        <span class="security-text">Secured by Binance Pay</span>
                    </div>
                </div>
            </div>
        `).show();
        
        this.setupPaymentInterface();
        this.startPaymentTimer(orderData.expires_in);
    }

    setupPaymentInterface() {
        // Tab switching
        jQuery('.tab-button').on('click', (e) => {
            const tabName = jQuery(e.target).data('tab');
            
            jQuery('.tab-button').removeClass('active');
            jQuery('.payment-tab').removeClass('active');
            
            jQuery(e.target).addClass('active');
            jQuery(`#${tabName}-tab`).addClass('active');
        });
    }

    toggleQRCode() {
        const activeTab = jQuery('.tab-button.active').data('tab');
        const nextTab = activeTab === 'qr' ? 'deep-link' : 'qr';
        
        jQuery(`.tab-button[data-tab="${nextTab}"]`).click();
    }

    startPaymentTimer(expiresIn) {
        let timeLeft = expiresIn;
        const timerDisplay = jQuery('.timer-display');
        
        const timer = setInterval(() => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            timerDisplay.text(`${minutes}:${seconds.toString().padStart(2, '0')}`);
            
            if (timeLeft <= 300) { // 5 minutes remaining
                timerDisplay.addClass('urgent');
            }
            
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
        }, 5000); // Check every 5 seconds
    }

    async checkPaymentStatus() {
        if (!this.orderId) return;
        
        try {
            const response = await fetch(epg_binance_vars.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'epg_check_binance_payment',
                    nonce: epg_binance_vars.nonce,
                    order_id: this.orderId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                const status = result.data.status;
                this.updatePaymentStatus(status);
                
                if (status === 'completed' || status === 'confirmed') {
                    this.handlePaymentSuccess(result.data);
                } else if (status === 'failed' || status === 'expired' || status === 'cancelled') {
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
        
        const statusConfigs = {
            'creating': { icon: '🔄', text: 'Creating payment order...', class: 'creating' },
            'waiting': { icon: '⏳', text: 'Waiting for payment confirmation...', class: 'waiting' },
            'pending': { icon: '🔄', text: 'Payment detected, confirming...', class: 'pending' },
            'confirmed': { icon: '✅', text: 'Payment confirmed!', class: 'confirmed' },
            'completed': { icon: '🎉', text: 'Payment completed successfully!', class: 'completed' },
            'failed': { icon: '❌', text: 'Payment failed', class: 'failed' },
            'expired': { icon: '⏰', text: 'Payment expired', class: 'expired' },
            'cancelled': { icon: '🚫', text: 'Payment cancelled', class: 'cancelled' }
        };
        
        const config = statusConfigs[status] || statusConfigs['waiting'];
        
        statusIndicator.removeClass(Object.keys(statusConfigs).join(' '))
                      .addClass(config.class);
        statusIcon.text(config.icon);
        statusText.text(config.text);
    }

    handlePaymentSuccess(paymentData) {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
        
        this.paymentInProgress = false;
        
        // Display success message
        jQuery('.epg-binance-payment-interface').append(`
            <div class="payment-success-overlay">
                <div class="success-content">
                    <div class="success-animation">
                        <div class="success-icon">🎉</div>
                        <h2>Payment Successful!</h2>
                    </div>
                    <div class="success-details">
                        <div class="detail-item">
                            <span class="detail-label">Transaction ID:</span>
                            <span class="detail-value">${paymentData.transaction_id}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Amount:</span>
                            <span class="detail-value">${paymentData.crypto_amount} ${paymentData.crypto_currency}</span>
                        </div>
                        ${paymentData.carbon_offset ? `
                        <div class="detail-item environmental">
                            <span class="detail-label">🌱 Carbon Offset:</span>
                            <span class="detail-value">Enabled</span>
                        </div>
                        ` : ''}
                    </div>
                    <div class="success-actions">
                        <p>Redirecting to confirmation page...</p>
                        <div class="loading-dots">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>
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
        
        const failureMessages = {
            'expired': 'Payment time has expired. Please try again.',
            'failed': 'Payment failed. Please try again or contact support.',
            'cancelled': 'Payment was cancelled. You can try again if needed.'
        };
        
        const message = failureMessages[status] || 'Payment was not completed.';
        
        jQuery('.epg-binance-payment-interface').append(`
            <div class="payment-failure-overlay">
                <div class="failure-content">
                    <div class="failure-icon">${status === 'expired' ? '⏰' : status === 'cancelled' ? '🚫' : '❌'}</div>
                    <h3>Payment ${status.charAt(0).toUpperCase() + status.slice(1)}</h3>
                    <p>${message}</p>
                    <div class="failure-actions">
                        <button class="retry-button" onclick="location.reload()">
                            Try Again
                        </button>
                        <button class="back-button" onclick="history.back()">
                            Go Back
                        </button>
                    </div>
                </div>
            </div>
        `);
    }

    handlePaymentExpired() {
        this.handlePaymentFailure('expired');
    }

    displayEnvironmentalInfo() {
        const envInfo = jQuery('.epg-binance-environmental-header');
        envInfo.html(`
            <div class="environmental-intro">
                <div class="intro-icon">🌍</div>
                <div class="intro-content">
                    <h2>Choose Environmentally Conscious Payments</h2>
                    <p>Different cryptocurrencies have varying environmental impacts. Make an informed choice.</p>
                </div>
            </div>
            <div class="environmental-stats">
                <div class="stat-item">
                    <span class="stat-number">10+</span>
                    <span class="stat-label">Cryptocurrencies</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">0.1kg</span>
                    <span class="stat-label">Lowest CO₂ Option</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">100%</span>
                    <span class="stat-label">Offset Available</span>
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
    if (typeof epg_binance_vars !== 'undefined') {
        window.epgBinanceGateway = new EPGBinanceGateway();
        
        // Set order data if available
        if (typeof epg_binance_order !== 'undefined') {
            window.epgBinanceGateway.setOrderData(epg_binance_order);
        }
    }
});
