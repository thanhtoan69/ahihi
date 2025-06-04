/**
 * Admin User Management JavaScript
 * 
 * Phase 31: User Management & Authentication - Admin Interface
 * Handles administrative user management functionality
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Admin User Management Class
    class EPAdminUserManagement {
        constructor() {
            this.init();
            this.bindEvents();
        }

        init() {
            // Initialize components
            this.initializeDataTables();
            this.initializeCharts();
            this.initializeFilters();
        }

        bindEvents() {
            // User management events
            $(document).on('click', '.ep-view-user-details', this.viewUserDetails.bind(this));
            $(document).on('click', '.ep-edit-user', this.editUser.bind(this));
            $(document).on('click', '.ep-verify-user', this.verifyUser.bind(this));
            $(document).on('click', '.ep-suspend-user', this.suspendUser.bind(this));
            $(document).on('click', '.ep-delete-user', this.deleteUser.bind(this));
            
            // Bulk actions
            $(document).on('click', '#ep-bulk-action-apply', this.processBulkAction.bind(this));
            $(document).on('change', '#ep-select-all-users', this.toggleSelectAll.bind(this));
            $(document).on('change', '.ep-user-checkbox', this.updateBulkActionsState.bind(this));
            
            // Points and level management
            $(document).on('click', '.ep-award-points', this.showAwardPointsModal.bind(this));
            $(document).on('click', '.ep-adjust-level', this.showAdjustLevelModal.bind(this));
            $(document).on('submit', '#ep-award-points-form', this.awardPoints.bind(this));
            $(document).on('submit', '#ep-adjust-level-form', this.adjustLevel.bind(this));
            
            // Communication
            $(document).on('click', '.ep-send-message', this.showSendMessageModal.bind(this));
            $(document).on('submit', '#ep-send-message-form', this.sendMessage.bind(this));
            
            // Export functionality
            $(document).on('click', '.ep-export-users', this.exportUsers.bind(this));
            $(document).on('click', '.ep-export-user-data', this.exportUserData.bind(this));
            
            // Modal events
            $(document).on('click', '.ep-modal-close, .ep-modal-overlay', this.closeModal.bind(this));
            $(document).on('click', '.ep-modal-content', function(e) { e.stopPropagation(); });
            
            // Real-time updates
            this.initializeRealTimeUpdates();
        }

        initializeDataTables() {
            if ($.fn.DataTable && $('#ep-users-table').length) {
                $('#ep-users-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: ep_admin_ajax.ajax_url,
                        type: 'POST',
                        data: function(d) {
                            d.action = 'ep_get_users_data';
                            d.nonce = ep_admin_ajax.nonce;
                            d.filters = $('#ep-user-filters').serialize();
                        }
                    },
                    columns: [
                        { data: 'select', orderable: false, searchable: false },
                        { data: 'user_info' },
                        { data: 'email' },
                        { data: 'role' },
                        { data: 'level' },
                        { data: 'points' },
                        { data: 'score' },
                        { data: 'status' },
                        { data: 'registered' },
                        { data: 'actions', orderable: false, searchable: false }
                    ],
                    order: [[8, 'desc']],
                    pageLength: 25,
                    responsive: true,
                    language: {
                        processing: ep_admin_text.loading,
                        search: ep_admin_text.search,
                        lengthMenu: ep_admin_text.show_entries,
                        info: ep_admin_text.showing_entries,
                        infoEmpty: ep_admin_text.no_entries,
                        infoFiltered: ep_admin_text.filtered_from,
                        paginate: {
                            first: ep_admin_text.first,
                            last: ep_admin_text.last,
                            next: ep_admin_text.next,
                            previous: ep_admin_text.previous
                        },
                        emptyTable: ep_admin_text.no_users_found
                    }
                });
            }
        }

        initializeCharts() {
            // Initialize user statistics charts
            this.renderUserStatsChart();
            this.renderLevelDistributionChart();
            this.renderActivityChart();
        }

        renderUserStatsChart() {
            if (typeof Chart === 'undefined' || !$('#ep-user-stats-chart').length) return;

            const ctx = document.getElementById('ep-user-stats-chart').getContext('2d');
            
            $.ajax({
                url: ep_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ep_get_user_stats',
                    nonce: ep_admin_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: [
                                    ep_admin_text.active_users,
                                    ep_admin_text.inactive_users,
                                    ep_admin_text.pending_users
                                ],
                                datasets: [{
                                    data: [
                                        response.data.active,
                                        response.data.inactive,
                                        response.data.pending
                                    ],
                                    backgroundColor: [
                                        '#4CAF50',
                                        '#F44336',
                                        '#FF9800'
                                    ],
                                    borderWidth: 2,
                                    borderColor: '#fff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom'
                                    }
                                }
                            }
                        });
                    }
                }
            });
        }

        renderLevelDistributionChart() {
            if (typeof Chart === 'undefined' || !$('#ep-level-distribution-chart').length) return;

            const ctx = document.getElementById('ep-level-distribution-chart').getContext('2d');
            
            $.ajax({
                url: ep_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ep_get_level_distribution',
                    nonce: ep_admin_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: response.data.labels,
                                datasets: [{
                                    label: ep_admin_text.users_count,
                                    data: response.data.counts,
                                    backgroundColor: '#8BC34A',
                                    borderColor: '#689F38',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            stepSize: 1
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                }
                            }
                        });
                    }
                }
            });
        }

        renderActivityChart() {
            if (typeof Chart === 'undefined' || !$('#ep-activity-chart').length) return;

            const ctx = document.getElementById('ep-activity-chart').getContext('2d');
            
            $.ajax({
                url: ep_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ep_get_user_activity',
                    period: 30,
                    nonce: ep_admin_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: response.data.labels,
                                datasets: [
                                    {
                                        label: ep_admin_text.new_registrations,
                                        data: response.data.registrations,
                                        borderColor: '#2196F3',
                                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                                        tension: 0.4
                                    },
                                    {
                                        label: ep_admin_text.active_users,
                                        data: response.data.active_users,
                                        borderColor: '#4CAF50',
                                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                                        tension: 0.4
                                    }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'top'
                                    }
                                }
                            }
                        });
                    }
                }
            });
        }

        initializeFilters() {
            // Date range picker
            if ($.fn.daterangepicker && $('#ep-date-range').length) {
                $('#ep-date-range').daterangepicker({
                    ranges: {
                        [ep_admin_text.today]: [moment(), moment()],
                        [ep_admin_text.yesterday]: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        [ep_admin_text.last_7_days]: [moment().subtract(6, 'days'), moment()],
                        [ep_admin_text.last_30_days]: [moment().subtract(29, 'days'), moment()],
                        [ep_admin_text.this_month]: [moment().startOf('month'), moment().endOf('month')],
                        [ep_admin_text.last_month]: [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                    },
                    startDate: moment().subtract(29, 'days'),
                    endDate: moment(),
                    locale: {
                        format: 'MM/DD/YYYY'
                    }
                });
            }

            // Filter form submission
            $('#ep-user-filters').on('submit', (e) => {
                e.preventDefault();
                this.applyFilters();
            });

            $('#ep-reset-filters').on('click', () => {
                this.resetFilters();
            });
        }

        applyFilters() {
            if ($.fn.DataTable && $('#ep-users-table').length) {
                $('#ep-users-table').DataTable().ajax.reload();
            } else {
                // Reload page with filters
                const formData = $('#ep-user-filters').serialize();
                window.location.href = window.location.pathname + '?' + formData;
            }
        }

        resetFilters() {
            $('#ep-user-filters')[0].reset();
            this.applyFilters();
        }

        viewUserDetails(e) {
            e.preventDefault();
            const userId = $(e.currentTarget).data('user-id');
            
            $.ajax({
                url: ep_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ep_get_user_details',
                    user_id: userId,
                    nonce: ep_admin_ajax.nonce
                },
                beforeSend: () => {
                    this.showLoader(ep_admin_text.loading_user_details);
                },
                success: (response) => {
                    this.hideLoader();
                    if (response.success) {
                        this.showModal('user-details', response.data.html);
                    } else {
                        this.showNotification(response.data.message, 'error');
                    }
                },
                error: () => {
                    this.hideLoader();
                    this.showNotification(ep_admin_text.error_loading_details, 'error');
                }
            });
        }

        editUser(e) {
            e.preventDefault();
            const userId = $(e.currentTarget).data('user-id');
            const editUrl = $(e.currentTarget).data('edit-url') || 
                          `${ep_admin_ajax.admin_url}user-edit.php?user_id=${userId}`;
            
            window.open(editUrl, '_blank');
        }

        verifyUser(e) {
            e.preventDefault();
            const userId = $(e.currentTarget).data('user-id');
            
            if (!confirm(ep_admin_text.confirm_verify_user)) {
                return;
            }
            
            this.performUserAction('verify', userId, {
                success_message: ep_admin_text.user_verified,
                error_message: ep_admin_text.error_verify_user
            });
        }

        suspendUser(e) {
            e.preventDefault();
            const userId = $(e.currentTarget).data('user-id');
            
            if (!confirm(ep_admin_text.confirm_suspend_user)) {
                return;
            }
            
            this.performUserAction('suspend', userId, {
                success_message: ep_admin_text.user_suspended,
                error_message: ep_admin_text.error_suspend_user
            });
        }

        deleteUser(e) {
            e.preventDefault();
            const userId = $(e.currentTarget).data('user-id');
            
            if (!confirm(ep_admin_text.confirm_delete_user)) {
                return;
            }
            
            this.performUserAction('delete', userId, {
                success_message: ep_admin_text.user_deleted,
                error_message: ep_admin_text.error_delete_user
            });
        }

        performUserAction(action, userId, messages) {
            $.ajax({
                url: ep_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ep_user_action',
                    user_action: action,
                    user_id: userId,
                    nonce: ep_admin_ajax.nonce
                },
                beforeSend: () => {
                    this.showLoader();
                },
                success: (response) => {
                    this.hideLoader();
                    if (response.success) {
                        this.showNotification(messages.success_message, 'success');
                        this.refreshUserTable();
                    } else {
                        this.showNotification(response.data.message || messages.error_message, 'error');
                    }
                },
                error: () => {
                    this.hideLoader();
                    this.showNotification(messages.error_message, 'error');
                }
            });
        }

        toggleSelectAll(e) {
            const isChecked = $(e.currentTarget).prop('checked');
            $('.ep-user-checkbox').prop('checked', isChecked);
            this.updateBulkActionsState();
        }

        updateBulkActionsState() {
            const selectedCount = $('.ep-user-checkbox:checked').length;
            const $bulkActions = $('#ep-bulk-actions');
            
            if (selectedCount > 0) {
                $bulkActions.removeClass('disabled');
                $('#ep-selected-count').text(selectedCount);
            } else {
                $bulkActions.addClass('disabled');
            }
        }

        processBulkAction(e) {
            e.preventDefault();
            
            const action = $('#ep-bulk-action-select').val();
            const selectedUsers = $('.ep-user-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (!action) {
                this.showNotification(ep_admin_text.select_bulk_action, 'warning');
                return;
            }
            
            if (selectedUsers.length === 0) {
                this.showNotification(ep_admin_text.select_users, 'warning');
                return;
            }
            
            switch (action) {
                case 'verify':
                    this.bulkVerifyUsers(selectedUsers);
                    break;
                case 'suspend':
                    this.bulkSuspendUsers(selectedUsers);
                    break;
                case 'delete':
                    this.bulkDeleteUsers(selectedUsers);
                    break;
                case 'award_points':
                    this.showBulkAwardPointsModal(selectedUsers);
                    break;
                case 'send_message':
                    this.showBulkSendMessageModal(selectedUsers);
                    break;
                case 'export':
                    this.exportSelectedUsers(selectedUsers);
                    break;
                default:
                    this.showNotification(ep_admin_text.invalid_action, 'error');
            }
        }

        bulkVerifyUsers(userIds) {
            if (!confirm(ep_admin_text.confirm_bulk_verify)) {
                return;
            }
            
            this.performBulkAction('verify', userIds, {
                success_message: ep_admin_text.users_verified,
                error_message: ep_admin_text.error_verify_users
            });
        }

        bulkSuspendUsers(userIds) {
            if (!confirm(ep_admin_text.confirm_bulk_suspend)) {
                return;
            }
            
            this.performBulkAction('suspend', userIds, {
                success_message: ep_admin_text.users_suspended,
                error_message: ep_admin_text.error_suspend_users
            });
        }

        bulkDeleteUsers(userIds) {
            if (!confirm(ep_admin_text.confirm_bulk_delete)) {
                return;
            }
            
            this.performBulkAction('delete', userIds, {
                success_message: ep_admin_text.users_deleted,
                error_message: ep_admin_text.error_delete_users
            });
        }

        performBulkAction(action, userIds, messages) {
            $.ajax({
                url: ep_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ep_bulk_user_action',
                    user_action: action,
                    user_ids: userIds,
                    nonce: ep_admin_ajax.nonce
                },
                beforeSend: () => {
                    this.showLoader();
                },
                success: (response) => {
                    this.hideLoader();
                    if (response.success) {
                        this.showNotification(messages.success_message, 'success');
                        this.refreshUserTable();
                        this.clearSelection();
                    } else {
                        this.showNotification(response.data.message || messages.error_message, 'error');
                    }
                },
                error: () => {
                    this.hideLoader();
                    this.showNotification(messages.error_message, 'error');
                }
            });
        }

        showAwardPointsModal(e) {
            e.preventDefault();
            const userId = $(e.currentTarget).data('user-id');
            this.showBulkAwardPointsModal([userId]);
        }

        showBulkAwardPointsModal(userIds) {
            const modalHtml = `
                <div class="ep-modal-header">
                    <h3>${ep_admin_text.award_points_title}</h3>
                    <button type="button" class="ep-modal-close">&times;</button>
                </div>
                <div class="ep-modal-body">
                    <form id="ep-award-points-form">
                        <div class="ep-form-group">
                            <label for="points-amount">${ep_admin_text.points_amount}:</label>
                            <input type="number" id="points-amount" name="points" min="1" max="10000" required>
                            <small class="ep-form-help">${ep_admin_text.points_help}</small>
                        </div>
                        <div class="ep-form-group">
                            <label for="points-reason">${ep_admin_text.reason}:</label>
                            <textarea id="points-reason" name="reason" rows="3" placeholder="${ep_admin_text.reason_placeholder}"></textarea>
                        </div>
                        <div class="ep-form-group">
                            <label>
                                <input type="checkbox" id="notify-users" name="notify" checked>
                                ${ep_admin_text.notify_users}
                            </label>
                        </div>
                        <div class="ep-form-actions">
                            <button type="submit" class="button button-primary">${ep_admin_text.award_points}</button>
                            <button type="button" class="button ep-modal-close">${ep_admin_text.cancel}</button>
                        </div>
                        <input type="hidden" name="user_ids" value="${userIds.join(',')}">
                    </form>
                </div>
            `;
            
            this.showModal('award-points', modalHtml);
        }

        awardPoints(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = {
                action: 'ep_award_points',
                nonce: ep_admin_ajax.nonce
            };
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            $.ajax({
                url: ep_admin_ajax.ajax_url,
                type: 'POST',
                data: data,
                beforeSend: () => {
                    $('#ep-award-points-form button[type="submit"]').prop('disabled', true);
                    this.showLoader(ep_admin_text.awarding_points);
                },
                success: (response) => {
                    this.hideLoader();
                    $('#ep-award-points-form button[type="submit"]').prop('disabled', false);
                    
                    if (response.success) {
                        this.closeModal();
                        this.showNotification(ep_admin_text.points_awarded, 'success');
                        this.refreshUserTable();
                    } else {
                        this.showNotification(response.data.message, 'error');
                    }
                },
                error: () => {
                    this.hideLoader();
                    $('#ep-award-points-form button[type="submit"]').prop('disabled', false);
                    this.showNotification(ep_admin_text.error_award_points, 'error');
                }
            });
        }

        showAdjustLevelModal(e) {
            e.preventDefault();
            const userId = $(e.currentTarget).data('user-id');
            const currentLevel = $(e.currentTarget).data('current-level');
            
            const modalHtml = `
                <div class="ep-modal-header">
                    <h3>${ep_admin_text.adjust_level_title}</h3>
                    <button type="button" class="ep-modal-close">&times;</button>
                </div>
                <div class="ep-modal-body">
                    <form id="ep-adjust-level-form">
                        <div class="ep-form-group">
                            <label for="current-level">${ep_admin_text.current_level}:</label>
                            <input type="number" id="current-level" value="${currentLevel}" readonly>
                        </div>
                        <div class="ep-form-group">
                            <label for="new-level">${ep_admin_text.new_level}:</label>
                            <input type="number" id="new-level" name="level" min="1" max="100" value="${currentLevel}" required>
                            <small class="ep-form-help">${ep_admin_text.level_help}</small>
                        </div>
                        <div class="ep-form-group">
                            <label for="level-reason">${ep_admin_text.reason}:</label>
                            <textarea id="level-reason" name="reason" rows="3" placeholder="${ep_admin_text.level_reason_placeholder}"></textarea>
                        </div>
                        <div class="ep-form-actions">
                            <button type="submit" class="button button-primary">${ep_admin_text.adjust_level}</button>
                            <button type="button" class="button ep-modal-close">${ep_admin_text.cancel}</button>
                        </div>
                        <input type="hidden" name="user_id" value="${userId}">
                    </form>
                </div>
            `;
            
            this.showModal('adjust-level', modalHtml);
        }

        adjustLevel(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = {
                action: 'ep_adjust_level',
                nonce: ep_admin_ajax.nonce
            };
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            $.ajax({
                url: ep_admin_ajax.ajax_url,
                type: 'POST',
                data: data,
                beforeSend: () => {
                    $('#ep-adjust-level-form button[type="submit"]').prop('disabled', true);
                    this.showLoader(ep_admin_text.adjusting_level);
                },
                success: (response) => {
                    this.hideLoader();
                    $('#ep-adjust-level-form button[type="submit"]').prop('disabled', false);
                    
                    if (response.success) {
                        this.closeModal();
                        this.showNotification(ep_admin_text.level_adjusted, 'success');
                        this.refreshUserTable();
                    } else {
                        this.showNotification(response.data.message, 'error');
                    }
                },
                error: () => {
                    this.hideLoader();
                    $('#ep-adjust-level-form button[type="submit"]').prop('disabled', false);
                    this.showNotification(ep_admin_text.error_adjust_level, 'error');
                }
            });
        }

        showSendMessageModal(e) {
            e.preventDefault();
            const userId = $(e.currentTarget).data('user-id');
            this.showBulkSendMessageModal([userId]);
        }

        showBulkSendMessageModal(userIds) {
            const modalHtml = `
                <div class="ep-modal-header">
                    <h3>${ep_admin_text.send_message_title}</h3>
                    <button type="button" class="ep-modal-close">&times;</button>
                </div>
                <div class="ep-modal-body">
                    <form id="ep-send-message-form">
                        <div class="ep-form-group">
                            <label for="message-subject">${ep_admin_text.subject}:</label>
                            <input type="text" id="message-subject" name="subject" required>
                        </div>
                        <div class="ep-form-group">
                            <label for="message-content">${ep_admin_text.message}:</label>
                            <textarea id="message-content" name="message" rows="8" required></textarea>
                        </div>
                        <div class="ep-form-group">
                            <label for="message-type">${ep_admin_text.message_type}:</label>
                            <select id="message-type" name="type">
                                <option value="info">${ep_admin_text.info}</option>
                                <option value="warning">${ep_admin_text.warning}</option>
                                <option value="success">${ep_admin_text.success}</option>
                                <option value="urgent">${ep_admin_text.urgent}</option>
                            </select>
                        </div>
                        <div class="ep-form-group">
                            <label>
                                <input type="checkbox" id="send-email" name="send_email" checked>
                                ${ep_admin_text.send_email_notification}
                            </label>
                        </div>
                        <div class="ep-form-actions">
                            <button type="submit" class="button button-primary">${ep_admin_text.send_message}</button>
                            <button type="button" class="button ep-modal-close">${ep_admin_text.cancel}</button>
                        </div>
                        <input type="hidden" name="user_ids" value="${userIds.join(',')}">
                    </form>
                </div>
            `;
            
            this.showModal('send-message', modalHtml);
        }

        sendMessage(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = {
                action: 'ep_send_user_message',
                nonce: ep_admin_ajax.nonce
            };
            
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            $.ajax({
                url: ep_admin_ajax.ajax_url,
                type: 'POST',
                data: data,
                beforeSend: () => {
                    $('#ep-send-message-form button[type="submit"]').prop('disabled', true);
                    this.showLoader(ep_admin_text.sending_message);
                },
                success: (response) => {
                    this.hideLoader();
                    $('#ep-send-message-form button[type="submit"]').prop('disabled', false);
                    
                    if (response.success) {
                        this.closeModal();
                        this.showNotification(ep_admin_text.message_sent, 'success');
                    } else {
                        this.showNotification(response.data.message, 'error');
                    }
                },
                error: () => {
                    this.hideLoader();
                    $('#ep-send-message-form button[type="submit"]').prop('disabled', false);
                    this.showNotification(ep_admin_text.error_send_message, 'error');
                }
            });
        }

        exportUsers(e) {
            e.preventDefault();
            this.exportSelectedUsers([]);
        }

        exportUserData(e) {
            e.preventDefault();
            const userId = $(e.currentTarget).data('user-id');
            this.exportSelectedUsers([userId]);
        }

        exportSelectedUsers(userIds) {
            const form = $('<form>', {
                method: 'POST',
                action: ep_admin_ajax.admin_url + 'admin-post.php'
            });
            
            form.append($('<input>', {type: 'hidden', name: 'action', value: 'ep_export_users'}));
            form.append($('<input>', {type: 'hidden', name: 'user_ids', value: userIds.join(',')}));
            form.append($('<input>', {type: 'hidden', name: 'nonce', value: ep_admin_ajax.nonce}));
            form.append($('<input>', {type: 'hidden', name: 'filters', value: $('#ep-user-filters').serialize()}));
            
            $('body').append(form);
            form.submit();
            form.remove();
            
            this.showNotification(ep_admin_text.export_started, 'success');
        }

        initializeRealTimeUpdates() {
            // Check for real-time updates every 30 seconds
            setInterval(() => {
                this.checkForUpdates();
            }, 30000);
        }

        checkForUpdates() {
            $.ajax({
                url: ep_admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ep_check_user_updates',
                    last_check: this.lastUpdateCheck || Math.floor(Date.now() / 1000),
                    nonce: ep_admin_ajax.nonce
                },
                success: (response) => {
                    if (response.success && response.data.has_updates) {
                        this.showNotification(ep_admin_text.new_updates_available, 'info');
                        this.lastUpdateCheck = Math.floor(Date.now() / 1000);
                    }
                }
            });
        }

        refreshUserTable() {
            if ($.fn.DataTable && $('#ep-users-table').length) {
                $('#ep-users-table').DataTable().ajax.reload();
            } else {
                location.reload();
            }
        }

        clearSelection() {
            $('.ep-user-checkbox, #ep-select-all-users').prop('checked', false);
            this.updateBulkActionsState();
        }

        showModal(modalId, content) {
            let $modal = $(`#ep-modal-${modalId}`);
            
            if (!$modal.length) {
                $modal = $(`
                    <div id="ep-modal-${modalId}" class="ep-modal">
                        <div class="ep-modal-overlay"></div>
                        <div class="ep-modal-content">
                            ${content}
                        </div>
                    </div>
                `);
                $('body').append($modal);
            } else {
                $modal.find('.ep-modal-content').html(content);
            }
            
            $modal.fadeIn(300);
            $('body').addClass('ep-modal-open');
        }

        closeModal() {
            $('.ep-modal').fadeOut(300);
            $('body').removeClass('ep-modal-open');
        }

        showLoader(message = ep_admin_text.loading) {
            if (!$('#ep-loader').length) {
                $('body').append(`
                    <div id="ep-loader" class="ep-loader">
                        <div class="ep-loader-content">
                            <div class="ep-spinner"></div>
                            <p class="ep-loader-message">${message}</p>
                        </div>
                    </div>
                `);
            } else {
                $('#ep-loader .ep-loader-message').text(message);
            }
            
            $('#ep-loader').fadeIn(200);
        }

        hideLoader() {
            $('#ep-loader').fadeOut(200);
        }

        showNotification(message, type = 'info') {
            const $notification = $(`
                <div class="ep-notification ep-notification-${type}">
                    <div class="ep-notification-content">
                        <span class="ep-notification-message">${message}</span>
                        <button type="button" class="ep-notification-close">&times;</button>
                    </div>
                </div>
            `);
            
            $('body').append($notification);
            
            setTimeout(() => {
                $notification.addClass('ep-notification-show');
            }, 100);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $notification.removeClass('ep-notification-show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            }, 5000);
            
            // Handle close button
            $notification.find('.ep-notification-close').on('click', () => {
                $notification.removeClass('ep-notification-show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            });
        }
    }

    // Initialize when document is ready
    $(document).ready(() => {
        if ($('body').hasClass('environmental-platform_page_ep-user-management')) {
            new EPAdminUserManagement();
        }
    });

})(jQuery);
