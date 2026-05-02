-- ============================================================
-- Social Welfare Scheme Management System
-- Database: social_welfare
-- Created for XAMPP / MySQL 5.7+
-- ============================================================

CREATE DATABASE IF NOT EXISTS social_welfare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE social_welfare;

-- ============================================================
-- Table: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    dob DATE NOT NULL,
    gender ENUM('Male','Female','Other') NOT NULL,
    address TEXT,
    aadhar VARCHAR(12),
    annual_income DECIMAL(12,2) DEFAULT 0,
    category ENUM('General','OBC','SC','ST','EWS') DEFAULT 'General',
    profile_photo VARCHAR(255) DEFAULT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- Table: schemes
-- ============================================================
CREATE TABLE IF NOT EXISTS schemes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    eligibility TEXT NOT NULL,
    benefits TEXT NOT NULL,
    category ENUM('Agriculture','Education','Health','Housing','Employment','Women','Elderly','Disability','Other') DEFAULT 'Other',
    min_age INT DEFAULT 0,
    max_age INT DEFAULT 120,
    max_income DECIMAL(12,2) DEFAULT 999999,
    eligible_categories SET('General','OBC','SC','ST','EWS','All') DEFAULT 'All',
    required_documents TEXT,
    last_date DATE,
    status ENUM('active','inactive') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- Table: applications
-- ============================================================
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    scheme_id INT NOT NULL,
    status ENUM('pending','under_review','approved','rejected') DEFAULT 'pending',
    remarks TEXT,
    documents VARCHAR(255),
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (scheme_id) REFERENCES schemes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Table: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Default Admin Account
-- Password: Admin@123 (hashed with password_hash)
-- ============================================================
INSERT INTO users (name, email, phone, password, dob, gender, address, role) VALUES
('Admin User', 'admin@socialwelfare.gov', '9999999999',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 '1990-01-01', 'Male', 'Government Office, New Delhi', 'admin');

-- ============================================================
-- Sample Schemes
-- ============================================================
INSERT INTO schemes (title, description, eligibility, benefits, category, min_age, max_age, max_income, eligible_categories, required_documents, last_date, status, created_by) VALUES
(
    'PM Kisan Samman Nidhi',
    'Direct income support scheme for farmers providing ₹6000 per year in three equal installments of ₹2000 each to eligible farmer families.',
    'Small and marginal farmers owning cultivable land. Annual income below ₹2,00,000. Age between 18-65 years.',
    'Financial benefit of ₹6000 per year directly to bank account in three installments. No middlemen involved.',
    'Agriculture', 18, 65, 200000, 'General,OBC,SC,ST,EWS',
    'Aadhar Card, Land Records, Bank Passbook, Income Certificate',
    '2026-12-31', 'active', 1
),
(
    'National Scholarship Portal',
    'Merit-cum-means scholarship for students from economically weaker sections to pursue higher education.',
    'Students scoring above 50% marks. Family annual income below ₹2,50,000. Age 15-25 years.',
    'Scholarship amount up to ₹50,000 per year. Covers tuition fees and maintenance allowance.',
    'Education', 15, 25, 250000, 'OBC,SC,ST,EWS',
    'Mark Sheets, Income Certificate, Aadhar Card, Bank Passbook, Institution Certificate',
    '2026-09-30', 'active', 1
),
(
    'Pradhan Mantri Awas Yojana',
    'Housing for All mission to provide affordable housing to urban and rural poor with credit-linked subsidy.',
    'EWS/LIG/MIG categories. No pucca house in name or spouse name. Annual income below ₹6,00,000.',
    'Interest subsidy up to ₹2.67 lakh on home loans. Direct benefit transfer for house construction.',
    'Housing', 21, 60, 600000, 'OBC,SC,ST,EWS',
    'Aadhar Card, Income Certificate, Land Documents, Bank Statements, Self-Declaration',
    '2026-06-30', 'active', 1
),
(
    'Ayushman Bharat - PM-JAY',
    'World''s largest health insurance scheme providing coverage up to ₹5 lakh per family per year for secondary and tertiary hospitalization.',
    'Poor and vulnerable families as per SECC database. No age limit. BPL families.',
    'Health coverage up to ₹5 lakh per family per year. Cashless treatment at empanelled hospitals.',
    'Health', 0, 120, 150000, 'OBC,SC,ST,EWS',
    'Aadhar Card, Ration Card, Income Certificate, SECC/BPL Certificate',
    '2026-12-31', 'active', 1
),
(
    'MGNREGA (Mahatma Gandhi NREGS)',
    'Employment guarantee scheme providing 100 days of wage employment to rural households whose adult members volunteer unskilled manual work.',
    'Adult members of rural households willing to do unskilled manual work. Registered with Gram Panchayat.',
    '100 days guaranteed employment per year. Current wage rate ₹220 per day. Unemployment allowance if work not provided.',
    'Employment', 18, 65, 999999, 'All',
    'Aadhar Card, Ration Card, Bank Passbook, Job Card (from GP)',
    '2026-12-31', 'active', 1
),
(
    'Sukanya Samriddhi Yojana',
    'Small deposit savings scheme for girl child to meet education and marriage expenses.',
    'Girl child below 10 years. Only 2 girls per family. Guardians/parents must open account.',
    'High interest rate of 8.2% p.a. Tax benefits under 80C. Maturity amount at age 21.',
    'Women', 0, 10, 999999, 'All',
    'Girl Child Birth Certificate, Parent/Guardian Aadhar, PAN Card, Address Proof',
    '2026-12-31', 'active', 1
);

-- ============================================================
-- Sample regular user (Password: User@1234)
-- ============================================================
INSERT INTO users (name, email, phone, password, dob, gender, address, category, annual_income, role) VALUES
('Ramesh Kumar', 'ramesh@example.com', '9876543210',
 '$2y$10$TKh8H1.PFgs/B0XHD.f2IOVxOcZEtB4YZNF4B0ILXCy.4yFZIRBfe',
 '1995-06-15', 'Male', '12, Green Park, New Delhi - 110016', 'OBC', 80000, 'user');

-- ============================================================
-- Sample Applications
-- ============================================================
INSERT INTO applications (application_id, user_id, scheme_id, status, remarks, applied_at) VALUES
('SW2026-0001', 2, 2, 'approved', 'Documents verified. Scholarship approved for academic year 2026-27.', '2026-01-10 10:30:00'),
('SW2026-0002', 2, 5, 'pending', NULL, '2026-02-15 14:20:00'),
('SW2026-0003', 2, 4, 'under_review', 'Documents submitted. Under verification process.', '2026-03-01 09:00:00');

-- ============================================================
-- Sample Notifications
-- ============================================================
INSERT INTO notifications (user_id, message, is_read) VALUES
(2, 'Your application SW2026-0001 for National Scholarship Portal has been APPROVED! Congratulations!', 0),
(2, 'Your application SW2026-0003 for Ayushman Bharat is now Under Review. Please wait for further updates.', 0),
(2, 'New scheme "MGNREGA" is now available. Check your eligibility and apply today!', 1);
