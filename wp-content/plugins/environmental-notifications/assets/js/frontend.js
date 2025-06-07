/**
 * Environmental Notifications - Frontend JavaScript
 * Handles real-time notifications, messaging, and user interactions
 */

(function($) {
    'use strict';

    // Global configuration
    const ENV_NOTIFICATIONS = {
        ajax_url: env_notifications_ajax.ajax_url,
        nonce: env_notifications_ajax.nonce,
        user_id: env_notifications_ajax.user_id,
        realtime_url: env_notifications_ajax.realtime_url,
        push_public_key: env_notifications_ajax.push_public_key,
        connection: null,
        messageWindow: null,
        lastHeartbeat: Date.now(),
        reconnectAttempts: 0,
        maxReconnectAttempts: 5,
        reconnectDelay: 1000
    };

    /**
     * Initialize the notification system
     */
    function init() {
        setupNotificationBell();
        setupMessaging();
        setupToastNotifications();
        setupEmailPreferences();
        setupRealTimeConnection();
        setupPushNotifications();
        loadUnreadCount();
        
        // Cleanup on page unload
        $(window).on('beforeunload', cleanup);
    }

    /**
     * Setup notification bell and dropdown
     */
    function setupNotificationBell() {
        const $bell = $('.en-notification-bell');
        const $dropdown = $('.en-notification-dropdown');

        // Toggle dropdown
        $bell.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if ($dropdown.hasClass('active')) {
                closeNotificationDropdown();
            } else {
                openNotificationDropdown();
            }
        });

        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.en-notification-bell, .en-notification-dropdown').length) {
                closeNotificationDropdown();
            }
        });

        // Mark all as read
        $(document).on('click', '.en-mark-all-read', markAllAsRead);

        // Individual notification click
        $(document).on('click', '.en-notification-item', function() {
            const notificationId = $(this).data('id');
            if ($(this).hasClass('unread')) {
                markAsRead(notificationId);
            }
        });
    }

    /**
     * Open notification dropdown
     */
    function openNotificationDropdown() {
        $('.en-notification-dropdown').addClass('active');
        loadNotifications();
    }

    /**
     * Close notification dropdown
     */
    function closeNotificationDropdown() {
        $('.en-notification-dropdown').removeClass('active');
    }

    /**
     * Load notifications via AJAX
     */
    function loadNotifications() {
        const $list = $('.en-notification-list');
        $list.addClass('en-loading');

        $.ajax({
            url: ENV_NOTIFICATIONS.ajax_url,
            type: 'POST',
            data: {
                action: 'en_get_notifications',
                nonce: ENV_NOTIFICATIONS.nonce,
                limit: 10
            },
            success: function(response) {
                if (response.success) {
                    renderNotifications(response.data.notifications);
                } else {
                    showToast('Error loading notifications', 'error');
                }
            },
            error: function() {
                showToast('Failed to load notifications', 'error');
            },
            complete: function() {
                $list.removeClass('en-loading');
            }
        });
    }

    /**
     * Render notifications in dropdown
     */
    function renderNotifications(notifications) {
        const $list = $('.en-notification-list');
        
        if (!notifications || notifications.length === 0) {
            $list.html(`
                <div class="en-empty-notifications">
                    <div class="dashicons dashicons-bell"></div>
                    <p>No notifications yet</p>
                </div>
            `);
            return;
        }

        let html = '';
        notifications.forEach(function(notification) {
            html += `
                <div class="en-notification-item ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}">
                    <div class="en-notification-content">
                        <div class="en-notification-title">${escapeHtml(notification.title)}</div>
                        <div class="en-notification-message">${escapeHtml(notification.message)}</div>
                    </div>
                    <div class="en-notification-meta">
                        <span class="en-notification-time">${timeAgo(notification.created_at)}</span>
                        <span class="en-notification-type ${notification.type}">${notification.type}</span>
                    </div>
                </div>
            `;
        });

        $list.html(html);
    }

    /**
     * Mark notification as read
     */
    function markAsRead(notificationId) {
        $.ajax({
            url: ENV_NOTIFICATIONS.ajax_url,
            type: 'POST',
            data: {
                action: 'en_mark_notification_read',
                nonce: ENV_NOTIFICATIONS.nonce,
                notification_id: notificationId
            },
            success: function(response) {
                if (response.success) {
                    $(`.en-notification-item[data-id="${notificationId}"]`).removeClass('unread');
                    updateUnreadCount();
                }
            }
        });
    }

    /**
     * Mark all notifications as read
     */
    function markAllAsRead() {
        $.ajax({
            url: ENV_NOTIFICATIONS.ajax_url,
            type: 'POST',
            data: {
                action: 'en_mark_all_read',
                nonce: ENV_NOTIFICATIONS.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.en-notification-item').removeClass('unread');
                    $('.en-badge').hide();
                    showToast('All notifications marked as read', 'success');
                }
            }
        });
    }

    /**
     * Load and update unread count
     */
    function loadUnreadCount() {
        $.ajax({
            url: ENV_NOTIFICATIONS.ajax_url,
            type: 'POST',
            data: {
                action: 'en_get_unread_count',
                nonce: ENV_NOTIFICATIONS.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateBadge(response.data.count);
                }
            }
        });
    }

    /**
     * Update unread count
     */
    function updateUnreadCount() {
        const unreadCount = $('.en-notification-item.unread').length;
        updateBadge(unreadCount);
    }

    /**
     * Update notification badge
     */
    function updateBadge(count) {
        const $badge = $('.en-badge');
        if (count > 0) {
            $badge.text(count > 99 ? '99+' : count).show();
        } else {
            $badge.hide();
        }
    }

    /**
     * Setup in-app messaging system
     */
    function setupMessaging() {
        const $toggle = $('.en-message-toggle');
        const $window = $('.en-message-window');
        const $close = $('.en-message-close');
        const $form = $('.en-message-form');
        const $input = $('.en-message-input');

        // Toggle message window
        $toggle.on('click', function() {
            if ($window.hasClass('active')) {
                closeMessageWindow();
            } else {
                openMessageWindow();
            }
        });

        // Close message window
        $close.on('click', closeMessageWindow);

        // Send message
        $form.on('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });

        // Auto-resize textarea
        $input.on('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

        // Send on Enter (not Shift+Enter)
        $input.on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        loadMessageCount();
    }

    /**
     * Open message window
     */
    function openMessageWindow() {
        $('.en-message-window').addClass('active');
        loadMessages();
        $('.en-message-input').focus();
    }

    /**
     * Close message window
     */
    function closeMessageWindow() {
        $('.en-message-window').removeClass('active');
    }

    /**
     * Load messages
     */
    function loadMessages() {
        const $list = $('.en-message-list');
        $list.addClass('en-loading');

        $.ajax({
            url: ENV_NOTIFICATIONS.ajax_url,
            type: 'POST',
            data: {
                action: 'en_get_messages',
                nonce: ENV_NOTIFICATIONS.nonce,
                limit: 50
            },
            success: function(response) {
                if (response.success) {
                    renderMessages(response.data.messages);
                }
            },
            complete: function() {
                $list.removeClass('en-loading');
            }
        });
    }

    /**
     * Render messages in chat window
     */
    function renderMessages(messages) {
        const $list = $('.en-message-list');
        let html = '';

        messages.forEach(function(message) {
            const isOwnMessage = message.sender_id == ENV_NOTIFICATIONS.user_id;
            html += `
                <div class="en-message-item ${isOwnMessage ? 'sent' : 'received'}">
                    <div class="en-message-content">${escapeHtml(message.content)}</div>
                    <div class="en-message-time">${timeAgo(message.created_at)}</div>
                </div>
            `;
        });

        if (html === '') {
            html = '<div class="en-empty-messages">No messages yet. Start a conversation!</div>';
        }

        $list.html(html);
        scrollToBottom($list[0]);
    }

    /**
     * Send a message
     */
    function sendMessage() {
        const $input = $('.en-message-input');
        const $send = $('.en-message-send');
        const content = $input.val().trim();

        if (!content) return;

        $send.prop('disabled', true);

        $.ajax({
            url: ENV_NOTIFICATIONS.ajax_url,
            type: 'POST',
            data: {
                action: 'en_send_message',
                nonce: ENV_NOTIFICATIONS.nonce,
                content: content,
                recipient_id: 0 // System messages for now
            },
            success: function(response) {
                if (response.success) {
                    $input.val('').trigger('input');
                    addMessageToList(content, true);
                } else {
                    showToast('Failed to send message', 'error');
                }
            },
            error: function() {
                showToast('Failed to send message', 'error');
            },
            complete: function() {
                $send.prop('disabled', false);
                $input.focus();
            }
        });
    }

    /**
     * Add message to chat list
     */
    function addMessageToList(content, isSent) {
        const $list = $('.en-message-list');
        const messageHtml = `
            <div class="en-message-item ${isSent ? 'sent' : 'received'}">
                <div class="en-message-content">${escapeHtml(content)}</div>
                <div class="en-message-time">Just now</div>
            </div>
        `;
        
        $list.append(messageHtml);
        scrollToBottom($list[0]);
    }

    /**
     * Load message count
     */
    function loadMessageCount() {
        $.ajax({
            url: ENV_NOTIFICATIONS.ajax_url,
            type: 'POST',
            data: {
                action: 'en_get_unread_messages_count',
                nonce: ENV_NOTIFICATIONS.nonce
            },
            success: function(response) {
                if (response.success && response.data.count > 0) {
                    updateMessageBadge(response.data.count);
                }
            }
        });
    }

    /**
     * Update message badge
     */
    function updateMessageBadge(count) {
        let $badge = $('.en-message-badge');
        if ($badge.length === 0) {
            $badge = $('<span class="en-message-badge"></span>');
            $('.en-message-toggle').append($badge);
        }
        $badge.text(count > 99 ? '99+' : count).show();
    }

    /**
     * Setup toast notifications
     */
    function setupToastNotifications() {
        // Create toast container if it doesn't exist
        if (!$('.en-toast-container').length) {
            $('body').append('<div class="en-toast-container"></div>');
        }
    }

    /**
     * Show toast notification
     */
    function showToast(message, type = 'info', title = '', duration = 5000) {
        const icons = {
            success: 'dashicons-yes-alt',
            error: 'dashicons-dismiss',
            warning: 'dashicons-warning',
            info: 'dashicons-info'
        };

        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div class="en-toast ${type}" id="${toastId}">
                <div class="en-toast-icon dashicons ${icons[type] || icons.info}"></div>
                <div class="en-toast-content">
                    ${title ? `<div class="en-toast-title">${escapeHtml(title)}</div>` : ''}
                    <div class="en-toast-message">${escapeHtml(message)}</div>
                </div>
                <button class="en-toast-close" onclick="removeToast('${toastId}')">×</button>
            </div>
        `;

        $('.en-toast-container').append(toastHtml);
        
        // Show toast
        setTimeout(() => {
            $('#' + toastId).addClass('show');
        }, 100);

        // Auto-remove after duration
        setTimeout(() => {
            removeToast(toastId);
        }, duration);
    }

    /**
     * Remove toast notification
     */
    window.removeToast = function(toastId) {
        const $toast = $('#' + toastId);
        $toast.removeClass('show');
        setTimeout(() => {
            $toast.remove();
        }, 300);
    };

    /**
     * Setup email preferences modal
     */
    function setupEmailPreferences() {
        $(document).on('click', '.en-email-preferences-btn', openPreferencesModal);
        $(document).on('click', '.en-preferences-modal .en-message-close, .en-preferences-modal .en-btn-secondary', closePreferencesModal);
        $(document).on('click', '.en-preference-toggle', togglePreference);
        $(document).on('click', '.en-preferences-modal .en-btn-primary', savePreferences);
    }

    /**
     * Open preferences modal
     */
    function openPreferencesModal() {
        if (!$('.en-preferences-modal').length) {
            createPreferencesModal();
        }
        $('.en-preferences-modal').addClass('active');
        loadEmailPreferences();
    }

    /**
     * Close preferences modal
     */
    function closePreferencesModal() {
        $('.en-preferences-modal').removeClass('active');
    }

    /**
     * Create preferences modal HTML
     */
    function createPreferencesModal() {
        const modalHtml = `
            <div class="en-preferences-modal">
                <div class="en-preferences-content">
                    <div class="en-preferences-header">
                        <h2>Email Preferences</h2>
                        <button class="en-message-close">×</button>
                    </div>
                    <div class="en-preferences-body">
                        <div class="en-preference-group">
                            <h3>Environmental Alerts</h3>
                            <div class="en-preference-item">
                                <div class="en-preference-label">
                                    <div class="en-preference-title">Air Quality Alerts</div>
                                    <div class="en-preference-description">Receive notifications about air quality changes in your area</div>
                                </div>
                                <div class="en-preference-toggle" data-pref="air_quality"></div>
                            </div>
                            <div class="en-preference-item">
                                <div class="en-preference-label">
                                    <div class="en-preference-title">Weather Warnings</div>
                                    <div class="en-preference-description">Get alerts about severe weather conditions</div>
                                </div>
                                <div class="en-preference-toggle" data-pref="weather_warnings"></div>
                            </div>
                        </div>
                        <div class="en-preference-group">
                            <h3>Community Updates</h3>
                            <div class="en-preference-item">
                                <div class="en-preference-label">
                                    <div class="en-preference-title">New Reports</div>
                                    <div class="en-preference-description">Notifications when new environmental reports are published</div>
                                </div>
                                <div class="en-preference-toggle" data-pref="new_reports"></div>
                            </div>
                            <div class="en-preference-item">
                                <div class="en-preference-label">
                                    <div class="en-preference-title">Discussion Updates</div>
                                    <div class="en-preference-description">Updates on forum discussions you're following</div>
                                </div>
                                <div class="en-preference-toggle" data-pref="discussion_updates"></div>
                            </div>
                        </div>
                    </div>
                    <div class="en-preferences-footer">
                        <button class="en-btn en-btn-secondary">Cancel</button>
                        <button class="en-btn en-btn-primary">Save Preferences</button>
                    </div>
                </div>
            </div>
        `;
        $('body').append(modalHtml);
    }

    /**
     * Toggle preference setting
     */
    function togglePreference() {
        $(this).toggleClass('active');
    }

    /**
     * Load email preferences
     */
    function loadEmailPreferences() {
        $.ajax({
            url: ENV_NOTIFICATIONS.ajax_url,
            type: 'POST',
            data: {
                action: 'en_get_email_preferences',
                nonce: ENV_NOTIFICATIONS.nonce
            },
            success: function(response) {
                if (response.success) {
                    Object.keys(response.data.preferences).forEach(function(pref) {
                        const $toggle = $(`.en-preference-toggle[data-pref="${pref}"]`);
                        if (response.data.preferences[pref]) {
                            $toggle.addClass('active');
                        }
                    });
                }
            }
        });
    }

    /**
     * Save preferences
     */
    function savePreferences() {
        const preferences = {};
        $('.en-preference-toggle').each(function() {
            const pref = $(this).data('pref');
            preferences[pref] = $(this).hasClass('active');
        });

        $.ajax({
            url: ENV_NOTIFICATIONS.ajax_url,
            type: 'POST',
            data: {
                action: 'en_update_email_preferences',
                nonce: ENV_NOTIFICATIONS.nonce,
                preferences: preferences
            },
            success: function(response) {
                if (response.success) {
                    showToast('Preferences saved successfully', 'success');
                    closePreferencesModal();
                } else {
                    showToast('Failed to save preferences', 'error');
                }
            }
        });
    }

    /**
     * Setup real-time connection
     */
    function setupRealTimeConnection() {
        if (!ENV_NOTIFICATIONS.realtime_url) return;

        connectToRealTime();

        // Reconnect on connection loss
        setInterval(function() {
            if (Date.now() - ENV_NOTIFICATIONS.lastHeartbeat > 35000) {
                console.log('Connection lost, reconnecting...');
                connectToRealTime();
            }
        }, 30000);
    }

    /**
     * Connect to real-time server
     */
    function connectToRealTime() {
        if (ENV_NOTIFICATIONS.connection) {
            ENV_NOTIFICATIONS.connection.close();
        }

        const url = `${ENV_NOTIFICATIONS.realtime_url}?user_id=${ENV_NOTIFICATIONS.user_id}`;
        ENV_NOTIFICATIONS.connection = new EventSource(url);

        ENV_NOTIFICATIONS.connection.onopen = function() {
            console.log('Real-time connection established');
            ENV_NOTIFICATIONS.reconnectAttempts = 0;
            ENV_NOTIFICATIONS.lastHeartbeat = Date.now();
        };

        ENV_NOTIFICATIONS.connection.onmessage = function(event) {
            handleRealTimeMessage(JSON.parse(event.data));
        };

        ENV_NOTIFICATIONS.connection.onerror = function() {
            console.log('Real-time connection error');
            handleConnectionError();
        };
    }

    /**
     * Handle real-time messages
     */
    function handleRealTimeMessage(data) {
        ENV_NOTIFICATIONS.lastHeartbeat = Date.now();

        switch (data.type) {
            case 'heartbeat':
                // Just update last heartbeat
                break;
            case 'notification':
                handleNewNotification(data.data);
                break;
            case 'message':
                handleNewMessage(data.data);
                break;
        }
    }

    /**
     * Handle new notification
     */
    function handleNewNotification(notification) {
        // Update badge
        updateBadge(parseInt($('.en-badge').text() || 0) + 1);
        
        // Show toast
        showToast(notification.message, 'info', notification.title);
        
        // Add to dropdown if open
        if ($('.en-notification-dropdown').hasClass('active')) {
            loadNotifications();
        }
    }

    /**
     * Handle new message
     */
    function handleNewMessage(message) {
        // Update message badge
        updateMessageBadge(parseInt($('.en-message-badge').text() || 0) + 1);
        
        // Add to chat if window is open
        if ($('.en-message-window').hasClass('active')) {
            addMessageToList(message.content, false);
        }
        
        // Show toast if window is closed
        else {
            showToast('New message received', 'info');
        }
    }

    /**
     * Handle connection errors
     */
    function handleConnectionError() {
        if (ENV_NOTIFICATIONS.reconnectAttempts < ENV_NOTIFICATIONS.maxReconnectAttempts) {
            ENV_NOTIFICATIONS.reconnectAttempts++;
            const delay = ENV_NOTIFICATIONS.reconnectDelay * Math.pow(2, ENV_NOTIFICATIONS.reconnectAttempts - 1);
            
            setTimeout(function() {
                console.log(`Reconnection attempt ${ENV_NOTIFICATIONS.reconnectAttempts}`);
                connectToRealTime();
            }, delay);
        }
    }

    /**
     * Setup push notifications
     */
    function setupPushNotifications() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            console.log('Push notifications not supported');
            return;
        }

        // Register service worker
        navigator.serviceWorker.register('/wp-content/plugins/environmental-notifications/assets/js/sw.js')
            .then(function(registration) {
                console.log('Service Worker registered');
                checkPushSubscription(registration);
            })
            .catch(function(error) {
                console.log('Service Worker registration failed:', error);
            });
    }

    /**
     * Check push subscription status
     */
    function checkPushSubscription(registration) {
        registration.pushManager.getSubscription()
            .then(function(subscription) {
                if (subscription) {
                    // Already subscribed
                } else {
                    // Offer to subscribe
                    offerPushSubscription(registration);
                }
            });
    }

    /**
     * Offer push subscription
     */
    function offerPushSubscription(registration) {
        // Only offer if user hasn't been asked recently
        if (localStorage.getItem('push-notification-asked') === 'true') {
            return;
        }

        setTimeout(function() {
            if (confirm('Would you like to receive push notifications for important environmental alerts?')) {
                subscribeToPush(registration);
            } else {
                localStorage.setItem('push-notification-asked', 'true');
            }
        }, 5000);
    }

    /**
     * Subscribe to push notifications
     */
    function subscribeToPush(registration) {
        const applicationServerKey = urlBase64ToUint8Array(ENV_NOTIFICATIONS.push_public_key);
        
        registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: applicationServerKey
        })
        .then(function(subscription) {
            sendSubscriptionToServer(subscription);
        })
        .catch(function(error) {
            console.log('Push subscription failed:', error);
            showToast('Failed to enable push notifications', 'error');
        });
    }

    /**
     * Send subscription to server
     */
    function sendSubscriptionToServer(subscription) {
        $.ajax({
            url: ENV_NOTIFICATIONS.ajax_url,
            type: 'POST',
            data: {
                action: 'en_save_push_subscription',
                nonce: ENV_NOTIFICATIONS.nonce,
                subscription: JSON.stringify(subscription)
            },
            success: function(response) {
                if (response.success) {
                    showToast('Push notifications enabled', 'success');
                    localStorage.setItem('push-notification-asked', 'true');
                }
            }
        });
    }

    /**
     * Utility functions
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60
        };

        for (const [unit, secondsInUnit] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / secondsInUnit);
            if (interval >= 1) {
                return `${interval} ${unit}${interval === 1 ? '' : 's'} ago`;
            }
        }

        return 'Just now';
    }

    function scrollToBottom(element) {
        element.scrollTop = element.scrollHeight;
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function cleanup() {
        if (ENV_NOTIFICATIONS.connection) {
            ENV_NOTIFICATIONS.connection.close();
        }
    }

    // Initialize when document is ready
    $(document).ready(init);

})(jQuery);
