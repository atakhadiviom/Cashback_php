SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'company'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customers ADD COLUMN company VARCHAR(150) NULL AFTER last_name', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'email'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customers ADD COLUMN email VARCHAR(150) NULL AFTER phone_number', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'address'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customers ADD COLUMN address TEXT NULL AFTER email', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'description'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customers ADD COLUMN description TEXT NULL AFTER address', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND INDEX_NAME = 'idx_customers_company'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customers ADD INDEX idx_customers_company (company)', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND INDEX_NAME = 'idx_customers_email'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customers ADD INDEX idx_customers_email (email)', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
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
