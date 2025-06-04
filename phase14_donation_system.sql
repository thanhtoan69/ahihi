-- PHASE 14: DONATION SYSTEM
-- Hệ thống quyên góp và từ thiện môi trường
USE environmental_platform;

-- ========================================
-- DONATION ORGANIZATIONS TABLE
-- Quản lý các tổ chức từ thiện
-- ========================================

CREATE TABLE donation_organizations (
    organization_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Basic Information
    organization_name VARCHAR(255) NOT NULL,
    organization_slug VARCHAR(255) UNIQUE NOT NULL,
    organization_type ENUM('ngo', 'charity', 'foundation', 'environmental_group', 'community_group') NOT NULL,
    registration_number VARCHAR(100) UNIQUE,
    
    -- Contact Information
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(20),
    website_url VARCHAR(500),
    address TEXT,
    
    -- Organization Details
    description LONGTEXT,
    mission_statement TEXT,
    established_year YEAR,
    
    -- Environmental Focus
    environmental_focus JSON, -- ["climate_change", "biodiversity", "pollution", "renewable_energy"]
    impact_areas JSON, -- ["local", "national", "international"]
    sdg_goals JSON, -- UN Sustainable Development Goals they support
    
    -- Verification and Trust
    verification_status ENUM('pending', 'verified', 'rejected', 'suspended') DEFAULT 'pending',
    verified_by INT DEFAULT NULL,
    verification_date TIMESTAMP NULL,
    tax_exempt_status BOOLEAN DEFAULT FALSE,
    transparency_score DECIMAL(3,2) DEFAULT 0, -- 0-10 based on reporting
    
    -- Financial Information
    total_donations_received DECIMAL(15,2) DEFAULT 0,
    total_projects_funded INT DEFAULT 0,
    administrative_percentage DECIMAL(5,2) DEFAULT 0, -- % of donations used for admin
    
    -- Media and Documents
    logo_url VARCHAR(500),
    cover_image_url VARCHAR(500),
    documents JSON, -- ["certificate.pdf", "financial_report.pdf"]
    
    -- Status and Settings
    is_active BOOLEAN DEFAULT TRUE,
    accepts_donations BOOLEAN DEFAULT TRUE,
    featured BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_status_active (verification_status, is_active),
    INDEX idx_env_focus (environmental_focus(100)),
    INDEX idx_transparency (transparency_score DESC),
    INDEX idx_featured (featured, verification_status)
) ENGINE=InnoDB;

-- ========================================
-- DONATION CAMPAIGNS TABLE
-- Các chiến dịch quyên góp cụ thể
-- ========================================

CREATE TABLE donation_campaigns (
    campaign_id INT PRIMARY KEY AUTO_INCREMENT,
    organization_id INT NOT NULL,
    
    -- Campaign Basic Info
    campaign_name VARCHAR(255) NOT NULL,
    campaign_slug VARCHAR(255) UNIQUE NOT NULL,
    campaign_type ENUM('one_time', 'recurring', 'emergency', 'project_based') DEFAULT 'one_time',
    
    -- Campaign Details
    description LONGTEXT NOT NULL,
    short_description TEXT,
    story TEXT, -- Compelling story for the cause
    
    -- Financial Goals
    target_amount DECIMAL(15,2) NOT NULL,
    current_amount DECIMAL(15,2) DEFAULT 0,
    minimum_donation DECIMAL(10,2) DEFAULT 10000, -- VND
    suggested_amounts JSON, -- [50000, 100000, 250000, 500000, 1000000]
    
    -- Environmental Impact
    environmental_category ENUM('reforestation', 'ocean_cleanup', 'renewable_energy', 'wildlife_protection', 'pollution_control', 'education', 'research') NOT NULL,
    expected_impact TEXT, -- Description of expected environmental impact
    impact_metrics JSON, -- {"trees_planted": 1000, "co2_reduced_tons": 50}
    
    -- Campaign Timeline
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    is_time_limited BOOLEAN DEFAULT TRUE,
    
    -- Media and Content
    featured_image_url VARCHAR(500),
    gallery_images JSON,
    video_url VARCHAR(500),
    
    -- Campaign Settings
    status ENUM('draft', 'active', 'paused', 'completed', 'cancelled') DEFAULT 'draft',
    featured BOOLEAN DEFAULT FALSE,
    urgent BOOLEAN DEFAULT FALSE,
    allow_anonymous_donations BOOLEAN DEFAULT TRUE,
    
    -- Transparency and Reporting
    progress_updates JSON, -- Array of progress update objects
    expense_breakdown JSON, -- How the money will be/was spent
    impact_reports JSON, -- Environmental impact achieved
    
    -- Engagement Metrics
    view_count INT DEFAULT 0,
    share_count INT DEFAULT 0,
    donor_count INT DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (organization_id) REFERENCES donation_organizations(organization_id) ON DELETE CASCADE,
    INDEX idx_org_status (organization_id, status),
    INDEX idx_category_active (environmental_category, status),
    INDEX idx_featured_urgent (featured, urgent, status),
    INDEX idx_end_date (end_date),
    INDEX idx_target_progress (target_amount, current_amount)
) ENGINE=InnoDB;

