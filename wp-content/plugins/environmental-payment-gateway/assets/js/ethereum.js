/**
 * Environmental Payment Gateway - Ethereum Gateway Frontend
 * 
 * Handles Ethereum and ERC-20 token payments with Web3 integration
 * Includes environmental impact tracking and Layer 2 network support
 */

class EPGEthereumGateway {
    constructor() {
        this.web3 = null;
        this.account = null;
        this.provider = null;
        this.networkId = null;
        this.supportedNetworks = {
            1: 'Ethereum Mainnet',
            137: 'Polygon',
            42161: 'Arbitrum One',
            10: 'Optimism'
        };
        this.supportedTokens = {
            'ETH': {
                symbol: 'ETH',
                name: 'Ethereum',
                decimals: 18,
                environmental_rating: 'high',
                carbon_per_tx: 60.0
            },
            'USDT': {
                symbol: 'USDT',
                name: 'Tether USD',
                decimals: 6,
                address: '0xdAC17F958D2ee523a2206206994597C13D831ec7',
                environmental_rating: 'high',
                carbon_per_tx: 60.0
            },
            'USDC': {
                symbol: 'USDC',
                name: 'USD Coin',
                decimals: 6,
                address: '0xA0b86a33E6441B86eed4e2AD2bb5Dd4A21E5BDB1',
                environmental_rating: 'high',
                carbon_per_tx: 60.0
            },
            'MATIC': {
                symbol: 'MATIC',
                name: 'Polygon',
                decimals: 18,
                address: '0x7D1AfA7B718fb893dB30A3aBc0Cfc608AaCfeBB0',
                environmental_rating: 'low',
                carbon_per_tx: 0.1
            }
        };
        this.currentOrder = null;
        this.paymentInProgress = false;
        
        this.init();
    }

    async init() {
        this.setupEventListeners();
        await this.detectProvider();
        this.loadEnvironmentalData();
    }

    setupEventListeners() {
        // Connect Wallet Button
        jQuery(document).on('click', '.epg-ethereum-connect-wallet', (e) => {
            e.preventDefault();
            this.connectWallet();
        });

        // Token Selection
        jQuery(document).on('change', '.epg-ethereum-token-select', (e) => {
            this.handleTokenChange(e.target.value);
        });

        // Network Selection
        jQuery(document).on('change', '.epg-ethereum-network-select', (e) => {
            this.switchNetwork(parseInt(e.target.value));
        });

        // Payment Confirmation
        jQuery(document).on('click', '.epg-ethereum-confirm-payment', (e) => {
            e.preventDefault();
            this.processPayment();
        });

        // Carbon Offset Toggle
        jQuery(document).on('change', '.epg-ethereum-carbon-offset', (e) => {
            this.updateCarbonOffset(e.target.checked);
        });

        // Layer 2 Network Promotion
        jQuery(document).on('click', '.epg-ethereum-use-layer2', (e) => {
            e.preventDefault();
            this.promoteLayer2Networks();
        });
    }

    async detectProvider() {
        if (typeof window.ethereum !== 'undefined') {
            this.provider = window.ethereum;
            this.web3 = new Web3(this.provider);
            this.updateWalletStatus('detected');
        } else {
            this.updateWalletStatus('not_detected');
        }
    }

    async connectWallet() {
        if (!this.provider) {
            this.showWalletInstallPrompt();
            return;
        }

        try {
            this.updateWalletStatus('connecting');
            
            const accounts = await this.provider.request({
                method: 'eth_requestAccounts'
            });
            
            this.account = accounts[0];
            this.networkId = await this.web3.eth.net.getId();
            
            this.updateWalletStatus('connected');
            this.displayWalletInfo();
            this.checkNetworkCompatibility();
            
        } catch (error) {
            console.error('Wallet connection failed:', error);
            this.updateWalletStatus('connection_failed');
        }
    }

    updateWalletStatus(status) {
        const container = jQuery('.epg-ethereum-wallet-status');
        
        const statusMessages = {
            'detected': 'Web3 wallet detected. Click to connect.',
            'not_detected': 'No Web3 wallet detected. Please install MetaMask or similar.',
            'connecting': 'Connecting to wallet...',
            'connected': 'Wallet connected successfully!',
            'connection_failed': 'Failed to connect wallet. Please try again.'
        };
        
        container.removeClass('detected not_detected connecting connected connection_failed')
                 .addClass(status)
                 .find('.status-message')
                 .text(statusMessages[status]);
    }

