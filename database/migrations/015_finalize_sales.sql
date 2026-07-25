SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer_followups' AND COLUMN_NAME = 'purchase_id'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customer_followups ADD COLUMN purchase_id BIGINT UNSIGNED NULL AFTER finalized_at', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer_followups' AND CONSTRAINT_NAME = 'fk_followups_purchase'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customer_followups ADD CONSTRAINT fk_followups_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE SET NULL', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
ALTER TABLE activity_logs
  MODIFY COLUMN activity_type ENUM(
    'login','logout','customer_create','customer_edit','customer_delete','customer_anonymize','customer_import',
    'purchase_create','purchase_void','wallet_reduction','operator_create','operator_edit',
    'sms_sent','sms_failed','report_export','settings_update','service_create',
    'followup_create','followup_update','followup_won','followup_lost',
    'reminder_seen','reminder_done'
  ) NOT NULL;
