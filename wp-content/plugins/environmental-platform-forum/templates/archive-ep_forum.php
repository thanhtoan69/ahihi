<?php
/**
 * Forum Archive Template
 */

get_header(); ?>

<div class="ep-forum-container">
    <div class="forum-header">
        <h1>Forum Cộng đồng Môi trường</h1>
        <p class="forum-description">
            Nơi chia sẻ kiến thức, kinh nghiệm và thảo luận về các vấn đề môi trường. 
            Cùng nhau xây dựng một cộng đồng bảo vệ môi trường bền vững.
        </p>
        
        <div class="forum-stats">
            <?php
            $forums_count = wp_count_posts('ep_forum');
            $topics_count = wp_count_posts('ep_topic');
            $replies_count = wp_count_posts('ep_reply');
            ?>
            <div class="stat-item">
                <span class="count"><?php echo $forums_count->publish; ?></span>
                <span class="label">Diễn đàn</span>
            </div>
            <div class="stat-item">
                <span class="count"><?php echo $topics_count->publish; ?></span>
                <span class="label">Chủ đề</span>
            </div>
            <div class="stat-item">
                <span class="count"><?php echo $replies_count->publish; ?></span>
                <span class="label">Phản hồi</span>
            </div>
        </div>
    </div>

    <div class="forum-navigation">
        <div class="forum-categories">
            <h3>Danh mục</h3>
            <ul class="category-list">
                <?php
                $categories = get_terms(array(
                    'taxonomy' => 'forum_category',
                    'hide_empty' => false
                ));
                
                foreach ($categories as $category) {
                    $count = $category->count;
                    echo '<li>';
                    echo '<a href="' . get_term_link($category) . '">';
                    echo $category->name . ' <span class="count">(' . $count . ')</span>';
                    echo '</a>';
                    echo '</li>';
                }
                ?>
            </ul>
        </div>
        
        <div class="environmental-topics">
            <h3>Chủ đề môi trường</h3>
            <ul class="topic-list">
                <?php
                $env_topics = get_terms(array(
                    'taxonomy' => 'environmental_topic',
                    'hide_empty' => false
                ));
                
                foreach ($env_topics as $topic) {
                    $count = $topic->count;
                    echo '<li>';
                    echo '<a href="' . get_term_link($topic) . '">';
                    echo $topic->name . ' <span class="count">(' . $count . ')</span>';
                    echo '</a>';
                    echo '</li>';
                }
                ?>
            </ul>
        </div>
    </div>

    <div class="forums-list">
        <h2>Các diễn đàn</h2>
        
        <div class="forums-grid">
            <?php
            $forums = get_posts(array(
                'post_type' => 'ep_forum',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'menu_order',
                'order' => 'ASC'
            ));
            
            if ($forums): ?>
                <?php foreach ($forums as $forum): ?>
                    <div class="forum-item">
                        <div class="forum-icon">
                            <?php if (has_post_thumbnail($forum->ID)): ?>
                                <?php echo get_the_post_thumbnail($forum->ID, 'thumbnail'); ?>
                            <?php else: ?>
                                <div class="default-icon">🌱</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="forum-info">
                            <h3 class="forum-title">
                                <a href="<?php echo get_permalink($forum->ID); ?>">
                                    <?php echo $forum->post_title; ?>
                                </a>
                            </h3>
                            
                            <div class="forum-description">
                                <?php echo wp_trim_words($forum->post_content, 20); ?>
                            </div>
                            
                            <div class="forum-meta">
                                <?php
                                $topics_count = get_posts(array(
                                    'post_type' => 'ep_topic',
                                    'meta_key' => '_forum_id',
                                    'meta_value' => $forum->ID,
                                    'post_status' => 'publish',
                                    'numberposts' => -1,
                                    'fields' => 'ids'
                                ));
                                
                                $replies_count = 0;
                                foreach ($topics_count as $topic_id) {
                                    $topic_replies = get_posts(array(
                                        'post_type' => 'ep_reply',
                                        'meta_key' => '_topic_id',
                                        'meta_value' => $topic_id,
                                        'post_status' => 'publish',
                                        'numberposts' => -1,
                                        'fields' => 'ids'
                                    ));
                                    $replies_count += count($topic_replies);
                                }
                                ?>
                                <span class="topics-count"><?php echo count($topics_count); ?> chủ đề</span>
                                <span class="replies-count"><?php echo $replies_count; ?> phản hồi</span>
                            </div>
                        </div>
                        
                        <div class="forum-latest">
                            <?php
                            $latest_topic = get_posts(array(
                                'post_type' => 'ep_topic',
                                'meta_key' => '_forum_id',
                                'meta_value' => $forum->ID,
                                'post_status' => 'publish',
                                'numberposts' => 1,
                                'orderby' => 'date',
                                'order' => 'DESC'
                            ));
                            
                            if ($latest_topic): ?>
                                <div class="latest-topic">
                                    <h4><a href="<?php echo get_permalink($latest_topic[0]->ID); ?>">
                                        <?php echo wp_trim_words($latest_topic[0]->post_title, 8); ?>
                                    </a></h4>
                                    <span class="latest-author">
                                        bởi <?php echo get_the_author_meta('display_name', $latest_topic[0]->post_author); ?>
                                    </span>
                                    <span class="latest-date">
                                        <?php echo human_time_diff(strtotime($latest_topic[0]->post_date), current_time('timestamp')) . ' trước'; ?>
                                    </span>
                                </div>
                            <?php else: ?>
                                <div class="no-topics">
                                    <span>Chưa có chủ đề nào</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-forums">
                    <p>Chưa có diễn đàn nào được tạo.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
        <h2>Hoạt động gần đây</h2>
        
        <div class="activity-list">
            <?php
            $recent_topics = get_posts(array(
                'post_type' => 'ep_topic',
                'posts_per_page' => 5,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            $recent_replies = get_posts(array(
                'post_type' => 'ep_reply',
                'posts_per_page' => 5,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            // Combine and sort by date
            $recent_activity = array_merge($recent_topics, $recent_replies);
            usort($recent_activity, function($a, $b) {
                return strtotime($b->post_date) - strtotime($a->post_date);
            });
            
            $recent_activity = array_slice($recent_activity, 0, 10);
            
            foreach ($recent_activity as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <?php echo $activity->post_type === 'ep_topic' ? '💬' : '↩️'; ?>
                    </div>
                    
                    <div class="activity-content">
                        <?php if ($activity->post_type === 'ep_topic'): ?>
                            <span class="activity-text">
                                <strong><?php echo get_the_author_meta('display_name', $activity->post_author); ?></strong>
                                đã tạo chủ đề mới: 
                                <a href="<?php echo get_permalink($activity->ID); ?>">
                                    <?php echo wp_trim_words($activity->post_title, 10); ?>
                                </a>
                            </span>
                        <?php else: ?>
                            <?php $topic_id = get_post_meta($activity->ID, '_topic_id', true); ?>
                            <span class="activity-text">
                                <strong><?php echo get_the_author_meta('display_name', $activity->post_author); ?></strong>
                                đã trả lời trong: 
                                <a href="<?php echo get_permalink($topic_id); ?>">
                                    <?php echo get_the_title($topic_id); ?>
                                </a>
                            </span>
                        <?php endif; ?>
                        
                        <span class="activity-time">
                            <?php echo human_time_diff(strtotime($activity->post_date), current_time('timestamp')) . ' trước'; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Online Users -->
    <div class="online-users">
        <h3>Thành viên trực tuyến</h3>
        <div class="users-list">
            <?php
            // Get users who have been active in the last 5 minutes
            $online_users = get_users(array(
                'meta_key' => 'last_activity',
                'meta_value' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
                'meta_compare' => '>',
                'number' => 10
            ));
            
            if ($online_users):
                foreach ($online_users as $user): ?>
                    <span class="online-user">
                        <?php echo get_avatar($user->ID, 24); ?>
                        <?php echo $user->display_name; ?>
                    </span>
                <?php endforeach;
            else: ?>
                <span class="no-users">Không có thành viên nào trực tuyến</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
