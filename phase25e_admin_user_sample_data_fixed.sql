-- ========================================
-- Phase 25E: Admin User & Sample Data (FIXED)
-- Environmental Platform Database
-- Date: June 4, 2025
-- ========================================

USE environmental_platform;

-- Set proper character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ========================================
-- 1. CREATE ADMIN USER ACCOUNT
-- ========================================

-- Check if admin user already exists
SET @admin_exists = (SELECT COUNT(*) FROM users WHERE username = 'admin');

-- Only insert if admin doesn't exist
INSERT INTO users (
    username,
    email,
    password_hash,
    first_name,
    last_name,
    user_type,
    is_verified,
    green_points,
    experience_points,
    user_level,
    is_active,
    total_carbon_saved
)
SELECT 
    'admin',
    'admin@environmental-platform.vn',
    -- Using bcrypt hash for password 'Admin@2025'
    '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey',
    'System',
    'Administrator',
    'admin',
    TRUE,
    10000,
    2000,
    10,
    TRUE,
    100.00
WHERE @admin_exists = 0;

-- ========================================
-- 2. ROLE-BASED ACCESS CONTROL SYSTEM
-- ========================================

-- Create user roles table if not exists
CREATE TABLE IF NOT EXISTS user_roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_description TEXT,
    permission_level INT DEFAULT 10,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create user permissions table if not exists
