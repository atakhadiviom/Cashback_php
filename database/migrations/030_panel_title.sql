-- 030: editable panel/system title (shown in the sidebar, page title and login page)

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cashback_settings' AND COLUMN_NAME = 'panel_title'
);
SET @__cb_sql := IF(
  @__cb_exists = 0,
  'ALTER TABLE cashback_settings ADD COLUMN panel_title VARCHAR(150) NULL AFTER default_price_type',
  'DO 0'
);
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;

UPDATE cashback_settings
   SET panel_title = 'سامانه پیگیری فروش و باشگاه مشتریان و کش بک'
 WHERE id = 1
   AND (panel_title IS NULL OR panel_title = '');
