/**
 * Bitcoin Gateway Frontend JavaScript
 * 
 * Handles Bitcoin payment interface with QR code generation,
 * blockchain monitoring, and environmental impact tracking
 * 
 * @package EnvironmentalPaymentGateway
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    class EPGBitcoinGateway {
        constructor() {
            this.paymentData = {};
            this.monitoringInterval = null;
            this.qrCode = null;
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.loadQRCodeLibrary();
        }
        
        bindEvents() {
            $(document).on('change', '#payment_method_epg_bitcoin', () => {
                this.showBitcoinInterface();
            });
            
            $(document).on('click', '.epg-bitcoin-generate-address', () => {
                this.generatePaymentAddress();
            });
            
            $(document).on('click', '.epg-bitcoin-copy-address', () => {
                this.copyToClipboard();
            });
            
            $(document).on('click', '.epg-bitcoin-refresh-status', () => {
                this.checkPaymentStatus();
            });
            
            $(document).on('change', '.epg-bitcoin-offset-toggle', () => {
                this.toggleCarbonOffset();
            });
        }
        
        showBitcoinInterface() {
            const interfaceHtml = this.buildBitcoinInterface();
            $('.payment_method_epg_bitcoin .payment_box').html(interfaceHtml);
            this.displayEnvironmentalWarning();
        }
        
        buildBitcoinInterface() {
            return `
                <div class="epg-bitcoin-payment-interface">
                    <div class="epg-bitcoin-header">
                        <h4>₿ Bitcoin Payment</h4>
                        <p>Send Bitcoin to the address below to complete your payment</p>
                    </div>
                    
                    <div class="epg-environmental-warning">
                        <div class="epg-warning-icon">⚠️</div>
                        <div class="epg-warning-content">
                            <strong>Environmental Impact Notice</strong>
                            <p>Bitcoin has a high carbon footprint. We automatically offset emissions with your payment.</p>
                            <button type="button" class="epg-show-impact-details">Learn More</button>
                        </div>
                    </div>
                    
                    <div class="epg-bitcoin-payment-section" style="display: none;">
                        <div class="epg-payment-amount">
                            <div class="epg-amount-display">
                                <span class="epg-btc-amount" id="epg-btc-amount">0.00000000</span>
                                <span class="epg-btc-symbol">BTC</span>
                            </div>
                            <div class="epg-fiat-equivalent">
                                ≈ $<span id="epg-fiat-amount">${this.getOrderTotal()}</span> USD
                            </div>
                        </div>
                        
                        <div class="epg-bitcoin-address-section">
                            <div class="epg-address-header">
                                <h5>Payment Address</h5>
                                <button type="button" class="epg-bitcoin-generate-address button-secondary">
                                    Generate Address
                                </button>
                            </div>
                            
                            <div class="epg-address-display" style="display: none;">
                                <div class="epg-qr-code-container">
                                    <div id="epg-bitcoin-qr-code"></div>
                                    <p class="epg-qr-caption">Scan with Bitcoin wallet</p>
                                </div>
                                
                                <div class="epg-address-text">
                                    <label>Bitcoin Address:</label>
                                    <div class="epg-address-input">
                                        <input type="text" id="epg-bitcoin-address" readonly>
                                        <button type="button" class="epg-bitcoin-copy-address">Copy</button>
                                    </div>
                                </div>
                                
                                <div class="epg-payment-info">
                                    <div class="epg-info-row">
                                        <span>Network:</span>
                                        <span id="epg-bitcoin-network">${epg_bitcoin_params.network}</span>
                                    </div>
                                    <div class="epg-info-row">
                                        <span>Confirmations required:</span>
                                        <span>${epg_bitcoin_params.confirmations_required}</span>
                                    </div>
                                    <div class="epg-info-row">
                                        <span>Payment timeout:</span>
                                        <span id="epg-payment-timeout">${epg_bitcoin_params.payment_timeout} minutes</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="epg-payment-status" style="display: none;">
                            <div class="epg-status-header">
                                <h5>Payment Status</h5>
                                <button type="button" class="epg-bitcoin-refresh-status">🔄 Refresh</button>
                            </div>
                            
                            <div class="epg-status-display">
                                <div class="epg-status-indicator">
                                    <div class="epg-status-icon" id="epg-status-icon">⏳</div>
                                    <div class="epg-status-text" id="epg-status-text">Waiting for payment</div>
                                </div>
                                
                                <div class="epg-confirmations-progress">
                                    <div class="epg-progress-bar">
                                        <div class="epg-progress-fill" id="epg-confirmations-progress" style="width: 0%"></div>
                                    </div>
                                    <div class="epg-progress-text">
                                        <span id="epg-confirmations-count">0</span> / ${epg_bitcoin_params.confirmations_required} confirmations
                                    </div>
                                </div>
                                
                                <div class="epg-transaction-details" style="display: none;">
                                    <div class="epg-detail-row">
                                        <span>Transaction ID:</span>
                                        <span id="epg-transaction-id">-</span>
                                    </div>
                                    <div class="epg-detail-row">
                                        <span>Block height:</span>
                                        <span id="epg-block-height">-</span>
                                    </div>
                                    <div class="epg-detail-row">
                                        <span>Fee paid:</span>
                                        <span id="epg-transaction-fee">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="epg-environmental-section">
                        <h5>🌍 Environmental Impact</h5>
                        
                        <div class="epg-carbon-impact">
                            <div class="epg-impact-item">
                                <span class="epg-impact-label">Energy consumption:</span>
                                <span class="epg-impact-value" id="epg-energy-consumption">~741 kWh</span>
                            </div>
                            <div class="epg-impact-item">
                                <span class="epg-impact-label">Carbon footprint:</span>
                                <span class="epg-impact-value" id="epg-carbon-footprint">~0.35 kg CO2</span>
                            </div>
                            <div class="epg-impact-item">
                                <span class="epg-impact-label">Environmental rating:</span>
                                <span class="epg-impact-value epg-rating-high">🔥 High Impact</span>
                            </div>
                        </div>
                        
                        <div class="epg-offset-section">
                            <label class="epg-offset-toggle-label">
                                <input type="checkbox" class="epg-bitcoin-offset-toggle" checked>
                                <span>🌱 Automatic carbon offset (${epg_bitcoin_params.carbon_offset_percentage}% of payment)</span>
                            </label>
                            
                            <div class="epg-offset-details">
                                <p>Offset amount: $<span id="epg-offset-amount">0.00</span></p>
                                <p>This supports renewable energy projects and reforestation initiatives.</p>
                            </div>
                        </div>
                        
                        <div class="epg-green-alternatives">
                            <h6>💡 Consider greener alternatives:</h6>
                            <ul>
                                <li><strong>Ethereum:</strong> 99.9% more energy efficient (Proof of Stake)</li>
                                <li><strong>Cardano:</strong> Designed for sustainability from the ground up</li>
                                <li><strong>Solana:</strong> Fast and energy-efficient blockchain</li>
                            </ul>
                            <button type="button" class="epg-switch-payment-method">Switch to Green Crypto</button>
                        </div>
                    </div>
                    
                    <div class="epg-bitcoin-instructions">
                        <h5>📱 How to Pay</h5>
                        <ol>
                            <li>Click "Generate Address" to create a unique payment address</li>
                            <li>Scan the QR code with your Bitcoin wallet or copy the address</li>
                            <li>Send the exact amount shown above</li>
                            <li>Wait for network confirmations (usually 10-60 minutes)</li>
                            <li>Your order will be processed automatically</li>
                        </ol>
                        
                        <div class="epg-wallet-suggestions">
                            <p><strong>Recommended wallets:</strong></p>
                            <div class="epg-wallet-links">
                                <a href="https://electrum.org/" target="_blank">Electrum</a>
                                <a href="https://bluewallet.io/" target="_blank">BlueWallet</a>
                                <a href="https://blockstream.com/green/" target="_blank">Blockstream Green</a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        loadQRCodeLibrary() {
            if (typeof QRCode === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js';
                script.onload = () => {
                    console.log('QR Code library loaded');
                };
                document.head.appendChild(script);
            }
        }
        
        generatePaymentAddress() {
            this.showGeneratingIndicator();
            
            $.ajax({
                url: epg_bitcoin_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'epg_generate_bitcoin_address',
                    order_id: epg_bitcoin_params.order_id,
                    nonce: epg_bitcoin_params.nonce
                },
                success: (response) => {
                    this.hideGeneratingIndicator();
                    
                    if (response.success) {
                        this.paymentData = response.data;
                        this.displayPaymentAddress(response.data);
                        this.startPaymentMonitoring();
                        this.updateEnvironmentalData();
                    } else {
                        this.showError(response.data.message || 'Failed to generate address');
                    }
                },
                error: () => {
                    this.hideGeneratingIndicator();
                    this.showError('Network error. Please try again.');
                }
            });
        }
        
        displayPaymentAddress(data) {
            $('#epg-btc-amount').text(data.btc_amount);
            $('#epg-bitcoin-address').val(data.address);
            
            // Generate QR code
            this.generateQRCode(data.address, data.btc_amount);
            
            $('.epg-address-display').fadeIn();
            $('.epg-payment-status').fadeIn();
            
            // Start countdown timer
            this.startPaymentTimer(data.expires_at);
        }
        
        generateQRCode(address, amount) {
            const bitcoinUri = `bitcoin:${address}?amount=${amount}`;
            
            if (typeof QRCode !== 'undefined') {
                $('#epg-bitcoin-qr-code').empty();
                
                QRCode.toCanvas(document.getElementById('epg-bitcoin-qr-code'), bitcoinUri, {
                    width: 200,
                    height: 200,
                    margin: 2,
                    color: {
                        dark: '#000000',
                        light: '#FFFFFF'
                    }
                }, (error) => {
                    if (error) {
                        console.error('QR Code generation error:', error);
                        $('#epg-bitcoin-qr-code').html('<p>QR code generation failed</p>');
                    }
                });
            } else {
                // Fallback to online QR code service
                const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(bitcoinUri)}`;
                $('#epg-bitcoin-qr-code').html(`<img src="${qrUrl}" alt="Bitcoin QR Code" style="max-width: 200px;">`);
            }
        }
        
        startPaymentMonitoring() {
            this.monitoringInterval = setInterval(() => {
                this.checkPaymentStatus();
            }, 15000); // Check every 15 seconds
            
            // Stop monitoring after timeout
            setTimeout(() => {
                if (this.monitoringInterval) {
                    clearInterval(this.monitoringInterval);
                    this.handlePaymentTimeout();
                }
            }, epg_bitcoin_params.payment_timeout * 60 * 1000);
        }
        
        checkPaymentStatus() {
            if (!this.paymentData.address) {
                return;
            }
            
            $.ajax({
                url: epg_bitcoin_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'epg_check_bitcoin_payment',
                    address: this.paymentData.address,
                    order_id: epg_bitcoin_params.order_id,
                    nonce: epg_bitcoin_params.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updatePaymentStatus(response.data);
                    }
                },
                error: () => {
                    console.error('Failed to check payment status');
                }
            });
        }
        
        updatePaymentStatus(statusData) {
            const status = statusData.status;
            const confirmations = statusData.confirmations || 0;
            const requiredConfirmations = epg_bitcoin_params.confirmations_required;
            
            // Update status indicator
            if (status === 'detected') {
                $('#epg-status-icon').text('🔍');
                $('#epg-status-text').text('Payment detected, waiting for confirmations');
            } else if (status === 'confirmed') {
                $('#epg-status-icon').text('✅');
                $('#epg-status-text').text('Payment confirmed');
                this.handlePaymentSuccess(statusData);
            } else if (status === 'failed') {
                $('#epg-status-icon').text('❌');
                $('#epg-status-text').text('Payment failed');
                this.handlePaymentFailure(statusData);
            }
            
            // Update confirmations progress
            const progressPercentage = Math.min((confirmations / requiredConfirmations) * 100, 100);
            $('#epg-confirmations-progress').css('width', progressPercentage + '%');
            $('#epg-confirmations-count').text(confirmations);
            
            // Show transaction details if available
            if (statusData.transaction_id) {
                $('#epg-transaction-id').text(statusData.transaction_id);
                $('#epg-block-height').text(statusData.block_height || '-');
                $('#epg-transaction-fee').text(statusData.fee || '-');
                $('.epg-transaction-details').fadeIn();
            }
        }
        
        handlePaymentSuccess(statusData) {
            clearInterval(this.monitoringInterval);
            
            this.showSuccessMessage();
            
            // Process carbon offset if enabled
            if ($('.epg-bitcoin-offset-toggle').prop('checked')) {
                this.processCarbonOffset();
            }
            
            // Redirect to success page
            setTimeout(() => {
                window.location.href = epg_bitcoin_params.success_url + '&transaction_id=' + statusData.transaction_id;
            }, 3000);
        }
        
        handlePaymentFailure(statusData) {
            clearInterval(this.monitoringInterval);
            this.showError('Payment failed: ' + (statusData.error_message || 'Unknown error'));
        }
        
        handlePaymentTimeout() {
            $('#epg-status-icon').text('⏰');
            $('#epg-status-text').text('Payment timeout - address expired');
            this.showError('Payment timeout. Please generate a new address.');
        }
        
        startPaymentTimer(expiresAt) {
            const expiryTime = new Date(expiresAt).getTime();
            
            const timerInterval = setInterval(() => {
                const now = new Date().getTime();
                const timeLeft = expiryTime - now;
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    $('#epg-payment-timeout').text('Expired');
                    return;
                }
                
                const minutes = Math.floor(timeLeft / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                
                $('#epg-payment-timeout').text(`${minutes}:${seconds.toString().padStart(2, '0')} remaining`);
            }, 1000);
        }
        
        displayEnvironmentalWarning() {
            $(document).on('click', '.epg-show-impact-details', () => {
                this.showEnvironmentalImpactModal();
            });
            
            $(document).on('click', '.epg-switch-payment-method', () => {
                this.showAlternativePaymentMethods();
            });
        }
        
        showEnvironmentalImpactModal() {
            const modalHtml = `
                <div class="epg-modal-overlay">
                    <div class="epg-modal epg-environmental-modal">
                        <div class="epg-modal-header">
                            <h3>🌍 Bitcoin Environmental Impact</h3>
                            <button class="epg-modal-close">&times;</button>
                        </div>
                        <div class="epg-modal-content">
                            <div class="epg-impact-comparison">
                                <h4>Energy Consumption Comparison</h4>
                                <div class="epg-comparison-chart">
                                    <div class="epg-comparison-item">
                                        <span class="epg-crypto-name">Bitcoin</span>
                                        <div class="epg-energy-bar btc-bar" style="width: 100%"></div>
                                        <span class="epg-energy-value">741 kWh</span>
                                    </div>
                                    <div class="epg-comparison-item">
                                        <span class="epg-crypto-name">Ethereum</span>
                                        <div class="epg-energy-bar eth-bar" style="width: 0.1%"></div>
                                        <span class="epg-energy-value">0.02 kWh</span>
                                    </div>
                                    <div class="epg-comparison-item">
                                        <span class="epg-crypto-name">Cardano</span>
                                        <div class="epg-energy-bar ada-bar" style="width: 0.05%"></div>
                                        <span class="epg-energy-value">0.01 kWh</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="epg-offset-explanation">
                                <h4>How We Offset Bitcoin's Impact</h4>
                                <ul>
                                    <li>🌳 Tree planting projects (0.5 trees per transaction)</li>
                                    <li>⚡ Renewable energy investments</li>
                                    <li>🏭 Carbon capture technology funding</li>
                                    <li>📊 Transparent impact reporting</li>
                                </ul>
                            </div>
                            
                            <div class="epg-modal-actions">
                                <button class="epg-continue-bitcoin button-primary">Continue with Bitcoin</button>
                                <button class="epg-choose-green button-secondary">Choose Green Alternative</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            $('.epg-modal-close, .epg-continue-bitcoin').on('click', () => {
                $('.epg-modal-overlay').fadeOut(() => {
                    $('.epg-modal-overlay').remove();
                    $('.epg-bitcoin-payment-section').fadeIn();
                });
            });
            
            $('.epg-choose-green').on('click', () => {
                $('.epg-modal-overlay').remove();
                this.showAlternativePaymentMethods();
            });
        }
        
        showAlternativePaymentMethods() {
            const alternativesHtml = `
                <div class="epg-green-alternatives-popup">
                    <h4>🌱 Green Cryptocurrency Options</h4>
                    <div class="epg-green-options">
                        <div class="epg-green-option" data-method="ethereum">
                            <span class="epg-crypto-icon">⟠</span>
                            <div class="epg-option-details">
                                <strong>Ethereum</strong>
                                <p>99.9% more energy efficient • Proof of Stake</p>
                            </div>
                            <span class="epg-eco-rating">🌿🌿🌿</span>
                        </div>
                        <div class="epg-green-option" data-method="cardano">
                            <span class="epg-crypto-icon">₳</span>
                            <div class="epg-option-details">
                                <strong>Cardano</strong>
                                <p>Peer-reviewed sustainability • Academic approach</p>
                            </div>
                            <span class="epg-eco-rating">🌿🌿🌿🌿</span>
                        </div>
                        <div class="epg-green-option" data-method="solana">
                            <span class="epg-crypto-icon">◎</span>
                            <div class="epg-option-details">
                                <strong>Solana</strong>
                                <p>Ultra-fast and energy efficient</p>
                            </div>
                            <span class="epg-eco-rating">🌿🌿🌿🌿</span>
                        </div>
                    </div>
                    <div class="epg-popup-actions">
                        <button class="epg-switch-method button-primary">Switch Payment Method</button>
                        <button class="epg-stay-bitcoin button-secondary">Stay with Bitcoin</button>
                    </div>
                </div>
            `;
            
            $('body').append(alternativesHtml);
            
            $('.epg-green-option').on('click', function() {
                $('.epg-green-option').removeClass('selected');
                $(this).addClass('selected');
            });
            
            $('.epg-stay-bitcoin').on('click', () => {
                $('.epg-green-alternatives-popup').fadeOut(() => {
                    $('.epg-green-alternatives-popup').remove();
                    $('.epg-bitcoin-payment-section').fadeIn();
                });
            });
        }
        
        updateEnvironmentalData() {
            const orderTotal = this.getOrderTotal();
            const energyConsumption = 741; // kWh per transaction (approximate)
            const carbonFootprint = energyConsumption * 0.0005; // kg CO2
            const offsetAmount = orderTotal * (parseFloat(epg_bitcoin_params.carbon_offset_percentage) / 100);
            
            $('#epg-energy-consumption').text(`~${energyConsumption} kWh`);
            $('#epg-carbon-footprint').text(`~${carbonFootprint.toFixed(2)} kg CO2`);
            $('#epg-offset-amount').text(offsetAmount.toFixed(2));
        }
        
        toggleCarbonOffset() {
            const isEnabled = $('.epg-bitcoin-offset-toggle').prop('checked');
            const offsetAmount = isEnabled ? this.getOrderTotal() * 0.02 : 0;
            
            $('#epg-offset-amount').text(offsetAmount.toFixed(2));
            
            if (isEnabled) {
                $('.epg-offset-details').fadeIn();
            } else {
                $('.epg-offset-details').fadeOut();
            }
        }
        
        processCarbonOffset() {
            $.ajax({
                url: epg_bitcoin_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'epg_process_carbon_offset',
                    order_id: epg_bitcoin_params.order_id,
                    amount: $('#epg-offset-amount').text(),
                    nonce: epg_bitcoin_params.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showOffsetSuccess();
                    }
                }
            });
        }
        
        copyToClipboard() {
            const address = $('#epg-bitcoin-address').val();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(address).then(() => {
                    this.showCopySuccess();
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = address;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                this.showCopySuccess();
            }
        }
        
        showCopySuccess() {
            const originalText = $('.epg-bitcoin-copy-address').text();
            $('.epg-bitcoin-copy-address').text('Copied!').addClass('copied');
            
            setTimeout(() => {
                $('.epg-bitcoin-copy-address').text(originalText).removeClass('copied');
            }, 2000);
        }
        
        showGeneratingIndicator() {
            $('.epg-bitcoin-generate-address').prop('disabled', true).text('Generating...');
        }
        
        hideGeneratingIndicator() {
            $('.epg-bitcoin-generate-address').prop('disabled', false).text('Generate Address');
        }
        
        showSuccessMessage() {
            const successHtml = `
                <div class="epg-success-notification">
                    <h3>✅ Payment Confirmed!</h3>
                    <p>Your Bitcoin payment has been confirmed on the blockchain.</p>
                    <p>🌱 Carbon offset has been processed automatically.</p>
                </div>
            `;
            
            $('.epg-bitcoin-payment-interface').prepend(successHtml);
        }
        
        showOffsetSuccess() {
            const offsetHtml = `
                <div class="epg-offset-success">
                    <p>🌳 Carbon offset contribution processed successfully!</p>
                </div>
            `;
            
            $('.epg-offset-section').append(offsetHtml);
        }
        
        showError(message) {
            const errorHtml = `
                <div class="epg-bitcoin-error">
                    <p><strong>Error:</strong> ${message}</p>
                </div>
            `;
            
            $('.epg-bitcoin-payment-interface').prepend(errorHtml);
            
            setTimeout(() => {
                $('.epg-bitcoin-error').fadeOut(() => {
                    $('.epg-bitcoin-error').remove();
                });
            }, 5000);
        }
        
        getOrderTotal() {
            const totalElement = $('.order-total .amount, .cart-total .amount').last();
            const totalText = totalElement.text().replace(/[^\d.,]/g, '');
            return parseFloat(totalText) || 0;
        }
    }
    
    // Initialize when DOM is ready
    $(document).ready(() => {
        if (typeof epg_bitcoin_params !== 'undefined') {
            new EPGBitcoinGateway();
        }
    });
    
})(jQuery);
