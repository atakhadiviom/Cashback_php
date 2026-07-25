-- Installs created from database/schema.sql already have the full schema but an empty
-- schema_migrations table. Mark matching migrations as applied so later ALTER/ADD
-- statements are not re-executed during app updates.

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '001_cashback_settings.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'purchases'
    AND COLUMN_NAME = 'cashback_percent_applied'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '002_void_reversal.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'purchases'
    AND COLUMN_NAME = 'cashback_percent_applied'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '003_redemption_settings.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cashback_settings'
    AND COLUMN_NAME = 'min_redemption_amount'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '004_permissions_soft_delete.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'permissions'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '005_otp_portal.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'otp_codes'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '006_sms_retry.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'sms_logs'
    AND COLUMN_NAME = 'retry_count'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '007_loyalty.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'promotions'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '008_api_keys.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'api_keys'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '009_company_national_code_length.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'customers'
    AND COLUMN_NAME = 'national_code'
    AND CHARACTER_MAXIMUM_LENGTH >= 11
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '010_optional_customer_identifier.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'customers'
    AND COLUMN_NAME = 'national_code'
    AND IS_NULLABLE = 'YES'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '011_cashback_settings_defaults.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cashback_settings'
    AND COLUMN_NAME = 'min_purchase_amount'
    AND IS_NULLABLE = 'NO'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '012_elevator_services.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'service_records'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '013_crm_phase1.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'customer_followups'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '014_followup_reminders.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'followup_reminders'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '015_finalize_sales.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'customer_followups'
    AND COLUMN_NAME = 'purchase_id'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '016_customer_tier_ranges.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'customer_tiers'
    AND COLUMN_NAME = 'max_lifetime_spend'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '017_enabled_menus.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'cashback_settings'
    AND COLUMN_NAME = 'enabled_menus'
);

INSERT IGNORE INTO schema_migrations (version, applied_at)
SELECT '018_payment_due_dates.sql', NOW() FROM DUAL
WHERE EXISTS (
  SELECT 1 FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'payment_due_dates'
);
