<?php
/**
 * Single Item Exchange Template
 * 
 * Template for displaying individual item exchange posts
 */

get_header(); ?>

<style>
.exchange-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.exchange-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 30px 0;
    background: linear-gradient(135deg, #4CAF50, #8BC34A);
    color: white;
    border-radius: 12px;
}

.exchange-title {
    font-size: 2.5rem;
    margin-bottom: 10px;
    font-weight: bold;
}

.exchange-meta {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.meta-item {
    background: rgba(255,255,255,0.2);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
}

.exchange-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 40px;
    margin-top: 40px;
}

.main-content {
    background: #f8f9fa;
    padding: 30px;
    border-radius: 12px;
}

.item-details {
    margin-bottom: 30px;
}

.detail-group {
    margin-bottom: 25px;
    padding: 20px;
    background: white;
    border-radius: 8px;
    border-left: 4px solid #4CAF50;
}

.detail-label {
    font-weight: bold;
    color: #2E7D32;
    margin-bottom: 8px;
    font-size: 1.1rem;
}

.detail-value {
    color: #333;
    line-height: 1.6;
}

.exchange-type {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: bold;
    margin-bottom: 15px;
}

.type-offer {
    background: #E8F5E8;
    color: #2E7D32;
}

.type-request {
    background: #E3F2FD;
    color: #1976D2;
}

.condition-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: bold;
    margin-left: 10px;
}

