SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'permissions'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE users ADD COLUMN permissions JSON NULL AFTER role', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
UPDATE users SET permissions = JSON_OBJECT(
  'purchase', true, 'reduce_wallet', true, 'export', true, 'void_purchase', true,
  'manage_settings', true, 'manage_users', true, 'import_customers', true, 'manage_api', true
) WHERE role = 'admin' AND permissions IS NULL;

UPDATE users SET permissions = JSON_OBJECT(
  'purchase', true, 'reduce_wallet', true, 'export', true, 'void_purchase', false,
  'manage_settings', false, 'manage_users', false, 'import_customers', false, 'manage_api', false
) WHERE role = 'operator' AND permissions IS NULL;
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'deleted_at'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customers ADD COLUMN deleted_at DATETIME NULL AFTER updated_at', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'referred_by_customer_id'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customers ADD COLUMN referred_by_customer_id BIGINT UNSIGNED NULL AFTER deleted_at', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'tier_id'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customers ADD COLUMN tier_id BIGINT UNSIGNED NULL AFTER referred_by_customer_id', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND INDEX_NAME = 'idx_customers_deleted_at'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'CREATE INDEX idx_customers_deleted_at ON customers (deleted_at)', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
