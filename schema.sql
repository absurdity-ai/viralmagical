-- ViralMagical Database Schema
-- Run this to create the tables

-- Apps table: stores all created viral apps
CREATE TABLE IF NOT EXISTS apps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) NOT NULL,
    prompt TEXT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    sponsor VARCHAR(50) NOT NULL,
    short_code VARCHAR(10) NOT NULL UNIQUE,
    full_prompt TEXT,
    user_id VARCHAR(100) DEFAULT 'anonymous',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_short_code (short_code),
    INDEX idx_user_id (user_id),
    INDEX idx_uuid (uuid),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Add a views/analytics table for tracking
CREATE TABLE IF NOT EXISTS app_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    app_id INT NOT NULL,
    viewer_ip VARCHAR(45),
    viewer_user_agent TEXT,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE,
    INDEX idx_app_id (app_id),
    INDEX idx_viewed_at (viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Add a table for tracking remixes
CREATE TABLE IF NOT EXISTS app_remixes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_app_id INT NOT NULL,
    remix_app_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (original_app_id) REFERENCES apps(id) ON DELETE CASCADE,
    FOREIGN KEY (remix_app_id) REFERENCES apps(id) ON DELETE CASCADE,
    INDEX idx_original (original_app_id),
    INDEX idx_remix (remix_app_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Logs table: stores all API calls for monitoring and analytics
CREATE TABLE IF NOT EXISTS api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint VARCHAR(50) NOT NULL,
    prompt TEXT,
    response TEXT,
    token_count INT DEFAULT NULL,
    model VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_endpoint (endpoint),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
