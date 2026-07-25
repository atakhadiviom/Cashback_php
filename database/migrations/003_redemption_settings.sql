SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cashback_settings' AND COLUMN_NAME = 'min_redemption_amount'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE cashback_settings ADD COLUMN min_redemption_amount DECIMAL(15,2) NULL AFTER max_cashback_per_purchase', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cashback_settings' AND COLUMN_NAME = 'max_redemption_percent_of_purchase'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE cashback_settings ADD COLUMN max_redemption_percent_of_purchase DECIMAL(5,2) NULL AFTER min_redemption_amount', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cashback_settings' AND COLUMN_NAME = 'large_redemption_threshold'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE cashback_settings ADD COLUMN large_redemption_threshold DECIMAL(15,2) NULL AFTER max_redemption_percent_of_purchase', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