-- ========================================
-- DONATIONS TABLE  
-- Các khoản quyên góp thực tế
-- ========================================

CREATE TABLE donations (
    donation_id INT PRIMARY KEY AUTO_INCREMENT,
    
    -- Donation Basic Info
    campaign_id INT NOT NULL,
    donor_user_id INT DEFAULT NULL, -- NULL for anonymous donations
    donation_code VARCHAR(50) UNIQUE NOT NULL, -- Unique tracking code
    
    -- Donation Amount and Type
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) DEFAULT 'VND',
    donation_type ENUM('one_time', 'monthly', 'yearly') DEFAULT 'one_time',
    
    -- Donor Information (for anonymous donations)
    donor_name VARCHAR(255) DEFAULT NULL,
    donor_email VARCHAR(255) DEFAULT NULL,
    donor_phone VARCHAR(20) DEFAULT NULL,
    is_anonymous BOOLEAN DEFAULT FALSE,
    
    -- Payment Information
    payment_method ENUM('bank_transfer', 'credit_card', 'e_wallet', 'cash', 'crypto') NOT NULL,
    payment_gateway VARCHAR(50), -- 'vnpay', 'momo', 'zalopay', etc.
    payment_transaction_id VARCHAR(255),
    payment_reference VARCHAR(255),
    
    -- Payment Status and Tracking
    payment_status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_date TIMESTAMP NULL,
    payment_confirmation_date TIMESTAMP NULL,
    
    -- Recurring Donation Settings
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_frequency ENUM('monthly', 'quarterly', 'yearly') DEFAULT NULL,
    next_payment_date DATE DEFAULT NULL,
    recurring_end_date DATE DEFAULT NULL,
    parent_donation_id INT DEFAULT NULL, -- For recurring payments
    
    -- Corporate Matching and Special Programs
    is_corporate_donation BOOLEAN DEFAULT FALSE,
    corporate_matching_eligible BOOLEAN DEFAULT FALSE,
    matched_amount DECIMAL(12,2) DEFAULT 0,
    employer_name VARCHAR(255) DEFAULT NULL,
    
    -- Dedication and Messages
    dedication_type ENUM('none', 'in_honor', 'in_memory') DEFAULT 'none',
    dedication_name VARCHAR(255) DEFAULT NULL,
    donation_message TEXT,
    public_comment TEXT, -- Public message to show on campaign page
    
    -- Tax and Receipts
    tax_deductible BOOLEAN DEFAULT TRUE,
    receipt_requested BOOLEAN DEFAULT TRUE,
    receipt_sent BOOLEAN DEFAULT FALSE,
    receipt_sent_date TIMESTAMP NULL,
    
    -- Environmental Impact Tracking
    eco_points_earned INT DEFAULT 0,
    estimated_carbon_offset DECIMAL(10,3) DEFAULT 0, -- kg CO2
    
    -- Engagement and Communication
    donor_opted_for_updates BOOLEAN DEFAULT TRUE,
    thank_you_sent BOOLEAN DEFAULT FALSE,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES donation_campaigns(campaign_id) ON DELETE CASCADE,
    FOREIGN KEY (donor_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (parent_donation_id) REFERENCES donations(donation_id) ON DELETE SET NULL,
    
    INDEX idx_campaign_status (campaign_id, payment_status),
    INDEX idx_donor_user (donor_user_id, payment_status),
    INDEX idx_payment_date (payment_date DESC),
    INDEX idx_recurring (is_recurring, next_payment_date),
    INDEX idx_corporate (is_corporate_donation, payment_status),
    INDEX idx_donation_code (donation_code),
    INDEX idx_amount_date (amount DESC, created_at DESC)
) ENGINE=InnoDB;

