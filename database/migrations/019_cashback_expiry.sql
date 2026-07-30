-- Cashback expiry (FIFO lots) + expiry warning SMS.
-- Each cashback credit becomes a "lot" with its own expiry date. Redemptions consume
-- the oldest lots first, so only genuinely unspent cashback ever expires.

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cashback_settings' AND COLUMN_NAME = 'cashback_expiry_months'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE cashback_settings ADD COLUMN cashback_expiry_months INT UNSIGNED NOT NULL DEFAULT 12 AFTER duplicate_purchase_window_minutes', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cashback_settings' AND COLUMN_NAME = 'cashback_expiry_warning_days'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE cashback_settings ADD COLUMN cashback_expiry_warning_days INT UNSIGNED NOT NULL DEFAULT 30 AFTER cashback_expiry_months', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;

CREATE TABLE IF NOT EXISTS cashback_lots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  wallet_transaction_id BIGINT UNSIGNED NULL,
  purchase_id BIGINT UNSIGNED NULL,
  source ENUM('purchase','birthday','referral','opening','manual') NOT NULL DEFAULT 'purchase',
  amount DECIMAL(15,2) NOT NULL,
  consumed_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
  expired_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
  credited_at DATETIME NOT NULL,
  expires_at DATETIME NULL,
  warned_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_cashback_lots_customer (customer_id, credited_at),
  INDEX idx_cashback_lots_expires (expires_at),
  INDEX idx_cashback_lots_warned (warned_at, expires_at),
  CONSTRAINT fk_cashback_lots_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_cashback_lots_wallet FOREIGN KEY (wallet_transaction_id) REFERENCES wallet_transactions(id) ON DELETE SET NULL,
  CONSTRAINT fk_cashback_lots_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cashback_expiry_sms_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  expiry_month CHAR(7) NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  sms_log_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_cashback_expiry_sms (customer_id, expiry_month),
  CONSTRAINT fk_cashback_expiry_sms_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_cashback_expiry_sms_log FOREIGN KEY (sms_log_id) REFERENCES sms_logs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE wallet_transactions
  MODIFY COLUMN type ENUM('cashback','reduction','reversal','expiry') NOT NULL;

ALTER TABLE sms_logs
  MODIFY COLUMN event_type ENUM(
    'purchase','birthday','wallet_reduction','welcome','manual','otp','purchase_void','referral_bonus',
    'service_confirmation','contract_renewal','due_date','due_date_reminder','cashback_expiry'
  ) NOT NULL;

ALTER TABLE activity_logs
  MODIFY COLUMN activity_type ENUM(
    'login','logout','customer_create','customer_edit','customer_delete','customer_anonymize','customer_import',
    'purchase_create','purchase_void','wallet_reduction','operator_create','operator_edit',
    'sms_sent','sms_failed','report_export','settings_update','service_create',
    'followup_create','followup_update','followup_won','followup_lost','reminder_seen','reminder_done',
    'due_date_create','due_date_update','due_date_delete','cashback_expiry'
  ) NOT NULL;

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sms_settings' AND COLUMN_NAME = 'cashback_expiry_sms_enabled'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE sms_settings ADD COLUMN cashback_expiry_sms_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER due_date_reminder_sms_enabled', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sms_settings' AND COLUMN_NAME = 'cashback_expiry_template'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE sms_settings ADD COLUMN cashback_expiry_template TEXT NULL AFTER due_date_reminder_template', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;

UPDATE sms_settings SET cashback_expiry_template = 'آقای/خانم {full_name}
مبلغ {expiring_amount} ریال از کش‌بک شما در تاریخ {expiry_date} منقضی می‌شود. موجودی فعلی کیف پول: {wallet_balance} ریال. لطفاً پیش از این تاریخ از آن استفاده کنید.
با تشکر
{company_name}' WHERE id = 1 AND cashback_expiry_template IS NULL;

-- Opening lot for balances credited before this feature: the clock starts now, so every
-- existing customer gets the full expiry period (plus the warning SMS) before anything expires.
SET @__cb_months := (SELECT cashback_expiry_months FROM cashback_settings WHERE id = 1);
SET @__cb_months := IFNULL(@__cb_months, 12);

INSERT INTO cashback_lots
  (customer_id, source, amount, consumed_amount, expired_amount, credited_at, expires_at, created_at, updated_at)
SELECT
  c.id, 'opening', c.wallet_balance, 0, 0, NOW(),
  IF(@__cb_months > 0, DATE_ADD(NOW(), INTERVAL @__cb_months MONTH), NULL),
  NOW(), NOW()
FROM customers c
WHERE c.wallet_balance > 0
  AND NOT EXISTS (SELECT 1 FROM cashback_lots l WHERE l.customer_id = c.id);
