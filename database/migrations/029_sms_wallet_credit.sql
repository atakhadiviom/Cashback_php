-- 029: SMS event for wallet top-ups (شارژ کیف پول)

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sms_settings' AND COLUMN_NAME = 'wallet_credit_sms_enabled'
);
SET @__cb_sql := IF(
  @__cb_exists = 0,
  'ALTER TABLE sms_settings ADD COLUMN wallet_credit_sms_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER wallet_reduction_sms_enabled',
  'DO 0'
);
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sms_settings' AND COLUMN_NAME = 'wallet_credit_template'
);
SET @__cb_sql := IF(
  @__cb_exists = 0,
  'ALTER TABLE sms_settings ADD COLUMN wallet_credit_template TEXT NULL AFTER wallet_reduction_template',
  'DO 0'
);
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;

UPDATE sms_settings
   SET wallet_credit_template = '{full_name} عزیز، مبلغ {wallet_balance} ریال به کیف پول شما اضافه شد. {company_name}'
 WHERE id = 1
   AND (wallet_credit_template IS NULL OR wallet_credit_template = '');