-- ========================================
-- DONATION IMPACT REPORTS TABLE
-- Báo cáo tác động từ các khoản quyên góp
-- ========================================

CREATE TABLE donation_impact_reports (
    report_id INT PRIMARY KEY AUTO_INCREMENT,
    campaign_id INT NOT NULL,
    
    -- Report Information
    report_title VARCHAR(255) NOT NULL,
    report_period_start DATE NOT NULL,
    report_period_end DATE NOT NULL,
    report_type ENUM('quarterly', 'annual', 'project_completion', 'milestone') DEFAULT 'quarterly',
    
    -- Financial Summary
    total_donations_period DECIMAL(15,2) NOT NULL,
    total_expenses_period DECIMAL(15,2) NOT NULL,
    administrative_costs DECIMAL(15,2) DEFAULT 0,
    program_costs DECIMAL(15,2) NOT NULL,
    
    -- Environmental Impact Achieved
    impact_metrics_achieved JSON, -- {"trees_planted": 1500, "co2_reduced_tons": 75}
    environmental_outcomes TEXT,
    beneficiaries_reached INT DEFAULT 0,
    
    -- Detailed Breakdown
    expense_breakdown JSON, -- Detailed spending categories
    activities_completed JSON, -- List of completed activities
    challenges_faced TEXT,
    lessons_learned TEXT,
    
    -- Media and Evidence
    photos JSON, -- Before/after photos, project photos
    videos JSON,
    testimonials JSON, -- Beneficiary testimonials
    third_party_verification JSON, -- External audit results
    
    -- Future Plans
    next_steps TEXT,
    future_funding_needs DECIMAL(15,2) DEFAULT 0,
    
    -- Report Status
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_date TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES donation_campaigns(campaign_id) ON DELETE CASCADE,
    INDEX idx_campaign_period (campaign_id, report_period_end DESC),
    INDEX idx_status_published (status, published_date DESC)
) ENGINE=InnoDB;

-- ========================================
-- DONATION SUBSCRIPTIONS TABLE
-- Quản lý quyên góp định kỳ
-- ========================================

