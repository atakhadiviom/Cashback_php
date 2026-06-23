CREATE TABLE IF NOT EXISTS followup_reminders (
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

ALTER TABLE activity_logs
  MODIFY COLUMN activity_type ENUM(
    'login','logout','customer_create','customer_edit','customer_delete','customer_anonymize','customer_import',
    'purchase_create','purchase_void','wallet_reduction','operator_create','operator_edit',
    'sms_sent','sms_failed','report_export','settings_update','service_create',
    'followup_create','followup_update','reminder_seen','reminder_done'
  ) NOT NULL;
