CREATE TABLE IF NOT EXISTS api_keys (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  key_hash VARCHAR(255) NOT NULL,
  key_prefix VARCHAR(12) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_used_at DATETIME NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_api_keys_user FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE purchases
  ADD COLUMN idempotency_key VARCHAR(64) NULL AFTER promotion_id;

CREATE UNIQUE INDEX uq_purchases_idempotency ON purchases (idempotency_key);
