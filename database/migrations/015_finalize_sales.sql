ALTER TABLE customer_followups
  ADD COLUMN purchase_id BIGINT UNSIGNED NULL AFTER finalized_at,
  ADD CONSTRAINT fk_followups_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE SET NULL;

ALTER TABLE activity_logs
  MODIFY COLUMN activity_type ENUM(
    'login','logout','customer_create','customer_edit','customer_delete','customer_anonymize','customer_import',
    'purchase_create','purchase_void','wallet_reduction','operator_create','operator_edit',
    'sms_sent','sms_failed','report_export','settings_update','service_create',
    'followup_create','followup_update','followup_won','followup_lost',
    'reminder_seen','reminder_done'
  ) NOT NULL;
