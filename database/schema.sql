SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS customer_tag_assignments;
DROP TABLE IF EXISTS customer_tags;
DROP TABLE IF EXISTS api_keys;
DROP TABLE IF EXISTS otp_codes;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS promotions;
DROP TABLE IF EXISTS followup_reminders;
DROP TABLE IF EXISTS customer_followups;
DROP TABLE IF EXISTS customer_tiers;
DROP TABLE IF EXISTS schema_migrations;
DROP TABLE IF EXISTS contract_renewal_sms_history;
DROP TABLE IF EXISTS service_records;
DROP TABLE IF EXISTS birthday_sms_history;
DROP TABLE IF EXISTS sms_logs;
DROP TABLE IF EXISTS sms_settings;
DROP TABLE IF EXISTS cashback_settings;
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
  permissions JSON NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_tiers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  min_lifetime_spend DECIMAL(15,2) NOT NULL DEFAULT 0,
  cashback_percent DECIMAL(5,2) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  company VARCHAR(150) NULL,
  national_code VARCHAR(11) NULL UNIQUE,
  phone_number CHAR(11) NOT NULL,
  email VARCHAR(150) NULL,
  address TEXT NULL,
  description TEXT NULL,
  birthday DATE NULL,
  wallet_balance DECIMAL(15,2) NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  deleted_at DATETIME NULL,
  referred_by_customer_id BIGINT UNSIGNED NULL,
  tier_id BIGINT UNSIGNED NULL,
  contract_number VARCHAR(64) NULL,
  contract_starts_at DATE NULL,
  contract_ends_at DATE NULL,
  UNIQUE KEY uq_customers_contract_number (contract_number),
  INDEX idx_customers_name (last_name, first_name),
  INDEX idx_customers_company (company),
  INDEX idx_customers_phone (phone_number),
  INDEX idx_customers_email (email),
  INDEX idx_customers_birthday (birthday),
  INDEX idx_customers_deleted_at (deleted_at),
  INDEX idx_customers_contract_ends_at (contract_ends_at),
  CONSTRAINT fk_customers_created_by FOREIGN KEY (created_by) REFERENCES users(id),
  CONSTRAINT fk_customers_referred_by FOREIGN KEY (referred_by_customer_id) REFERENCES customers(id) ON DELETE SET NULL,
  CONSTRAINT fk_customers_tier FOREIGN KEY (tier_id) REFERENCES customer_tiers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_followups (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  operator_id BIGINT UNSIGNED NOT NULL,
  followup_date DATETIME NOT NULL,
  pre_invoice_amount DECIMAL(15,2) NULL,
  invoice_amount DECIMAL(15,2) NULL,
  sales_status ENUM('negotiating','pre_invoice_sent','waiting_customer','callback','won','lost') NOT NULL DEFAULT 'negotiating',
  conversation_notes TEXT NOT NULL,
  next_contact_date DATE NULL,
  reminder_time TIME NULL,
  attachment_path VARCHAR(255) NULL,
  final_result ENUM('won','lost') NULL,
  finalized_sale_amount DECIMAL(15,2) NULL,
  finalized_at DATETIME NULL,
  purchase_id BIGINT UNSIGNED NULL,
  lost_reason TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_followups_customer_date (customer_id, followup_date),
  INDEX idx_followups_operator_date (operator_id, followup_date),
  INDEX idx_followups_sales_status (sales_status),
  INDEX idx_followups_next_contact (next_contact_date, reminder_time),
  CONSTRAINT fk_followups_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_followups_operator FOREIGN KEY (operator_id) REFERENCES users(id),
  CONSTRAINT fk_followups_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE followup_reminders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  followup_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  operator_id BIGINT UNSIGNED NOT NULL,
  remind_at DATETIME NOT NULL,
  status ENUM('pending','seen','done','missed') NOT NULL DEFAULT 'pending',
  seen_at DATETIME NULL,
  done_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_followup_reminders_followup (followup_id),
  INDEX idx_reminders_operator_status_time (operator_id, status, remind_at),
  INDEX idx_reminders_status_time (status, remind_at),
  CONSTRAINT fk_reminders_followup FOREIGN KEY (followup_id) REFERENCES customer_followups(id) ON DELETE CASCADE,
  CONSTRAINT fk_reminders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_reminders_operator FOREIGN KEY (operator_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE promotions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  percent_bonus DECIMAL(5,2) NOT NULL DEFAULT 0,
  fixed_bonus DECIMAL(15,2) NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchases (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  cashback_amount DECIMAL(15,2) NOT NULL,
  cashback_percent_applied DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  status ENUM('active','voided') NOT NULL DEFAULT 'active',
  invoice_ref VARCHAR(64) NULL,
  void_reason VARCHAR(255) NULL,
  promotion_id BIGINT UNSIGNED NULL,
  idempotency_key VARCHAR(64) NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_purchases_invoice_ref (invoice_ref),
  UNIQUE KEY uq_purchases_idempotency (idempotency_key),
  INDEX idx_purchases_customer_date (customer_id, created_at),
  INDEX idx_purchases_created_at (created_at),
  INDEX idx_purchases_status (status),
  CONSTRAINT fk_purchases_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_purchases_created_by FOREIGN KEY (created_by) REFERENCES users(id),
  CONSTRAINT fk_purchases_promotion FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wallet_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  type ENUM('cashback','reduction','reversal') NOT NULL,
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

CREATE TABLE cashback_settings (
  id TINYINT UNSIGNED PRIMARY KEY,
  cashback_percent DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  min_purchase_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
  max_cashback_per_purchase DECIMAL(15,2) NULL,
  min_redemption_amount DECIMAL(15,2) NULL,
  max_redemption_percent_of_purchase DECIMAL(5,2) NULL,
  large_redemption_threshold DECIMAL(15,2) NULL,
  birthday_bonus_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
  referral_bonus_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
  duplicate_purchase_window_minutes INT UNSIGNED NOT NULL DEFAULT 5,
  updated_at DATETIME NOT NULL
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
  service_sms_enabled TINYINT(1) NOT NULL DEFAULT 0,
  contract_renewal_sms_enabled TINYINT(1) NOT NULL DEFAULT 0,
  purchase_template TEXT NOT NULL,
  birthday_template TEXT NOT NULL,
  wallet_reduction_template TEXT NOT NULL,
  welcome_template TEXT NOT NULL,
  otp_template TEXT NULL,
  purchase_void_template TEXT NULL,
  referral_template TEXT NULL,
  service_template TEXT NULL,
  contract_renewal_template TEXT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sms_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NULL,
  phone_number VARCHAR(20) NOT NULL,
  event_type ENUM('purchase','birthday','wallet_reduction','welcome','manual','otp','purchase_void','referral_bonus','service_confirmation','contract_renewal') NOT NULL,
  message TEXT NOT NULL,
  provider VARCHAR(50) NOT NULL DEFAULT 'ippanel',
  provider_response TEXT NULL,
  status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  retry_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  next_retry_at DATETIME NULL,
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_sms_logs_event (event_type),
  INDEX idx_sms_logs_status (status),
  INDEX idx_sms_logs_created_at (created_at),
  CONSTRAINT fk_sms_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE service_records (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  technician_id BIGINT UNSIGNED NOT NULL,
  service_date DATE NOT NULL,
  service_type ENUM('periodic','repair','inspection','other') NOT NULL,
  description TEXT NULL,
  paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
  payment_status ENUM('unpaid','paid') NOT NULL,
  sms_log_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_service_records_customer_date (customer_id, service_date),
  INDEX idx_service_records_technician (technician_id),
  INDEX idx_service_records_service_date (service_date),
  INDEX idx_service_records_payment_status (payment_status),
  CONSTRAINT fk_service_records_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_service_records_technician FOREIGN KEY (technician_id) REFERENCES users(id),
  CONSTRAINT fk_service_records_sms_log FOREIGN KEY (sms_log_id) REFERENCES sms_logs(id) ON DELETE SET NULL,
  CONSTRAINT fk_service_records_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contract_renewal_sms_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  contract_ends_at DATE NOT NULL,
  reminder_days SMALLINT UNSIGNED NOT NULL,
  sms_log_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_contract_renewal_reminder (customer_id, contract_ends_at, reminder_days),
  CONSTRAINT fk_contract_renewal_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_contract_renewal_sms_log FOREIGN KEY (sms_log_id) REFERENCES sms_logs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE birthday_sms_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  sent_year SMALLINT UNSIGNED NOT NULL,
  sms_log_id BIGINT UNSIGNED NULL,
  bonus_credited TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_birthday_customer_year (customer_id, sent_year),
  CONSTRAINT fk_birthday_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_birthday_sms_log FOREIGN KEY (sms_log_id) REFERENCES sms_logs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  activity_type ENUM(
    'login','logout','customer_create','customer_edit','customer_delete','customer_anonymize','customer_import',
    'purchase_create','purchase_void','wallet_reduction','operator_create','operator_edit',
    'sms_sent','sms_failed','report_export','settings_update','service_create',
    'followup_create','followup_update','followup_won','followup_lost','reminder_seen','reminder_done'
  ) NOT NULL,
  description VARCHAR(500) NOT NULL,
  customer_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_activity_type (activity_type),
  INDEX idx_activity_created_at (created_at),
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_activity_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE otp_codes (
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

CREATE TABLE login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL,
  ip_address VARCHAR(45) NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  INDEX idx_login_username_time (username, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_tags (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_tag_assignments (
  customer_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (customer_id, tag_id),
  CONSTRAINT fk_cta_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_cta_tag FOREIGN KEY (tag_id) REFERENCES customer_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_keys (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  key_hash VARCHAR(255) NOT NULL,
  key_prefix VARCHAR(12) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_used_at DATETIME NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_api_keys_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE schema_migrations (
  version VARCHAR(64) PRIMARY KEY,
  applied_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO customer_tiers (id, name, min_lifetime_spend, cashback_percent, sort_order, created_at)
VALUES (1, 'عادی', 0, 5.00, 0, NOW());

INSERT INTO cashback_settings (
  id, cashback_percent, duplicate_purchase_window_minutes, birthday_bonus_amount, referral_bonus_amount, updated_at
) VALUES (1, 5.00, 5, 0, 0, NOW());

INSERT INTO sms_settings (
  id, api_token, sender_number, sms_enabled, purchase_sms_enabled, birthday_sms_enabled,
  wallet_reduction_sms_enabled, welcome_sms_enabled, service_sms_enabled, contract_renewal_sms_enabled,
  purchase_template, birthday_template,
  wallet_reduction_template, welcome_template, otp_template, purchase_void_template, referral_template,
  service_template, contract_renewal_template, updated_at
) VALUES (
  1, NULL, NULL, 0, 0, 0, 0, 0, 0, 0,
  'سلام {full_name}، خرید شما به مبلغ {purchase_amount} ثبت شد و مبلغ {cashback_amount} ریال کش‌بک به کیف پول شما اضافه شد. موجودی کیف پول: {wallet_balance} ریال',
  '{full_name} عزیز، تولدتان مبارک! از طرف {company_name} برای شما آرزوی سلامتی و شادی داریم.',
  'سلام {full_name}، مبلغ {purchase_amount} ریال از کیف پول شما کسر شد. موجودی جدید: {wallet_balance} ریال',
  'سلام {full_name} عزیز، عضویت شما در {company_name} ثبت شد.',
  'کد ورود به پرتال کش‌بک {company_name}: {otp_code}',
  'سلام {full_name}، خرید شما به مبلغ {purchase_amount} ابطال شد و مبلغ {cashback_amount} ریال از کیف پول کسر شد.',
  'سلام {full_name}، مبلغ {cashback_amount} ریال بابت معرفی مشتری جدید به کیف پول شما اضافه شد.',
  'سلام {full_name}، سرویس {service_type} شما در تاریخ {service_date} ثبت شد. مبلغ پرداختی: {paid_amount} ریال.',
  'سلام {full_name}، قرارداد شماره {contract_number} شما تا {contract_ends_at} معتبر است. برای تمدید با ما تماس بگیرید.',
  NOW()
);
