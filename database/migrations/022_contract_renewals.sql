-- Contract renewals: a history of every contract renewal (who renewed, previous
-- dates, new dates) plus an activity-log type for it. The existing
-- contract_renewal cron then sends the reminder SMS 5 days before the new end date.

CREATE TABLE IF NOT EXISTS contract_renewals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  operator_id BIGINT UNSIGNED NOT NULL,
  previous_starts_at DATE NULL,
  previous_ends_at DATE NULL,
  new_starts_at DATE NULL,
  new_ends_at DATE NOT NULL,
  note VARCHAR(500) NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_contract_renewals_customer_date (customer_id, created_at),
  INDEX idx_contract_renewals_operator (operator_id),
  INDEX idx_contract_renewals_ends_at (new_ends_at),
  CONSTRAINT fk_contract_renewals_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_contract_renewals_operator FOREIGN KEY (operator_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE activity_logs
  MODIFY COLUMN activity_type ENUM(
    'login','logout','customer_create','customer_edit','customer_delete','customer_anonymize','customer_import',
    'purchase_create','purchase_void','wallet_reduction','operator_create','operator_edit',
    'sms_sent','sms_failed','report_export','settings_update','service_create',
    'followup_create','followup_update','followup_won','followup_lost','reminder_seen','reminder_done',
    'due_date_create','due_date_update','due_date_delete','cashback_expiry','followup_delete','contract_renewal'
  ) NOT NULL;
