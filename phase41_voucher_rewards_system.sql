-- ========================================
-- PHASE 41: VOUCHER & REWARDS MANAGEMENT SYSTEM
-- Environmental Platform Database Enhancement
-- Date: June 5, 2025
-- ========================================

USE environmental_platform;

-- ========================================
-- 1. VOUCHER CAMPAIGNS TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS voucher_campaigns (
    campaign_id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_name VARCHAR(255) NOT NULL,
    campaign_description TEXT,
    campaign_type ENUM('eco_action', 'milestone', 'seasonal', 'partner', 'referral', 'quiz_completion') NOT NULL DEFAULT 'eco_action',
    
    -- Campaign Rules
    trigger_conditions JSON, -- Rules that trigger voucher generation
    reward_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    reward_type ENUM('percentage', 'fixed_amount', 'points', 'free_shipping') NOT NULL DEFAULT 'fixed_amount',
    
    -- Validity Settings
    validity_days INT NOT NULL DEFAULT 30,
    usage_limit INT DEFAULT NULL, -- NULL = unlimited
    user_usage_limit INT DEFAULT 1, -- How many times per user
    
    -- Campaign Status
    campaign_status ENUM('draft', 'active', 'paused', 'expired', 'completed') NOT NULL DEFAULT 'draft',
    start_date DATETIME NOT NULL,
    end_date DATETIME,
    
    -- Targeting
    target_user_levels JSON, -- Which user levels can receive this
    target_regions JSON, -- Geographic targeting
    minimum_eco_points INT DEFAULT 0,
    
    -- Analytics
    total_vouchers_generated INT DEFAULT 0,
    total_vouchers_used INT DEFAULT 0,
    total_savings_provided DECIMAL(12,2) DEFAULT 0.00,
    
    -- Audit
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_campaign_status (campaign_status),
    INDEX idx_campaign_dates (start_date, end_date),
    INDEX idx_campaign_type (campaign_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 2. VOUCHERS TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS vouchers (
    voucher_id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_code VARCHAR(50) UNIQUE NOT NULL,
    campaign_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Voucher Details
    voucher_name VARCHAR(255) NOT NULL,
    voucher_description TEXT,
    voucher_value DECIMAL(10,2) NOT NULL,
    voucher_type ENUM('percentage', 'fixed_amount', 'points', 'free_shipping', 'product_specific') NOT NULL,
    
    -- Usage Rules
    minimum_purchase_amount DECIMAL(10,2) DEFAULT 0.00,
    maximum_discount_amount DECIMAL(10,2) DEFAULT NULL,
    applicable_products JSON, -- Product IDs or categories
    applicable_categories JSON,
    
    -- Validity
    issue_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date DATETIME NOT NULL,
    
    -- Status & Usage
    voucher_status ENUM('active', 'used', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
    used_date DATETIME NULL,
    used_order_id INT NULL, -- WooCommerce order ID
    used_amount DECIMAL(10,2) DEFAULT 0.00,
    
    -- Environmental Impact (what action earned this voucher)
    earning_action VARCHAR(100), -- 'quiz_completed', 'waste_classified', 'carbon_reduced', etc.
    earning_details JSON, -- Additional context about the earning action
    environmental_impact JSON, -- CO2 saved, waste reduced, etc.
    
    -- QR Code & Security
    qr_code_data TEXT, -- Base64 encoded QR code
    security_hash VARCHAR(255), -- For verification
    
    -- Audit
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES voucher_campaigns(campaign_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_voucher_user (user_id),
    INDEX idx_voucher_status (voucher_status),
    INDEX idx_voucher_expiry (expiry_date),
    INDEX idx_voucher_code (voucher_code),
    INDEX idx_earning_action (earning_action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 3. VOUCHER USAGE TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS voucher_usage (
    usage_id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Usage Details
    usage_context ENUM('woocommerce', 'partner_store', 'direct_redemption', 'point_conversion') NOT NULL,
    order_id INT NULL, -- WooCommerce order ID
    partner_transaction_id VARCHAR(100) NULL,
    
    -- Financial Details
    original_amount DECIMAL(10,2) NOT NULL,
    discount_applied DECIMAL(10,2) NOT NULL,
    final_amount DECIMAL(10,2) NOT NULL,
    
    -- Environmental Impact of Purchase
    purchase_environmental_score DECIMAL(5,2) DEFAULT 0.00,
    eco_points_earned INT DEFAULT 0,
    carbon_footprint_reduced DECIMAL(8,3) DEFAULT 0.000, -- kg CO2
    
    -- Verification
    verification_status ENUM('pending', 'verified', 'disputed', 'refunded') NOT NULL DEFAULT 'verified',
    verification_notes TEXT,
    
    -- Analytics Data
    device_type VARCHAR(50),
    user_location JSON,
    referral_source VARCHAR(100),
    
    -- Timestamps
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at DATETIME NULL,
    
    FOREIGN KEY (voucher_id) REFERENCES vouchers(voucher_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_usage_voucher (voucher_id),
    INDEX idx_usage_user (user_id),
    INDEX idx_usage_context (usage_context),
    INDEX idx_usage_date (used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 4. REWARD PROGRAMS TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS reward_programs (
    program_id INT AUTO_INCREMENT PRIMARY KEY,
    program_name VARCHAR(255) NOT NULL,
    program_description TEXT,
    program_type ENUM('loyalty', 'referral', 'milestone', 'seasonal', 'partner') NOT NULL,
    
    -- Program Configuration
    program_rules JSON NOT NULL, -- Detailed rules and conditions
    reward_structure JSON NOT NULL, -- How rewards are calculated
    tier_system JSON, -- Bronze, Silver, Gold, Platinum levels
    
    -- Eligibility
    minimum_eco_points INT DEFAULT 0,
    minimum_user_level INT DEFAULT 1,
    required_actions JSON, -- Actions required to participate
    
    -- Duration
    program_status ENUM('draft', 'active', 'paused', 'completed') NOT NULL DEFAULT 'draft',
    start_date DATETIME NOT NULL,
    end_date DATETIME NULL,
    
    -- Analytics
    total_participants INT DEFAULT 0,
    total_rewards_distributed DECIMAL(12,2) DEFAULT 0.00,
    program_roi DECIMAL(8,4) DEFAULT 0.0000,
    
    -- Environmental Impact
    total_co2_saved DECIMAL(10,3) DEFAULT 0.000, -- kg CO2
    total_waste_reduced DECIMAL(10,2) DEFAULT 0.00, -- kg
    total_trees_equivalent INT DEFAULT 0,
    
    -- Audit
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_program_status (program_status),
    INDEX idx_program_type (program_type),
    INDEX idx_program_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 5. USER REWARDS TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS user_rewards (
    reward_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    program_id INT NOT NULL,
    
    -- Reward Details
    reward_name VARCHAR(255) NOT NULL,
    reward_description TEXT,
    reward_type ENUM('voucher', 'points', 'badge', 'physical_item', 'experience', 'discount') NOT NULL,
    reward_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    
    -- Earning Details
    earning_action VARCHAR(100) NOT NULL, -- What action earned this reward
    earning_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    earning_details JSON, -- Context about the earning action
    
    -- Reward Status
    reward_status ENUM('earned', 'claimed', 'delivered', 'expired', 'cancelled') NOT NULL DEFAULT 'earned',
    claim_date DATETIME NULL,
    delivery_date DATETIME NULL,
    expiry_date DATETIME NULL,
    
    -- Environmental Context
    environmental_impact JSON, -- Impact of the action that earned this reward
    sustainability_score DECIMAL(5,2) DEFAULT 0.00,
    
    -- Delivery Information (for physical rewards)
    delivery_address JSON NULL,
    delivery_status VARCHAR(50) NULL,
    tracking_number VARCHAR(100) NULL,
    
    -- Verification
    verification_required BOOLEAN DEFAULT FALSE,
    verification_status ENUM('pending', 'verified', 'rejected') NULL,
    verification_notes TEXT,
    
    -- Audit
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES reward_programs(program_id) ON DELETE CASCADE,
    INDEX idx_user_rewards (user_id),
    INDEX idx_reward_status (reward_status),
    INDEX idx_earning_date (earning_date),
    INDEX idx_earning_action (earning_action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 6. PARTNER DISCOUNTS TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS partner_discounts (
    partner_id INT AUTO_INCREMENT PRIMARY KEY,
    partner_name VARCHAR(255) NOT NULL,
    partner_description TEXT,
    partner_type ENUM('eco_store', 'restaurant', 'service', 'online_shop', 'local_business') NOT NULL,
    
    -- Contact Information
    contact_email VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(50),
    website_url VARCHAR(500),
    
    -- Business Details
    business_address JSON,
    business_hours JSON,
    sustainability_certifications JSON,
    
    -- Discount Configuration
    discount_type ENUM('percentage', 'fixed_amount', 'buy_one_get_one', 'free_shipping') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    minimum_purchase DECIMAL(10,2) DEFAULT 0.00,
    maximum_discount DECIMAL(10,2) NULL,
    
    -- Eligibility & Rules
    required_eco_points INT DEFAULT 0,
    required_user_level INT DEFAULT 1,
    usage_limits JSON, -- Per user, per day, per month limits
    
    -- Partnership Terms
    commission_rate DECIMAL(5,4) DEFAULT 0.0000, -- Platform commission percentage
    payment_terms VARCHAR(100) DEFAULT 'net_30',
    contract_start_date DATE NOT NULL,
    contract_end_date DATE NULL,
    
    -- Integration
    api_endpoint VARCHAR(500) NULL,
    api_key_hash VARCHAR(255) NULL,
    webhook_url VARCHAR(500) NULL,
    integration_type ENUM('manual', 'api', 'webhook') DEFAULT 'manual',
    
    -- Analytics
    total_redemptions INT DEFAULT 0,
    total_revenue_generated DECIMAL(12,2) DEFAULT 0.00,
    total_commission_earned DECIMAL(12,2) DEFAULT 0.00,
    average_order_value DECIMAL(10,2) DEFAULT 0.00,
    
    -- Environmental Impact
    estimated_co2_savings DECIMAL(10,3) DEFAULT 0.000,
    sustainability_rating DECIMAL(3,2) DEFAULT 0.00, -- 0-10 scale
    
    -- Status
    partner_status ENUM('pending', 'active', 'paused', 'suspended', 'terminated') NOT NULL DEFAULT 'pending',
    verification_status ENUM('unverified', 'verified', 'rejected') DEFAULT 'unverified',
    
    -- Audit
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_partner_status (partner_status),
    INDEX idx_partner_type (partner_type),
    INDEX idx_sustainability_rating (sustainability_rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 7. REWARD TRANSACTIONS TABLE
-- ========================================
CREATE TABLE IF NOT EXISTS reward_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_type ENUM('voucher_earned', 'voucher_used', 'points_earned', 'points_spent', 'reward_claimed', 'commission_paid') NOT NULL,
    
    -- Transaction Details
    related_id INT NULL, -- ID of related voucher, reward, etc.
    related_type VARCHAR(50) NULL, -- 'voucher', 'reward', 'campaign', etc.
    
    -- Financial Information
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'USD',
    points_involved INT DEFAULT 0,
    
    -- Transaction Context
    trigger_action VARCHAR(100), -- What action caused this transaction
    action_details JSON,
    environmental_context JSON,
    
    -- Partner Information (if applicable)
    partner_id INT NULL,
    partner_transaction_id VARCHAR(100) NULL,
    commission_amount DECIMAL(10,2) DEFAULT 0.00,
    
    -- Verification & Status
    transaction_status ENUM('pending', 'completed', 'failed', 'cancelled', 'disputed') NOT NULL DEFAULT 'pending',
    verification_hash VARCHAR(255),
    processing_notes TEXT,
    
    -- Timestamps
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_date DATETIME NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (partner_id) REFERENCES partner_discounts(partner_id) ON DELETE SET NULL,
    INDEX idx_transaction_user (user_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_transaction_status (transaction_status),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_partner_transactions (partner_id, transaction_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SAMPLE DATA INSERTION
-- ========================================

-- Insert Sample Voucher Campaigns
INSERT INTO voucher_campaigns (
    campaign_name, campaign_description, campaign_type, trigger_conditions, 
    reward_amount, reward_type, validity_days, start_date, created_by
) VALUES 
(
    'Quiz Master Rewards', 
    'Earn vouchers by completing environmental knowledge quizzes',
    'quiz_completion',
    '{"quiz_score_min": 80, "quiz_category": "all", "frequency": "daily"}',
    5.00, 'fixed_amount', 30, NOW(), 1
),
(
    'Waste Warrior Discount', 
    'Rewards for accurate waste classification submissions',
    'eco_action',
    '{"action": "waste_classification", "accuracy_min": 90, "submissions_min": 10}',
    15.00, 'percentage', 45, NOW(), 1
),
(
    'Carbon Saver Special', 
    'Special vouchers for significant carbon footprint reduction',
    'milestone',
    '{"carbon_saved_kg": 50, "timeframe_days": 30}',
    25.00, 'fixed_amount', 60, NOW(), 1
);

-- Insert Sample Reward Programs
INSERT INTO reward_programs (
    program_name, program_description, program_type, program_rules, 
    reward_structure, tier_system, start_date, created_by
) VALUES 
(
    'Eco Champions Loyalty Program',
    'Progressive rewards for consistent environmental actions',
    'loyalty',
    '{"point_multiplier": 1.5, "bonus_actions": ["daily_quiz", "waste_classification"]}',
    '{"bronze": {"min_points": 0, "bonus": 0.1}, "silver": {"min_points": 500, "bonus": 0.2}, "gold": {"min_points": 1500, "bonus": 0.3}}',
    '{"bronze": "0-499", "silver": "500-1499", "gold": "1500+"}',
    NOW(), 1
),
(
    'Green Referral Network',
    'Earn rewards for bringing eco-conscious friends to the platform',
    'referral',
    '{"referral_bonus": 100, "referee_bonus": 50, "completion_actions": ["profile_complete", "first_quiz"]}',
    '{"immediate": 50, "milestone_bonus": 100, "recurring": 10}',
    '{"starter": "1-5 referrals", "ambassador": "6-20 referrals", "champion": "21+ referrals"}',
    NOW(), 1
);

-- Insert Sample Partner Discounts
INSERT INTO partner_discounts (
    partner_name, partner_description, partner_type, contact_email,
    discount_type, discount_value, minimum_purchase, required_eco_points,
    contract_start_date, created_by
) VALUES 
(
    'GreenLife Organic Store',
    'Premium organic products with zero-waste packaging',
    'eco_store',
    'partnership@greenlife.eco',
    'percentage', 20.00, 25.00, 100,
    CURDATE(), 1
),
(
    'EcoRide Bike Sharing',
    'Sustainable transportation solutions for urban areas',
    'service',
    'rewards@ecoride.com',
    'fixed_amount', 5.00, 0.00, 50,
    CURDATE(), 1
),
(
    'PlantBased Café',
    'Locally sourced, plant-based meals with compostable packaging',
    'restaurant',
    'hello@plantbasedcafe.com',
    'percentage', 15.00, 15.00, 75,
    CURDATE(), 1
);

-- ========================================
-- CREATE VIEWS FOR ANALYTICS
-- ========================================

-- Voucher Campaign Performance View
CREATE OR REPLACE VIEW voucher_campaign_analytics AS
SELECT 
    vc.campaign_id,
    vc.campaign_name,
    vc.campaign_type,
    vc.total_vouchers_generated,
    vc.total_vouchers_used,
    ROUND((vc.total_vouchers_used / NULLIF(vc.total_vouchers_generated, 0)) * 100, 2) as usage_rate_percent,
    vc.total_savings_provided,
    COUNT(v.voucher_id) as active_vouchers,
    SUM(CASE WHEN v.voucher_status = 'used' THEN v.used_amount ELSE 0 END) as total_redeemed_value,
    AVG(CASE WHEN v.voucher_status = 'used' THEN v.used_amount ELSE NULL END) as avg_redemption_value,
    vc.start_date,
    vc.end_date,
    vc.campaign_status
FROM voucher_campaigns vc
LEFT JOIN vouchers v ON vc.campaign_id = v.campaign_id
GROUP BY vc.campaign_id;

-- User Reward Summary View
CREATE OR REPLACE VIEW user_reward_summary AS
SELECT 
    u.user_id,
    u.username,
    u.green_points,
    COUNT(ur.reward_id) as total_rewards_earned,
    SUM(ur.reward_value) as total_reward_value,
    COUNT(CASE WHEN ur.reward_status = 'claimed' THEN 1 END) as rewards_claimed,
    COUNT(v.voucher_id) as total_vouchers,
    COUNT(CASE WHEN v.voucher_status = 'used' THEN 1 END) as vouchers_used,
    SUM(CASE WHEN v.voucher_status = 'used' THEN v.used_amount ELSE 0 END) as total_savings,
    MAX(ur.earning_date) as last_reward_date
FROM users u
LEFT JOIN user_rewards ur ON u.user_id = ur.user_id
LEFT JOIN vouchers v ON u.user_id = v.user_id
GROUP BY u.user_id;

-- Partner Performance View
CREATE OR REPLACE VIEW partner_performance AS
SELECT 
    pd.partner_id,
    pd.partner_name,
    pd.partner_type,
    pd.total_redemptions,
    pd.total_revenue_generated,
    pd.total_commission_earned,
    pd.average_order_value,
    pd.sustainability_rating,
    COUNT(rt.transaction_id) as recent_transactions,
    SUM(rt.amount) as recent_revenue
FROM partner_discounts pd
LEFT JOIN reward_transactions rt ON pd.partner_id = rt.partner_id 
    AND rt.transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY pd.partner_id;

-- ========================================
-- UPDATE EXISTING TABLES (Add voucher integration)
-- ========================================

-- Add voucher tracking to user activities (if table exists)
-- This will be handled in the WordPress plugin integration

-- ========================================
-- VERIFICATION QUERIES
-- ========================================

-- Verify all tables were created
SELECT 
    'Phase 41 Table Creation' as verification_step,
    'voucher_campaigns' as table_name,
    CASE WHEN COUNT(*) > 0 THEN 'EXISTS' ELSE 'MISSING' END as status
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform' AND table_name = 'voucher_campaigns'

UNION ALL

SELECT 
    'Phase 41 Table Creation' as verification_step,
    'vouchers' as table_name,
    CASE WHEN COUNT(*) > 0 THEN 'EXISTS' ELSE 'MISSING' END as status
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform' AND table_name = 'vouchers'

UNION ALL

SELECT 
    'Phase 41 Table Creation' as verification_step,
    'voucher_usage' as table_name,
    CASE WHEN COUNT(*) > 0 THEN 'EXISTS' ELSE 'MISSING' END as status
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform' AND table_name = 'voucher_usage'

UNION ALL

SELECT 
    'Phase 41 Table Creation' as verification_step,
    'reward_programs' as table_name,
    CASE WHEN COUNT(*) > 0 THEN 'EXISTS' ELSE 'MISSING' END as status
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform' AND table_name = 'reward_programs'

UNION ALL

SELECT 
    'Phase 41 Table Creation' as verification_step,
    'user_rewards' as table_name,
    CASE WHEN COUNT(*) > 0 THEN 'EXISTS' ELSE 'MISSING' END as status
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform' AND table_name = 'user_rewards'

UNION ALL

SELECT 
    'Phase 41 Table Creation' as verification_step,
    'partner_discounts' as table_name,
    CASE WHEN COUNT(*) > 0 THEN 'EXISTS' ELSE 'MISSING' END as status
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform' AND table_name = 'partner_discounts'

UNION ALL

SELECT 
    'Phase 41 Table Creation' as verification_step,
    'reward_transactions' as table_name,
    CASE WHEN COUNT(*) > 0 THEN 'EXISTS' ELSE 'MISSING' END as status
FROM information_schema.tables 
WHERE table_schema = 'environmental_platform' AND table_name = 'reward_transactions';

-- Sample data verification
SELECT 'Sample Data Check' as verification_step, COUNT(*) as campaign_count FROM voucher_campaigns;
SELECT 'Sample Data Check' as verification_step, COUNT(*) as program_count FROM reward_programs;
SELECT 'Sample Data Check' as verification_step, COUNT(*) as partner_count FROM partner_discounts;

-- ========================================
-- COMPLETION SUMMARY
-- ========================================
SELECT 
    'PHASE 41: VOUCHER & REWARDS MANAGEMENT' as phase_name,
    '7 new tables created' as implementation,
    'Sample data inserted' as data_status,
    'Analytics views created' as views_status,
    'COMPLETED SUCCESSFULLY' as status,
    NOW() as completion_time;