    displayWalletInfo() {
        const walletInfo = jQuery('.epg-ethereum-wallet-info');
        const networkName = this.supportedNetworks[this.networkId] || 'Unknown Network';
        
        walletInfo.html(`
            <div class="wallet-details">
                <div class="address">
                    <strong>Address:</strong> 
                    <span class="truncate">${this.truncateAddress(this.account)}</span>
                    <button class="copy-address" data-address="${this.account}">Copy</button>
                </div>
                <div class="network">
                    <strong>Network:</strong> ${networkName}
                    ${this.networkId !== 1 && this.networkId !== 137 ? 
                        '<span class="network-warning">⚠️ Consider switching to a more eco-friendly network</span>' : ''}
                </div>
            </div>
        `).show();
        
        this.setupCopyAddress();
    }

    setupCopyAddress() {
        jQuery('.copy-address').on('click', function() {
            const address = jQuery(this).data('address');
            navigator.clipboard.writeText(address).then(() => {
                jQuery(this).text('Copied!').addClass('copied');
                setTimeout(() => {
                    jQuery(this).text('Copy').removeClass('copied');
                }, 2000);
            });
        });
    }

    truncateAddress(address) {
        return `${address.substring(0, 6)}...${address.substring(address.length - 4)}`;
    }

    checkNetworkCompatibility() {
        const isSupported = this.supportedNetworks.hasOwnProperty(this.networkId);
        const networkStatus = jQuery('.epg-ethereum-network-status');
        
        if (isSupported) {
            networkStatus.removeClass('unsupported').addClass('supported')
                        .html('<span class="status-icon">✅</span> Network supported');
        } else {
            networkStatus.removeClass('supported').addClass('unsupported')
                        .html('<span class="status-icon">❌</span> Please switch to a supported network');
        }
        
        // Show Layer 2 promotion for high-impact networks
        if (this.networkId === 1) { // Ethereum Mainnet
            this.showLayer2Promotion();
        }
    }

    showLayer2Promotion() {
        const promotion = jQuery('.epg-ethereum-layer2-promotion');
        promotion.html(`
            <div class="environmental-notice">
                <h4>🌱 Consider Eco-Friendly Alternatives</h4>
                <p>Ethereum Mainnet has high energy consumption. Consider using Layer 2 networks:</p>
                <div class="layer2-options">
                    <button class="layer2-option" data-network="137">
                        <span class="network-name">Polygon</span>
                        <span class="eco-rating">🌱 99% less energy</span>
                    </button>
                    <button class="layer2-option" data-network="42161">
                        <span class="network-name">Arbitrum</span>
                        <span class="eco-rating">🌱 95% less energy</span>
                    </button>
                    <button class="layer2-option" data-network="10">
                        <span class="network-name">Optimism</span>
                        <span class="eco-rating">🌱 95% less energy</span>
                    </button>
                </div>
            </div>
        `).show();
        
        jQuery('.layer2-option').on('click', (e) => {
            const networkId = parseInt(jQuery(e.currentTarget).data('network'));
            this.switchNetwork(networkId);
        });
    }

    async switchNetwork(networkId) {
        if (!this.provider) return;
        
        const networkHex = '0x' + networkId.toString(16);
        
        try {
            await this.provider.request({
                method: 'wallet_switchEthereumChain',
                params: [{ chainId: networkHex }]
            });
            
            this.networkId = networkId;
            this.checkNetworkCompatibility();
            this.updateEnvironmentalImpact();
            
        } catch (error) {
            if (error.code === 4902) {
                // Network not added to wallet
                await this.addNetwork(networkId);
            } else {
                console.error('Network switch failed:', error);
            }
        }
    }

