-- 027: per-key API scopes (read,write). Keys created before this migration
-- behave as "read,write" because the column default covers them.

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'api_keys' AND COLUMN_NAME = 'scopes'
);
SET @__cb_sql := IF(
  @__cb_exists = 0,
  'ALTER TABLE api_keys ADD COLUMN scopes VARCHAR(64) NOT NULL DEFAULT ''read,write'' AFTER is_active',
  'DO 0'
);
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
