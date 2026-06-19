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
    is_verified TINYINT(1) DEFAULT 0,
    verification_token   VARCHAR(64) NULL,
    verification_expires  DATETIME NULL,
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
    updated_by    INT UNSIGNED NULL,
    updated_at    TIMESTAMP NULL,
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by)  REFERENCES users(id) ON DELETE SET NULL,
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
INSERT INTO users (name, email, password, is_admin, is_verified) VALUES
('Site Admin', 'admin@tayyabsnacks.com',
 '$2y$10$Rn49XbRBi1VaO9H6AnkdfOhBEGhhe.D.4.HYAJaquZDWuHT7qXS2q', 1, 1);

-- ── Starter Campaigns — Authentic Persian Snacks for the Local Halal Market ──
-- Owned by Site Admin (id=1) as platform-curated launch content.
INSERT INTO campaigns (user_id, category_id, title, description, goal_amount, city, deadline, status) VALUES
(1, 8, 'Ardeh va Shireh — Traditional Persian Sesame Paste & Date Molasses', 'Ardeh (sesame seed paste) eaten with Shireh (grape or date molasses) is a beloved Persian breakfast and snack staple, rich in protein and naturally sweetened. We want to bring this authentic, tayyab, additive-free spread to Quetta households who already love Irani cafe culture. Funds go toward a stone-grinding sesame mill and first packaging run.', 140000.00, 'Quetta', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'active'),
(1, 4, 'Lavashak — Persian Sour Fruit Roll-Up Snacks', 'Lavashak is a thin, tangy dried fruit leather made from sour plum, apricot, or tamarind — wildly popular with kids and adults across Iran as a tangy on-the-go snack. We are launching a halal, no-added-sugar lavashak line in Karachi using locally sourced fruit. Help us fund our drying equipment and first batch of packaging.', 110000.00, 'Karachi', DATE_ADD(CURDATE(), INTERVAL 40 DAY), 'active'),
(1, 1, 'Tokhmeh — Roasted Persian Seed Mix (Sunflower, Pumpkin & Melon)', 'Tokhmeh — a mix of roasted and salted sunflower, pumpkin, and melon seeds — is one of the most iconic snacks of Iranian tea culture, especially during Yalda Night and family gatherings. We want to produce a tayyab-certified roasted seed mix for the Lahore market using clean, non-GMO seeds and no artificial flavouring.', 95000.00, 'Lahore', DATE_ADD(CURDATE(), INTERVAL 35 DAY), 'active'),
(1, 1, 'Nan-e Jow — Crispy Persian Barley Crackers', 'Nan-e Jow are thin, crispy barley flour crackers traditionally enjoyed with cheese, honey, or tea — a wholesome alternative to fried packaged snacks. We are setting up a small bakery in Quetta to produce these authentic barley crackers using stone-ground flour, completely tayyab and preservative-free.', 130000.00, 'Quetta', DATE_ADD(CURDATE(), INTERVAL 50 DAY), 'active'),
(1, 2, 'Sohan-e Qom — Saffron Pistachio Brittle Toffee', 'Sohan is Iran''s most famous saffron brittle toffee, originating from the holy city of Qom, made with saffron, pistachios, and a caramelized wheat-sprout base. We want to introduce authentic, handcrafted Sohan to Karachi''s halal sweets market, made the traditional way with real saffron and no artificial colouring.', 220000.00, 'Karachi', DATE_ADD(CURDATE(), INTERVAL 55 DAY), 'active'),
(1, 3, 'Pashmak — Saffron Persian Cotton Candy', 'Pashmak is a delicate, floss-like Persian confection similar to cotton candy but made from toasted flour and sugar, traditionally saffron-scented. Loved by children and adults alike, we want to bring this unique halal treat to Lahore''s sweet shops and birthday parties. Funds cover our pulling machine and first production batch.', 90000.00, 'Lahore', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active'),
(1, 2, 'Gaz-e Esfahani — Rosewater Pistachio Nougat', 'Gaz is a soft Persian nougat from Isfahan made with rosewater, pistachios or almonds, and manna (a natural sweet resin) — a centuries-old confection unlike anything in the local sweets market. We are a small family business in Karachi seeking funding for traditional copper kettles and our first wholesale run.', 260000.00, 'Karachi', DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'active'),
(1, 1, 'Doogh-Flavoured Chips — A Unique Yogurt-Drink Twist on Potato Chips', 'Inspired by Doogh, the beloved Persian fermented yogurt drink, we are developing a genuinely unique tangy, herbed yogurt-flavoured potato chip — a flavour you simply cannot find on local shelves. Halal-certified, no MSG. Help us fund our first commercial frying and seasoning run in Karachi.', 180000.00, 'Karachi', DATE_ADD(CURDATE(), INTERVAL 40 DAY), 'active'),
(1, 2, 'Nan-e Nokhodchi — Chickpea Flour Clover Cookies', 'Nan-e Nokhodchi are delicate, melt-in-your-mouth clover-shaped cookies made from roasted chickpea flour, rosewater, and cardamom — a Persian Nowruz tradition now beloved year-round. We want to bake these tayyab treats fresh in Quetta for Eid and everyday tea-time. Funds go toward our oven upgrade and first packaging order.', 100000.00, 'Quetta', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'active');