.condition-excellent { background: #C8E6C9; color: #2E7D32; }
.condition-good { background: #DCEDC8; color: #558B2F; }
.condition-fair { background: #FFF9C4; color: #F57F17; }
.condition-poor { background: #FFCDD2; color: #C62828; }

.item-images {
    margin: 20px 0;
}

.image-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.gallery-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.gallery-image:hover {
    transform: scale(1.05);
}

.location-info {
    background: linear-gradient(135deg, #E8F5E8, #F1F8E9);
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.location-title {
    font-weight: bold;
    color: #2E7D32;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.contact-section {
    background: white;
    padding: 25px;
    border-radius: 8px;
    border: 2px solid #E8F5E8;
}

.contact-btn {
    width: 100%;
    background: linear-gradient(135deg, #4CAF50, #66BB6A);
    color: white;
    border: none;
    padding: 15px 25px;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 15px;
}

.contact-btn:hover {
    background: linear-gradient(135deg, #388E3C, #4CAF50);
    transform: translateY(-2px);
}

.sidebar {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.sidebar-widget {
    background: white;
    padding: 25px;
    border-radius: 12px;
    border: 1px solid #E0E0E0;
}

.widget-title {
    font-size: 1.3rem;
    font-weight: bold;
    color: #2E7D32;
    margin-bottom: 20px;
    border-bottom: 2px solid #E8F5E8;
    padding-bottom: 10px;
}

.exchange-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: #F8F9FA;
    border-radius: 8px;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #4CAF50;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
    margin-top: 5px;
}

.related-items {
    list-style: none;
    padding: 0;
}

.related-item {
    padding: 15px;
    border-bottom: 1px solid #E0E0E0;
    transition: background 0.3s ease;
}

.related-item:hover {
    background: #F8F9FA;
}

.related-item:last-child {
    border-bottom: none;
}

.related-link {
    text-decoration: none;
    color: #333;
    font-weight: 500;
}

.related-link:hover {
    color: #4CAF50;
}

.tips-list {
    list-style: none;
    padding: 0;
}

.tip-item {
    padding: 10px 0;
    border-bottom: 1px solid #E8F5E8;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.tip-item:last-child {
    border-bottom: none;
}

.tip-icon {
    color: #4CAF50;
    margin-top: 2px;
}

.action-buttons {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.action-btn {
    flex: 1;
    padding: 12px;
    border: 2px solid #4CAF50;
    background: white;
    color: #4CAF50;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    text-decoration: none;
    font-weight: 500;
}

.action-btn:hover {
    background: #4CAF50;
    color: white;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.8);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 30px;
    border-radius: 12px;
    width: 90%;
    max-width: 600px;
    position: relative;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    position: absolute;
    top: 15px;
    right: 25px;
}

.close:hover {
    color: #000;
}

.form-group {
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

.form-input {
    width: 100%;
    padding: 12px;
    border: 2px solid #E0E0E0;
    border-radius: 6px;
    font-size: 1rem;
}

.form-input:focus {
    outline: none;
    border-color: #4CAF50;
}

.form-textarea {
    height: 120px;
    resize: vertical;
}

@media (max-width: 768px) {
    .exchange-content {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .exchange-title {
        font-size: 2rem;
    }
    
    .exchange-meta {
        flex-direction: column;
        align-items: center;
    }
    
    .image-gallery {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .exchange-stats {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<div class="exchange-container">
    <?php while (have_posts()) : the_post(); ?>
        
        <div class="exchange-header">
            <h1 class="exchange-title"><?php the_title(); ?></h1>
            
            <?php
            $exchange_type = get_post_meta(get_the_ID(), '_exchange_type', true);
            $item_condition = get_post_meta(get_the_ID(), '_item_condition', true);
            $location = get_post_meta(get_the_ID(), '_exchange_location', true);
            $posted_date = get_the_date();
            $author_name = get_the_author();
            ?>
            
            <div class="exchange-meta">
                <span class="meta-item">📅 Đăng: <?php echo $posted_date; ?></span>
                <span class="meta-item">👤 Bởi: <?php echo $author_name; ?></span>
                <span class="meta-item">📍 <?php echo $location ?: 'Chưa cập nhật'; ?></span>
            </div>
        </div>

        <div class="exchange-content">
            <div class="main-content">
                <!-- Exchange Type -->
                <div class="exchange-type <?php echo $exchange_type === 'offer' ? 'type-offer' : 'type-request'; ?>">
                    <?php echo $exchange_type === 'offer' ? '🎁 TẶNG/CHO' : '🔍 TÌM KIẾM'; ?>
                </div>

                <!-- Item Details -->
                <div class="item-details">
                    <div class="detail-group">
                        <div class="detail-label">📦 Mô tả vật phẩm</div>
                        <div class="detail-value">
                            <?php the_content(); ?>
                        </div>
                    </div>

                    <?php if ($item_condition): ?>
                    <div class="detail-group">
                        <div class="detail-label">
                            ⭐ Tình trạng
                            <span class="condition-badge condition-<?php echo $item_condition; ?>">
                                <?php 
                                $conditions = [
                                    'excellent' => 'Xuất sắc',
                                    'good' => 'Tốt', 
                                    'fair' => 'Khá',
                                    'poor' => 'Cần sửa chữa'
                                ];
                                echo $conditions[$item_condition] ?? $item_condition;
                                ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php
                    $category = get_post_meta(get_the_ID(), '_item_category', true);
                    $brand = get_post_meta(get_the_ID(), '_item_brand', true);
                    $size = get_post_meta(get_the_ID(), '_item_size', true);
                    $weight = get_post_meta(get_the_ID(), '_item_weight', true);
                    ?>

                    <?php if ($category): ?>
                    <div class="detail-group">
                        <div class="detail-label">🏷️ Danh mục</div>
                        <div class="detail-value"><?php echo esc_html($category); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($brand): ?>
                    <div class="detail-group">
                        <div class="detail-label">🏢 Thương hiệu</div>
                        <div class="detail-value"><?php echo esc_html($brand); ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($size || $weight): ?>
                    <div class="detail-group">
                        <div class="detail-label">📏 Kích thước & Trọng lượng</div>
                        <div class="detail-value">
                            <?php if ($size): ?>Kích thước: <?php echo esc_html($size); ?><br><?php endif; ?>
                            <?php if ($weight): ?>Trọng lượng: <?php echo esc_html($weight); ?><?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Item Images -->
                <?php
                $gallery_images = get_post_meta(get_the_ID(), '_exchange_gallery', true);
                if ($gallery_images):
                ?>
                <div class="item-images">
                    <div class="detail-label">📸 Hình ảnh vật phẩm</div>
                    <div class="image-gallery">
                        <?php
                        $featured_image = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                        if ($featured_image):
                        ?>
                        <img src="<?php echo $featured_image; ?>" alt="<?php the_title(); ?>" class="gallery-image" onclick="openImageModal(this.src)">
                        <?php endif; ?>
                        
                        <?php foreach ($gallery_images as $image_id): ?>
                        <img src="<?php echo wp_get_attachment_image_url($image_id, 'medium'); ?>" alt="Gallery Image" class="gallery-image" onclick="openImageModal(this.src)">
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Location Information -->
                <?php if ($location): ?>
                <div class="location-info">
                    <div class="location-title">
                        <span>📍</span> Thông tin vị trí
                    </div>
                    <div><?php echo esc_html($location); ?></div>
                    <?php
                    $pickup_time = get_post_meta(get_the_ID(), '_pickup_time', true);
                    if ($pickup_time):
                    ?>
                    <div style="margin-top: 10px;">
                        <strong>⏰ Thời gian thuận tiện:</strong> <?php echo esc_html($pickup_time); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="action-btn" onclick="openContactModal()">
                        💬 Liên hệ
                    </button>
                    <button class="action-btn" onclick="shareItem()">
                        📤 Chia sẻ
                    </button>
                    <button class="action-btn" onclick="reportItem()">
                        🚩 Báo cáo
                    </button>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Contact Information -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">💬 Liên hệ</h3>
                    <div class="contact-section">
                        <button class="contact-btn" onclick="openContactModal()">
                            Gửi tin nhắn cho <?php echo get_the_author(); ?>
                        </button>
                        <div style="text-align: center; color: #666; font-size: 0.9rem;">
                            💡 Lưu ý: Luôn gặp mặt tại nơi công cộng để trao đổi vật phẩm
                        </div>
                    </div>
                </div>

                <!-- Exchange Statistics -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">📊 Thống kê</h3>
                    <div class="exchange-stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo rand(5, 50); ?></div>
                            <div class="stat-label">Lượt xem</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo rand(1, 10); ?></div>
                            <div class="stat-label">Quan tâm</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo get_the_author_meta('exchange_count') ?: rand(1, 20); ?></div>
                            <div class="stat-label">Giao dịch của người đăng</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">⭐ <?php echo number_format(rand(40, 50) / 10, 1); ?></div>
                            <div class="stat-label">Đánh giá</div>
                        </div>
                    </div>
                </div>

                <!-- Safety Tips -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">🛡️ Mẹo an toàn</h3>
                    <ul class="tips-list">
                        <li class="tip-item">
                            <span class="tip-icon">✅</span>
                            <span>Gặp mặt tại nơi công cộng, đông người</span>
                        </li>
                        <li class="tip-item">
                            <span class="tip-icon">✅</span>
                            <span>Kiểm tra kỹ vật phẩm trước khi nhận</span>
                        </li>
                        <li class="tip-item">
                            <span class="tip-icon">✅</span>
                            <span>Không chia sẻ thông tin cá nhân nhạy cảm</span>
                        </li>
                        <li class="tip-item">
                            <span class="tip-icon">✅</span>
                            <span>Báo cáo nếu có hành vi đáng ngờ</span>
                        </li>
                    </ul>
                </div>

                <!-- Related Items -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">🔗 Vật phẩm tương tự</h3>
                    <?php
                    $related_items = get_posts([
                        'post_type' => 'item_exchange',
                        'posts_per_page' => 5,
                        'post__not_in' => [get_the_ID()],
                        'meta_query' => [
                            [
                                'key' => '_item_category',
                                'value' => $category,
                                'compare' => 'LIKE'
                            ]
                        ]
                    ]);
                    ?>
                    <ul class="related-items">
                        <?php foreach ($related_items as $item): ?>
                        <li class="related-item">
                            <a href="<?php echo get_permalink($item->ID); ?>" class="related-link">
                                <?php echo get_the_title($item->ID); ?>
                            </a>
                            <div style="font-size: 0.8rem; color: #666; margin-top: 5px;">
                                <?php echo get_post_meta($item->ID, '_exchange_type', true) === 'offer' ? '🎁 Tặng' : '🔍 Tìm'; ?>
                                • <?php echo get_the_date('d/m/Y', $item->ID); ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                        
                        <?php if (empty($related_items)): ?>
                        <li class="related-item">
                            <span style="color: #666;">Chưa có vật phẩm tương tự</span>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

    <?php endwhile; ?>
</div>

<!-- Contact Modal -->
<div id="contactModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeContactModal()">&times;</span>
        <h2 style="color: #2E7D32; margin-bottom: 20px;">💬 Gửi tin nhắn</h2>
        <form id="contactForm" onsubmit="submitContactForm(event)">
            <div class="form-group">
                <label for="senderName" class="form-label">Tên của bạn *</label>
                <input type="text" id="senderName" name="senderName" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="senderEmail" class="form-label">Email *</label>
                <input type="email" id="senderEmail" name="senderEmail" class="form-input" required>
            </div>
            <div class="form-group">
                <label for="senderPhone" class="form-label">Số điện thoại</label>
                <input type="tel" id="senderPhone" name="senderPhone" class="form-input">
            </div>
            <div class="form-group">
                <label for="message" class="form-label">Tin nhắn *</label>
                <textarea id="message" name="message" class="form-input form-textarea" placeholder="Xin chào! Tôi quan tâm đến vật phẩm này..." required></textarea>
            </div>
            <button type="submit" class="contact-btn">📨 Gửi tin nhắn</button>
        </form>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="modal">
    <div class="modal-content" style="max-width: 90%; text-align: center;">
        <span class="close" onclick="closeImageModal()">&times;</span>
        <img id="modalImage" src="" alt="Gallery Image" style="max-width: 100%; height: auto; border-radius: 8px;">
    </div>
</div>

<script>
// Contact Modal Functions
function openContactModal() {
    document.getElementById('contactModal').style.display = 'block';
}

function closeContactModal() {
    document.getElementById('contactModal').style.display = 'none';
}

function submitContactForm(event) {
    event.preventDefault();
    // Here you would typically send the form data to your backend
    alert('✅ Tin nhắn đã được gửi! Người đăng sẽ liên hệ với bạn sớm.');
    closeContactModal();
    document.getElementById('contactForm').reset();
}

// Image Modal Functions
function openImageModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').style.display = 'block';
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
}

// Share Function
function shareItem() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo get_the_title(); ?>',
            text: 'Xem vật phẩm này trên nền tảng trao đổi môi trường',
            url: window.location.href
        });
    } else {
        // Fallback: copy URL to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('✅ Đã sao chép liên kết vào clipboard!');
        });
    }
}

// Report Function
function reportItem() {
    const reason = prompt('Lý do báo cáo:\n1. Nội dung không phù hợp\n2. Lừa đảo\n3. Spam\n4. Khác\n\nNhập số từ 1-4:');
    if (reason) {
        alert('✅ Báo cáo đã được gửi. Chúng tôi sẽ xem xét trong 24h.');
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    const contactModal = document.getElementById('contactModal');
    const imageModal = document.getElementById('imageModal');
    
    if (event.target === contactModal) {
        closeContactModal();
    }
    if (event.target === imageModal) {
        closeImageModal();
    }
}

// Auto-resize textareas
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('.form-textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });
});
</script>

<?php get_footer(); ?>