CREATE TABLE donation_subscriptions (
    subscription_id INT PRIMARY KEY AUTO_INCREMENT,
    donor_user_id INT NOT NULL,
    campaign_id INT NOT NULL,
    
    -- Subscription Details
    amount DECIMAL(12,2) NOT NULL,
    frequency ENUM('monthly', 'quarterly', 'yearly') NOT NULL,
    
    -- Subscription Status
    status ENUM('active', 'paused', 'cancelled', 'failed') DEFAULT 'active',
    
    -- Payment Information
    payment_method ENUM('bank_transfer', 'credit_card', 'e_wallet') NOT NULL,
    payment_token VARCHAR(255), -- Stored payment method token
    
    -- Scheduling
    start_date DATE NOT NULL,
    next_payment_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    last_payment_date DATE DEFAULT NULL,
    
    -- Statistics
    total_payments_made INT DEFAULT 0,
    total_amount_donated DECIMAL(15,2) DEFAULT 0,
    failed_payment_count INT DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (donor_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES donation_campaigns(campaign_id) ON DELETE CASCADE,
    
    INDEX idx_user_status (donor_user_id, status),
    INDEX idx_next_payment (next_payment_date, status),
    INDEX idx_campaign_active (campaign_id, status)
) ENGINE=InnoDB;

-- ========================================
-- SAMPLE DATA INSERTION
-- ========================================

-- Insert sample organizations
INSERT INTO donation_organizations (
    organization_name, organization_slug, organization_type, contact_email, 
    description, environmental_focus, verification_status, is_active
) VALUES 
(
    'Quỹ Bảo vệ Môi trường Việt Nam', 
    'quy-bao-ve-moi-truong-vn', 
    'ngo', 
    'contact@greenfund.vn',
    'Tổ chức phi lợi nhuận hoạt động vì môi trường xanh, sạch, đẹp cho Việt Nam',
    '["climate_change", "biodiversity", "pollution"]',
    'verified',
    TRUE
),
(
    'Hội Bảo vệ Rừng và Động vật Hoang dã', 
    'bao-ve-rung-dong-vat', 
    'charity', 
    'info@wildlifevn.org',
    'Bảo vệ và phục hồi hệ sinh thái rừng, bảo tồn động vật hoang dã',
    '["biodiversity", "wildlife_protection"]',
    'verified',
    TRUE
),
(
    'Nhóm Tình nguyện Làm sạch Biển', 
    'lam-sach-bien', 
    'community_group', 
    'volunteer@cleansea.vn',
    'Nhóm tình nguyện viên trẻ cam kết làm sạch biển và bảo vệ sinh vật biển',
    '["ocean_cleanup", "pollution"]',
    'verified',
    TRUE
);

-- Insert sample campaigns  
INSERT INTO donation_campaigns (
    organization_id, campaign_name, campaign_slug, campaign_type,
    description, target_amount, environmental_category, status
) VALUES 
(
    1, 
    'Trồng 10,000 cây xanh cho thành phố', 
    'trong-10000-cay-xanh', 
    'project_based',
    'Chiến dịch trồng 10,000 cây xanh tại các khu vực đô thị để cải thiện chất lượng không khí và giảm nhiệt độ.',
    500000000, -- 500 million VND
    'reforestation',
    'active'
),
(
    2,
    'Cứu hộ và bảo tồn voọc chà vá chân nâu',
    'cuu-ho-vooc-cha-va-chan-nau',
    'recurring',
    'Chương trình bảo tồn loài voọc chà vá chân nâu quý hiếm đang có nguy cơ tuyệt chủng.',
    300000000, -- 300 million VND
    'wildlife_protection', 
    'active'
),
(
    3,
    'Làm sạch 100km bờ biển Việt Nam',
    'lam-sach-100km-bo-bien',
    'one_time',
    'Tổ chức các hoạt động dọn dẹp rác thải nhựa và ô nhiễm tại 100km bờ biển.',
    200000000, -- 200 million VND
    'ocean_cleanup',
    'active'
);

-- Insert sample donations
INSERT INTO donations (
    campaign_id, donor_user_id, donation_code, amount, payment_method,
    payment_status, is_anonymous, eco_points_earned
) VALUES 
(1, 1, 'DON001', 1000000, 'bank_transfer', 'completed', FALSE, 100),
(1, 2, 'DON002', 500000, 'e_wallet', 'completed', FALSE, 50),
(2, 1, 'DON003', 2000000, 'credit_card', 'completed', FALSE, 200),
(3, 3, 'DON004', 300000, 'bank_transfer', 'completed', TRUE, 30),
(1, NULL, 'DON005', 750000, 'cash', 'completed', TRUE, 75);

-- Update campaign amounts based on donations
UPDATE donation_campaigns dc 
SET current_amount = (
    SELECT COALESCE(SUM(d.amount), 0) 
    FROM donations d 
    WHERE d.campaign_id = dc.campaign_id 
    AND d.payment_status = 'completed'
);

-- Update organization totals
UPDATE donation_organizations do
SET total_donations_received = (
    SELECT COALESCE(SUM(d.amount), 0)
    FROM donations d 
    JOIN donation_campaigns dc ON d.campaign_id = dc.campaign_id
    WHERE dc.organization_id = do.organization_id
    AND d.payment_status = 'completed'
);

COMMIT;
