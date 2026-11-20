-- 073: customer club core (tier history, RFM segments, club options)

CREATE TABLE IF NOT EXISTS customer_tier_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  old_tier_id BIGINT UNSIGNED NULL,
  new_tier_id BIGINT UNSIGNED NULL,
  basis VARCHAR(16) NOT NULL DEFAULT 'lifetime',
  spend DECIMAL(15,2) NOT NULL DEFAULT 0,
  reason VARCHAR(32) NOT NULL DEFAULT 'recalc',
  actor_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_cth_customer (customer_id, created_at),
  INDEX idx_cth_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_segments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(32) NOT NULL,
  name VARCHAR(64) NOT NULL,
  color VARCHAR(16) NOT NULL DEFAULT 'secondary',
  description VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_customer_segments_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO customer_segments (code, name, color, description, sort_order, is_active) VALUES
  ('champions', 'قهرمانان', 'success', 'خرید تازه، پرتکرار و پرمبلغ', 10, 1),
  ('cant_lose', 'نباید از دست برود', 'danger', 'سابقاً پرمصرف اما مدتی است خرید نکرده', 20, 1),
  ('loyal', 'وفادار', 'primary', 'خرید منظم و تکرارشونده', 30, 1),
  ('at_risk', 'در خطر', 'warning', 'پرتکرار بوده اما اخیراً فاصله افتاده', 40, 1),
  ('potential', 'بالقوه وفادار', 'info', 'مبلغ خرید بالا و خرید نسبتاً تازه', 50, 1),
  ('new', 'جدید', 'secondary', 'اولین خرید تازه', 60, 1),
  ('promising', 'امیدبخش', 'info', 'خرید تازه با تکرار کم', 70, 1),
  ('needs_attention', 'نیازمند توجه', 'warning', 'فاصله خرید در حال افزایش', 80, 1),
  ('hibernating', 'خواب‌رفته', 'secondary', 'مدت‌هاست خرید نکرده', 90, 1),
  ('lost', 'از دست رفته', 'dark', 'بدون خرید در بازه طولانی', 100, 1);

CREATE TABLE IF NOT EXISTS customer_segment_members (
  customer_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
  segment_code VARCHAR(32) NOT NULL,
  r_score TINYINT UNSIGNED NOT NULL DEFAULT 1,
  f_score TINYINT UNSIGNED NOT NULL DEFAULT 1,
  m_score TINYINT UNSIGNED NOT NULL DEFAULT 1,
  rfm_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  recency_days INT NULL,
  frequency INT NOT NULL DEFAULT 0,
  monetary DECIMAL(15,2) NOT NULL DEFAULT 0,
  computed_at DATETIME NOT NULL,
  INDEX idx_csm_segment (segment_code),
  INDEX idx_csm_rfm (rfm_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'tier_changed_at'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE customers ADD COLUMN tier_changed_at DATETIME NULL', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cashback_settings' AND COLUMN_NAME = 'club_enabled'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE cashback_settings ADD COLUMN club_enabled TINYINT(1) NOT NULL DEFAULT 1', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cashback_settings' AND COLUMN_NAME = 'club_options'
);
SET @__cb_sql := IF(@__cb_exists = 0, 'ALTER TABLE cashback_settings ADD COLUMN club_options JSON NULL', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
