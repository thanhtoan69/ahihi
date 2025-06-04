-- Create Events Table
CREATE TABLE IF NOT EXISTS events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    event_type ENUM('workshop', 'cleanup', 'conference', 'webinar', 'volunteer') NOT NULL,
    event_mode ENUM('online', 'offline', 'hybrid') DEFAULT 'offline',
    organizer_id INT NOT NULL,
    venue_name VARCHAR(255),
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    max_participants INT DEFAULT 100,
    points_reward INT DEFAULT 10,
    status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
