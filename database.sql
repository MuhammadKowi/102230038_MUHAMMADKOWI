SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS fee_categories;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE app_settings (
  setting_key VARCHAR(80) PRIMARY KEY,
  setting_value TEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','santri') NOT NULL DEFAULT 'santri',
  full_name VARCHAR(150) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE students (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  student_no VARCHAR(40) NOT NULL UNIQUE,
  class_level VARCHAR(80) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE fee_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  spp_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  description VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id VARCHAR(80) NOT NULL UNIQUE,
  student_id BIGINT UNSIGNED NOT NULL,
  fee_category_id BIGINT UNSIGNED NOT NULL,
  billing_month DATE NOT NULL,
  payment_type ENUM('spp','jenjang','combined') NOT NULL DEFAULT 'combined',
  category_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  spp_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(12,2) NOT NULL,
  method ENUM('cash','bank_transfer') NOT NULL,
  status ENUM('pending','paid','rejected','failed','expire') NOT NULL DEFAULT 'pending',
  proof_path VARCHAR(255) NULL,
  bank_destination ENUM('BRI','BCA') NULL,
  notes VARCHAR(500) NULL,
  verified_by BIGINT UNSIGNED NULL,
  verified_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pay_student FOREIGN KEY (student_id) REFERENCES students(id),
  CONSTRAINT fk_pay_category FOREIGN KEY (fee_category_id) REFERENCES fee_categories(id),
  CONSTRAINT fk_pay_verifier FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_pay_status (status),
  INDEX idx_pay_month (billing_month)
) ENGINE=InnoDB;

-- Demo users. Password for both accounts: password
INSERT INTO users (username, password_hash, role, full_name) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator Keuangan');


