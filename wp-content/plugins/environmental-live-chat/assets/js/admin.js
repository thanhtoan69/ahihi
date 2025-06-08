/**
 * Environmental Platform - Live Chat & Customer Support Admin JavaScript
 * Handles all admin dashboard functionality including operator interface, analytics, and management
 */

(function($) {
    'use strict';

    // Global variables
    var chatInterval = null;
    var dashboardInterval = null;
    var currentPage = 'dashboard';
    var activeChats = {};
    var soundEnabled = true;

    // Admin Dashboard Class
    function AdminDashboard() {
        this.init();
    }

    AdminDashboard.prototype = {
        init: function() {
            this.bindEvents();
            this.initializeDashboard();
            this.startDashboardUpdates();
        },

        bindEvents: function() {
            var self = this;

            // Navigation
            $(document).on('click', '.env-admin-nav a', function(e) {
                e.preventDefault();
                var page = $(this).data('page');
                self.showPage(page);
                
                $('.env-admin-nav a').removeClass('current');
                $(this).addClass('current');
            });

            // Dashboard refresh
            $(document).on('click', '#refresh-dashboard', function() {
                self.refreshDashboard();
            });

            // Sound toggle
            $(document).on('click', '#toggle-sound', function() {
                soundEnabled = !soundEnabled;
                $(this).toggleClass('sound-off');
                localStorage.setItem('env_chat_sound', soundEnabled);
            });

            // Export data
            $(document).on('click', '.export-data', function() {
                var type = $(this).data('type');
                self.exportData(type);
            });
        },

        initializeDashboard: function() {
            // Load saved sound preference
            var savedSound = localStorage.getItem('env_chat_sound');
            if (savedSound !== null) {
                soundEnabled = savedSound === 'true';
                $('#toggle-sound').toggleClass('sound-off', !soundEnabled);
            }

            // Initialize charts
            this.initializeCharts();
            
            // Load initial data
            this.loadDashboardData();
        },

        showPage: function(page) {
            currentPage = page;
            $('.env-admin-page').hide();
            $('#env-admin-' + page).show();
            
            // Load page-specific data
            switch(page) {
                case 'live-chat':
                    this.loadLiveChatData();
                    break;
                case 'tickets':
                    this.loadTicketsData();
                    break;
                case 'faq':
                    this.loadFAQData();
                    break;
                case 'analytics':
                    this.loadAnalyticsData();
                    break;
                case 'settings':
                    this.loadSettingsData();
                    break;
            }
        },

        startDashboardUpdates: function() {
            var self = this;
            
            // Update dashboard every 30 seconds
            dashboardInterval = setInterval(function() {
                if (currentPage === 'dashboard') {
                    self.loadDashboardData();
                }
            }, 30000);
            
            // Update live chat every 5 seconds
            chatInterval = setInterval(function() {
                if (currentPage === 'live-chat') {
                    self.updateLiveChats();
                }
            }, 5000);
        },

        loadDashboardData: function() {
            var self = this;
            
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_get_dashboard_stats',
                    nonce: envChatAdminAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateDashboardStats(response.data);
                    }
                }
            });
        },

        updateDashboardStats: function(data) {
            // Update overview stats
            $('#active-chats-count').text(data.active_chats || 0);
            $('#pending-tickets-count').text(data.pending_tickets || 0);
            $('#online-operators-count').text(data.online_operators || 0);
            $('#satisfaction-rate').text((data.satisfaction_rate || 0).toFixed(1) + '%');
            
            // Update today's stats
            $('#today-chats').text(data.today_chats || 0);
            $('#today-tickets').text(data.today_tickets || 0);
            $('#avg-response-time').text(this.formatTime(data.avg_response_time || 0));
            $('#resolved-tickets').text(data.resolved_tickets || 0);
            
            // Update charts if data available
            if (data.chart_data) {
                this.updateCharts(data.chart_data);
            }
            
            // Update recent activity
            if (data.recent_activity) {
                this.updateRecentActivity(data.recent_activity);
            }
        },

        initializeCharts: function() {
            // Initialize Chart.js charts
            var ctx1 = document.getElementById('chats-chart');
            if (ctx1) {
                this.chatsChart = new Chart(ctx1, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Cuộc trò chuyện',
                            data: [],
                            borderColor: '#007cba',
                            backgroundColor: 'rgba(0, 124, 186, 0.1)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
            
            var ctx2 = document.getElementById('tickets-chart');
            if (ctx2) {
                this.ticketsChart = new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Tickets',
                            data: [],
                            backgroundColor: '#00a32a'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        },

        updateCharts: function(chartData) {
            if (this.chatsChart && chartData.chats) {
                this.chatsChart.data.labels = chartData.chats.labels;
                this.chatsChart.data.datasets[0].data = chartData.chats.data;
                this.chatsChart.update();
            }
            
            if (this.ticketsChart && chartData.tickets) {
                this.ticketsChart.data.labels = chartData.tickets.labels;
                this.ticketsChart.data.datasets[0].data = chartData.tickets.data;
                this.ticketsChart.update();
            }
        },

        updateRecentActivity: function(activities) {
            var container = $('#recent-activity-list');
            container.empty();
            
            activities.forEach(function(activity) {
                var activityHTML = '<div class="activity-item">';
                activityHTML += '<div class="activity-icon activity-' + activity.type + '"></div>';
                activityHTML += '<div class="activity-content">';
                activityHTML += '<div class="activity-text">' + activity.message + '</div>';
                activityHTML += '<div class="activity-time">' + activity.time + '</div>';
                activityHTML += '</div>';
                activityHTML += '</div>';
                
                container.append(activityHTML);
            });
        },

        refreshDashboard: function() {
            this.loadDashboardData();
            this.showNotification('Dashboard đã được cập nhật', 'success');
        },

        formatTime: function(seconds) {
            if (seconds < 60) {
                return seconds + 's';
            } else if (seconds < 3600) {
                return Math.floor(seconds / 60) + 'm';
            } else {
                return Math.floor(seconds / 3600) + 'h ' + Math.floor((seconds % 3600) / 60) + 'm';
            }
        },

        exportData: function(type) {
            var self = this;
            
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_export_data',
                    type: type,
                    nonce: envChatAdminAjax.nonce
                },
                beforeSend: function() {
                    self.showNotification('Đang xuất dữ liệu...', 'info');
                },
                success: function(response) {
                    if (response.success) {
                        // Trigger download
                        var link = document.createElement('a');
                        link.href = response.data.url;
                        link.download = response.data.filename;
                        link.click();
                        
                        self.showNotification('Xuất dữ liệu thành công', 'success');
                    } else {
                        self.showNotification(response.data || 'Lỗi xuất dữ liệu', 'error');
                    }
                }
            });
        },

        showNotification: function(message, type) {
            var notification = '<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>';
            $('.env-admin-notices').html(notification);
            
            setTimeout(function() {
                $('.notice').fadeOut();
            }, 5000);
        }
    };

    // Live Chat Manager Class
    function LiveChatManager() {
        this.init();
    }

    LiveChatManager.prototype = {
        init: function() {
            this.bindEvents();
            this.loadActiveChats();
        },

        bindEvents: function() {
            var self = this;

            // Chat selection
            $(document).on('click', '.chat-item', function() {
                var chatId = $(this).data('chat-id');
                self.selectChat(chatId);
            });

            // Send message
            $(document).on('click', '#send-operator-message', function() {
                self.sendMessage();
            });

            $(document).on('keypress', '#operator-message-input', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });

            // Transfer chat
            $(document).on('click', '#transfer-chat', function() {
                var chatId = $('.chat-item.active').data('chat-id');
                self.showTransferDialog(chatId);
            });

            // End chat
            $(document).on('click', '#end-chat', function() {
                var chatId = $('.chat-item.active').data('chat-id');
                self.endChat(chatId);
            });

            // File upload
            $(document).on('change', '#operator-file-input', function() {
                self.handleFileUpload(this.files);
            });

            // Quick responses
            $(document).on('click', '.quick-response', function() {
                var response = $(this).data('response');
                $('#operator-message-input').val(response);
            });

            // Operator status change
            $(document).on('change', '#operator-status', function() {
                var status = $(this).val();
                self.updateOperatorStatus(status);
            });
        },

        loadActiveChats: function() {
            var self = this;
            
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_get_active_chats',
                    nonce: envChatAdminAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateChatsList(response.data);
                    }
                }
            });
        },

        updateLiveChats: function() {
            this.loadActiveChats();
            
            // Update messages for active chat
            var activeChatId = $('.chat-item.active').data('chat-id');
            if (activeChatId) {
                this.loadChatMessages(activeChatId);
            }
        },

        updateChatsList: function(chats) {
            var container = $('#active-chats-list');
            var currentActive = $('.chat-item.active').data('chat-id');
            
            container.empty();
            
            chats.forEach(function(chat) {
                var chatHTML = '<div class="chat-item" data-chat-id="' + chat.id + '">';
                chatHTML += '<div class="chat-customer">';
                chatHTML += '<div class="customer-name">' + chat.customer_name + '</div>';
                chatHTML += '<div class="customer-email">' + chat.customer_email + '</div>';
                chatHTML += '</div>';
                chatHTML += '<div class="chat-info">';
                chatHTML += '<div class="chat-time">' + chat.started_at + '</div>';
                chatHTML += '<div class="chat-status status-' + chat.status + '">' + chat.status + '</div>';
                chatHTML += '</div>';
                if (chat.unread_count > 0) {
                    chatHTML += '<div class="unread-badge">' + chat.unread_count + '</div>';
                }
                chatHTML += '</div>';
                
                container.append(chatHTML);
            });
            
            // Restore active chat
            if (currentActive) {
                $('.chat-item[data-chat-id="' + currentActive + '"]').addClass('active');
            }
        },

        selectChat: function(chatId) {
            $('.chat-item').removeClass('active');
            $('.chat-item[data-chat-id="' + chatId + '"]').addClass('active');
            
            this.loadChatMessages(chatId);
            this.loadCustomerInfo(chatId);
            
            // Mark as read
            this.markChatAsRead(chatId);
        },

        loadChatMessages: function(chatId) {
            var self = this;
            
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_get_chat_messages',
                    chat_id: chatId,
                    nonce: envChatAdminAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.displayMessages(response.data);
                    }
                }
            });
        },

        displayMessages: function(messages) {
            var container = $('#chat-messages-container');
            container.empty();
            
            messages.forEach(function(message) {
                var messageHTML = '<div class="message message-' + message.sender_type + '">';
                
                if (message.sender_type === 'customer') {
                    messageHTML += '<div class="message-avatar customer-avatar"></div>';
                } else {
                    messageHTML += '<div class="message-avatar operator-avatar"></div>';
                }
                
                messageHTML += '<div class="message-content">';
                
                if (message.file_url) {
                    messageHTML += '<div class="message-file">';
                    messageHTML += '<a href="' + message.file_url + '" target="_blank">' + message.file_name + '</a>';
                    messageHTML += '</div>';
                } else {
                    messageHTML += '<div class="message-text">' + message.message + '</div>';
                }
                
                messageHTML += '<div class="message-time">' + message.created_at + '</div>';
                messageHTML += '</div>';
                messageHTML += '</div>';
                
                container.append(messageHTML);
            });
            
            this.scrollMessagesToBottom();
        },

        loadCustomerInfo: function(chatId) {
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_get_customer_info',
                    chat_id: chatId,
                    nonce: envChatAdminAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#customer-info').html(response.data);
                    }
                }
            });
        },

        sendMessage: function() {
            var chatId = $('.chat-item.active').data('chat-id');
            var message = $('#operator-message-input').val().trim();
            
            if (!chatId || !message) {
                return;
            }

            var self = this;
            
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_send_operator_message',
                    chat_id: chatId,
                    message: message,
                    nonce: envChatAdminAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#operator-message-input').val('');
                        self.loadChatMessages(chatId);
                    } else {
                        alert(response.data || 'Không thể gửi tin nhắn');
                    }
                }
            });
        },

        handleFileUpload: function(files) {
            var chatId = $('.chat-item.active').data('chat-id');
            
            if (!chatId || !files.length) {
                return;
            }

            var self = this;
            var formData = new FormData();
            
            for (var i = 0; i < files.length; i++) {
                formData.append('files[]', files[i]);
            }
            
            formData.append('action', 'env_operator_upload_file');
            formData.append('chat_id', chatId);
            formData.append('nonce', envChatAdminAjax.nonce);

            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        self.loadChatMessages(chatId);
                    } else {
                        alert(response.data || 'Không thể tải lên tệp');
                    }
                },
                complete: function() {
                    $('#operator-file-input').val('');
                }
            });
        },

        endChat: function(chatId) {
            if (!confirm('Bạn có chắc muốn kết thúc cuộc trò chuyện này?')) {
                return;
            }

            var self = this;
            
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_operator_end_chat',
                    chat_id: chatId,
                    nonce: envChatAdminAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.chat-item[data-chat-id="' + chatId + '"]').remove();
                        $('#chat-messages-container').empty();
                        $('#customer-info').empty();
                    } else {
                        alert(response.data || 'Không thể kết thúc cuộc trò chuyện');
                    }
                }
            });
        },

        markChatAsRead: function(chatId) {
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_mark_chat_read',
                    chat_id: chatId,
                    nonce: envChatAdminAjax.nonce
                }
            });
        },

        updateOperatorStatus: function(status) {
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_update_operator_status',
                    status: status,
                    nonce: envChatAdminAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#operator-status-indicator').removeClass().addClass('status-' + status);
                    }
                }
            });
        },

        scrollMessagesToBottom: function() {
            var container = $('#chat-messages-container');
            container.scrollTop(container[0].scrollHeight);
        }
    };

    // Tickets Manager Class
    function TicketsManager() {
        this.init();
    }

    TicketsManager.prototype = {
        init: function() {
            this.bindEvents();
            this.loadTickets();
        },

        bindEvents: function() {
            var self = this;

            // Ticket filters
            $(document).on('change', '#tickets-filter-status, #tickets-filter-priority', function() {
                self.loadTickets();
            });

            // Ticket search
            $(document).on('input', '#tickets-search', function() {
                clearTimeout(self.searchTimeout);
                self.searchTimeout = setTimeout(function() {
                    self.loadTickets();
                }, 300);
            });

            // View ticket
            $(document).on('click', '.view-ticket', function() {
                var ticketId = $(this).data('ticket-id');
                self.viewTicket(ticketId);
            });

            // Reply to ticket
            $(document).on('click', '#reply-ticket', function() {
                self.replyToTicket();
            });

            // Update ticket status
            $(document).on('change', '#ticket-status', function() {
                var ticketId = $('#ticket-modal').data('ticket-id');
                var status = $(this).val();
                self.updateTicketStatus(ticketId, status);
            });

            // Assign ticket
            $(document).on('change', '#ticket-assignee', function() {
                var ticketId = $('#ticket-modal').data('ticket-id');
                var assignee = $(this).val();
                self.assignTicket(ticketId, assignee);
            });

            // Bulk actions
            $(document).on('click', '#apply-bulk-action', function() {
                self.applyBulkAction();
            });

            // Close modal
            $(document).on('click', '.modal-close, .modal-overlay', function() {
                $('.modal').hide();
            });
        },

        loadTickets: function() {
            var self = this;
            var filters = {
                status: $('#tickets-filter-status').val(),
                priority: $('#tickets-filter-priority').val(),
                search: $('#tickets-search').val()
            };
            
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_get_tickets',
                    filters: filters,
                    nonce: envChatAdminAjax.nonce
                },
                beforeSend: function() {
                    $('#tickets-table tbody').html('<tr><td colspan="7">Đang tải...</td></tr>');
                },
                success: function(response) {
                    if (response.success) {
                        self.displayTickets(response.data);
                    }
                }
            });
        },

        displayTickets: function(tickets) {
            var tbody = $('#tickets-table tbody');
            tbody.empty();
            
            if (tickets.length === 0) {
                tbody.html('<tr><td colspan="7">Không có ticket nào</td></tr>');
                return;
            }
            
            tickets.forEach(function(ticket) {
                var row = '<tr>';
                row += '<td><input type="checkbox" name="ticket_ids[]" value="' + ticket.id + '"></td>';
                row += '<td><a href="#" class="view-ticket" data-ticket-id="' + ticket.id + '">#' + ticket.ticket_number + '</a></td>';
                row += '<td>' + ticket.subject + '</td>';
                row += '<td>' + ticket.customer_name + '</td>';
                row += '<td><span class="status-badge status-' + ticket.status + '">' + ticket.status + '</span></td>';
                row += '<td><span class="priority-badge priority-' + ticket.priority + '">' + ticket.priority + '</span></td>';
                row += '<td>' + ticket.created_at + '</td>';
                row += '</tr>';
                
                tbody.append(row);
            });
        },

        viewTicket: function(ticketId) {
            var self = this;
            
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_get_ticket_details',
                    ticket_id: ticketId,
                    nonce: envChatAdminAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showTicketModal(response.data);
                    }
                }
            });
        },

        showTicketModal: function(ticket) {
            var modal = $('#ticket-modal');
            modal.data('ticket-id', ticket.id);
            
            // Update modal content
            $('#modal-ticket-number').text('#' + ticket.ticket_number);
            $('#modal-ticket-subject').text(ticket.subject);
            $('#modal-customer-info').html(ticket.customer_info);
            $('#modal-ticket-content').html(ticket.content);
            $('#modal-ticket-replies').html(ticket.replies);
            
            // Update form fields
            $('#ticket-status').val(ticket.status);
            $('#ticket-priority').val(ticket.priority);
            $('#ticket-assignee').val(ticket.assigned_to);
            
            modal.show();
        },

        replyToTicket: function() {
            var ticketId = $('#ticket-modal').data('ticket-id');
            var reply = $('#ticket-reply-content').val().trim();
            
            if (!reply) {
                alert('Vui lòng nhập nội dung phản hồi');
                return;
            }

            var self = this;
            
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_reply_ticket',
                    ticket_id: ticketId,
                    reply: reply,
                    nonce: envChatAdminAjax.nonce
                },
                beforeSend: function() {
                    $('#reply-ticket').prop('disabled', true).text('Đang gửi...');
                },
                success: function(response) {
                    if (response.success) {
                        $('#ticket-reply-content').val('');
                        self.viewTicket(ticketId); // Refresh ticket details
                    } else {
                        alert(response.data || 'Không thể gửi phản hồi');
                    }
                },
                complete: function() {
                    $('#reply-ticket').prop('disabled', false).text('Gửi phản hồi');
                }
            });
        },

        updateTicketStatus: function(ticketId, status) {
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_update_ticket_status',
                    ticket_id: ticketId,
                    status: status,
                    nonce: envChatAdminAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update the status in the tickets table
                        $('.view-ticket[data-ticket-id="' + ticketId + '"]').closest('tr')
                            .find('.status-badge').removeClass().addClass('status-badge status-' + status).text(status);
                    }
                }
            });
        },

        assignTicket: function(ticketId, assignee) {
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_assign_ticket',
                    ticket_id: ticketId,
                    assignee: assignee,
                    nonce: envChatAdminAjax.nonce
                }
            });
        },

        applyBulkAction: function() {
            var action = $('#bulk-actions').val();
            var selectedTickets = $('input[name="ticket_ids[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (!action || selectedTickets.length === 0) {
                alert('Vui lòng chọn hành động và ít nhất một ticket');
                return;
            }

            var self = this;
            
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_bulk_ticket_action',
                    bulk_action: action,
                    ticket_ids: selectedTickets,
                    nonce: envChatAdminAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.loadTickets();
                        alert('Đã áp dụng hành động thành công');
                    } else {
                        alert(response.data || 'Có lỗi xảy ra');
                    }
                }
            });
        }
    };

    // FAQ Manager Class
    function FAQManager() {
        this.init();
    }

    FAQManager.prototype = {
        init: function() {
            this.bindEvents();
            this.loadFAQs();
        },

        bindEvents: function() {
            var self = this;

            // Add new FAQ
            $(document).on('click', '#add-new-faq', function() {
                self.showFAQForm();
            });

            // Edit FAQ
            $(document).on('click', '.edit-faq', function() {
                var faqId = $(this).data('faq-id');
                self.editFAQ(faqId);
            });

            // Delete FAQ
            $(document).on('click', '.delete-faq', function() {
                var faqId = $(this).data('faq-id');
                self.deleteFAQ(faqId);
            });

            // Save FAQ
            $(document).on('click', '#save-faq', function() {
                self.saveFAQ();
            });

            // Import/Export FAQs
            $(document).on('click', '#import-faqs', function() {
                $('#import-file').click();
            });

            $(document).on('change', '#import-file', function() {
                self.importFAQs(this.files[0]);
            });

            $(document).on('click', '#export-faqs', function() {
                self.exportFAQs();
            });

            // Category filter
            $(document).on('change', '#faq-category-filter', function() {
                self.loadFAQs();
            });

            // Search FAQs
            $(document).on('input', '#faq-search', function() {
                clearTimeout(self.searchTimeout);
                self.searchTimeout = setTimeout(function() {
                    self.loadFAQs();
                }, 300);
            });
        },

        loadFAQs: function() {
            var self = this;
            var filters = {
                category: $('#faq-category-filter').val(),
                search: $('#faq-search').val()
            };
            
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_get_admin_faqs',
                    filters: filters,
                    nonce: envChatAdminAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.displayFAQs(response.data);
                    }
                }
            });
        },

        displayFAQs: function(faqs) {
            var container = $('#faqs-list');
            container.empty();
            
            faqs.forEach(function(faq) {
                var faqHTML = '<div class="faq-item" data-faq-id="' + faq.id + '">';
                faqHTML += '<div class="faq-header">';
                faqHTML += '<h4>' + faq.question + '</h4>';
                faqHTML += '<div class="faq-actions">';
                faqHTML += '<button class="edit-faq" data-faq-id="' + faq.id + '">Sửa</button>';
                faqHTML += '<button class="delete-faq" data-faq-id="' + faq.id + '">Xóa</button>';
                faqHTML += '</div>';
                faqHTML += '</div>';
                faqHTML += '<div class="faq-content">';
                faqHTML += '<div class="faq-answer">' + faq.answer + '</div>';
                faqHTML += '<div class="faq-meta">';
                faqHTML += '<span class="faq-category">' + faq.category + '</span>';
                faqHTML += '<span class="faq-views">' + faq.view_count + ' lượt xem</span>';
                faqHTML += '<span class="faq-rating">' + faq.helpful_count + '/' + (faq.helpful_count + faq.not_helpful_count) + ' hữu ích</span>';
                faqHTML += '</div>';
                faqHTML += '</div>';
                faqHTML += '</div>';
                
                container.append(faqHTML);
            });
        },

        showFAQForm: function(faq = null) {
            var modal = $('#faq-modal');
            
            if (faq) {
                $('#faq-form-title').text('Sửa FAQ');
                $('#faq-question').val(faq.question);
                $('#faq-answer').val(faq.answer);
                $('#faq-category').val(faq.category);
                $('#faq-tags').val(faq.tags);
                modal.data('faq-id', faq.id);
            } else {
                $('#faq-form-title').text('Thêm FAQ mới');
                $('#faq-form')[0].reset();
                modal.removeData('faq-id');
            }
            
            modal.show();
        },

        editFAQ: function(faqId) {
            var self = this;
            
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_get_faq_details',
                    faq_id: faqId,
                    nonce: envChatAdminAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showFAQForm(response.data);
                    }
                }
            });
        },

        saveFAQ: function() {
            var faqId = $('#faq-modal').data('faq-id');
            var formData = {
                action: faqId ? 'env_update_faq' : 'env_create_faq',
                question: $('#faq-question').val(),
                answer: $('#faq-answer').val(),
                category: $('#faq-category').val(),
                tags: $('#faq-tags').val(),
                nonce: envChatAdminAjax.nonce
            };
            
            if (faqId) {
                formData.faq_id = faqId;
            }

            var self = this;
            
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $('#faq-modal').hide();
                        self.loadFAQs();
                    } else {
                        alert(response.data || 'Có lỗi xảy ra');
                    }
                }
            });
        },

        deleteFAQ: function(faqId) {
            if (!confirm('Bạn có chắc muốn xóa FAQ này?')) {
                return;
            }

            var self = this;
            
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_delete_faq',
                    faq_id: faqId,
                    nonce: envChatAdminAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.loadFAQs();
                    } else {
                        alert(response.data || 'Không thể xóa FAQ');
                    }
                }
            });
        },

        importFAQs: function(file) {
            if (!file) return;
            
            var formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'env_import_faqs');
            formData.append('nonce', envChatAdminAjax.nonce);

            var self = this;
            
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Import thành công ' + response.data.count + ' FAQ');
                        self.loadFAQs();
                    } else {
                        alert(response.data || 'Lỗi import FAQs');
                    }
                }
            });
        },

        exportFAQs: function() {
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_export_faqs',
                    nonce: envChatAdminAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Trigger download
                        var link = document.createElement('a');
                        link.href = response.data.url;
                        link.download = response.data.filename;
                        link.click();
                    }
                }
            });
        }
    };

    // Settings Manager Class
    function SettingsManager() {
        this.init();
    }

    SettingsManager.prototype = {
        init: function() {
            this.bindEvents();
            this.initializeColorPickers();
        },

        bindEvents: function() {
            var self = this;

            // Save settings
            $(document).on('click', '#save-settings', function() {
                self.saveSettings();
            });

            // Settings tabs
            $(document).on('click', '.settings-tab', function() {
                var tab = $(this).data('tab');
                self.showTab(tab);
            });

            // Test email settings
            $(document).on('click', '#test-email', function() {
                self.testEmailSettings();
            });

            // Reset to defaults
            $(document).on('click', '#reset-defaults', function() {
                if (confirm('Bạn có chắc muốn khôi phục cài đặt mặc định?')) {
                    self.resetToDefaults();
                }
            });
        },

        showTab: function(tab) {
            $('.settings-tab').removeClass('active');
            $('.settings-tab[data-tab="' + tab + '"]').addClass('active');
            
            $('.settings-panel').hide();
            $('#' + tab + '-settings').show();
        },

        initializeColorPickers: function() {
            // Initialize color pickers if available
            if ($.fn.wpColorPicker) {
                $('.color-picker').wpColorPicker();
            }
        },

        saveSettings: function() {
            var formData = $('#settings-form').serialize();
            formData += '&action=env_save_settings&nonce=' + envChatAdminAjax.nonce;

            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: formData,
                beforeSend: function() {
                    $('#save-settings').prop('disabled', true).text('Đang lưu...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Cài đặt đã được lưu thành công');
                    } else {
                        alert(response.data || 'Có lỗi xảy ra khi lưu cài đặt');
                    }
                },
                complete: function() {
                    $('#save-settings').prop('disabled', false).text('Lưu cài đặt');
                }
            });
        },

        testEmailSettings: function() {
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_test_email',
                    nonce: envChatAdminAjax.nonce
                },
                beforeSend: function() {
                    $('#test-email').prop('disabled', true).text('Đang gửi...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Email test đã được gửi thành công');
                    } else {
                        alert(response.data || 'Không thể gửi email test');
                    }
                },
                complete: function() {
                    $('#test-email').prop('disabled', false).text('Gửi email test');
                }
            });
        },

        resetToDefaults: function() {
            $.ajax({
                url: envChatAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'env_reset_settings',
                    nonce: envChatAdminAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || 'Không thể khôi phục cài đặt mặc định');
                    }
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize admin components
        new AdminDashboard();
        
        if ($('#env-admin-live-chat').length) {
            new LiveChatManager();
        }
        
        if ($('#env-admin-tickets').length) {
            new TicketsManager();
        }
        
        if ($('#env-admin-faq').length) {
            new FAQManager();
        }
        
        if ($('#env-admin-settings').length) {
            new SettingsManager();
        }

        // Play notification sound
        window.playNotificationSound = function() {
            if (soundEnabled && typeof Audio !== 'undefined') {
                var audio = new Audio(envChatAdminAjax.notificationSound);
                audio.play().catch(function() {
                    // Ignore audio play errors
                });
            }
        };
    });

    // Clean up intervals when leaving page
    $(window).on('beforeunload', function() {
        if (chatInterval) {
            clearInterval(chatInterval);
        }
        
        if (dashboardInterval) {
            clearInterval(dashboardInterval);
        }
    });

})(jQuery);
