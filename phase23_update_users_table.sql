-- Add missing columns to users table for Phase 23 stored procedures
USE environmental_platform;

-- Add missing columns to users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS experience_points INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS user_level INT DEFAULT 1,
ADD COLUMN IF NOT EXISTS login_streak INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS longest_streak INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS total_carbon_saved DECIMAL(10,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_users_level ON users(user_level);
CREATE INDEX IF NOT EXISTS idx_users_points ON users(green_points);
CREATE INDEX IF NOT EXISTS idx_users_streak ON users(login_streak);

-- Update existing users with default values
UPDATE users 
SET experience_points = COALESCE(green_points * 2, 0),
    user_level = GREATEST(1, FLOOR(SQRT(COALESCE(green_points, 0) / 100)) + 1),
    last_login = created_at,
    updated_at = NOW()
WHERE experience_points IS NULL OR user_level IS NULL;

SELECT 'Users table updated successfully for Phase 23!' as result;
