-- 075: sales lead fields on customer follow-ups

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer_followups' AND COLUMN_NAME = 'lead_source'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customer_followups ADD COLUMN lead_source VARCHAR(24) NULL', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer_followups' AND COLUMN_NAME = 'lead_score'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customer_followups ADD COLUMN lead_score TINYINT UNSIGNED NULL', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer_followups' AND COLUMN_NAME = 'lead_probability'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customer_followups ADD COLUMN lead_probability TINYINT UNSIGNED NULL', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer_followups' AND COLUMN_NAME = 'expected_close_date'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customer_followups ADD COLUMN expected_close_date DATE NULL', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
