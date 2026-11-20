-- 028: club festivals (special-discount campaigns) and their recipients

CREATE TABLE IF NOT EXISTS club_festivals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(500) NULL,
  discount_text VARCHAR(200) NULL,
  discount_percent DECIMAL(5,2) NULL,
  discount_code VARCHAR(40) NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  send_at DATETIME NULL,
  audience VARCHAR(24) NOT NULL DEFAULT 'club_members',
  audience_value VARCHAR(64) NULL,
  template TEXT NOT NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'draft',
  sent_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_festival_status_send (status, send_at),
  INDEX idx_festival_dates (starts_at, ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS club_festival_recipients (
  festival_id BIGINT UNSIGNED NOT NULL,
  customer_id BIGINT UNSIGNED NOT NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'sent',
  sms_log_id BIGINT UNSIGNED NULL,
  sent_at DATETIME NOT NULL,
  PRIMARY KEY (festival_id, customer_id),
  INDEX idx_festival_recipients_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
