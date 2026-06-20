ALTER TABLE customers
  ADD COLUMN contract_number VARCHAR(64) NULL AFTER tier_id,
  ADD COLUMN contract_starts_at DATE NULL AFTER contract_number,
  ADD COLUMN contract_ends_at DATE NULL AFTER contract_starts_at,
  ADD UNIQUE KEY uq_customers_contract_number (contract_number),
  ADD INDEX idx_customers_contract_ends_at (contract_ends_at);

CREATE TABLE IF NOT EXISTS service_records (
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

CREATE TABLE IF NOT EXISTS contract_renewal_sms_history (
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

ALTER TABLE sms_settings
  ADD COLUMN service_sms_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER welcome_sms_enabled,
  ADD COLUMN contract_renewal_sms_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER service_sms_enabled,
  ADD COLUMN service_template TEXT NULL AFTER referral_template,
  ADD COLUMN contract_renewal_template TEXT NULL AFTER service_template;

UPDATE sms_settings SET service_template = 'سلام {full_name}، سرویس {service_type} شما در تاریخ {service_date} ثبت شد. مبلغ پرداختی: {paid_amount} ریال.' WHERE id = 1 AND service_template IS NULL;
UPDATE sms_settings SET contract_renewal_template = 'سلام {full_name}، قرارداد شماره {contract_number} شما تا {contract_ends_at} معتبر است. برای تمدید با ما تماس بگیرید.' WHERE id = 1 AND contract_renewal_template IS NULL;

ALTER TABLE sms_logs
  MODIFY COLUMN event_type ENUM('purchase','birthday','wallet_reduction','welcome','manual','otp','purchase_void','referral_bonus','service_confirmation','contract_renewal') NOT NULL;

ALTER TABLE activity_logs
  MODIFY COLUMN activity_type ENUM(
    'login','logout','customer_create','customer_edit','customer_delete','customer_anonymize','customer_import',
    'purchase_create','purchase_void','wallet_reduction','operator_create','operator_edit',
    'sms_sent','sms_failed','report_export','settings_update','service_create'
  ) NOT NULL;
