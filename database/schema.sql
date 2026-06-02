SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS birthday_sms_history;
DROP TABLE IF EXISTS sms_logs;
DROP TABLE IF EXISTS sms_settings;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS wallet_transactions;
DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','operator') NOT NULL DEFAULT 'operator',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  national_code CHAR(10) NOT NULL UNIQUE,
  phone_number CHAR(11) NOT NULL,
  birthday DATE NULL,
  wallet_balance DECIMAL(15,2) NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_customers_name (last_name, first_name),
  INDEX idx_customers_phone (phone_number),
  INDEX idx_customers_birthday (birthday),
  CONSTRAINT fk_customers_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchases (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  cashback_amount DECIMAL(15,2) NOT NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_purchases_customer_date (customer_id, created_at),
  INDEX idx_purchases_created_at (created_at),
  CONSTRAINT fk_purchases_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_purchases_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wallet_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  type ENUM('cashback','reduction') NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  balance_after DECIMAL(15,2) NOT NULL,
  reason VARCHAR(255) NULL,
  purchase_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_wallet_customer_date (customer_id, created_at),
  CONSTRAINT fk_wallet_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_wallet_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE SET NULL,
  CONSTRAINT fk_wallet_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sms_settings (
  id TINYINT UNSIGNED PRIMARY KEY,
  api_token TEXT NULL,
  sender_number VARCHAR(50) NULL,
  sms_enabled TINYINT(1) NOT NULL DEFAULT 0,
  purchase_sms_enabled TINYINT(1) NOT NULL DEFAULT 0,
  birthday_sms_enabled TINYINT(1) NOT NULL DEFAULT 0,
  wallet_reduction_sms_enabled TINYINT(1) NOT NULL DEFAULT 0,
  welcome_sms_enabled TINYINT(1) NOT NULL DEFAULT 0,
  purchase_template TEXT NOT NULL,
  birthday_template TEXT NOT NULL,
  wallet_reduction_template TEXT NOT NULL,
  welcome_template TEXT NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sms_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NULL,
  phone_number VARCHAR(20) NOT NULL,
  event_type ENUM('purchase','birthday','wallet_reduction','welcome','manual') NOT NULL,
  message TEXT NOT NULL,
  provider VARCHAR(50) NOT NULL DEFAULT 'ippanel',
  provider_response TEXT NULL,
  status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_sms_logs_event (event_type),
  INDEX idx_sms_logs_status (status),
  INDEX idx_sms_logs_created_at (created_at),
  CONSTRAINT fk_sms_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE birthday_sms_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  sent_year SMALLINT UNSIGNED NOT NULL,
  sms_log_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_birthday_customer_year (customer_id, sent_year),
  CONSTRAINT fk_birthday_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_birthday_sms_log FOREIGN KEY (sms_log_id) REFERENCES sms_logs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  activity_type ENUM('login','logout','customer_create','customer_edit','purchase_create','wallet_reduction','operator_create','operator_edit','sms_sent','sms_failed','report_export') NOT NULL,
  description VARCHAR(500) NOT NULL,
  customer_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_activity_type (activity_type),
  INDEX idx_activity_created_at (created_at),
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_activity_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO sms_settings (
  id, api_token, sender_number, sms_enabled, purchase_sms_enabled, birthday_sms_enabled,
  wallet_reduction_sms_enabled, welcome_sms_enabled, purchase_template, birthday_template,
  wallet_reduction_template, welcome_template, updated_at
) VALUES (
  1, NULL, NULL, 0, 0, 0, 0, 0,
  'سلام {full_name}، خرید شما به مبلغ {purchase_amount} ثبت شد و مبلغ {cashback_amount} ریال کش‌بک به کیف پول شما اضافه شد. موجودی کیف پول: {wallet_balance} ریال',
  '{full_name} عزیز، تولدتان مبارک! از طرف {company_name} برای شما آرزوی سلامتی و شادی داریم.',
  'سلام {full_name}، مبلغ {purchase_amount} ریال از کیف پول شما کسر شد. موجودی جدید: {wallet_balance} ریال',
  'سلام {full_name} عزیز، عضویت شما در {company_name} ثبت شد.',
  NOW()
);
