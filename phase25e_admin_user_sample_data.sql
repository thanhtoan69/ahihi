-- ========================================
-- Phase 25E: Admin User & Sample Data
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
    total_carbon_saved,
    bio
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
    1000,
    2000,
    10,
    TRUE,
    100.00,
    'System administrator account for the Environmental Platform.'
WHERE @admin_exists = 0;

-- ========================================
-- 2. CREATE DEFAULT USER ROLES AND PERMISSIONS
-- ========================================

-- Create user_roles table if it doesn't exist
CREATE TABLE IF NOT EXISTS user_roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_description TEXT,
    permission_level INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create user_permissions table if it doesn't exist
CREATE TABLE IF NOT EXISTS user_permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    permission_description TEXT,
    permission_code VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create user_role_permissions table if it doesn't exist
CREATE TABLE IF NOT EXISTS user_role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES user_roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES user_permissions(permission_id) ON DELETE CASCADE
);

-- Create user_role_assignments table if it doesn't exist
CREATE TABLE IF NOT EXISTS user_role_assignments (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_by INT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES user_roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Insert default roles if they don't exist
INSERT IGNORE INTO user_roles (role_name, role_description, permission_level) VALUES
('Administrator', 'Full system access with all permissions', 100),
('Moderator', 'Content moderation and user management', 50),
('Content Creator', 'Can create and manage content', 30),
('Business Partner', 'Business accounts with enhanced access', 20),
('Organization', 'Organization accounts with specific permissions', 15),
('Regular User', 'Standard user account with basic permissions', 10);

-- Insert basic permissions if they don't exist
INSERT IGNORE INTO user_permissions (permission_name, permission_description, permission_code) VALUES
-- User Management
('Manage Users', 'Create, edit, and delete user accounts', 'manage_users'),
('View Users', 'View user profiles and information', 'view_users'),
('Manage Roles', 'Assign and manage user roles', 'manage_roles'),

-- Content Management
('Create Content', 'Create new content and articles', 'create_content'),
('Edit Content', 'Edit existing content', 'edit_content'),
('Delete Content', 'Delete content from the platform', 'delete_content'),
('Publish Content', 'Publish content without moderation', 'publish_content'),

-- Comment Management
('Moderate Comments', 'Approve, edit, or delete comments', 'moderate_comments'),
('Post Comments', 'Add comments to content', 'post_comments'),

-- Environmental Data
('Manage Environmental Data', 'Add or edit environmental data', 'manage_env_data'),
('View Environmental Data', 'Access to environmental data', 'view_env_data'),

-- Waste Management
('Manage Waste Categories', 'Create and edit waste categories', 'manage_waste_categories'),
('Submit Waste Data', 'Submit waste classification data', 'submit_waste_data'),

-- System Administration
('Access Admin Panel', 'Access to administration panel', 'access_admin'),
('Manage System Settings', 'Configure system settings', 'manage_system'),
('View System Reports', 'Access to system reports and analytics', 'view_reports');

-- Assign permissions to roles
-- Administrator Role
INSERT IGNORE INTO user_role_permissions (role_id, permission_id)
SELECT 
    (SELECT role_id FROM user_roles WHERE role_name = 'Administrator'),
    permission_id
FROM user_permissions;

-- Moderator Role
INSERT IGNORE INTO user_role_permissions (role_id, permission_id)
SELECT 
    (SELECT role_id FROM user_roles WHERE role_name = 'Moderator'),
    permission_id
FROM user_permissions
WHERE permission_code IN (
    'view_users', 'moderate_comments', 'edit_content', 'delete_content',
    'publish_content', 'post_comments', 'view_env_data', 'view_reports',
    'submit_waste_data'
);

-- Content Creator Role
INSERT IGNORE INTO user_role_permissions (role_id, permission_id)
SELECT 
    (SELECT role_id FROM user_roles WHERE role_name = 'Content Creator'),
    permission_id
FROM user_permissions
WHERE permission_code IN (
    'create_content', 'edit_content', 'post_comments', 
    'view_env_data', 'submit_waste_data'
);

-- Regular User Role
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
    bio
)
VALUES
-- Moderator
('moderator', 'moderator@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Minh', 'Moderator', 'moderator', TRUE, 500, 1000, 5, TRUE, 'Content moderator for the Environmental Platform'),

-- Content Creator
('content_creator', 'content@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Linh', 'Creator', 'individual', TRUE, 300, 600, 3, TRUE, 'Environmental content creator and advocate'),

-- Business Partner
('ecocompany', 'business@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Eco', 'Company', 'business', TRUE, 800, 1500, 6, TRUE, 'Sustainable business focusing on eco-friendly products'),

-- Organization
('greenorg', 'org@environmental-platform.vn', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Green', 'Organization', 'organization', TRUE, 700, 1400, 5, TRUE, 'Non-profit environmental organization'),

-- Regular Users
('user1', 'user1@example.com', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Tran', 'Hung', 'individual', TRUE, 120, 240, 2, TRUE, 'Environmental enthusiast and recycling advocate'),
('user2', 'user2@example.com', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Nguyen', 'Thi', 'individual', TRUE, 150, 300, 2, TRUE, 'Passionate about reducing carbon footprint'),
('user3', 'user3@example.com', '$2y$10$vI8aWBnW3fID.ZQ4XoDAduTghHrIB9zYvZLuM57VgcH5.XSMlqIey', 'Le', 'Van', 'individual', TRUE, 80, 160, 1, TRUE, 'New to environmental conservation');

-- Assign roles to sample users
INSERT IGNORE INTO user_role_assignments (user_id, role_id, assigned_by)
VALUES
-- Assign Moderator role
((SELECT user_id FROM users WHERE username = 'moderator'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Moderator'),
 (SELECT user_id FROM users WHERE username = 'admin')),

-- Assign Content Creator role
((SELECT user_id FROM users WHERE username = 'content_creator'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Content Creator'),
 (SELECT user_id FROM users WHERE username = 'admin')),

-- Assign Business Partner role
((SELECT user_id FROM users WHERE username = 'ecocompany'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Business Partner'),
 (SELECT user_id FROM users WHERE username = 'admin')),

-- Assign Organization role
((SELECT user_id FROM users WHERE username = 'greenorg'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Organization'),
 (SELECT user_id FROM users WHERE username = 'admin')),

-- Assign Regular User role to other users
((SELECT user_id FROM users WHERE username = 'user1'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Regular User'),
 (SELECT user_id FROM users WHERE username = 'admin')),

((SELECT user_id FROM users WHERE username = 'user2'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Regular User'),
 (SELECT user_id FROM users WHERE username = 'admin')),

((SELECT user_id FROM users WHERE username = 'user3'), 
 (SELECT role_id FROM user_roles WHERE role_name = 'Regular User'),
 (SELECT user_id FROM users WHERE username = 'admin'));

-- ========================================
-- VERIFICATION QUERIES
-- ========================================

-- Verify admin user was created
SELECT 'ADMIN USER VERIFICATION' as section;
SELECT 
    user_id,
    username,
    email,
    user_type,
    is_verified,
    user_level
FROM 
    users 
WHERE 
    username = 'admin';

-- Verify roles and permissions
SELECT 'ROLES AND PERMISSIONS VERIFICATION' as section;
SELECT 
    r.role_name,
    COUNT(rp.permission_id) as permission_count
FROM 
    user_roles r
LEFT JOIN 
    user_role_permissions rp ON r.role_id = rp.role_id
GROUP BY 
    r.role_id, r.role_name
ORDER BY 
    r.permission_level DESC;

-- Verify sample users and their roles
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
