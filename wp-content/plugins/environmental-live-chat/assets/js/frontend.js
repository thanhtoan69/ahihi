/**
 * Environmental Platform - Live Chat & Customer Support Frontend JavaScript
 * Handles all customer-facing functionality including chat widget, FAQ search, and support forms
 */

(function($) {
    'use strict';

    // Global variables
    var chatWidget = null;
    var chatSession = null;
    var messageInterval = null;
    var isTyping = false;
    var typingTimeout = null;
    var currentChatId = null;
    var unreadCount = 0;

    // Chat Widget Class
    function ChatWidget() {
        this.init();
    }

    ChatWidget.prototype = {
        init: function() {
            this.createWidget();
            this.bindEvents();
            this.initializeSettings();
            this.checkBusinessHours();
        },

        createWidget: function() {
            var widgetHTML = `
                <div id="env-chat-widget" class="env-chat-widget">
                    <div id="env-chat-button" class="env-chat-button">
                        <i class="dashicons dashicons-format-chat"></i>
                        <span class="env-chat-badge" id="env-chat-badge" style="display: none;">0</span>
                    </div>
                    <div id="env-chat-window" class="env-chat-window" style="display: none;">
                        <div class="env-chat-header">
                            <div class="env-chat-title">
                                <h4>Hỗ trợ Khách hàng</h4>
                                <div class="env-chat-status" id="env-chat-status">
                                    <span class="status-indicator offline"></span>
                                    <span class="status-text">Offline</span>
                                </div>
                            </div>
                            <div class="env-chat-controls">
                                <button id="env-chat-minimize" class="chat-control-btn">
                                    <i class="dashicons dashicons-minus"></i>
                                </button>
                                <button id="env-chat-close" class="chat-control-btn">
                                    <i class="dashicons dashicons-no"></i>
                                </button>
                            </div>
                        </div>
                        <div class="env-chat-body">
                            <div id="env-pre-chat-form" class="env-pre-chat-form">
                                <h4>Bắt đầu cuộc trò chuyện</h4>
                                <form id="env-start-chat-form">
                                    <div class="form-group">
                                        <input type="text" id="chat-name" name="name" placeholder="Họ tên *" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="email" id="chat-email" name="email" placeholder="Email *" required>
                                    </div>
                                    <div class="form-group">
                                        <input type="tel" id="chat-phone" name="phone" placeholder="Số điện thoại">
                                    </div>
                                    <div class="form-group">
                                        <select id="chat-department" name="department">
                                            <option value="general">Tư vấn chung</option>
                                            <option value="technical">Hỗ trợ kỹ thuật</option>
                                            <option value="sales">Kinh doanh</option>
                                            <option value="billing">Thanh toán</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <textarea id="chat-message" name="message" placeholder="Tin nhắn đầu tiên..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Bắt đầu Chat</button>
                                </form>
                            </div>
                            <div id="env-chat-messages" class="env-chat-messages" style="display: none;">
                                <div class="messages-container" id="messages-container"></div>
                                <div class="typing-indicator" id="typing-indicator" style="display: none;">
                                    <span></span><span></span><span></span>
                                    <span class="typing-text">Nhân viên đang gõ...</span>
                                </div>
                            </div>
                            <div id="env-chat-input-area" class="env-chat-input-area" style="display: none;">
                                <div class="input-container">
                                    <div class="file-upload-area">
                                        <input type="file" id="chat-file-input" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
                                        <button type="button" id="chat-file-btn" class="file-upload-btn">
                                            <i class="dashicons dashicons-paperclip"></i>
                                        </button>
                                    </div>
                                    <textarea id="chat-message-input" placeholder="Nhập tin nhắn..." rows="1"></textarea>
                                    <button type="button" id="chat-send-btn" class="send-btn">
                                        <i class="dashicons dashicons-arrow-right-alt2"></i>
                                    </button>
                                </div>
                                <div class="chat-actions">
                                    <button type="button" id="end-chat-btn" class="btn btn-secondary btn-sm">Kết thúc Chat</button>
                                </div>
                            </div>
                            <div id="env-chat-rating" class="env-chat-rating" style="display: none;">
                                <h4>Đánh giá cuộc trò chuyện</h4>
                                <div class="rating-stars">
                                    <span class="star" data-rating="1">★</span>
                                    <span class="star" data-rating="2">★</span>
                                    <span class="star" data-rating="3">★</span>
                                    <span class="star" data-rating="4">★</span>
                                    <span class="star" data-rating="5">★</span>
                                </div>
                                <textarea id="rating-comment" placeholder="Nhận xét (tùy chọn)"></textarea>
                                <div class="rating-actions">
                                    <button type="button" id="submit-rating-btn" class="btn btn-primary btn-sm">Gửi đánh giá</button>
                                    <button type="button" id="skip-rating-btn" class="btn btn-secondary btn-sm">Bỏ qua</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(widgetHTML);
        },

        bindEvents: function() {
            var self = this;

            // Toggle chat window
            $(document).on('click', '#env-chat-button', function() {
                self.toggleChatWindow();
            });

            // Chat controls
            $(document).on('click', '#env-chat-minimize', function() {
                self.minimizeChat();
            });

            $(document).on('click', '#env-chat-close', function() {
                self.closeChat();
            });

            // Start chat form
            $(document).on('submit', '#env-start-chat-form', function(e) {
                e.preventDefault();
                self.startChat();
            });

            // Send message
            $(document).on('click', '#chat-send-btn', function() {
                self.sendMessage();
            });

            // Enter key to send message
            $(document).on('keypress', '#chat-message-input', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
                self.handleTyping();
            });

            // File upload
            $(document).on('click', '#chat-file-btn', function() {
                $('#chat-file-input').click();
            });

            $(document).on('change', '#chat-file-input', function() {
                self.handleFileUpload(this.files);
            });

            // End chat
            $(document).on('click', '#end-chat-btn', function() {
                self.endChat();
            });

            // Rating system
            $(document).on('click', '.rating-stars .star', function() {
                var rating = $(this).data('rating');
                self.selectRating(rating);
            });

            $(document).on('click', '#submit-rating-btn', function() {
                self.submitRating();
            });

            $(document).on('click', '#skip-rating-btn', function() {
                self.skipRating();
            });

            // Auto-resize textarea
            $(document).on('input', '#chat-message-input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        },

        initializeSettings: function() {
            // Get widget settings from localized data
            if (typeof envChatSettings !== 'undefined') {
                this.applySettings(envChatSettings);
            }
        },

        applySettings: function(settings) {
            var widget = $('#env-chat-widget');
            
            // Position
            widget.removeClass('position-bottom-right position-bottom-left position-top-right position-top-left');
            widget.addClass('position-' + (settings.position || 'bottom-right'));
            
            // Colors
            if (settings.primary_color) {
                this.setCSSVariable('--chat-primary-color', settings.primary_color);
            }
            
            if (settings.secondary_color) {
                this.setCSSVariable('--chat-secondary-color', settings.secondary_color);
            }
        },

        setCSSVariable: function(property, value) {
            document.documentElement.style.setProperty(property, value);
        },

        checkBusinessHours: function() {
            var self = this;
            
            $.ajax({
                url: envChatAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_check_business_hours',
                    nonce: envChatAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateOperatorStatus(response.data);
                    }
                }
            });
        },

        updateOperatorStatus: function(data) {
            var statusIndicator = $('#env-chat-status .status-indicator');
            var statusText = $('#env-chat-status .status-text');
            
            if (data.available) {
                statusIndicator.removeClass('offline').addClass('online');
                statusText.text('Online');
            } else {
                statusIndicator.removeClass('online').addClass('offline');
                statusText.text(data.message || 'Offline');
            }
        },

        toggleChatWindow: function() {
            var chatWindow = $('#env-chat-window');
            var isVisible = chatWindow.is(':visible');
            
            if (isVisible) {
                chatWindow.slideUp(300);
            } else {
                chatWindow.slideDown(300);
                this.resetUnreadCount();
            }
        },

        minimizeChat: function() {
            $('#env-chat-window').slideUp(300);
        },

        closeChat: function() {
            if (currentChatId) {
                this.endChat();
            } else {
                $('#env-chat-window').slideUp(300);
            }
        },

        startChat: function() {
            var self = this;
            var formData = new FormData($('#env-start-chat-form')[0]);
            formData.append('action', 'env_start_chat');
            formData.append('nonce', envChatAjax.nonce);

            $.ajax({
                url: envChatAjax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('#env-start-chat-form button[type="submit"]').prop('disabled', true).text('Đang kết nối...');
                },
                success: function(response) {
                    if (response.success) {
                        currentChatId = response.data.chat_id;
                        self.showChatInterface();
                        self.startMessagePolling();
                        
                        // Add initial message if provided
                        var initialMessage = $('#chat-message').val();
                        if (initialMessage) {
                            setTimeout(function() {
                                $('#chat-message-input').val(initialMessage);
                                self.sendMessage();
                            }, 500);
                        }
                    } else {
                        self.showError(response.data || 'Không thể bắt đầu cuộc trò chuyện');
                    }
                },
                error: function() {
                    self.showError('Lỗi kết nối. Vui lòng thử lại.');
                },
                complete: function() {
                    $('#env-start-chat-form button[type="submit"]').prop('disabled', false).text('Bắt đầu Chat');
                }
            });
        },

        showChatInterface: function() {
            $('#env-pre-chat-form').hide();
            $('#env-chat-messages').show();
            $('#env-chat-input-area').show();
            
            // Add system message
            this.addMessage({
                type: 'system',
                message: 'Cuộc trò chuyện đã bắt đầu. Nhân viên sẽ hỗ trợ bạn trong giây lát.',
                timestamp: new Date().toISOString()
            });
        },

        sendMessage: function() {
            var messageInput = $('#chat-message-input');
            var message = messageInput.val().trim();
            
            if (!message || !currentChatId) {
                return;
            }

            var self = this;
            
            // Add message to UI immediately
            this.addMessage({
                type: 'customer',
                message: message,
                timestamp: new Date().toISOString()
            });
            
            messageInput.val('').trigger('input');

            // Send to server
            $.ajax({
                url: envChatAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_send_message',
                    chat_id: currentChatId,
                    message: message,
                    nonce: envChatAjax.nonce
                },
                success: function(response) {
                    if (!response.success) {
                        self.showError(response.data || 'Không thể gửi tin nhắn');
                    }
                }
            });
        },

        handleFileUpload: function(files) {
            if (!files.length || !currentChatId) {
                return;
            }

            var self = this;
            var formData = new FormData();
            
            for (var i = 0; i < files.length; i++) {
                formData.append('files[]', files[i]);
            }
            
            formData.append('action', 'env_upload_chat_file');
            formData.append('chat_id', currentChatId);
            formData.append('nonce', envChatAjax.nonce);

            $.ajax({
                url: envChatAjax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    self.showUploadProgress();
                },
                success: function(response) {
                    if (response.success) {
                        // Files uploaded successfully
                        response.data.forEach(function(file) {
                            self.addMessage({
                                type: 'customer',
                                message: '',
                                file: file,
                                timestamp: new Date().toISOString()
                            });
                        });
                    } else {
                        self.showError(response.data || 'Không thể tải lên tệp');
                    }
                },
                complete: function() {
                    self.hideUploadProgress();
                    $('#chat-file-input').val('');
                }
            });
        },

        startMessagePolling: function() {
            var self = this;
            
            if (messageInterval) {
                clearInterval(messageInterval);
            }
            
            messageInterval = setInterval(function() {
                self.checkNewMessages();
            }, 2000); // Poll every 2 seconds
        },

        checkNewMessages: function() {
            if (!currentChatId) {
                return;
            }

            var self = this;
            
            $.ajax({
                url: envChatAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_get_messages',
                    chat_id: currentChatId,
                    nonce: envChatAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.processNewMessages(response.data);
                    }
                }
            });
        },

        processNewMessages: function(messages) {
            var messagesContainer = $('#messages-container');
            var currentMessages = messagesContainer.find('.message');
            var currentCount = currentMessages.length;
            
            if (messages.length > currentCount) {
                // Add new messages
                for (var i = currentCount; i < messages.length; i++) {
                    this.addMessage(messages[i], false);
                }
                
                // Update unread count if chat is not visible
                if (!$('#env-chat-window').is(':visible')) {
                    this.updateUnreadCount(messages.length - currentCount);
                }
            }
        },

        addMessage: function(messageData, scroll = true) {
            var messagesContainer = $('#messages-container');
            var messageHTML = this.formatMessage(messageData);
            
            messagesContainer.append(messageHTML);
            
            if (scroll) {
                this.scrollToBottom();
            }
        },

        formatMessage: function(messageData) {
            var timestamp = new Date(messageData.timestamp);
            var timeString = timestamp.toLocaleTimeString('vi-VN', {
                hour: '2-digit',
                minute: '2-digit'
            });
            
            var messageClass = 'message message-' + messageData.type;
            var messageHTML = '<div class="' + messageClass + '">';
            
            if (messageData.type === 'operator') {
                messageHTML += '<div class="message-avatar"><i class="dashicons dashicons-admin-users"></i></div>';
            }
            
            messageHTML += '<div class="message-content">';
            
            if (messageData.file) {
                messageHTML += this.formatFileMessage(messageData.file);
            } else {
                messageHTML += '<div class="message-text">' + this.escapeHtml(messageData.message) + '</div>';
            }
            
            messageHTML += '<div class="message-time">' + timeString + '</div>';
            messageHTML += '</div></div>';
            
            return messageHTML;
        },

        formatFileMessage: function(file) {
            var fileHTML = '<div class="file-message">';
            fileHTML += '<div class="file-icon"><i class="dashicons dashicons-media-default"></i></div>';
            fileHTML += '<div class="file-info">';
            fileHTML += '<div class="file-name">' + file.name + '</div>';
            fileHTML += '<div class="file-size">' + this.formatFileSize(file.size) + '</div>';
            fileHTML += '</div>';
            fileHTML += '<a href="' + file.url + '" target="_blank" class="file-download">';
            fileHTML += '<i class="dashicons dashicons-download"></i>';
            fileHTML += '</a>';
            fileHTML += '</div>';
            
            return fileHTML;
        },

        endChat: function() {
            if (!currentChatId) {
                return;
            }

            var self = this;
            
            $.ajax({
                url: envChatAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_end_chat',
                    chat_id: currentChatId,
                    nonce: envChatAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.stopMessagePolling();
                        self.showRatingForm();
                    }
                }
            });
        },

        showRatingForm: function() {
            $('#env-chat-messages').hide();
            $('#env-chat-input-area').hide();
            $('#env-chat-rating').show();
        },

        selectRating: function(rating) {
            $('.rating-stars .star').removeClass('selected');
            $('.rating-stars .star').each(function() {
                if ($(this).data('rating') <= rating) {
                    $(this).addClass('selected');
                }
            });
            
            $('#env-chat-rating').data('rating', rating);
        },

        submitRating: function() {
            var rating = $('#env-chat-rating').data('rating');
            var comment = $('#rating-comment').val();
            
            if (!rating) {
                this.showError('Vui lòng chọn số sao để đánh giá');
                return;
            }

            var self = this;
            
            $.ajax({
                url: envChatAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_rate_chat',
                    chat_id: currentChatId,
                    rating: rating,
                    comment: comment,
                    nonce: envChatAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showThankYou();
                    } else {
                        self.showError(response.data || 'Không thể gửi đánh giá');
                    }
                }
            });
        },

        skipRating: function() {
            this.showThankYou();
        },

        showThankYou: function() {
            $('#env-chat-rating').html('<div class="thank-you-message"><h4>Cảm ơn bạn!</h4><p>Cảm ơn bạn đã sử dụng dịch vụ hỗ trợ của chúng tôi.</p></div>');
            
            setTimeout(() => {
                this.resetChat();
                $('#env-chat-window').slideUp(300);
            }, 3000);
        },

        resetChat: function() {
            currentChatId = null;
            this.stopMessagePolling();
            
            // Reset UI
            $('#env-pre-chat-form').show();
            $('#env-chat-messages').hide();
            $('#env-chat-input-area').hide();
            $('#env-chat-rating').hide();
            
            // Clear messages
            $('#messages-container').empty();
            
            // Reset form
            $('#env-start-chat-form')[0].reset();
            $('#rating-comment').val('');
            $('.rating-stars .star').removeClass('selected');
            $('#env-chat-rating').removeData('rating');
        },

        stopMessagePolling: function() {
            if (messageInterval) {
                clearInterval(messageInterval);
                messageInterval = null;
            }
        },

        updateUnreadCount: function(count) {
            unreadCount += count;
            var badge = $('#env-chat-badge');
            
            if (unreadCount > 0) {
                badge.text(unreadCount).show();
            } else {
                badge.hide();
            }
        },

        resetUnreadCount: function() {
            unreadCount = 0;
            $('#env-chat-badge').hide();
        },

        handleTyping: function() {
            if (!currentChatId) return;
            
            if (!isTyping) {
                isTyping = true;
                this.sendTypingStatus(true);
            }
            
            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                isTyping = false;
                this.sendTypingStatus(false);
            }, 2000);
        },

        sendTypingStatus: function(typing) {
            $.ajax({
                url: envChatAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_typing_status',
                    chat_id: currentChatId,
                    typing: typing,
                    nonce: envChatAjax.nonce
                }
            });
        },

        showUploadProgress: function() {
            // Show upload progress indicator
            var progressHTML = '<div class="upload-progress">Đang tải lên...</div>';
            $('#messages-container').append(progressHTML);
            this.scrollToBottom();
        },

        hideUploadProgress: function() {
            $('.upload-progress').remove();
        },

        scrollToBottom: function() {
            var messagesContainer = $('#messages-container');
            messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
        },

        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        showError: function(message) {
            // Show error notification
            var errorHTML = '<div class="chat-error">' + message + '</div>';
            $('#messages-container').append(errorHTML);
            this.scrollToBottom();
            
            setTimeout(function() {
                $('.chat-error').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // FAQ Search Widget
    function FAQSearch() {
        this.init();
    }

    FAQSearch.prototype = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // FAQ search
            $(document).on('input', '.env-faq-search input', function() {
                var query = $(this).val();
                clearTimeout(self.searchTimeout);
                
                self.searchTimeout = setTimeout(function() {
                    self.searchFAQ(query);
                }, 300);
            });

            // FAQ category filter
            $(document).on('change', '.env-faq-categories select', function() {
                var category = $(this).val();
                self.filterByCategory(category);
            });

            // FAQ helpful buttons
            $(document).on('click', '.faq-helpful-btn', function() {
                var faqId = $(this).data('faq-id');
                var helpful = $(this).hasClass('helpful-yes');
                self.rateFAQ(faqId, helpful);
            });

            // Expandable FAQ items
            $(document).on('click', '.faq-item-title', function() {
                $(this).parent().toggleClass('expanded');
            });
        },

        searchFAQ: function(query) {
            var container = $('.env-faq-list');
            
            if (query.length < 2) {
                this.showAllFAQ();
                return;
            }

            $.ajax({
                url: envChatAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_search_faq',
                    query: query,
                    nonce: envChatAjax.nonce
                },
                beforeSend: function() {
                    container.addClass('loading');
                },
                success: function(response) {
                    if (response.success) {
                        container.html(response.data);
                    }
                },
                complete: function() {
                    container.removeClass('loading');
                }
            });
        },

        filterByCategory: function(category) {
            var container = $('.env-faq-list');
            
            $.ajax({
                url: envChatAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_filter_faq',
                    category: category,
                    nonce: envChatAjax.nonce
                },
                beforeSend: function() {
                    container.addClass('loading');
                },
                success: function(response) {
                    if (response.success) {
                        container.html(response.data);
                    }
                },
                complete: function() {
                    container.removeClass('loading');
                }
            });
        },

        rateFAQ: function(faqId, helpful) {
            $.ajax({
                url: envChatAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_rate_faq',
                    faq_id: faqId,
                    helpful: helpful ? 1 : 0,
                    nonce: envChatAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update UI to show rating was recorded
                        $('.faq-item[data-faq-id="' + faqId + '"]').find('.faq-helpful').html(
                            '<span class="rating-thanks">Cảm ơn phản hồi của bạn!</span>'
                        );
                    }
                }
            });
        },

        showAllFAQ: function() {
            var container = $('.env-faq-list');
            
            $.ajax({
                url: envChatAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_get_all_faq',
                    nonce: envChatAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        container.html(response.data);
                    }
                }
            });
        }
    };

    // Support Form Handler
    function SupportForm() {
        this.init();
    }

    SupportForm.prototype = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Support form submission
            $(document).on('submit', '.env-support-form form', function(e) {
                e.preventDefault();
                self.submitSupportForm($(this));
            });

            // File upload for support form
            $(document).on('change', '.env-support-form input[type="file"]', function() {
                self.validateFiles(this.files);
            });
        },

        submitSupportForm: function(form) {
            var formData = new FormData(form[0]);
            formData.append('action', 'env_submit_support_ticket');
            formData.append('nonce', envChatAjax.nonce);

            var submitBtn = form.find('button[type="submit"]');
            
            $.ajax({
                url: envChatAjax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    submitBtn.prop('disabled', true).text('Đang gửi...');
                },
                success: function(response) {
                    if (response.success) {
                        form.html('<div class="success-message"><h4>Gửi thành công!</h4><p>Ticket #' + response.data.ticket_number + ' đã được tạo. Chúng tôi sẽ phản hồi sớm nhất có thể.</p></div>');
                    } else {
                        alert(response.data || 'Có lỗi xảy ra. Vui lòng thử lại.');
                    }
                },
                error: function() {
                    alert('Lỗi kết nối. Vui lòng thử lại.');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).text('Gửi yêu cầu');
                }
            });
        },

        validateFiles: function(files) {
            var maxSize = 5 * 1024 * 1024; // 5MB
            var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            
            for (var i = 0; i < files.length; i++) {
                var file = files[i];
                
                if (file.size > maxSize) {
                    alert('Tệp "' + file.name + '" quá lớn. Kích thước tối đa là 5MB.');
                    return false;
                }
                
                if (allowedTypes.indexOf(file.type) === -1) {
                    alert('Tệp "' + file.name + '" không được hỗ trợ.');
                    return false;
                }
            }
            
            return true;
        }
    };

    // Knowledge Base Search
    function KnowledgeBase() {
        this.init();
    }

    KnowledgeBase.prototype = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            // Knowledge base search
            $(document).on('input', '.env-kb-search input', function() {
                var query = $(this).val();
                clearTimeout(self.searchTimeout);
                
                self.searchTimeout = setTimeout(function() {
                    self.searchKnowledgeBase(query);
                }, 300);
            });

            // Knowledge base category filter
            $(document).on('click', '.kb-category-filter', function() {
                var category = $(this).data('category');
                self.filterByCategory(category);
                
                $('.kb-category-filter').removeClass('active');
                $(this).addClass('active');
            });
        },

        searchKnowledgeBase: function(query) {
            var container = $('.env-kb-articles');
            
            $.ajax({
                url: envChatAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_search_knowledge_base',
                    query: query,
                    nonce: envChatAjax.nonce
                },
                beforeSend: function() {
                    container.addClass('loading');
                },
                success: function(response) {
                    if (response.success) {
                        container.html(response.data);
                    }
                },
                complete: function() {
                    container.removeClass('loading');
                }
            });
        },

        filterByCategory: function(category) {
            var container = $('.env-kb-articles');
            
            $.ajax({
                url: envChatAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_filter_knowledge_base',
                    category: category,
                    nonce: envChatAjax.nonce
                },
                beforeSend: function() {
                    container.addClass('loading');
                },
                success: function(response) {
                    if (response.success) {
                        container.html(response.data);
                    }
                },
                complete: function() {
                    container.removeClass('loading');
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize chat widget if enabled
        if (typeof envChatSettings !== 'undefined' && envChatSettings.enabled) {
            chatWidget = new ChatWidget();
        }

        // Initialize FAQ search
        if ($('.env-faq-widget').length) {
            new FAQSearch();
        }

        // Initialize support form
        if ($('.env-support-form').length) {
            new SupportForm();
        }

        // Initialize knowledge base
        if ($('.env-knowledge-base').length) {
            new KnowledgeBase();
        }

        // Auto-hide notifications
        setTimeout(function() {
            $('.env-notification').fadeOut();
        }, 5000);
    });

    $(window).on('beforeunload', function() {
        // Clean up when leaving page
        if (messageInterval) {
            clearInterval(messageInterval);
        }
        
        if (currentChatId) {
            // Send end chat signal
            navigator.sendBeacon(envChatAjax.ajaxurl, new URLSearchParams({
                action: 'env_end_chat',
                chat_id: currentChatId,
                nonce: envChatAjax.nonce
            }));
        }
    });

})(jQuery);
