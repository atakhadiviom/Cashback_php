SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cashback_settings' AND COLUMN_NAME = 'enabled_menus'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE cashback_settings ADD COLUMN enabled_menus TEXT NULL AFTER duplicate_purchase_window_minutes', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
UPDATE cashback_settings SET enabled_menus = NULL WHERE id = 1;
