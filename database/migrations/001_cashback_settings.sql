CREATE TABLE IF NOT EXISTS cashback_settings (
  id TINYINT UNSIGNED PRIMARY KEY,
  cashback_percent DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  min_purchase_amount DECIMAL(15,2) NULL,
  max_cashback_per_purchase DECIMAL(15,2) NULL,
  duplicate_purchase_window_minutes INT UNSIGNED NOT NULL DEFAULT 5,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO cashback_settings (id, cashback_percent, duplicate_purchase_window_minutes, updated_at)
VALUES (1, 5.00, 5, NOW());

ALTER TABLE purchases
  ADD COLUMN cashback_percent_applied DECIMAL(5,2) NOT NULL DEFAULT 5.00 AFTER cashback_amount,
  ADD COLUMN status ENUM('active','voided') NOT NULL DEFAULT 'active' AFTER cashback_percent_applied,
  ADD COLUMN invoice_ref VARCHAR(64) NULL AFTER status,
  ADD COLUMN void_reason VARCHAR(255) NULL AFTER invoice_ref;

UPDATE purchases SET cashback_percent_applied = 5.00 WHERE cashback_percent_applied = 5.00;

CREATE UNIQUE INDEX uq_purchases_invoice_ref ON purchases (invoice_ref);
