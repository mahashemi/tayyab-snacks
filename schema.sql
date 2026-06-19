-- Tayyab Snacks Database Schema (Production)
-- Run this file once to set up your database.
-- Command: mysql --default-character-set=utf8mb4 -u root -p < schema.sql

CREATE DATABASE IF NOT EXISTS tayyab_snacks
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tayyab_snacks;

-- ── Users ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    phone      VARCHAR(30),
    city       VARCHAR(100),
    country    VARCHAR(100) DEFAULT 'Pakistan',
    is_admin   TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ── Campaign Categories (Snack-Focused) ────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(10)
) ENGINE=InnoDB;

INSERT INTO categories (name, icon) VALUES
('Packaged Snacks (Chips, Crackers, Namkeen)', '🥨'),
('Bakery & Confectionery',                     '🍰'),
('Kids'' Snacks',                               '🧒'),
('Dried Fruits & Nuts',                        '🥜'),
('Halal Beverages & Juices',                   '🧃'),
('Mobile Snack Carts / Trucks',                '🛒'),
('Charity Snack Distribution',                 '❤️'),
('Other Snack Initiative',                     '📦');

-- ── Campaigns ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS campaigns (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    category_id   INT UNSIGNED,
    title         VARCHAR(200) NOT NULL,
    description   TEXT NOT NULL,
    goal_amount   DECIMAL(12,2) NOT NULL,
    raised_amount DECIMAL(12,2) DEFAULT 0.00,
    city          VARCHAR(100),
    image_url     VARCHAR(500),
    deadline      DATE,
    status        ENUM('pending','active','funded','closed','rejected') DEFAULT 'pending',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- ── Contributions ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contributions (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id  INT UNSIGNED NOT NULL,
    user_id      INT UNSIGNED,        -- NULL = anonymous
    donor_name   VARCHAR(100),
    amount       DECIMAL(10,2) NOT NULL,
    message      TEXT,
    is_anonymous TINYINT(1) DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_campaign (campaign_id)
) ENGINE=InnoDB;

-- ── Trigger: Update campaign raised_amount on contribution ────────────────
DELIMITER $$
CREATE TRIGGER after_contribution_insert
AFTER INSERT ON contributions
FOR EACH ROW
BEGIN
    UPDATE campaigns
    SET raised_amount = raised_amount + NEW.amount
    WHERE id = NEW.campaign_id;
END$$
DELIMITER ;

-- ── Initial Admin Account ───────────────────────────────────────────────
-- Default password: Admin@123
-- IMPORTANT: Log in immediately and change this password via your profile.
INSERT INTO users (name, email, password, is_admin) VALUES
('Site Admin', 'admin@tayyabsnacks.com',
 '$2y$10$Rn49XbRBi1VaO9H6AnkdfOhBEGhhe.D.4.HYAJaquZDWuHT7qXS2q', 1);
