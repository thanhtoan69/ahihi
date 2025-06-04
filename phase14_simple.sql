-- PHASE 14: DONATION SYSTEM (Simplified)
-- Hệ thống quyên góp môi trường đơn giản
USE environmental_platform;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS donations;
DROP TABLE IF EXISTS donation_campaigns;
DROP TABLE IF EXISTS donation_organizations;

-- ========================================
-- DONATION ORGANIZATIONS TABLE
-- ========================================

CREATE TABLE donation_organizations (
    org_id INT PRIMARY KEY AUTO_INCREMENT,
    org_name VARCHAR(255) NOT NULL,
    org_slug VARCHAR(255) UNIQUE NOT NULL,
    org_type ENUM('ngo', 'charity', 'foundation', 'community') NOT NULL,
    description TEXT,
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(20),
    website_url VARCHAR(255),
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    trust_score DECIMAL(3,2) DEFAULT 5.0,
    total_raised DECIMAL(15,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ========================================
-- DONATION CAMPAIGNS TABLE
-- ========================================

CREATE TABLE donation_campaigns (
    campaign_id INT PRIMARY KEY AUTO_INCREMENT,
    org_id INT NOT NULL,
    creator_user_id INT NOT NULL,
    campaign_name VARCHAR(255) NOT NULL,
    campaign_slug VARCHAR(255) UNIQUE NOT NULL,
    description LONGTEXT NOT NULL,
    funding_goal DECIMAL(12,2) NOT NULL,
    current_amount DECIMAL(12,2) DEFAULT 0,
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NOT NULL,
    campaign_type ENUM('emergency', 'project', 'ongoing', 'education') DEFAULT 'project',
    environmental_focus JSON, -- {"area": "forest", "impact": "reforestation"}
    status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    featured_image VARCHAR(255),
    donor_count INT DEFAULT 0,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (org_id) REFERENCES donation_organizations(org_id) ON DELETE CASCADE,
    FOREIGN KEY (creator_user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- DONATIONS TABLE
-- ========================================

CREATE TABLE donations (
    donation_id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    org_id INT NOT NULL,
    donor_user_id INT DEFAULT NULL, -- NULL for anonymous donations
    donation_amount DECIMAL(12,2) NOT NULL,
    donation_type ENUM('one_time', 'recurring') DEFAULT 'one_time',
    payment_method ENUM('bank_transfer', 'momo', 'zalopay', 'credit_card') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    is_anonymous BOOLEAN DEFAULT FALSE,
    donor_message TEXT,
    eco_points_awarded INT DEFAULT 0,
    payment_reference VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_date TIMESTAMP NULL,
    
    FOREIGN KEY (campaign_id) REFERENCES donation_campaigns(campaign_id) ON DELETE CASCADE,
    FOREIGN KEY (org_id) REFERENCES donation_organizations(org_id) ON DELETE CASCADE,
    FOREIGN KEY (donor_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ========================================
-- DONATION RECURRING SUBSCRIPTIONS TABLE
-- ========================================

CREATE TABLE donation_subscriptions (
    subscription_id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    donor_user_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    frequency ENUM('weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
    payment_method ENUM('bank_transfer', 'momo', 'zalopay', 'credit_card') NOT NULL,
    status ENUM('active', 'paused', 'cancelled', 'expired') DEFAULT 'active',
    next_payment_date TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES donation_campaigns(campaign_id) ON DELETE CASCADE,
    FOREIGN KEY (donor_user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- DONATION TRANSPARENCY REPORTS TABLE
-- ========================================

CREATE TABLE donation_reports (
    report_id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    org_id INT NOT NULL,
    report_type ENUM('monthly', 'quarterly', 'annual', 'project_completion') NOT NULL,
    report_title VARCHAR(255) NOT NULL,
    report_content LONGTEXT NOT NULL,
    financial_breakdown JSON, -- {"admin": 5, "project": 90, "fundraising": 5}
    impact_metrics JSON, -- {"trees_planted": 500, "co2_reduced": 1200}
    supporting_documents JSON, -- ["receipt1.pdf", "photo1.jpg"]
    published_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES donation_campaigns(campaign_id) ON DELETE CASCADE,
    FOREIGN KEY (org_id) REFERENCES donation_organizations(org_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========================================
-- SAMPLE DATA
-- ========================================

-- Insert sample organizations
INSERT INTO donation_organizations (org_name, org_slug, org_type, description, contact_email, verification_status, trust_score) VALUES
('Quỹ Bảo vệ Môi trường Việt Nam', 'quy-bao-ve-moi-truong-vn', 'ngo', 'Tổ chức hàng đầu về bảo vệ môi trường tại Việt Nam', 'contact@greenVN.org', 'verified', 9.5),
('Tổ chức Rừng Xanh', 'to-chuc-rung-xanh', 'ngo', 'Chuyên về trồng rừng và bảo vệ đa dạng sinh học', 'info@forestgreen.org', 'verified', 9.2),
('Hội Năng lượng Sạch', 'hoi-nang-luong-sach', 'foundation', 'Thúc đẩy năng lượng tái tạo tại Việt Nam', 'hello@cleanenergy.vn', 'verified', 8.8),
('Cộng đồng Biển Xanh', 'cong-dong-bien-xanh', 'community', 'Bảo vệ hệ sinh thái biển và ven biển', 'admin@bluesea.org', 'pending', 7.5);

-- Insert sample campaigns (ensure user_id 1 exists first)
INSERT INTO donation_campaigns (org_id, creator_user_id, campaign_name, campaign_slug, description, funding_goal, end_date, campaign_type, environmental_focus, status) VALUES
(1, 1, 'Trồng 10,000 Cây Xanh Miền Trung', 'trong-10000-cay-xanh-mien-trung', 'Dự án trồng cây quy mô lớn nhằm phục hồi rừng sau thiên tai và chống xói mòn đất tại các tỉnh miền Trung', 500000000.00, DATE_ADD(NOW(), INTERVAL 60 DAY), 'project', '{"area": "central_vietnam", "impact": "reforestation", "trees": 10000}', 'active'),
(2, 1, 'Bảo vệ Rừng Nguyên sinh Tây Nguyên', 'bao-ve-rung-nguyen-sinh-tay-nguyen', 'Chiến dịch bảo vệ và phục hồi rừng nguyên sinh tại Tây Nguyên, ngăn chặn nạn phá rừng bất hợp pháp', 750000000.00, DATE_ADD(NOW(), INTERVAL 90 DAY), 'project', '{"area": "highlands", "impact": "forest_protection", "hectares": 500}', 'active'),
(3, 1, 'Năng lượng Mặt trời cho Trường học', 'nang-luong-mat-troi-truong-hoc', 'Lắp đặt hệ thống điện mặt trời cho 50 trường học vùng sâu vùng xa, giảm phát thải carbon', 1000000000.00, DATE_ADD(NOW(), INTERVAL 120 DAY), 'education', '{"area": "rural_schools", "impact": "clean_energy", "schools": 50}', 'active'),
(4, 1, 'Dọn rác Biển và Bảo vệ San hô', 'don-rac-bien-bao-ve-san-ho', 'Chiến dịch dọn rác biển và bảo vệ rạn san hô tại các bãi biển Việt Nam', 300000000.00, DATE_ADD(NOW(), INTERVAL 45 DAY), 'emergency', '{"area": "coastal", "impact": "marine_protection", "beaches": 20}', 'active');

-- Insert sample donations (make sure donor user exists)
INSERT INTO donations (campaign_id, org_id, donor_user_id, donation_amount, payment_method, payment_status, eco_points_awarded, payment_date) VALUES
(1, 1, 1, 100000.00, 'momo', 'completed', 10, NOW()),
(1, 1, NULL, 250000.00, 'bank_transfer', 'completed', 0, NOW()), -- Anonymous donation
(2, 2, 1, 500000.00, 'zalopay', 'completed', 50, NOW()),
(3, 3, 1, 1000000.00, 'credit_card', 'completed', 100, NOW()),
(4, 4, 1, 150000.00, 'momo', 'completed', 15, NOW());

-- Update campaign current amounts and donor counts
UPDATE donation_campaigns dc
SET 
    current_amount = (
        SELECT COALESCE(SUM(d.donation_amount), 0)
        FROM donations d 
        WHERE d.campaign_id = dc.campaign_id AND d.payment_status = 'completed'
    ),
    donor_count = (
        SELECT COUNT(DISTINCT d.donor_user_id)
        FROM donations d 
        WHERE d.campaign_id = dc.campaign_id AND d.payment_status = 'completed' AND d.donor_user_id IS NOT NULL
    );

-- Update organization total raised amounts
UPDATE donation_organizations do
SET total_raised = (
    SELECT COALESCE(SUM(d.donation_amount), 0)
    FROM donations d 
    WHERE d.org_id = do.org_id AND d.payment_status = 'completed'
);

-- Insert sample transparency reports
INSERT INTO donation_reports (campaign_id, org_id, report_type, report_title, report_content, financial_breakdown, impact_metrics) VALUES
(1, 1, 'monthly', 'Báo cáo tiến độ tháng 6/2025 - Dự án trồng cây', 'Trong tháng 6, chúng tôi đã trồng được 1,200 cây xanh tại 3 tỉnh miền Trung. Chi phí thực tế đúng như dự kiến...', '{"project": 85, "admin": 10, "monitoring": 5}', '{"trees_planted": 1200, "co2_absorbed": 480, "area_restored": 12}'),
(2, 2, 'quarterly', 'Báo cáo quý II/2025 - Bảo vệ rừng Tây Nguyên', 'Quý II đã triển khai thành công hệ thống giám sát rừng bằng camera, ngăn chặn 15 vụ phá rừng...', '{"protection": 70, "equipment": 20, "admin": 10}', '{"forest_protected": 150, "illegal_logging_stopped": 15, "rangers_trained": 25}');

-- ========================================
-- USEFUL VIEWS FOR REPORTING
-- ========================================

-- Campaign performance view
CREATE VIEW campaign_performance AS
SELECT 
    c.campaign_id,
    c.campaign_name,
    o.org_name,
    c.funding_goal,
    c.current_amount,
    ROUND((c.current_amount / c.funding_goal) * 100, 2) as funding_percentage,
    c.donor_count,
    DATEDIFF(c.end_date, NOW()) as days_remaining,
    c.status
FROM donation_campaigns c
JOIN donation_organizations o ON c.org_id = o.org_id;

-- Top donors view  
CREATE VIEW top_donors AS
SELECT 
    u.user_id,
    u.username,
    u.first_name,
    u.last_name,
    COUNT(d.donation_id) as total_donations,
    SUM(d.donation_amount) as total_donated,
    AVG(d.donation_amount) as avg_donation
FROM users u
JOIN donations d ON u.user_id = d.donor_user_id
WHERE d.payment_status = 'completed'
GROUP BY u.user_id
ORDER BY total_donated DESC;

COMMIT;
