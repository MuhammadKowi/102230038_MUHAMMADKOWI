CREATE DATABASE IF NOT EXISTS pesantren_spp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pesantren_spp;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS fee_categories;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

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
  category_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  spp_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(12,2) NOT NULL,
  method ENUM('cash','bank_transfer','midtrans') NOT NULL,
  status ENUM('pending','paid','rejected','failed','expire') NOT NULL DEFAULT 'pending',
  proof_path VARCHAR(255) NULL,
  gateway_transaction_id VARCHAR(120) NULL,
  gateway_status VARCHAR(80) NULL,
  snap_token VARCHAR(255) NULL,
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
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator Keuangan'),
('santri001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'santri', 'Ahmad Fauzi');

INSERT INTO students (user_id, student_no, class_level) VALUES
(2, 'MH-2026-001', 'Wustha');

INSERT INTO fee_categories (name, amount, spp_amount, description) VALUES
('Ula', 150000, 100000, 'Kategori jenjang Ula'),
('Wustha', 200000, 125000, 'Kategori jenjang Wustha'),
('Ulya', 250000, 150000, 'Kategori jenjang Ulya');
