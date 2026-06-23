ALTER TABLE customers
  ADD COLUMN company VARCHAR(150) NULL AFTER last_name,
  ADD COLUMN email VARCHAR(150) NULL AFTER phone_number,
  ADD COLUMN address TEXT NULL AFTER email,
  ADD COLUMN description TEXT NULL AFTER address,
  ADD INDEX idx_customers_company (company),
  ADD INDEX idx_customers_email (email);

CREATE TABLE IF NOT EXISTS customer_followups (
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
  lost_reason TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_followups_customer_date (customer_id, followup_date),
  INDEX idx_followups_operator_date (operator_id, followup_date),
  INDEX idx_followups_sales_status (sales_status),
  INDEX idx_followups_next_contact (next_contact_date, reminder_time),
  CONSTRAINT fk_followups_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_followups_operator FOREIGN KEY (operator_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE activity_logs
  MODIFY COLUMN activity_type ENUM(
    'login','logout','customer_create','customer_edit','customer_delete','customer_anonymize','customer_import',
    'purchase_create','purchase_void','wallet_reduction','operator_create','operator_edit',
    'sms_sent','sms_failed','report_export','settings_update','service_create',
    'followup_create','followup_update'
  ) NOT NULL;
