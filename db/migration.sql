-- =============================================================
--  GAS-SIMHOT — migration.sql
--  Import this into phpMyAdmin on u442411629_gasleak
--  After import, visit: /gasleak/core/seed.php  (once only)
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS gas_simhot_ratings;
DROP TABLE IF EXISTS user_activity_logs;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS system_status;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(255) NOT NULL,
    role       ENUM('staff','admin','manager') NOT NULL DEFAULT 'staff',
    location   VARCHAR(100) NOT NULL DEFAULT 'General Area',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. ACTIVITY LOGS
CREATE TABLE user_activity_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    action     VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. SESSIONS
CREATE TABLE sessions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    token      VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. SYSTEM STATUS (single-row real-time state)
CREATE TABLE system_status (
    id                    INT PRIMARY KEY DEFAULT 1,
    is_active             TINYINT(1) NOT NULL DEFAULT 0,
    ppm                   INT NOT NULL DEFAULT 0,
    triggered_by          VARCHAR(255) DEFAULT NULL,
    location              VARCHAR(100) DEFAULT NULL,
    lat                   DECIMAL(10,7) DEFAULT NULL,
    lng                   DECIMAL(10,7) DEFAULT NULL,
    acknowledged_by_admin TINYINT(1) NOT NULL DEFAULT 0,
    ack_time              DATETIME DEFAULT NULL,
    triggered_at          DATETIME DEFAULT NULL,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO system_status (id) VALUES (1);

-- 5. RATINGS
CREATE TABLE gas_simhot_ratings (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    log_id     INT NULL,
    rating     TINYINT NOT NULL,
    comment    VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (log_id)  REFERENCES user_activity_logs(id) ON DELETE SET NULL
) ENGINE=InnoDB;
