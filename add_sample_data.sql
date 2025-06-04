USE environmental_platform;

-- Add sample preferences
INSERT INTO user_preferences (user_id, preference_key, preference_value, category) VALUES 
(1, 'dashboard_layout', '"grid"', 'dashboard'),
(1, 'notifications', '"daily"', 'notifications'),
(2, 'dashboard_layout', '"list"', 'dashboard'),
(2, 'notifications', '"weekly"', 'notifications');

-- Show final results
SELECT 'Phase 1 Database Setup Complete!' as status;

SELECT 'Tables Created:' as info;
SHOW TABLES;

SELECT 'Total Users:' as info;
SELECT COUNT(*) as total_users FROM users;

SELECT 'Users List:' as info;
SELECT user_id, username, email, first_name, last_name, user_type, green_points, is_verified FROM users;

SELECT 'User Preferences:' as info;
SELECT * FROM user_preferences;

SELECT 'Active Sessions:' as info;
SELECT COUNT(*) as total_sessions FROM user_sessions;

SELECT 'Password Resets:' as info;
SELECT COUNT(*) as total_resets FROM password_resets;
