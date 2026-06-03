CREATE TABLE IF NOT EXISTS customer_tiers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  min_lifetime_spend DECIMAL(15,2) NOT NULL DEFAULT 0,
  cashback_percent DECIMAL(5,2) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO customer_tiers (id, name, min_lifetime_spend, cashback_percent, sort_order, created_at)
VALUES (1, 'عادی', 0, 5.00, 0, NOW());

CREATE TABLE IF NOT EXISTS promotions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  percent_bonus DECIMAL(5,2) NOT NULL DEFAULT 0,
  fixed_bonus DECIMAL(15,2) NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE purchases
  ADD COLUMN promotion_id BIGINT UNSIGNED NULL AFTER void_reason;

ALTER TABLE cashback_settings
  ADD COLUMN birthday_bonus_amount DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER large_redemption_threshold,
  ADD COLUMN referral_bonus_amount DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER birthday_bonus_amount;

ALTER TABLE birthday_sms_history
  ADD COLUMN bonus_credited TINYINT(1) NOT NULL DEFAULT 0 AFTER sms_log_id;

CREATE TABLE IF NOT EXISTS customer_tags (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_tag_assignments (
  customer_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (customer_id, tag_id),
  CONSTRAINT fk_cta_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_cta_tag FOREIGN KEY (tag_id) REFERENCES customer_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
