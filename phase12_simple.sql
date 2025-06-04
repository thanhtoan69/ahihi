USE environmental_platform;

-- Create voucher_campaigns table
CREATE TABLE IF NOT EXISTS voucher_campaigns (
    campaign_id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_name VARCHAR(255) NOT NULL,
    campaign_slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    campaign_type ENUM('seasonal', 'promotional', 'loyalty', 'referral', 'environmental', 'milestone') NOT NULL,
    created_by INT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    campaign_status ENUM('draft', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'draft',
    auto_apply BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Create vouchers table with complex rules
CREATE TABLE IF NOT EXISTS vouchers (
    voucher_id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT,
    voucher_code VARCHAR(50) UNIQUE NOT NULL,
    voucher_name VARCHAR(255) NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed_amount', 'free_shipping', 'buy_x_get_y', 'green_points_multiplier', 'cashback') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    max_discount_amount DECIMAL(10,2) DEFAULT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    total_usage_limit INT DEFAULT NULL,
    current_usage INT DEFAULT 0,
    can_stack_with_others BOOLEAN DEFAULT FALSE,
    valid_from DATETIME NOT NULL,
    valid_until DATETIME NOT NULL,
    auto_apply BOOLEAN DEFAULT FALSE,
    voucher_status ENUM('draft', 'active', 'paused', 'expired', 'exhausted', 'cancelled') DEFAULT 'draft',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES voucher_campaigns(campaign_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Create voucher_usage table for tracking
CREATE TABLE IF NOT EXISTS voucher_usage (
    usage_id INT PRIMARY KEY AUTO_INCREMENT,
    voucher_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    usage_type ENUM('claimed', 'applied', 'used', 'refunded', 'expired_unused') NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    original_order_amount DECIMAL(10,2) DEFAULT 0,
    final_order_amount DECIMAL(10,2) DEFAULT 0,
    auto_applied BOOLEAN DEFAULT FALSE,
    application_source ENUM('manual', 'auto', 'recommendation', 'reminder') DEFAULT 'manual',
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voucher_id) REFERENCES vouchers(voucher_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Sample data
INSERT IGNORE INTO voucher_campaigns (campaign_name, campaign_slug, description, campaign_type, created_by, start_date, end_date, campaign_status, auto_apply) VALUES
('Tháng Môi trường 2025', 'thang-moi-truong-2025', 'Chiến dịch khuyến mãi nhân tháng môi trường thế giới', 'environmental', 1, '2025-06-01 00:00:00', '2025-06-30 23:59:59', 'active', TRUE),
('Chào mừng thành viên mới', 'chao-mung-thanh-vien-moi', 'Ưu đãi dành cho khách hàng lần đầu mua hàng', 'promotional', 1, '2025-01-01 00:00:00', '2025-12-31 23:59:59', 'active', TRUE);

INSERT IGNORE INTO vouchers (campaign_id, voucher_code, voucher_name, description, discount_type, discount_value, max_discount_amount, min_order_amount, total_usage_limit, valid_from, valid_until, can_stack_with_others, auto_apply, voucher_status, created_by) VALUES
(1, 'ECO2025', 'Giảm 20% sản phẩm xanh', 'Giảm giá 20% cho tất cả sản phẩm có eco_score cao', 'percentage', 20.00, 100000.00, 50000.00, 1000, '2025-06-01 00:00:00', '2025-06-30 23:59:59', TRUE, TRUE, 'active', 1),
(2, 'NEWBIE50', 'Giảm 50k đơn đầu tiên', 'Voucher chào mừng thành viên mới - giảm 50,000đ', 'fixed_amount', 50000.00, NULL, 100000.00, NULL, '2025-01-01 00:00:00', '2025-12-31 23:59:59', FALSE, TRUE, 'active', 1),
(NULL, 'FREESHIP100', 'Miễn phí vận chuyển', 'Miễn phí ship cho đơn từ 200k', 'free_shipping', 0.00, 50000.00, 200000.00, NULL, '2025-06-01 00:00:00', '2025-12-31 23:59:59', TRUE, TRUE, 'active', 1);

INSERT IGNORE INTO voucher_usage (voucher_id, user_id, order_id, usage_type, discount_amount, original_order_amount, final_order_amount, auto_applied, application_source) VALUES
(2, 2, 1, 'used', 50000.00, 150000.00, 100000.00, TRUE, 'auto'),
(1, 1, 2, 'used', 30000.00, 150000.00, 120000.00, FALSE, 'manual');

SELECT 'Phase 12: Voucher & Rewards System - COMPLETED!' as status;
