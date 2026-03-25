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
-- ============================================================
--  GAS-SIMHOT — Feature Upgrades Schema
--  Run this in phpMyAdmin AFTER your existing migration.sql
--  Adds tables for all 5 new features
-- ============================================================

USE u442411629_gasleak;

-- ── Feature 1: Safety Protocol Checklist ─────────────────────
-- Tracks which checklist items were completed per leak event
CREATE TABLE IF NOT EXISTS safety_checklists (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    log_id       INT NULL,
    task_name    VARCHAR(100) NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (log_id)  REFERENCES user_activity_logs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── Feature 2: Smart PPM Trend Readings ──────────────────────
-- Stores PPM readings for trend analysis (polled every 30s)
CREATE TABLE IF NOT EXISTS ppm_readings (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    ppm_value  INT NOT NULL DEFAULT 0,
    status     ENUM('safe','warning','danger') NOT NULL DEFAULT 'safe',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert initial reading
INSERT INTO ppm_readings (ppm_value, status) VALUES (0, 'safe');

-- ── Feature 4: Sensor Health / Inventory ─────────────────────
CREATE TABLE IF NOT EXISTS sensors (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    station_name      VARCHAR(100) NOT NULL UNIQUE,
    model             VARCHAR(100) DEFAULT 'MQ-2 Gas Sensor',
    serial_number     VARCHAR(100) DEFAULT NULL,
    installation_date DATE NOT NULL,
    last_maintenance  DATE DEFAULT NULL,
    status            ENUM('active','maintenance_required','offline') DEFAULT 'active',
    notes             TEXT DEFAULT NULL,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Seed default sensors
INSERT IGNORE INTO sensors (station_name, model, installation_date) VALUES
('Kitchen',     'MQ-2 Gas Sensor', DATE_SUB(CURDATE(), INTERVAL 7 MONTH)),
('Laboratory',  'MQ-2 Gas Sensor', DATE_SUB(CURDATE(), INTERVAL 2 MONTH)),
('Warehouse',   'MQ-2 Gas Sensor', DATE_SUB(CURDATE(), INTERVAL 8 MONTH)),
('Main Office', 'MQ-2 Gas Sensor', DATE_SUB(CURDATE(), INTERVAL 1 MONTH));

-- ── Add response_time column to system_status ─────────────────
-- Tracks how long it took from leak to reset (in seconds)
ALTER TABLE system_status
    ADD COLUMN IF NOT EXISTS response_time_seconds INT DEFAULT NULL;

-- ── Feature 3: Weekly Report helper view ─────────────────────
CREATE OR REPLACE VIEW leak_events_view AS
SELECT
    l.id,
    u.full_name,
    u.location        AS station,
    l.created_at      AS leak_time,
    (
        SELECT MIN(l2.created_at)
        FROM user_activity_logs l2
        WHERE l2.user_id = l.user_id
          AND l2.action = 'System Reset'
          AND l2.created_at > l.created_at
    ) AS reset_time,
    TIMESTAMPDIFF(
        SECOND,
        l.created_at,
        (
            SELECT MIN(l2.created_at)
            FROM user_activity_logs l2
            WHERE l2.user_id = l.user_id
              AND l2.action = 'System Reset'
              AND l2.created_at > l.created_at
        )
    ) AS response_seconds
FROM user_activity_logs l
JOIN users u ON l.user_id = u.id
WHERE l.action LIKE '%Leak%'
ORDER BY l.created_at DESC;