<?php
/**
 * Single Topic Template
 */

get_header(); ?>

<div class="ep-forum-container">
    <div class="topic-header">
        <div class="breadcrumb">
            <a href="<?php echo home_url('/forum'); ?>">Forum</a> >
            <?php
            $forum_id = get_post_meta(get_the_ID(), '_forum_id', true);
            if ($forum_id) {
                echo '<a href="' . get_permalink($forum_id) . '">' . get_the_title($forum_id) . '</a> > ';
            }
            ?>
            <span class="current"><?php the_title(); ?></span>
        </div>
        
        <h1 class="topic-title"><?php the_title(); ?></h1>
        
        <div class="topic-meta">
            <span class="author">
                Tạo bởi: <strong><?php the_author(); ?></strong>
            </span>
            <span class="date">
                <?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' trước'; ?>
            </span>
            <span class="views">
                <?php 
                $views = get_post_meta(get_the_ID(), '_topic_views', true) ?: 0;
                update_post_meta(get_the_ID(), '_topic_views', $views + 1);
                echo $views . ' lượt xem';
                ?>
            </span>
        </div>
        
        <div class="topic-actions">
            <?php if (current_user_can('moderate_forum')): ?>
                <div class="moderator-actions">
                    <button class="btn btn-sm moderate-btn" data-action="sticky" data-post-id="<?php echo get_the_ID(); ?>">
                        <?php echo get_post_meta(get_the_ID(), '_is_sticky', true) ? 'Bỏ ghim' : 'Ghim'; ?>
                    </button>
                    <button class="btn btn-sm moderate-btn" data-action="lock" data-post-id="<?php echo get_the_ID(); ?>">
                        <?php echo get_post_meta(get_the_ID(), '_is_locked', true) ? 'Mở khóa' : 'Khóa'; ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Original Topic Content -->
    <div class="topic-content">
        <div class="post-item original-post">
            <div class="post-author">
                <div class="author-avatar">
                    <?php echo get_avatar(get_the_author_meta('ID'), 64); ?>
                </div>
                <div class="author-info">
                    <h4><?php the_author(); ?></h4>
                    <span class="author-role">
                        <?php
                        $user = wp_get_current_user();
                        $roles = $user->roles;
                        echo !empty($roles) ? ucfirst($roles[0]) : 'Thành viên';
                        ?>
                    </span>
                    <span class="eco-points">
                        Eco Points: <?php echo get_user_meta(get_the_author_meta('ID'), 'eco_points', true) ?: 0; ?>
                    </span>
                </div>
            </div>
            
            <div class="post-content">
                <div class="post-date">
                    <?php echo get_the_date() . ' lúc ' . get_the_time(); ?>
                </div>
                <div class="content">
                    <?php the_content(); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Replies Section -->
    <div class="topic-replies">
        <h3>Phản hồi</h3>
        
        <div class="replies-list" id="replies-list">
            <?php
            $replies = get_posts(array(
                'post_type' => 'ep_reply',
                'meta_key' => '_topic_id',
                'meta_value' => get_the_ID(),
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'ASC'
            ));
            
            if ($replies): ?>
                <?php foreach ($replies as $reply): ?>
                    <div class="post-item reply-post" data-reply-id="<?php echo $reply->ID; ?>">
                        <div class="post-author">
                            <div class="author-avatar">
                                <?php echo get_avatar($reply->post_author, 48); ?>
                            </div>
                            <div class="author-info">
                                <h5><?php echo get_the_author_meta('display_name', $reply->post_author); ?></h5>
                                <span class="eco-points">
                                    Eco Points: <?php echo get_user_meta($reply->post_author, 'eco_points', true) ?: 0; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="post-content">
                            <div class="post-date">
                                <?php echo get_the_date('', $reply->ID) . ' lúc ' . get_the_time('', $reply->ID); ?>
                            </div>
                            <div class="content">
                                <?php echo apply_filters('the_content', $reply->post_content); ?>
                            </div>
                            
                            <div class="post-actions">
                                <?php if (current_user_can('create_forum_replies')): ?>
                                    <button class="btn btn-sm quote-btn" data-reply-id="<?php echo $reply->ID; ?>">
                                        Trích dẫn
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (current_user_can('moderate_forum')): ?>
                                    <button class="btn btn-sm btn-danger moderate-btn" 
                                            data-action="delete" 
                                            data-post-id="<?php echo $reply->ID; ?>">
                                        Xóa
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-replies">
                    <p>Chưa có phản hồi nào. Hãy là người đầu tiên trả lời!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reply Form -->
    <?php if (current_user_can('create_forum_replies') && !get_post_meta(get_the_ID(), '_is_locked', true)): ?>
        <div class="reply-form">
            <h3>Trả lời chủ đề</h3>
            <form id="create-reply-form">
                <div class="form-group">
                    <label for="reply-content">Nội dung phản hồi:</label>
                    <textarea id="reply-content" name="content" rows="8" required 
                              placeholder="Nhập phản hồi của bạn..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Gửi phản hồi</button>
                    <button type="button" id="save-draft" class="btn btn-secondary">Lưu nháp</button>
                </div>
                
                <input type="hidden" name="topic_id" value="<?php echo get_the_ID(); ?>">
                <input type="hidden" name="parent_id" value="0" id="parent-id">
                <?php wp_nonce_field('ep_forum_nonce', 'nonce'); ?>
            </form>
        </div>
    <?php elseif (!is_user_logged_in()): ?>
        <div class="login-prompt">
            <p><a href="<?php echo wp_login_url(get_permalink()); ?>">Đăng nhập</a> để trả lời chủ đề này.</p>
        </div>
    <?php elseif (get_post_meta(get_the_ID(), '_is_locked', true)): ?>
        <div class="locked-topic">
            <p>🔒 Chủ đề này đã bị khóa. Không thể trả lời.</p>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle reply submission
    $('#create-reply-form').submit(function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'ep_create_post',
            content: $('#reply-content').val(),
            topic_id: $('input[name="topic_id"]').val(),
            parent_id: $('input[name="parent_id"]').val(),
            nonce: $('input[name="nonce"]').val()
        };
        
        $.ajax({
            url: ep_forum_ajax.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#create-reply-form button[type="submit"]').prop('disabled', true).text('Đang gửi...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload(); // Refresh to show new reply
                } else {
                    alert(response.data || 'Có lỗi xảy ra');
                }
            },
            error: function() {
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
            },
            complete: function() {
                $('#create-reply-form button[type="submit"]').prop('disabled', false).text('Gửi phản hồi');
            }
        });
    });
    
    // Handle quote functionality
    $('.quote-btn').click(function() {
        var replyId = $(this).data('reply-id');
        var content = $(this).closest('.post-item').find('.content').text();
        var author = $(this).closest('.post-item').find('.author-info h5').text();
        
        var quotedContent = '[quote=' + author + ']' + content + '[/quote]\n\n';
        $('#reply-content').val($('#reply-content').val() + quotedContent);
        $('#reply-content').focus();
        
        // Scroll to reply form
        $('html, body').animate({
            scrollTop: $('.reply-form').offset().top
        }, 500);
    });
    
    // Handle moderation actions
    $('.moderate-btn').click(function() {
        var action = $(this).data('action');
        var postId = $(this).data('post-id');
        
        if (!confirm('Bạn có chắc chắn muốn thực hiện hành động này?')) {
            return;
        }
        
        $.ajax({
            url: ep_forum_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ep_moderate_content',
                action_type: action,
                post_id: postId,
                nonce: $('input[name="nonce"]').val()
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Có lỗi xảy ra');
                }
            }
        });
    });
    
    // Save draft functionality
    $('#save-draft').click(function() {
        var content = $('#reply-content').val();
        if (content) {
            localStorage.setItem('forum_draft_topic_' + $('input[name="topic_id"]').val(), content);
            alert('Nháp đã được lưu!');
        }
    });
    
    // Load draft on page load
    var topicId = $('input[name="topic_id"]').val();
    if (topicId) {
        var draft = localStorage.getItem('forum_draft_topic_' + topicId);
        if (draft) {
            $('#reply-content').val(draft);
        }
    }
});
</script>

<?php get_footer(); ?>
