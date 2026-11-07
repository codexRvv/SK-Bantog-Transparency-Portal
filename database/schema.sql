-- SK Barangay Bantog Budget Transparency Portal
-- Import into MySQL 8+ or MariaDB 10.5+; use utf8mb4.
CREATE DATABASE IF NOT EXISTS sk_bantog_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sk_bantog_portal;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','publisher','editor') NOT NULL DEFAULT 'editor',
  active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip_hash CHAR(64) NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_ip_time (ip_hash, attempted_at),
  INDEX idx_login_email_time (email, attempted_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS document_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 100
) ENGINE=InnoDB;

INSERT INTO document_categories (slug,name,description,sort_order) VALUES
 ('annual-budget','Approved Annual Budget','Approved annual SK financial allocations',10),
 ('specific-purpose','Specific Purpose Fund','Financial assistance and specific purpose fund records',20),
 ('quarterly-reports','Quarterly Reports','Quarterly financial reports and disclosures',30),
 ('accomplishments','Accomplishments','Programs, activities and accomplishment reports',40),
 ('plans','Plans and Programs','CBYDP, ABYIP and other approved SK plans',50),
 ('procurement','Procurement and Disbursements','Purchases, requests and disbursement records',60),
 ('other','Other Public Records','Additional published SK documents',70)
 ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), sort_order=VALUES(sort_order);

CREATE TABLE IF NOT EXISTS documents (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id INT UNSIGNED NOT NULL,
 title VARCHAR(200) NOT NULL,
 description TEXT NULL,
 fiscal_year SMALLINT UNSIGNED NOT NULL,
 quarter TINYINT UNSIGNED NULL,
 original_name VARCHAR(240) NOT NULL,
 stored_name VARCHAR(80) NOT NULL UNIQUE,
 mime_type VARCHAR(80) NOT NULL DEFAULT 'application/pdf',
 file_size BIGINT UNSIGNED NOT NULL,
 status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
 published_at DATETIME NULL,
 uploaded_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (category_id) REFERENCES document_categories(id),
 FOREIGN KEY (uploaded_by) REFERENCES users(id),
 INDEX idx_documents_public (status, fiscal_year, category_id, published_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS annual_budgets (
 fiscal_year SMALLINT UNSIGNED PRIMARY KEY,
 approved_amount DECIMAL(15,2) NOT NULL,
 remarks TEXT NULL,
 updated_by BIGINT UNSIGNED NOT NULL,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS expenditures (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 fiscal_year SMALLINT UNSIGNED NOT NULL,
 transaction_date DATE NOT NULL,
 title VARCHAR(200) NOT NULL,
 amount DECIMAL(15,2) NOT NULL,
 description TEXT NULL,
 status ENUM('draft','published') NOT NULL DEFAULT 'draft',
 recorded_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (recorded_by) REFERENCES users(id),
 INDEX idx_exp_public (fiscal_year,status,transaction_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS projects (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(200) NOT NULL,
 description TEXT NOT NULL,
 fiscal_year SMALLINT UNSIGNED NOT NULL,
 amount DECIMAL(15,2) NULL,
 status ENUM('planned','ongoing','completed','on-hold') NOT NULL DEFAULT 'planned',
 is_published TINYINT(1) NOT NULL DEFAULT 0,
 started_on DATE NULL,
 completed_on DATE NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (created_by) REFERENCES users(id),
 INDEX idx_projects_public (is_published,fiscal_year,status)
) ENGINE=InnoDB;