    async addNetwork(networkId) {
        const networkConfigs = {
            137: {
                chainId: '0x89',
                chainName: 'Polygon Mainnet',
                rpcUrls: ['https://polygon-rpc.com/'],
                nativeCurrency: {
                    name: 'MATIC',
                    symbol: 'MATIC',
                    decimals: 18
                },
                blockExplorerUrls: ['https://polygonscan.com/']
            },
            42161: {
                chainId: '0xa4b1',
                chainName: 'Arbitrum One',
                rpcUrls: ['https://arb1.arbitrum.io/rpc'],
                nativeCurrency: {
                    name: 'Ethereum',
                    symbol: 'ETH',
                    decimals: 18
                },
                blockExplorerUrls: ['https://arbiscan.io/']
            },
            10: {
                chainId: '0xa',
                chainName: 'Optimism',
                rpcUrls: ['https://mainnet.optimism.io'],
                nativeCurrency: {
                    name: 'Ethereum',
                    symbol: 'ETH',
                    decimals: 18
                },
                blockExplorerUrls: ['https://optimistic.etherscan.io/']
            }
        };
        
        const config = networkConfigs[networkId];
        if (!config) return;
        
        try {
            await this.provider.request({
                method: 'wallet_addEthereumChain',
                params: [config]
            });
        } catch (error) {
            console.error('Add network failed:', error);
        }
    }

    handleTokenChange(tokenSymbol) {
        const token = this.supportedTokens[tokenSymbol];
        if (!token) return;
        
        this.updateTokenInfo(token);
        this.updateEnvironmentalImpact();
        this.checkTokenBalance(token);
    }

    updateTokenInfo(token) {
        const tokenInfo = jQuery('.epg-ethereum-token-info');
        tokenInfo.html(`
            <div class="token-details">
                <div class="token-name">
                    <strong>${token.name} (${token.symbol})</strong>
                </div>
                <div class="environmental-rating rating-${token.environmental_rating}">
                    Environmental Impact: ${token.environmental_rating.toUpperCase()}
                    <span class="carbon-footprint">${token.carbon_per_tx} kg CO₂ per transaction</span>
                </div>
            </div>
        `).show();
    }

    async checkTokenBalance(token) {
        if (!this.account || !this.web3) return;
        
        try {
            let balance;
            
            if (token.symbol === 'ETH') {
                balance = await this.web3.eth.getBalance(this.account);
                balance = this.web3.utils.fromWei(balance, 'ether');
            } else {
                // ERC-20 token balance check would go here
                balance = '0'; // Placeholder
            }
            
            this.displayTokenBalance(token, balance);
            
        } catch (error) {
            console.error('Balance check failed:', error);
        }
    }

    displayTokenBalance(token, balance) {
        const balanceDisplay = jQuery('.epg-ethereum-token-balance');
        balanceDisplay.html(`
            <div class="balance-info">
                <strong>Available Balance:</strong> 
                ${parseFloat(balance).toFixed(6)} ${token.symbol}
            </div>
        `).show();
    }

    updateEnvironmentalImpact() {
        const selectedToken = jQuery('.epg-ethereum-token-select').val();
        const token = this.supportedTokens[selectedToken];
        
        if (!token) return;
        
        const impactDisplay = jQuery('.epg-ethereum-environmental-impact');
        const carbonFootprint = token.carbon_per_tx;
        const offsetCost = carbonFootprint * 0.02; // $0.02 per kg CO₂
        
        let impactClass = 'impact-' + token.environmental_rating;
        let recommendationHtml = '';
        
        if (token.environmental_rating === 'high') {
            recommendationHtml = `
                <div class="environmental-recommendation">
                    <h4>🌱 Environmental Recommendation</h4>
                    <p>This payment method has high environmental impact. Consider:</p>
                    <ul>
                        <li>Using Layer 2 networks (Polygon, Arbitrum, Optimism)</li>
                        <li>Enabling automatic carbon offset (+$${offsetCost.toFixed(2)})</li>
                        <li>Choosing eco-friendly tokens when available</li>
                    </ul>
                </div>
            `;
        }
        
        impactDisplay.html(`
            <div class="impact-summary ${impactClass}">
                <div class="carbon-footprint">
                    <span class="icon">🌍</span>
                    <span class="text">Carbon Footprint: ${carbonFootprint} kg CO₂</span>
                </div>
                <div class="offset-option">
                    <label>
                        <input type="checkbox" class="epg-ethereum-carbon-offset" ${carbonFootprint > 1 ? 'checked' : ''}>
                        Offset carbon footprint (+$${offsetCost.toFixed(2)})
                    </label>
                </div>
            </div>
            ${recommendationHtml}
        `).show();
    }

