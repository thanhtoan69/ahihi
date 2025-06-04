<?php
/**
 * Single Forum Template
 */

get_header(); ?>

<div class="ep-forum-container">
    <div class="ep-forum-header">
        <h1 class="forum-title"><?php the_title(); ?></h1>
        <div class="forum-description">
            <?php the_content(); ?>
        </div>
        
        <div class="forum-actions">
            <?php if (current_user_can('create_forum_topics')): ?>
                <button id="create-topic-btn" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tạo chủ đề mới
                </button>
            <?php else: ?>
                <p><a href="<?php echo wp_login_url(get_permalink()); ?>">Đăng nhập</a> để tạo chủ đề mới</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Topic Creation Form -->
    <div id="topic-form" class="topic-form" style="display: none;">
        <h3>Tạo chủ đề mới</h3>
        <form id="create-topic-form">
            <div class="form-group">
                <label for="topic-title">Tiêu đề chủ đề:</label>
                <input type="text" id="topic-title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="topic-content">Nội dung:</label>
                <textarea id="topic-content" name="content" rows="10" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="topic-category">Danh mục:</label>
                <select id="topic-category" name="category">
                    <option value="">Chọn danh mục</option>
                    <?php
                    $categories = get_terms(array(
                        'taxonomy' => 'forum_category',
                        'hide_empty' => false
                    ));
                    foreach ($categories as $category) {
                        echo '<option value="' . $category->slug . '">' . $category->name . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Tạo chủ đề</button>
                <button type="button" id="cancel-topic" class="btn btn-secondary">Hủy</button>
            </div>
            
            <input type="hidden" name="forum_id" value="<?php echo get_the_ID(); ?>">
            <?php wp_nonce_field('ep_forum_nonce', 'nonce'); ?>
        </form>
    </div>

    <!-- Topics List -->
    <div class="forum-topics">
        <h2>Các chủ đề</h2>
        
        <div class="topics-header">
            <div class="topics-filters">
                <select id="topic-sort">
                    <option value="latest">Mới nhất</option>
                    <option value="oldest">Cũ nhất</option>
                    <option value="most_replies">Nhiều phản hồi nhất</option>
                    <option value="most_views">Nhiều lượt xem nhất</option>
                </select>
            </div>
        </div>
        
        <div class="topics-list" id="topics-list">
            <?php
            $topics = get_posts(array(
                'post_type' => 'ep_topic',
                'meta_key' => '_forum_id',
                'meta_value' => get_the_ID(),
                'posts_per_page' => 20,
                'post_status' => 'publish'
            ));
            
            if ($topics): ?>
                <?php foreach ($topics as $topic): ?>
                    <div class="topic-item">
                        <div class="topic-info">
                            <h3 class="topic-title">
                                <a href="<?php echo get_permalink($topic->ID); ?>">
                                    <?php echo $topic->post_title; ?>
                                </a>
                                <?php if (get_post_meta($topic->ID, '_is_sticky', true)): ?>
                                    <span class="sticky-indicator">📌</span>
                                <?php endif; ?>
                            </h3>
                            
                            <div class="topic-meta">
                                <span class="author">
                                    Bởi: <strong><?php echo get_the_author_meta('display_name', $topic->post_author); ?></strong>
                                </span>
                                <span class="date">
                                    <?php echo human_time_diff(strtotime($topic->post_date), current_time('timestamp')) . ' trước'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="topic-stats">
                            <div class="stat">
                                <span class="count"><?php echo get_post_meta($topic->ID, '_topic_replies', true) ?: 0; ?></span>
                                <span class="label">Phản hồi</span>
                            </div>
                            <div class="stat">
                                <span class="count"><?php echo get_post_meta($topic->ID, '_topic_views', true) ?: 0; ?></span>
                                <span class="label">Lượt xem</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-topics">
                    <p>Chưa có chủ đề nào trong forum này. Hãy tạo chủ đề đầu tiên!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Show/hide topic creation form
    $('#create-topic-btn').click(function() {
        $('#topic-form').slideToggle();
    });
    
    $('#cancel-topic').click(function() {
        $('#topic-form').slideUp();
        $('#create-topic-form')[0].reset();
    });
    
    // Handle topic creation
    $('#create-topic-form').submit(function(e) {
        e.preventDefault();
        
        var formData = {
            action: 'ep_create_topic',
            title: $('#topic-title').val(),
            content: $('#topic-content').val(),
            forum_id: $('input[name="forum_id"]').val(),
            category: $('#topic-category').val(),
            nonce: $('input[name="nonce"]').val()
        };
        
        $.ajax({
            url: ep_forum_ajax.ajax_url,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#create-topic-form button[type="submit"]').prop('disabled', true).text('Đang tạo...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data || 'Có lỗi xảy ra');
                }
            },
            error: function() {
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
            },
            complete: function() {
                $('#create-topic-form button[type="submit"]').prop('disabled', false).text('Tạo chủ đề');
            }
        });
    });
    
    // Handle topic sorting
    $('#topic-sort').change(function() {
        var sortBy = $(this).val();
        // Implement AJAX sorting logic here
    });
});
</script>

<?php get_footer(); ?>
