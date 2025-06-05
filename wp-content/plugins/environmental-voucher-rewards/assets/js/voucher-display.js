/**
 * Voucher Display JavaScript
 * Handles voucher interaction functionality
 */

(function($) {
    'use strict';

    // Voucher Display Handler
    window.VoucherDisplay = {
        
        // Initialize voucher display functionality
        init: function() {
            this.bindEvents();
            this.initializeCountdowns();
            this.loadUserVouchers();
            this.initializeCarousels();
            this.initializeModals();
        },

        // Bind event handlers
        bindEvents: function() {
            $(document).on('click', '.voucher-card', this.handleVoucherClick);
            $(document).on('click', '.apply-voucher-btn', this.applyVoucher);
            $(document).on('click', '.share-voucher-btn', this.shareVoucher);
            $(document).on('click', '.qr-toggle-btn', this.toggleQRCode);
            $(document).on('click', '.filter-vouchers', this.filterVouchers);
            $(document).on('click', '.sort-vouchers', this.sortVouchers);
            $(document).on('submit', '.voucher-search-form', this.searchVouchers);
            $(document).on('click', '.redeem-reward-btn', this.redeemReward);
            $(document).on('click', '.view-details-btn', this.viewVoucherDetails);
        },

        // Handle voucher card click
        handleVoucherClick: function(e) {
            e.preventDefault();
            const $card = $(this);
            const voucherId = $card.data('voucher-id');
            
            if (!voucherId) return;

            // Toggle card selection
            $card.toggleClass('selected');
            
            // Update selection counter
            VoucherDisplay.updateSelectionCounter();
            
            // Load voucher details if not already loaded
            if (!$card.hasClass('details-loaded')) {
                VoucherDisplay.loadVoucherDetails(voucherId, $card);
            }
        },

        // Apply voucher to cart
        applyVoucher: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $btn = $(this);
            const voucherId = $btn.data('voucher-id');
            const voucherCode = $btn.data('voucher-code');
            
            if ($btn.hasClass('applying')) return;
            
            $btn.addClass('applying').prop('disabled', true);
            $btn.find('.btn-text').text('Applying...');
            
            $.ajax({
                url: voucher_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'apply_voucher_to_cart',
                    voucher_id: voucherId,
                    voucher_code: voucherCode,
                    nonce: voucher_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VoucherDisplay.showNotification('Voucher applied successfully!', 'success');
                        
                        // Update cart display
                        if (typeof wc_cart_fragments_params !== 'undefined') {
                            $(document.body).trigger('wc_fragment_refresh');
                        }
                        
                        // Update voucher status
                        $btn.closest('.voucher-card').addClass('applied');
                        $btn.removeClass('applying').addClass('applied');
                        $btn.find('.btn-text').text('Applied');
                        
                        // Redirect to cart if requested
                        if (response.data.redirect_to_cart) {
                            setTimeout(function() {
                                window.location.href = response.data.cart_url;
                            }, 1500);
                        }
                    } else {
                        VoucherDisplay.showNotification(response.data.message || 'Failed to apply voucher', 'error');
                        $btn.removeClass('applying').prop('disabled', false);
                        $btn.find('.btn-text').text('Apply');
                    }
                },
                error: function() {
                    VoucherDisplay.showNotification('Network error occurred', 'error');
                    $btn.removeClass('applying').prop('disabled', false);
                    $btn.find('.btn-text').text('Apply');
                }
            });
        },

        // Share voucher functionality
        shareVoucher: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $btn = $(this);
            const voucherId = $btn.data('voucher-id');
            const voucherTitle = $btn.data('voucher-title');
            
            if (navigator.share) {
                navigator.share({
                    title: 'Environmental Voucher',
                    text: `Check out this eco-friendly voucher: ${voucherTitle}`,
                    url: window.location.href
                }).then(() => {
                    VoucherDisplay.trackSocialShare(voucherId, 'native');
                });
            } else {
                // Fallback to social media buttons
                VoucherDisplay.showShareModal(voucherId, voucherTitle);
            }
        },

        // Toggle QR code display
        toggleQRCode: function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $btn = $(this);
            const $qrContainer = $btn.closest('.voucher-card').find('.qr-container');
            
            if ($qrContainer.is(':visible')) {
                $qrContainer.slideUp(300);
                $btn.find('.btn-text').text('Show QR');
            } else {
                // Load QR code if not already loaded
                const voucherId = $btn.data('voucher-id');
                if (!$qrContainer.find('.qr-code').length) {
                    VoucherDisplay.loadQRCode(voucherId, $qrContainer);
                }
                $qrContainer.slideDown(300);
                $btn.find('.btn-text').text('Hide QR');
            }
        },

        // Filter vouchers
        filterVouchers: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const filter = $btn.data('filter');
            
            // Update active filter
            $btn.siblings().removeClass('active');
            $btn.addClass('active');
            
            // Apply filter
            $('.voucher-card').each(function() {
                const $card = $(this);
                const cardType = $card.data('type');
                const cardStatus = $card.data('status');
                
                let show = true;
                
                switch (filter) {
                    case 'active':
                        show = cardStatus === 'active';
                        break;
                    case 'used':
                        show = cardStatus === 'used';
                        break;
                    case 'expired':
                        show = cardStatus === 'expired';
                        break;
                    case 'discount':
                        show = cardType === 'discount';
                        break;
                    case 'cashback':
                        show = cardType === 'cashback';
                        break;
                    case 'freebie':
                        show = cardType === 'freebie';
                        break;
                    default:
                        show = true;
                }
                
                if (show) {
                    $card.show().addClass('filtered-visible');
                } else {
                    $card.hide().removeClass('filtered-visible');
                }
            });
            
            VoucherDisplay.updateDisplayCounter();
        },

        // Sort vouchers
        sortVouchers: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const sort = $btn.data('sort');
            const $container = $('.vouchers-grid, .vouchers-list');
            
            // Update active sort
            $btn.siblings().removeClass('active');
            $btn.addClass('active');
            
            // Get all voucher cards
            const $cards = $container.find('.voucher-card').get();
            
            // Sort cards
            $cards.sort(function(a, b) {
                const $a = $(a);
                const $b = $(b);
                
                switch (sort) {
                    case 'newest':
                        return new Date($b.data('created')) - new Date($a.data('created'));
                    case 'oldest':
                        return new Date($a.data('created')) - new Date($b.data('created'));
                    case 'value-high':
                        return parseFloat($b.data('value')) - parseFloat($a.data('value'));
                    case 'value-low':
                        return parseFloat($a.data('value')) - parseFloat($b.data('value'));
                    case 'expiry':
                        return new Date($a.data('expiry')) - new Date($b.data('expiry'));
                    case 'alphabetical':
                        return $a.find('.voucher-title').text().localeCompare($b.find('.voucher-title').text());
                    default:
                        return 0;
                }
            });
            
            // Reorder DOM elements
            $.each($cards, function(index, card) {
                $container.append(card);
            });
            
            // Add animation
            $container.addClass('reordering');
            setTimeout(function() {
                $container.removeClass('reordering');
            }, 500);
        },

        // Search vouchers
        searchVouchers: function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const query = $form.find('input[type="search"]').val().toLowerCase();
            
            $('.voucher-card').each(function() {
                const $card = $(this);
                const title = $card.find('.voucher-title').text().toLowerCase();
                const description = $card.find('.voucher-description').text().toLowerCase();
                const tags = $card.data('tags') ? $card.data('tags').toLowerCase() : '';
                
                const matches = title.includes(query) || 
                               description.includes(query) || 
                               tags.includes(query);
                
                if (matches || query === '') {
                    $card.show().addClass('search-visible');
                } else {
                    $card.hide().removeClass('search-visible');
                }
            });
            
            VoucherDisplay.updateDisplayCounter();
        },

        // Redeem reward for voucher
        redeemReward: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const rewardId = $btn.data('reward-id');
            const pointsCost = $btn.data('points-cost');
            
            if ($btn.hasClass('redeeming')) return;
            
            // Confirm redemption
            if (!confirm(`Redeem this reward for ${pointsCost} points?`)) {
                return;
            }
            
            $btn.addClass('redeeming').prop('disabled', true);
            $btn.find('.btn-text').text('Redeeming...');
            
            $.ajax({
                url: voucher_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'redeem_reward_voucher',
                    reward_id: rewardId,
                    nonce: voucher_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        VoucherDisplay.showNotification('Reward redeemed successfully!', 'success');
                        
                        // Update user points display
                        $('.user-points-balance').text(response.data.new_balance);
                        
                        // Update button state
                        $btn.removeClass('redeeming').addClass('redeemed');
                        $btn.find('.btn-text').text('Redeemed');
                        
                        // Refresh voucher list
                        setTimeout(function() {
                            VoucherDisplay.loadUserVouchers();
                        }, 1000);
                        
                    } else {
                        VoucherDisplay.showNotification(response.data.message || 'Failed to redeem reward', 'error');
                        $btn.removeClass('redeeming').prop('disabled', false);
                        $btn.find('.btn-text').text('Redeem');
                    }
                },
                error: function() {
                    VoucherDisplay.showNotification('Network error occurred', 'error');
                    $btn.removeClass('redeeming').prop('disabled', false);
                    $btn.find('.btn-text').text('Redeem');
                }
            });
        },

        // View voucher details
        viewVoucherDetails: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const voucherId = $btn.data('voucher-id');
            
            VoucherDisplay.loadVoucherModal(voucherId);
        },

        // Load voucher details
        loadVoucherDetails: function(voucherId, $card) {
            $.ajax({
                url: voucher_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_voucher_details',
                    voucher_id: voucherId,
                    nonce: voucher_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const details = response.data;
                        
                        // Update card with details
                        if (details.terms) {
                            $card.find('.voucher-terms').html(details.terms);
                        }
                        
                        if (details.usage_instructions) {
                            $card.find('.usage-instructions').html(details.usage_instructions);
                        }
                        
                        $card.addClass('details-loaded');
                    }
                }
            });
        },

        // Load QR code
        loadQRCode: function(voucherId, $container) {
            $.ajax({
                url: voucher_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'generate_voucher_qr',
                    voucher_id: voucherId,
                    nonce: voucher_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $container.html(`
                            <div class="qr-code">
                                <img src="${response.data.qr_url}" alt="Voucher QR Code" />
                                <p class="qr-instructions">Scan this code at checkout</p>
                            </div>
                        `);
                    }
                }
            });
        },

        // Load user vouchers
        loadUserVouchers: function() {
            const $container = $('.user-vouchers-container');
            if (!$container.length) return;
            
            $.ajax({
                url: voucher_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_user_vouchers',
                    nonce: voucher_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $container.html(response.data.html);
                        VoucherDisplay.initializeCountdowns();
                    }
                }
            });
        },

        // Initialize countdown timers
        initializeCountdowns: function() {
            $('.voucher-countdown').each(function() {
                const $countdown = $(this);
                const expiry = new Date($countdown.data('expiry')).getTime();
                
                const timer = setInterval(function() {
                    const now = new Date().getTime();
                    const distance = expiry - now;
                    
                    if (distance < 0) {
                        clearInterval(timer);
                        $countdown.text('Expired');
                        $countdown.closest('.voucher-card').addClass('expired');
                        return;
                    }
                    
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    
                    if (days > 0) {
                        $countdown.text(`${days}d ${hours}h`);
                    } else if (hours > 0) {
                        $countdown.text(`${hours}h ${minutes}m`);
                    } else {
                        $countdown.text(`${minutes}m`);
                    }
                    
                    // Add urgency class when less than 24 hours
                    if (distance < 24 * 60 * 60 * 1000) {
                        $countdown.addClass('urgent');
                    }
                }, 1000);
            });
        },

        // Initialize carousels
        initializeCarousels: function() {
            $('.voucher-carousel').each(function() {
                const $carousel = $(this);
                let currentSlide = 0;
                const $slides = $carousel.find('.voucher-slide');
                const totalSlides = $slides.length;
                
                if (totalSlides <= 1) return;
                
                // Add navigation buttons
                $carousel.append(`
                    <button class="carousel-btn prev-btn" aria-label="Previous">‹</button>
                    <button class="carousel-btn next-btn" aria-label="Next">›</button>
                    <div class="carousel-dots"></div>
                `);
                
                // Add dots
                const $dots = $carousel.find('.carousel-dots');
                for (let i = 0; i < totalSlides; i++) {
                    $dots.append(`<button class="carousel-dot ${i === 0 ? 'active' : ''}" data-slide="${i}"></button>`);
                }
                
                // Navigation handlers
                $carousel.on('click', '.prev-btn', function() {
                    currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
                    updateCarousel();
                });
                
                $carousel.on('click', '.next-btn', function() {
                    currentSlide = (currentSlide + 1) % totalSlides;
                    updateCarousel();
                });
                
                $carousel.on('click', '.carousel-dot', function() {
                    currentSlide = parseInt($(this).data('slide'));
                    updateCarousel();
                });
                
                function updateCarousel() {
                    $slides.removeClass('active').eq(currentSlide).addClass('active');
                    $carousel.find('.carousel-dot').removeClass('active').eq(currentSlide).addClass('active');
                }
                
                // Auto-play
                if ($carousel.data('autoplay') !== false) {
                    setInterval(function() {
                        currentSlide = (currentSlide + 1) % totalSlides;
                        updateCarousel();
                    }, 5000);
                }
            });
        },

        // Initialize modals
        initializeModals: function() {
            // Share modal
            $('body').append(`
                <div id="share-modal" class="voucher-modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3>Share Voucher</h3>
                            <button class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="share-buttons">
                                <button class="share-btn facebook" data-platform="facebook">
                                    <i class="fab fa-facebook"></i> Facebook
                                </button>
                                <button class="share-btn twitter" data-platform="twitter">
                                    <i class="fab fa-twitter"></i> Twitter
                                </button>
                                <button class="share-btn linkedin" data-platform="linkedin">
                                    <i class="fab fa-linkedin"></i> LinkedIn
                                </button>
                                <button class="share-btn copy-link" data-platform="copy">
                                    <i class="fas fa-copy"></i> Copy Link
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            // Modal event handlers
            $(document).on('click', '.modal-close, .voucher-modal', function(e) {
                if (e.target === this) {
                    $('.voucher-modal').fadeOut(300);
                }
            });
            
            $(document).on('click', '.share-btn', function() {
                const platform = $(this).data('platform');
                const url = window.location.href;
                const title = $('#share-modal').data('title');
                
                VoucherDisplay.shareOnPlatform(platform, url, title);
            });
        },

        // Show share modal
        showShareModal: function(voucherId, title) {
            $('#share-modal').data('voucher-id', voucherId).data('title', title).fadeIn(300);
        },

        // Share on platform
        shareOnPlatform: function(platform, url, title) {
            let shareUrl = '';
            
            switch (platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
                    break;
                case 'linkedin':
                    shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`;
                    break;
                case 'copy':
                    navigator.clipboard.writeText(url).then(function() {
                        VoucherDisplay.showNotification('Link copied to clipboard!', 'success');
                    });
                    return;
            }
            
            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
                VoucherDisplay.trackSocialShare($('#share-modal').data('voucher-id'), platform);
            }
        },

        // Track social share
        trackSocialShare: function(voucherId, platform) {
            $.ajax({
                url: voucher_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'track_voucher_share',
                    voucher_id: voucherId,
                    platform: platform,
                    nonce: voucher_ajax.nonce
                }
            });
        },

        // Load voucher modal
        loadVoucherModal: function(voucherId) {
            $.ajax({
                url: voucher_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_voucher_modal',
                    voucher_id: voucherId,
                    nonce: voucher_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('body').append(response.data.html);
                        $('#voucher-details-modal').fadeIn(300);
                    }
                }
            });
        },

        // Update selection counter
        updateSelectionCounter: function() {
            const selected = $('.voucher-card.selected').length;
            $('.selection-counter').text(`${selected} selected`);
        },

        // Update display counter
        updateDisplayCounter: function() {
            const visible = $('.voucher-card:visible').length;
            const total = $('.voucher-card').length;
            $('.display-counter').text(`Showing ${visible} of ${total} vouchers`);
        },

        // Show notification
        showNotification: function(message, type = 'info') {
            const $notification = $(`
                <div class="voucher-notification ${type}">
                    <div class="notification-content">
                        <span class="notification-message">${message}</span>
                        <button class="notification-close">&times;</button>
                    </div>
                </div>
            `);
            
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.addClass('show');
            }, 100);
            
            // Auto hide after 5 seconds
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 5000);
            
            // Manual close
            $notification.on('click', '.notification-close', function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        VoucherDisplay.init();
    });

})(jQuery);
