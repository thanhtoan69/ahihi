-- Create users table first
USE environmental_platform;

CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone_number VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
    location VARCHAR(100),
    city VARCHAR(50),
    district VARCHAR(50),
    ward VARCHAR(50),
    latitude DECIMAL(10, 6),
    longitude DECIMAL(10, 6),
    avatar_url VARCHAR(255),
    cover_image_url VARCHAR(255),
    bio TEXT,
    interests JSON,
    languages JSON DEFAULT ('["vi", "en"]'),
    notification_preferences JSON DEFAULT ('{"email": true, "push": true, "sms": false}'),
    privacy_settings JSON DEFAULT ('{"profile_public": true, "show_location": false}'),
    total_carbon_saved DECIMAL(10,2) DEFAULT 0,
    green_points INT DEFAULT 0,
    experience_points INT DEFAULT 0,
    user_level INT DEFAULT 1,
    exchange_rating DECIMAL(3,2) DEFAULT 0,
    total_exchanges INT DEFAULT 0,
    is_exchange_verified BOOLEAN DEFAULT FALSE,
    preferred_exchange_radius INT DEFAULT 10,
    join_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    login_streak INT DEFAULT 0,
    longest_streak INT DEFAULT 0,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    verification_sent_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_banned BOOLEAN DEFAULT FALSE,
    ban_reason TEXT,
    banned_until TIMESTAMP NULL,
    user_type ENUM('individual', 'organization', 'business', 'admin', 'moderator') DEFAULT 'individual',
    organization_info JSON,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_location (city, district),
    INDEX idx_coordinates (latitude, longitude),
    INDEX idx_user_type_active (user_type, is_active),
    INDEX idx_green_points (green_points DESC),
    INDEX idx_last_active (last_active DESC),
    FULLTEXT(username, first_name, last_name, bio)
) ENGINE=InnoDB;
