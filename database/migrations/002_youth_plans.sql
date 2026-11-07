-- Apply once to an existing sk_bantog_portal database using phpMyAdmin > SQL.
-- Non-destructive: no existing tables or rows are modified.
CREATE TABLE IF NOT EXISTS youth_plans (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 plan_type ENUM('CBYDP','ABYIP') NOT NULL,
 title VARCHAR(200) NOT NULL,
 fiscal_year SMALLINT UNSIGNED NOT NULL,
 end_year SMALLINT UNSIGNED NOT NULL,
 description TEXT NULL,
 status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
 created_by BIGINT UNSIGNED NOT NULL,
 published_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (created_by) REFERENCES users(id),
 INDEX idx_youth_plan_public (status,plan_type,fiscal_year)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS youth_plan_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 plan_id BIGINT UNSIGNED NOT NULL,
 center_of_participation VARCHAR(150) NOT NULL,
 youth_concern TEXT NOT NULL,
 objective TEXT NOT NULL,
 program_title VARCHAR(200) NOT NULL,
 target_beneficiaries VARCHAR(150) NULL,
 budget DECIMAL(15,2) NULL,
 implementation_period VARCHAR(150) NULL,
 responsible_person VARCHAR(150) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (plan_id) REFERENCES youth_plans(id) ON DELETE CASCADE,
 INDEX idx_youth_plan_items (plan_id)
) ENGINE=InnoDB;
