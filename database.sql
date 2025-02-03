-- CREATE TABLE users (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     username VARCHAR(255) NOT NULL UNIQUE,
--     password VARCHAR(255) NOT NULL,
--     license_key VARCHAR(64) NOT NULL UNIQUE,
--     uid INT UNIQUE NOT NULL,
--     is_admin TINYINT(1) NOT NULL DEFAULT 0,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );

-- CREATE TABLE license_keys (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     key_value VARCHAR(64) NOT NULL UNIQUE,
--     is_used TINYINT(1) NOT NULL DEFAULT 0,
--     expires_at TIMESTAMP NULL DEFAULT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );

-- CREATE TABLE changelogs (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     title VARCHAR(255) NOT NULL,
--     description TEXT NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- );

-- CREATE TABLE settings (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(255) NOT NULL UNIQUE,
--     value VARCHAR(255) NOT NULL
-- );

-- INSERT INTO license_keys (key_value, is_used) VALUES ('admin', 0);

-- INSERT INTO settings (name, value) VALUES ('status', 'Offline');

-- INSERT INTO changelogs (title, description) VALUES
-- ('Initial Release', 'Version 1.0 with authentication and license key validation.'),
-- ('Security Patch', 'Fixed vulnerabilities and improved encryption.'),
-- ('UI Update', 'Enhanced dashboard styling with modern glassmorphism.');

-- SET @counter = 0;
-- UPDATE users SET uid = (@counter := @counter + 1) ORDER BY id;



CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    license_key VARCHAR(64) NOT NULL UNIQUE,
    uid INT UNIQUE NOT NULL,  -- Removed AUTO_INCREMENT to avoid MySQL error
    is_admin TINYINT(1) NOT NULL DEFAULT 0,
    failed_login_attempts INT DEFAULT 0, -- Track failed login attempts
    last_login TIMESTAMP NULL DEFAULT NULL, -- Store last successful login time
    status ENUM('Active', 'Banned', 'Suspended', 'Deactivated') NOT NULL DEFAULT 'Active', -- Account status
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE license_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_value VARCHAR(64) NOT NULL UNIQUE,
    is_used TINYINT(1) NOT NULL DEFAULT 0,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE changelogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    value VARCHAR(255) NOT NULL
);

-- ✅ New Table: Login Logs
CREATE TABLE login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    ip_address VARCHAR(50) NOT NULL,
    user_agent TEXT NOT NULL,
    device_type VARCHAR(50) NULL,
    operating_system VARCHAR(50) NULL,
    screen_resolution VARCHAR(20) NULL,
    referer_url TEXT NULL,
    isp_name VARCHAR(255) NULL,
    cookies TEXT NULL,
    failed_attempt INT DEFAULT 0, -- Tracks failed attempts if any
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL DEFAULT NULL
);

-- ✅ Insert Default Values
INSERT INTO license_keys (key_value, is_used) VALUES ('admin', 0);
INSERT INTO settings (name, value) VALUES ('status', 'Offline');

INSERT INTO changelogs (title, description) VALUES
('Initial Release', 'Version 1.0 with authentication and license key validation.'),
('Security Patch', 'Fixed vulnerabilities and improved encryption.'),
('UI Update', 'Enhanced dashboard styling with modern glassmorphism.');

-- ✅ Assign Sequential UID to Existing Users
SET @counter = 0;
UPDATE users SET uid = (@counter := @counter + 1) ORDER BY id;
