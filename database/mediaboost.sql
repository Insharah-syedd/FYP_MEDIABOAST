-- ============================================================
--  MediaBoost Database
--  All Media Marketing — Client Management System
--  Author: Insharah Syed | Lincoln University Malaysia
-- ============================================================

CREATE DATABASE IF NOT EXISTS mediaboost CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mediaboost;

-- ============================================================
-- 1. USERS TABLE (Admin, Manager, Employee)
-- ============================================================
CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','manager','employee') DEFAULT 'employee',
    phone       VARCHAR(20),
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. CLIENTS TABLE
-- ============================================================
CREATE TABLE clients (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    business_name   VARCHAR(150) NOT NULL,
    contact_person  VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    phone           VARCHAR(20),
    city            VARCHAR(80),
    industry        VARCHAR(100),
    package         ENUM('basic','standard','premium') DEFAULT 'basic',
    assigned_to     INT,                          -- FK to users (manager/employee)
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- 3. SERVICES TABLE
-- ============================================================
CREATE TABLE services (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    price       DECIMAL(10,2),
    is_active   TINYINT(1) DEFAULT 1
);

-- ============================================================
-- 4. LEADS TABLE
-- ============================================================
CREATE TABLE leads (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150),
    phone           VARCHAR(20),
    business_name   VARCHAR(150),
    service_interest VARCHAR(100),
    budget          ENUM('under_50k','50k_150k','150k_500k','500k_plus') DEFAULT 'under_50k',
    source          ENUM('website','whatsapp','referral','facebook','instagram','other') DEFAULT 'website',
    status          ENUM('new','contacted','interested','negotiation','closed_won','closed_lost','junk') DEFAULT 'new',
    notes           TEXT,
    assigned_to     INT,
    ai_score        TINYINT DEFAULT 0,            -- 0-100 AI priority score
    follow_up_date  DATE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- 5. PROJECTS TABLE
-- ============================================================
CREATE TABLE projects (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    title           VARCHAR(200) NOT NULL,
    description     TEXT,
    service_type    ENUM('seo','social_media','web_dev','content','backlinks','other') NOT NULL,
    status          ENUM('pending','in_progress','review','completed','paused','cancelled') DEFAULT 'pending',
    start_date      DATE,
    deadline        DATE,
    assigned_to     INT,
    progress        TINYINT DEFAULT 0,            -- 0-100 percentage
    budget          DECIMAL(10,2),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- 6. PROJECT DELIVERABLES
-- ============================================================
CREATE TABLE deliverables (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    project_id  INT NOT NULL,
    title       VARCHAR(200) NOT NULL,
    file_path   VARCHAR(500),
    notes       TEXT,
    submitted_by INT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- 7. CAMPAIGN REPORTS (Analytics Data)
-- ============================================================
CREATE TABLE reports (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    client_id       INT NOT NULL,
    project_id      INT,
    report_month    DATE NOT NULL,               -- First day of the month
    seo_clicks      INT DEFAULT 0,
    seo_impressions INT DEFAULT 0,
    seo_position    DECIMAL(5,2) DEFAULT 0,
    fb_reach        INT DEFAULT 0,
    fb_engagement   INT DEFAULT 0,
    ig_reach        INT DEFAULT 0,
    ig_engagement   INT DEFAULT 0,
    website_visits  INT DEFAULT 0,
    leads_generated INT DEFAULT 0,
    summary         TEXT,
    pdf_path        VARCHAR(500),
    created_by      INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- 8. SERVICE BOOKINGS (from website form)
-- ============================================================
CREATE TABLE bookings (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    phone           VARCHAR(20),
    business_name   VARCHAR(150),
    service_id      INT,
    budget_range    ENUM('under_50k','50k_150k','150k_500k','500k_plus'),
    message         TEXT,
    preferred_date  DATE,
    status          ENUM('pending','reviewed','converted','rejected') DEFAULT 'pending',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL
);

-- ============================================================
-- 9. PORTFOLIO PROJECTS (public showcase)
-- ============================================================
CREATE TABLE portfolio (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200) NOT NULL,
    category    ENUM('web_dev','seo','social_media','content','backlinks') NOT NULL,
    description TEXT,
    client_name VARCHAR(100),
    thumbnail   VARCHAR(500),
    live_url    VARCHAR(500),
    results     TEXT,
    is_featured TINYINT(1) DEFAULT 0,
    sort_order  INT DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 10. NOTIFICATIONS
-- ============================================================
CREATE TABLE notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT,
    client_id   INT,
    type        ENUM('new_lead','lead_status','project_update','report_ready','booking','system') DEFAULT 'system',
    message     TEXT NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    link        VARCHAR(300),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Default admin account (password: Admin@123)
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@allmediamarketing.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Sara Khan', 'sara@allmediamarketing.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager'),
('Ali Ahmed', 'ali@allmediamarketing.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee');

-- Sample services
INSERT INTO services (name, description, price) VALUES
('SEO Optimization', 'Complete on-page and off-page SEO', 15000.00),
('Social Media Marketing', 'Facebook, Instagram management', 12000.00),
('Web Development', 'Custom website design and development', 50000.00),
('Content Writing', 'Blog posts, product descriptions', 8000.00),
('Backlink Building', 'High authority backlink campaigns', 10000.00);

-- Sample client (password: Client@123)
INSERT INTO clients (business_name, contact_person, email, password, phone, city, package, assigned_to) VALUES
('TechStart PK', 'Bilal Raza', 'bilal@techstart.pk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '03001234567', 'Lahore', 'premium', 2);

-- Sample leads
INSERT INTO leads (name, email, phone, business_name, service_interest, source, status, budget, ai_score, assigned_to) VALUES
('Kamran Malik', 'kamran@example.com', '03111234567', 'Malik Traders', 'SEO Optimization', 'website', 'new', '50k_150k', 85, 2),
('Fatima Zahra', 'fatima@example.com', '03221234567', 'Zahra Boutique', 'Social Media Marketing', 'instagram', 'contacted', 'under_50k', 62, 2),
('Usman Ghani', 'usman@example.com', '03331234567', 'Ghani Corp', 'Web Development', 'referral', 'interested', '500k_plus', 95, 1);

-- Sample project
INSERT INTO projects (client_id, title, service_type, status, start_date, deadline, assigned_to, progress, budget) VALUES
(1, 'TechStart SEO Campaign Q2', 'seo', 'in_progress', '2026-04-01', '2026-06-30', 3, 45, 15000.00);

-- Sample portfolio
INSERT INTO portfolio (title, category, description, client_name, results, is_featured) VALUES
('E-commerce SEO Success', 'seo', 'Full SEO overhaul for fashion brand', 'Style PK', '200% traffic increase in 3 months', 1),
('Restaurant Website', 'web_dev', 'Modern responsive website', 'Lahori Dhaba', 'Mobile-first, 98 PageSpeed score', 1),
('Social Media Growth', 'social_media', 'FB + IG management campaign', 'TechStart PK', '5000 new followers in 60 days', 0);