CREATE TABLE IF NOT EXISTS user_permissions (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    permission_code VARCHAR(50) NOT NULL UNIQUE,
    permission_name VARCHAR(100) NOT NULL,
    permission_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create user role permissions mapping table if not exists
CREATE TABLE IF NOT EXISTS user_role_permissions (
    role_id INT,
    permission_id INT,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES user_roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES user_permissions(permission_id) ON DELETE CASCADE
);

-- Create user role assignments table if not exists
CREATE TABLE IF NOT EXISTS user_role_assignments (
    user_id INT,
    role_id INT,
    assigned_by INT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES user_roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Insert standard roles
INSERT IGNORE INTO user_roles (role_name, role_description, permission_level)
VALUES
('Administrator', 'Full system access with all permissions', 100),
('Moderator', 'Content moderation and user management', 50),
('Content Creator', 'Can create and manage content', 30),
('Business Partner', 'Business accounts with enhanced access', 20),
('Organization', 'Organization accounts with specific permissions', 15),
('Regular User', 'Standard user account with basic permissions', 10);

-- Insert standard permissions
INSERT IGNORE INTO user_permissions (permission_code, permission_name, permission_description)
VALUES
-- Admin permissions
('manage_users', 'Manage Users', 'Create, edit, and delete user accounts'),
('manage_roles', 'Manage Roles', 'Create, edit, and delete roles and permissions'),
('manage_settings', 'Manage Settings', 'Modify system settings and configurations'),
('view_analytics', 'View Analytics', 'Access system analytics and reports'),

-- Moderator permissions
('moderate_content', 'Moderate Content', 'Review and moderate user-generated content'),
('manage_posts', 'Manage Posts', 'Edit or delete posts from any user'),
('manage_comments', 'Manage Comments', 'Edit or delete comments from any user'),
('verify_users', 'Verify Users', 'Verify user accounts and credentials'),
('ban_users', 'Ban Users', 'Temporarily or permanently ban users'),

-- Content creator permissions
('create_articles', 'Create Articles', 'Create and publish articles'),
('create_events', 'Create Events', 'Create and manage environmental events'),
('create_campaigns', 'Create Campaigns', 'Create and manage environmental campaigns'),
('upload_media', 'Upload Media', 'Upload images, videos, and other media'),
('feature_content', 'Feature Content', 'Mark content as featured'),

-- Regular user permissions
('post_comments', 'Post Comments', 'Comment on posts and articles'),
('view_env_data', 'View Environmental Data', 'Access environmental data and statistics'),
('submit_waste_data', 'Submit Waste Data', 'Submit personal waste management data');

-- Assign permissions to roles
-- Admin gets all permissions
INSERT IGNORE INTO user_role_permissions (role_id, permission_id)
SELECT 
    (SELECT role_id FROM user_roles WHERE role_name = 'Administrator'),
    permission_id
FROM user_permissions;

-- Moderator permissions
INSERT IGNORE INTO user_role_permissions (role_id, permission_id)
SELECT 
    (SELECT role_id FROM user_roles WHERE role_name = 'Moderator'),
    permission_id
FROM user_permissions
WHERE permission_code IN (
    'moderate_content', 'manage_posts', 'manage_comments',
    'verify_users', 'ban_users', 'view_analytics',
    'post_comments', 'view_env_data', 'submit_waste_data'
);

-- Content Creator permissions
INSERT IGNORE INTO user_role_permissions (role_id, permission_id)
SELECT 
    (SELECT role_id FROM user_roles WHERE role_name = 'Content Creator'),
    permission_id
FROM user_permissions
WHERE permission_code IN (
    'create_articles', 'create_events', 'create_campaigns', 
    'upload_media', 'feature_content'
);

-- Regular User permissions
INSERT IGNORE INTO user_role_permissions (role_id, permission_id)
SELECT 
    (SELECT role_id FROM user_roles WHERE role_name = 'Regular User'),
    permission_id
FROM user_permissions
WHERE permission_code IN (
    'post_comments', 'view_env_data', 'submit_waste_data'
);

-- Assign Admin role to admin user
INSERT IGNORE INTO user_role_assignments (user_id, role_id)
SELECT 
    (SELECT user_id FROM users WHERE username = 'admin'),
    (SELECT role_id FROM user_roles WHERE role_name = 'Administrator');

-- ========================================
-- 3. INSERT SAMPLE DATA
-- ========================================

-- Create sample users with different roles
INSERT IGNORE INTO users (
    username,
    email,
    password_hash,
    first_name,
    last_name,
    user_type,
    is_verified,
    green_points,
    experience_points,
    user_level,
    is_active,
    total_carbon_saved
)
VALUES
-- Moderator
('moderator', 'moderator@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Minh', 'Moderator', 'moderator', TRUE, 500, 1000, 5, TRUE, 50.00),

-- Content Creator
('content_creator', 'content@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Linh', 'Creator', 'individual', TRUE, 300, 600, 3, TRUE, 30.00),

-- Business Partner
('ecocompany', 'business@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Eco', 'Company', 'business', TRUE, 800, 1500, 6, TRUE, 120.00),

-- Organization
('greenorg', 'org@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Green', 'Organization', 'organization', TRUE, 700, 1400, 5, TRUE, 80.00),

-- Regular Users
('user1', 'user1@example.com', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Hoa', 'Nguyen', 'individual', TRUE, 150, 300, 2, TRUE, 15.00),
('user2', 'user2@example.com', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Tuan', 'Tran', 'individual', FALSE, 50, 100, 1, TRUE, 5.00),
('user3', 'user3@example.com', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Mai', 'Le', 'individual', TRUE, 200, 400, 2, TRUE, 25.00);

-- Assign roles to sample users
INSERT IGNORE INTO user_role_assignments (user_id, role_id)
VALUES
-- Moderator
((SELECT user_id FROM users WHERE username = 'moderator'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Moderator')),

-- Content Creator
((SELECT user_id FROM users WHERE username = 'content_creator'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Content Creator')),

-- Business Partner
((SELECT user_id FROM users WHERE username = 'ecocompany'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Business Partner')),

-- Organization
((SELECT user_id FROM users WHERE username = 'greenorg'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Organization')),

-- Regular Users
((SELECT user_id FROM users WHERE username = 'user1'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Regular User')),
((SELECT user_id FROM users WHERE username = 'user2'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Regular User')),
((SELECT user_id FROM users WHERE username = 'user3'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Regular User'));

-- ========================================
-- 4. VERIFICATION QUERIES
-- ========================================

SELECT 'ADMIN USER VERIFICATION' as section;
SELECT user_id, username, email, user_type, is_verified, user_level 
FROM users 
WHERE username = 'admin';

SELECT 'ROLES AND PERMISSIONS VERIFICATION' as section;
SELECT 
    r.role_name, 
    COUNT(rp.permission_id) AS permission_count
FROM 
    user_roles r
LEFT JOIN 
    user_role_permissions rp ON r.role_id = rp.role_id
GROUP BY 
    r.role_name
ORDER BY 
    r.permission_level DESC;

SELECT 'USER ROLE ASSIGNMENTS VERIFICATION' as section;
SELECT 
    u.username, 
    u.user_type, 
    r.role_name,
    u.is_verified,
    u.green_points
FROM 
    users u
JOIN 
    user_role_assignments ra ON u.user_id = ra.user_id
JOIN 
    user_roles r ON ra.role_id = r.role_id
ORDER BY 
    r.permission_level DESC, u.username;
