CREATE TABLE IF NOT EXISTS payment_due_dates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  purchase_id BIGINT UNSIGNED NULL,
  operator_id BIGINT UNSIGNED NOT NULL,
  due_date DATE NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  due_type ENUM('check','installment','invoice','other') NOT NULL DEFAULT 'other',
  reference_number VARCHAR(64) NULL,
  description TEXT NULL,
  status ENUM('pending','paid','overdue','cancelled') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_due_dates_date_status (due_date, status),
  INDEX idx_due_dates_customer (customer_id),
  INDEX idx_due_dates_reference (reference_number),
  INDEX idx_due_dates_purchase (purchase_id),
  INDEX idx_due_dates_type_status (due_type, status),
  CONSTRAINT fk_due_dates_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_due_dates_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE SET NULL,
  CONSTRAINT fk_due_dates_operator FOREIGN KEY (operator_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS due_date_sms_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  due_date_id BIGINT UNSIGNED NOT NULL,
  reminder_kind ENUM('created','before_3d','before_1d','on_day','after_overdue') NOT NULL,
  sms_log_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_due_date_sms_kind (due_date_id, reminder_kind),
  CONSTRAINT fk_due_date_sms_due FOREIGN KEY (due_date_id) REFERENCES payment_due_dates(id) ON DELETE CASCADE,
  CONSTRAINT fk_due_date_sms_log FOREIGN KEY (sms_log_id) REFERENCES sms_logs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE sms_settings
  ADD COLUMN due_date_sms_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER contract_renewal_sms_enabled,
  ADD COLUMN due_date_reminder_sms_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER due_date_sms_enabled,
  ADD COLUMN due_date_template TEXT NULL AFTER contract_renewal_template,
  ADD COLUMN due_date_reminder_template TEXT NULL AFTER due_date_template;

UPDATE sms_settings SET due_date_template = 'آقای/خانم {full_name}
با سلام، سررسید پرداخت شما به مبلغ {due_amount} در تاریخ {due_date} می‌باشد. لطفاً در موعد مقرر نسبت به پرداخت اقدام فرمایید.
با تشکر
{company_name}' WHERE id = 1 AND due_date_template IS NULL;

UPDATE sms_settings SET due_date_reminder_template = 'آقای/خانم {full_name}
یادآوری: سررسید پرداخت شما به مبلغ {due_amount} در تاریخ {due_date} می‌باشد. لطفاً در موعد مقرر نسبت به پرداخت اقدام فرمایید.
با تشکر
{company_name}' WHERE id = 1 AND due_date_reminder_template IS NULL;

ALTER TABLE sms_logs
  MODIFY COLUMN event_type ENUM(
    'purchase','birthday','wallet_reduction','welcome','manual','otp','purchase_void','referral_bonus',
    'service_confirmation','contract_renewal','due_date','due_date_reminder'
  ) NOT NULL;

ALTER TABLE activity_logs
  MODIFY COLUMN activity_type ENUM(
    'login','logout','customer_create','customer_edit','customer_delete','customer_anonymize','customer_import',
    'purchase_create','purchase_void','wallet_reduction','operator_create','operator_edit',
    'sms_sent','sms_failed','report_export','settings_update','service_create',
    'followup_create','followup_update','followup_won','followup_lost','reminder_seen','reminder_done',
    'due_date_create','due_date_update','due_date_delete'
  ) NOT NULL;
