/**
 * Environmental Platform Forum JavaScript
 * Frontend functionality for forum system
 */

(function($) {
    'use strict';

    // Forum object to hold all functionality
    const EPForum = {
        
        init: function() {
            this.bindEvents();
            this.initFormValidation();
            this.initModeration();
            this.initRealTimeUpdates();
            this.initEcoPointsDisplay();
        },
        
        bindEvents: function() {
            // Topic creation form
            $(document).on('submit', '.ep-topic-form', this.handleTopicSubmit);
            
            // Reply creation form
            $(document).on('submit', '.ep-reply-form', this.handleReplySubmit);
            
            // Quote reply button
            $(document).on('click', '.ep-quote-btn', this.handleQuoteReply);
            
            // Like/Unlike buttons
            $(document).on('click', '.ep-like-btn', this.handleLikeToggle);
            
            // Report content button
            $(document).on('click', '.ep-report-btn', this.handleReportContent);
            
            // Expand/Collapse threads
            $(document).on('click', '.ep-expand-thread', this.handleThreadToggle);
            
            // Search functionality
            $(document).on('input', '.ep-forum-search', this.debounce(this.handleSearch, 300));
            
            // Auto-save drafts
            $(document).on('input', '.ep-forum-editor', this.debounce(this.saveDraft, 2000));
        },
        
        handleTopicSubmit: function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = form.find('.ep-submit-btn');
            const originalText = submitBtn.text();
            
            // Validate form
            if (!EPForum.validateTopicForm(form)) {
                return false;
            }
            
            // Show loading state
            submitBtn.prop('disabled', true).text(ep_forum_ajax.strings.loading);
            
            // Prepare form data
            const formData = {
                action: 'ep_create_topic',
                nonce: ep_forum_ajax.nonce,
                title: form.find('[name="topic_title"]').val(),
                content: form.find('[name="topic_content"]').val(),
                forum_id: form.find('[name="forum_id"]').val(),
                category: form.find('[name="category"]').val(),
                tags: form.find('[name="tags"]').val()
            };
            
            // Submit via AJAX
            $.post(ep_forum_ajax.ajax_url, formData)
                .done(function(response) {
                    if (response.success) {
                        EPForum.showMessage('success', response.data.message);
                        
                        // Redirect to new topic
                        if (response.data.redirect) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1500);
                        }
                        
                        // Clear form
                        form[0].reset();
                        
                        // Award eco points notification
                        EPForum.showEcoPointsEarned(10, 'Tạo chủ đề mới');
                        
                    } else {
                        EPForum.showMessage('error', response.data || ep_forum_ajax.strings.error);
                    }
                })
                .fail(function() {
                    EPForum.showMessage('error', ep_forum_ajax.strings.error);
                })
                .always(function() {
                    submitBtn.prop('disabled', false).text(originalText);
                });
        },
        
        handleReplySubmit: function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = form.find('.ep-submit-btn');
            const originalText = submitBtn.text();
            
            // Validate form
            if (!EPForum.validateReplyForm(form)) {
                return false;
            }
            
            // Show loading state
            submitBtn.prop('disabled', true).text(ep_forum_ajax.strings.loading);
            
            // Prepare form data
            const formData = {
                action: 'ep_create_post',
                nonce: ep_forum_ajax.nonce,
                content: form.find('[name="reply_content"]').val(),
                topic_id: form.find('[name="topic_id"]').val(),
                parent_id: form.find('[name="parent_id"]').val() || 0
            };
            
            // Submit via AJAX
            $.post(ep_forum_ajax.ajax_url, formData)
                .done(function(response) {
                    if (response.success) {
                        EPForum.showMessage('success', response.data.message);
                        
                        // Add new reply to the page
                        EPForum.addReplyToPage(response.data);
                        
                        // Clear form
                        form[0].reset();
                        
                        // Award eco points notification
                        EPForum.showEcoPointsEarned(5, 'Trả lời chủ đề');
                        
                        // Update reply count
                        EPForum.updateReplyCount();
                        
                    } else {
                        EPForum.showMessage('error', response.data || ep_forum_ajax.strings.error);
                    }
                })
                .fail(function() {
                    EPForum.showMessage('error', ep_forum_ajax.strings.error);
                })
                .always(function() {
                    submitBtn.prop('disabled', false).text(originalText);
                });
        },
        
        handleQuoteReply: function(e) {
            e.preventDefault();
            
            const replyId = $(this).data('reply-id');
            const replyContent = $(this).closest('.ep-reply-item').find('.ep-reply-content').text();
            const replyAuthor = $(this).closest('.ep-reply-item').find('.ep-reply-author').text();
            
            // Create quote text
            const quoteText = `[quote="${replyAuthor}"]${replyContent.trim()}[/quote]\n\n`;
            
            // Insert into reply form
            const replyForm = $('.ep-reply-form textarea[name="reply_content"]');
            const currentContent = replyForm.val();
            replyForm.val(quoteText + currentContent).focus();
            
            // Scroll to reply form
            $('html, body').animate({
                scrollTop: replyForm.offset().top - 100
            }, 500);
        },
        
        handleLikeToggle: function(e) {
            e.preventDefault();
            
            const button = $(this);
            const postId = button.data('post-id');
            const isLiked = button.hasClass('liked');
            
            // Optimistic UI update
            if (isLiked) {
                button.removeClass('liked').find('.like-count').text(function(i, text) {
                    return parseInt(text) - 1;
                });
            } else {
                button.addClass('liked').find('.like-count').text(function(i, text) {
                    return parseInt(text) + 1;
                });
            }
            
            // Send AJAX request
            $.post(ep_forum_ajax.ajax_url, {
                action: 'ep_toggle_like',
                nonce: ep_forum_ajax.nonce,
                post_id: postId,
                is_liked: !isLiked
            }).fail(function() {
                // Revert on failure
                if (isLiked) {
                    button.addClass('liked').find('.like-count').text(function(i, text) {
                        return parseInt(text) + 1;
                    });
                } else {
                    button.removeClass('liked').find('.like-count').text(function(i, text) {
                        return parseInt(text) - 1;
                    });
                }
                EPForum.showMessage('error', 'Không thể cập nhật lượt thích');
            });
        },
        
        handleReportContent: function(e) {
            e.preventDefault();
            
            const postId = $(this).data('post-id');
            const reason = prompt('Lý do báo cáo:');
            
            if (!reason) return;
            
            $.post(ep_forum_ajax.ajax_url, {
                action: 'ep_report_content',
                nonce: ep_forum_ajax.nonce,
                post_id: postId,
                reason: reason
            })
            .done(function(response) {
                if (response.success) {
                    EPForum.showMessage('success', 'Báo cáo đã được gửi. Cảm ơn bạn đã giúp duy trì cộng đồng sạch.');
                } else {
                    EPForum.showMessage('error', response.data || 'Không thể gửi báo cáo');
                }
            })
            .fail(function() {
                EPForum.showMessage('error', 'Có lỗi xảy ra khi gửi báo cáo');
            });
        },
        
        handleThreadToggle: function(e) {
            e.preventDefault();
            
            const button = $(this);
            const threadId = button.data('thread-id');
            const repliesContainer = $(`.ep-thread-replies[data-thread-id="${threadId}"]`);
            
            if (repliesContainer.is(':visible')) {
                repliesContainer.slideUp();
                button.text('Mở rộng thảo luận');
            } else {
                repliesContainer.slideDown();
                button.text('Thu gọn thảo luận');
            }
        },
        
        handleSearch: function() {
            const query = $(this).val();
            const searchResults = $('.ep-search-results');
            
            if (query.length < 3) {
                searchResults.hide();
                return;
            }
            
            $.post(ep_forum_ajax.ajax_url, {
                action: 'ep_forum_search',
                nonce: ep_forum_ajax.nonce,
                query: query
            })
            .done(function(response) {
                if (response.success) {
                    searchResults.html(response.data.html).show();
                }
            });
        },
        
        saveDraft: function() {
            const editor = $(this);
            const content = editor.val();
            const draftKey = editor.data('draft-key');
            
            if (content.length > 10) {
                localStorage.setItem(`ep_forum_draft_${draftKey}`, content);
                EPForum.showMessage('info', 'Bản nháp đã được lưu', 2000);
            }
        },
        
        initFormValidation: function() {
            // Real-time validation for topic form
            $('.ep-topic-form [name="topic_title"]').on('input', function() {
                const title = $(this).val();
                const feedback = $(this).siblings('.validation-feedback');
                
                if (title.length < 5) {
                    feedback.text('Tiêu đề phải có ít nhất 5 ký tự').addClass('error');
                } else if (title.length > 200) {
                    feedback.text('Tiêu đề không được vượt quá 200 ký tự').addClass('error');
                } else {
                    feedback.text('').removeClass('error');
                }
            });
            
            // Content validation
            $('.ep-forum-editor').on('input', function() {
                const content = $(this).val();
                const feedback = $(this).siblings('.validation-feedback');
                const wordCount = content.trim().split(/\s+/).length;
                
                if (content.length < 10) {
                    feedback.text('Nội dung quá ngắn').addClass('error');
                } else if (content.length > 5000) {
                    feedback.text('Nội dung quá dài').addClass('error');
                } else {
                    feedback.text(`${wordCount} từ`).removeClass('error');
                }
            });
        },
        
        initModeration: function() {
            // Moderation actions
            $(document).on('click', '.ep-mod-btn', function(e) {
                e.preventDefault();
                
                const action = $(this).data('action');
                const postId = $(this).data('post-id');
                
                if (action === 'delete' && !confirm('Bạn có chắc muốn xóa nội dung này?')) {
                    return;
                }
                
                $.post(ep_forum_ajax.ajax_url, {
                    action: 'ep_moderate_content',
                    nonce: ep_forum_ajax.nonce,
                    post_id: postId,
                    action_type: action
                })
                .done(function(response) {
                    if (response.success) {
                        EPForum.showMessage('success', response.data.message);
                        
                        // Update UI based on action
                        if (action === 'delete') {
                            $(`.ep-topic-item[data-post-id="${postId}"], .ep-reply-item[data-post-id="${postId}"]`).fadeOut();
                        } else if (action === 'sticky') {
                            $(`.ep-topic-item[data-post-id="${postId}"]`).addClass('sticky');
                        } else if (action === 'lock') {
                            $(`.ep-topic-item[data-post-id="${postId}"]`).addClass('locked');
                        }
                    } else {
                        EPForum.showMessage('error', response.data || 'Không thể thực hiện hành động');
                    }
                })
                .fail(function() {
                    EPForum.showMessage('error', 'Có lỗi xảy ra');
                });
            });
        },
        
        initRealTimeUpdates: function() {
            // Check for new replies every 30 seconds
            if ($('.ep-topic-content').length) {
                setInterval(this.checkForNewReplies, 30000);
            }
            
            // Update user presence
            setInterval(this.updateUserPresence, 60000);
        },
        
        initEcoPointsDisplay: function() {
            // Animate eco points when they change
            $(document).on('click', '.ep-eco-points', function() {
                $(this).addClass('bounce');
                setTimeout(() => {
                    $(this).removeClass('bounce');
                }, 600);
            });
        },
        
        checkForNewReplies: function() {
            const topicId = $('.ep-topic-content').data('topic-id');
            const lastReplyId = $('.ep-reply-item:last').data('reply-id') || 0;
            
            $.post(ep_forum_ajax.ajax_url, {
                action: 'ep_check_new_replies',
                nonce: ep_forum_ajax.nonce,
                topic_id: topicId,
                last_reply_id: lastReplyId
            })
            .done(function(response) {
                if (response.success && response.data.has_new) {
                    EPForum.showNewRepliesNotification(response.data.count);
                }
            });
        },
        
        updateUserPresence: function() {
            $.post(ep_forum_ajax.ajax_url, {
                action: 'ep_update_presence',
                nonce: ep_forum_ajax.nonce,
                page: window.location.pathname
            });
        },
        
        validateTopicForm: function(form) {
            let isValid = true;
            
            const title = form.find('[name="topic_title"]').val().trim();
            const content = form.find('[name="topic_content"]').val().trim();
            
            if (title.length < 5) {
                this.showMessage('error', 'Tiêu đề phải có ít nhất 5 ký tự');
                isValid = false;
            }
            
            if (content.length < 10) {
                this.showMessage('error', 'Nội dung phải có ít nhất 10 ký tự');
                isValid = false;
            }
            
            return isValid;
        },
        
        validateReplyForm: function(form) {
            const content = form.find('[name="reply_content"]').val().trim();
            
            if (content.length < 10) {
                this.showMessage('error', 'Phản hồi phải có ít nhất 10 ký tự');
                return false;
            }
            
            return true;
        },
        
        addReplyToPage: function(replyData) {
            const replyHtml = this.buildReplyHtml(replyData);
            $('.ep-replies-section').append(replyHtml);
            
            // Scroll to new reply
            $('html, body').animate({
                scrollTop: $('.ep-reply-item:last').offset().top - 100
            }, 500);
        },
        
        buildReplyHtml: function(replyData) {
            return `
                <div class="ep-reply-item" data-reply-id="${replyData.reply_id}">
                    <div class="ep-reply-header">
                        <span class="ep-reply-author">${replyData.author}</span>
                        <span class="ep-reply-date">vừa xong</span>
                    </div>
                    <div class="ep-reply-content">
                        ${replyData.content}
                    </div>
                    <div class="ep-reply-actions">
                        <button class="ep-reply-btn ep-quote-btn" data-reply-id="${replyData.reply_id}">
                            Trích dẫn
                        </button>
                        <button class="ep-reply-btn ep-like-btn" data-post-id="${replyData.reply_id}">
                            👍 <span class="like-count">0</span>
                        </button>
                    </div>
                </div>
            `;
        },
        
        updateReplyCount: function() {
            const currentCount = parseInt($('.topic-reply-count').text()) || 0;
            $('.topic-reply-count').text(currentCount + 1);
        },
        
        showMessage: function(type, message, duration = 5000) {
            const messageHtml = `
                <div class="ep-message ep-${type}" style="position: fixed; top: 20px; right: 20px; z-index: 9999; padding: 15px 20px; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); max-width: 400px;">
                    ${message}
                    <button style="float: right; margin-left: 10px; background: none; border: none; font-size: 18px; cursor: pointer;">&times;</button>
                </div>
            `;
            
            const $message = $(messageHtml);
            $('body').append($message);
            
            // Auto hide
            setTimeout(() => {
                $message.fadeOut(() => $message.remove());
            }, duration);
            
            // Manual close
            $message.find('button').on('click', () => {
                $message.fadeOut(() => $message.remove());
            });
        },
        
        showEcoPointsEarned: function(points, action) {
            const notification = `
                <div class="ep-eco-notification" style="position: fixed; top: 80px; right: 20px; z-index: 9999; background: #e8f5e8; color: #2E7D32; padding: 15px 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 24px;">🌱</span>
                    <div>
                        <div style="font-weight: 600;">+${points} Eco Points</div>
                        <div style="font-size: 0.9em; opacity: 0.8;">${action}</div>
                    </div>
                </div>
            `;
            
            const $notification = $(notification);
            $('body').append($notification);
            
            // Animate in
            $notification.css('transform', 'translateX(100%)').animate({
                transform: 'translateX(0)'
            }, 300);
            
            // Auto hide
            setTimeout(() => {
                $notification.animate({
                    transform: 'translateX(100%)',
                    opacity: 0
                }, 300, () => $notification.remove());
            }, 3000);
        },
        
        showNewRepliesNotification: function(count) {
            const notification = `
                <div class="ep-new-replies-notification" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999; background: #4CAF50; color: white; padding: 15px 20px; border-radius: 12px; cursor: pointer;">
                    ${count} phản hồi mới - Nhấn để xem
                </div>
            `;
            
            const $notification = $(notification);
            $('body').append($notification);
            
            $notification.on('click', () => {
                location.reload();
            });
            
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, 10000);
        },
        
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func.apply(this, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        EPForum.init();
        
        // Restore drafts
        $('.ep-forum-editor').each(function() {
            const draftKey = $(this).data('draft-key');
            const savedDraft = localStorage.getItem(`ep_forum_draft_${draftKey}`);
            
            if (savedDraft && $(this).val().trim() === '') {
                $(this).val(savedDraft);
                EPForum.showMessage('info', 'Bản nháp đã được khôi phục', 3000);
            }
        });
    });

})(jQuery);
