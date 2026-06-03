CREATE TABLE IF NOT EXISTS otp_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  phone_number CHAR(11) NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  customer_id BIGINT UNSIGNED NULL,
  expires_at DATETIME NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_otp_phone (phone_number),
  INDEX idx_otp_expires (expires_at),
  CONSTRAINT fk_otp_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL,
  ip_address VARCHAR(45) NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  INDEX idx_login_username_time (username, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE sms_settings
  ADD COLUMN otp_template TEXT NULL AFTER welcome_template;

UPDATE sms_settings SET otp_template = 'کد ورود به پرتال کش‌بک {company_name}: {otp_code}' WHERE id = 1 AND otp_template IS NULL;