    updateCarbonOffset(enabled) {
        const selectedToken = jQuery('.epg-ethereum-token-select').val();
        const token = this.supportedTokens[selectedToken];
        
        if (!token) return;
        
        const offsetCost = token.carbon_per_tx * 0.02;
        const totalDisplay = jQuery('.epg-ethereum-payment-total');
        
        // Update total with/without offset cost
        this.calculateTotal(enabled ? offsetCost : 0);
    }

    calculateTotal(offsetCost = 0) {
        if (!this.currentOrder) return;
        
        const baseAmount = parseFloat(this.currentOrder.amount);
        const total = baseAmount + offsetCost;
        
        const totalDisplay = jQuery('.epg-ethereum-payment-total');
        totalDisplay.html(`
            <div class="total-breakdown">
                <div class="line-item">
                    <span>Order Amount:</span>
                    <span>$${baseAmount.toFixed(2)}</span>
                </div>
                ${offsetCost > 0 ? `
                <div class="line-item offset">
                    <span>Carbon Offset:</span>
                    <span>+$${offsetCost.toFixed(2)}</span>
                </div>
                ` : ''}
                <div class="line-item total">
                    <span><strong>Total:</strong></span>
                    <span><strong>$${total.toFixed(2)}</strong></span>
                </div>
            </div>
        `);
    }

    async processPayment() {
        if (this.paymentInProgress) return;
        
        if (!this.account) {
            alert('Please connect your wallet first');
            return;
        }
        
        const selectedToken = jQuery('.epg-ethereum-token-select').val();
        const token = this.supportedTokens[selectedToken];
        
        if (!token) {
            alert('Please select a token');
            return;
        }
        
        this.paymentInProgress = true;
        this.updatePaymentStatus('preparing');
        
        try {
            const carbonOffset = jQuery('.epg-ethereum-carbon-offset').is(':checked');
            const offsetCost = carbonOffset ? token.carbon_per_tx * 0.02 : 0;
            const totalAmount = parseFloat(this.currentOrder.amount) + offsetCost;
            
            // Convert USD to token amount (this would need real exchange rate)
            const tokenAmount = totalAmount / 2000; // Placeholder conversion
            
            let txHash;
            
            if (token.symbol === 'ETH') {
                txHash = await this.sendEthPayment(tokenAmount);
            } else {
                txHash = await this.sendTokenPayment(token, tokenAmount);
            }
            
            this.updatePaymentStatus('confirming');
            await this.monitorTransaction(txHash);
            
            // Submit payment data to backend
            await this.submitPaymentData({
                transaction_hash: txHash,
                token_symbol: token.symbol,
                token_amount: tokenAmount,
                usd_amount: totalAmount,
                carbon_offset: carbonOffset,
                network_id: this.networkId,
                wallet_address: this.account
            });
            
            this.updatePaymentStatus('completed');
            
        } catch (error) {
            console.error('Payment failed:', error);
            this.updatePaymentStatus('failed');
        } finally {
            this.paymentInProgress = false;
        }
    }

    async sendEthPayment(amount) {
        const amountWei = this.web3.utils.toWei(amount.toString(), 'ether');
        
        const transaction = await this.web3.eth.sendTransaction({
            from: this.account,
            to: this.currentOrder.recipient_address,
            value: amountWei,
            gas: 21000
        });
        
        return transaction.transactionHash;
    }

    async sendTokenPayment(token, amount) {
        // ERC-20 token transfer implementation would go here
        // This is a placeholder
        throw new Error('Token payments not implemented yet');
    }

    async monitorTransaction(txHash) {
        return new Promise((resolve, reject) => {
            const checkTransaction = async () => {
                try {
                    const receipt = await this.web3.eth.getTransactionReceipt(txHash);
                    
                    if (receipt) {
                        if (receipt.status) {
                            resolve(receipt);
                        } else {
                            reject(new Error('Transaction failed'));
                        }
                    } else {
                        // Transaction still pending
                        setTimeout(checkTransaction, 3000);
                    }
                } catch (error) {
                    reject(error);
                }
            };
            
            checkTransaction();
        });
    }

