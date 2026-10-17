-- Adds a dedicated activity-log type for hard-deleting a sales follow-up record.

ALTER TABLE activity_logs
  MODIFY COLUMN activity_type ENUM(
    'login','logout','customer_create','customer_edit','customer_delete','customer_anonymize','customer_import',
    'purchase_create','purchase_void','wallet_reduction','operator_create','operator_edit',
    'sms_sent','sms_failed','report_export','settings_update','service_create',
    'followup_create','followup_update','followup_won','followup_lost','reminder_seen','reminder_done',
    'due_date_create','due_date_update','due_date_delete','cashback_expiry','followup_delete'
  ) NOT NULL;
