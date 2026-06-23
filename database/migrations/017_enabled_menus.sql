ALTER TABLE cashback_settings
  ADD COLUMN enabled_menus TEXT NULL AFTER duplicate_purchase_window_minutes;

-- Default: enable all menus (empty or null means all enabled)
UPDATE cashback_settings SET enabled_menus = NULL WHERE id = 1;
