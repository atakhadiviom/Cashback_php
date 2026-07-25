CREATE TABLE IF NOT EXISTS cashback_settings (
  id TINYINT UNSIGNED PRIMARY KEY,
  cashback_percent DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  min_purchase_amount DECIMAL(15,2) NULL,
  max_cashback_per_purchase DECIMAL(15,2) NULL,
  duplicate_purchase_window_minutes INT UNSIGNED NOT NULL DEFAULT 5,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO cashback_settings (id, cashback_percent, duplicate_purchase_window_minutes, updated_at)
VALUES (1, 5.00, 5, NOW());
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchases' AND COLUMN_NAME = 'cashback_percent_applied'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE purchases ADD COLUMN cashback_percent_applied DECIMAL(5,2) NOT NULL DEFAULT 5.00 AFTER cashback_amount', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchases' AND COLUMN_NAME = 'status'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE purchases ADD COLUMN status ENUM(\'active\',\'voided\') NOT NULL DEFAULT \'active\' AFTER cashback_percent_applied', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchases' AND COLUMN_NAME = 'invoice_ref'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE purchases ADD COLUMN invoice_ref VARCHAR(64) NULL AFTER status', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchases' AND COLUMN_NAME = 'void_reason'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE purchases ADD COLUMN void_reason VARCHAR(255) NULL AFTER invoice_ref', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
UPDATE purchases SET cashback_percent_applied = 5.00 WHERE cashback_percent_applied = 5.00;

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchases' AND INDEX_NAME = 'uq_purchases_invoice_ref'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'CREATE UNIQUE INDEX uq_purchases_invoice_ref ON purchases (invoice_ref)', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