    updatePaymentStatus(status) {
        const statusContainer = jQuery('.epg-ethereum-payment-status');
        
        const statusMessages = {
            'preparing': '🔄 Preparing transaction...',
            'confirming': '⏳ Confirming on blockchain...',
            'completed': '✅ Payment completed successfully!',
            'failed': '❌ Payment failed. Please try again.'
        };
        
        statusContainer.removeClass('preparing confirming completed failed')
                      .addClass(status)
                      .find('.status-message')
                      .text(statusMessages[status]);
        
        if (status === 'completed') {
            setTimeout(() => {
                window.location.href = this.currentOrder.return_url;
            }, 3000);
        }
    }

    async submitPaymentData(paymentData) {
        try {
            const response = await fetch(epg_ethereum_vars.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'epg_process_ethereum_payment',
                    nonce: epg_ethereum_vars.nonce,
                    order_id: this.currentOrder.id,
                    payment_data: JSON.stringify(paymentData)
                })
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.data);
            }
            
            return result.data;
            
        } catch (error) {
            console.error('Payment submission failed:', error);
            throw error;
        }
    }

    showWalletInstallPrompt() {
        const prompt = jQuery('.epg-ethereum-install-prompt');
        prompt.html(`
            <div class="install-wallet-prompt">
                <h3>Web3 Wallet Required</h3>
                <p>To use Ethereum payments, please install a Web3 wallet:</p>
                <div class="wallet-options">
                    <a href="https://metamask.io/download/" target="_blank" class="wallet-option">
                        <img src="${epg_ethereum_vars.plugin_url}/assets/images/metamask-logo.png" alt="MetaMask">
                        <span>MetaMask</span>
                    </a>
                    <a href="https://www.coinbase.com/wallet" target="_blank" class="wallet-option">
                        <img src="${epg_ethereum_vars.plugin_url}/assets/images/coinbase-wallet-logo.png" alt="Coinbase Wallet">
                        <span>Coinbase Wallet</span>
                    </a>
                    <a href="https://trustwallet.com/" target="_blank" class="wallet-option">
                        <img src="${epg_ethereum_vars.plugin_url}/assets/images/trust-wallet-logo.png" alt="Trust Wallet">
                        <span>Trust Wallet</span>
                    </a>
                </div>
            </div>
        `).show();
    }

    loadEnvironmentalData() {
        // Load environmental impact data
        this.environmentalData = {
            ethereum_daily_energy: 173, // TWh per year
            polygon_energy_savings: 99.9, // Percentage reduction
            carbon_offset_rate: 0.02 // USD per kg CO₂
        };
    }

    promoteLayer2Networks() {
        const promotion = jQuery('.epg-ethereum-layer2-detailed');
        promotion.html(`
            <div class="layer2-detailed-info">
                <h3>🌱 Choose Eco-Friendly Networks</h3>
                <div class="network-comparison">
                    <div class="network-card ethereum">
                        <h4>Ethereum Mainnet</h4>
                        <div class="stats">
                            <div class="stat">⚡ Energy: Very High</div>
                            <div class="stat">💰 Gas Fees: $20-100+</div>
                            <div class="stat">⏱️ Speed: 15 seconds</div>
                            <div class="stat environmental-impact">🌍 CO₂: 60kg per tx</div>
                        </div>
                    </div>
                    <div class="network-card polygon recommended">
                        <h4>Polygon <span class="badge">RECOMMENDED</span></h4>
                        <div class="stats">
                            <div class="stat">⚡ Energy: 99.9% less</div>
                            <div class="stat">💰 Gas Fees: $0.01-0.10</div>
                            <div class="stat">⏱️ Speed: 2 seconds</div>
                            <div class="stat environmental-impact">🌍 CO₂: 0.1kg per tx</div>
                        </div>
                        <button class="switch-network-btn" data-network="137">Switch to Polygon</button>
                    </div>
                </div>
                <div class="environmental-benefits">
                    <h4>Environmental Benefits of Layer 2:</h4>
                    <ul>
                        <li>🌱 Reduce energy consumption by up to 99.9%</li>
                        <li>💚 Lower carbon footprint per transaction</li>
                        <li>💰 Significantly lower transaction fees</li>
                        <li>⚡ Faster transaction confirmation</li>
                    </ul>
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
    if (typeof epg_ethereum_vars !== 'undefined') {
        window.epgEthereumGateway = new EPGEthereumGateway();
        
        // Set order data if available
        if (typeof epg_ethereum_order !== 'undefined') {
            window.epgEthereumGateway.setOrderData(epg_ethereum_order);
        }
    }
});
