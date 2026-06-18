-- Tayyab Snacks Database Schema
-- Run this file once to set up your database.
-- Command: mysql -u root -p < schema.sql

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

-- ── Demo Users ────────────────────────────────────────────────────────────
-- Default password for all demo users: Admin@123
INSERT INTO users (name, email, password, city, is_admin) VALUES
('Admin',           'admin@tayyabsnacks.com',    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Karachi', 1),
('Brother Yasir',   'yasir@example.com',          '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Karachi', 0),
('Sister Noor',     'noor@example.com',           '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lahore',  0),
('Hajji Rasheed',   'rasheed@example.com',        '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Islamabad', 0);

-- ── Demo Campaigns ────────────────────────────────────────────────────────
INSERT INTO campaigns (user_id, category_id, title, description, goal_amount, raised_amount, city, deadline, status) VALUES
(2, 3,
 'Tayyab Kids Snacks — No Artificial Colours or Preservatives',
 'We are launching a line of snacks for Muslim children: no artificial colours, no pork-derived gelatin, no preservatives. Just clean, tayyab ingredients that parents can trust. Help us fund our first production run and halal certification.',
 150000.00, 62000.00, 'Karachi', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active'),

(3, 1,
 'Homemade Namkeen Brand — Scaling From Kitchen to Shelf',
 'Our family recipe namkeen (spiced snack mix) has been loved by our neighbourhood for years. We want to scale into proper packaging and get halal certification so we can sell in local stores. Funds go toward packaging machine and first bulk ingredient order.',
 120000.00, 45000.00, 'Lahore', DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'active'),

(4, 4,
 'Premium Dried Fruit & Nut Gift Boxes for Eid',
 'We source dates, almonds, and dried apricots directly from local farms and want to launch beautiful gift boxes for Eid season. Funding will cover packaging design, boxes, and our first wholesale order.',
 200000.00, 138000.00, 'Islamabad', DATE_ADD(CURDATE(), INTERVAL 40 DAY), 'active'),

(2, 5,
 'Tayyab Fresh Juice Bottling — No Added Sugar or Preservatives',
 'Launching a halal, no-added-sugar fresh juice brand bottled fresh daily. We need funding for bottling equipment and our first month of cold-press production.',
 180000.00, 30000.00, 'Karachi', DATE_ADD(CURDATE(), INTERVAL 35 DAY), 'active');

-- ── Demo Contributions ────────────────────────────────────────────────────
INSERT INTO contributions (campaign_id, user_id, donor_name, amount, message) VALUES
(1, 3, 'Sister Noor',    20000.00, 'Finally snacks we can trust for our kids! May Allah bless this.'),
(1, 4, 'Hajji Rasheed',  42000.00, 'Proud to support tayyab entrepreneurs. JazakAllah Khair.'),
(2, 2, 'Brother Yasir',  15000.00, 'Your namkeen is the best in the neighbourhood, time to scale up!'),
(2, 4, 'Hajji Rasheed',  30000.00, 'Allah bless this initiative and multiply its reward.'),
(3, 2, 'Brother Yasir',  58000.00, 'Beautiful idea for Eid gifting. Ordering as soon as you launch!'),
(3, 3, 'Sister Noor',    80000.00, 'Supporting local farmers and tayyab food, can''t wait.'),
(4, 3, 'Sister Noor',    30000.00, 'No added sugar juice is exactly what our family needs.');
