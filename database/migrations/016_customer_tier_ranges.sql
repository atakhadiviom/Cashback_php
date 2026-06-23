ALTER TABLE customer_tiers
  ADD COLUMN max_lifetime_spend DECIMAL(15,2) NULL AFTER min_lifetime_spend,
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER cashback_percent;

UPDATE customer_tiers SET name = 'برنزی', min_lifetime_spend = 0, max_lifetime_spend = 50000000 WHERE id = 1;

INSERT INTO customer_tiers (name, min_lifetime_spend, max_lifetime_spend, cashback_percent, sort_order, created_at, is_active)
VALUES 
('نقره‌ای', 50000000, 150000000, 7.00, 1, NOW(), 1),
('طلایی', 150000000, NULL, 10.00, 2, NOW(), 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

ALTER TABLE activity_logs
  MODIFY COLUMN activity_type ENUM(
    'login','logout','customer_create','customer_edit','customer_delete','customer_anonymize','customer_import',
    'purchase_create','purchase_void','wallet_reduction','operator_create','operator_edit',
    'sms_sent','sms_failed','report_export','settings_update','service_create',
    'followup_create','followup_update','followup_won','followup_lost',
    'reminder_seen','reminder_done','customer_tier_update'
  ) NOT NULL;
