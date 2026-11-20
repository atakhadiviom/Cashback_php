-- 074: scheduled club reminders (rules + outbox) and flexible sms event types

CREATE TABLE IF NOT EXISTS club_reminder_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kind VARCHAR(32) NOT NULL,
  label VARCHAR(80) NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 0,
  direction VARCHAR(8) NOT NULL DEFAULT 'after',
  offset_days INT NOT NULL DEFAULT 0,
  time_of_day TIME NOT NULL DEFAULT '10:00:00',
  template TEXT NOT NULL,
  conditions JSON NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_crr_kind (kind),
  INDEX idx_crr_enabled (enabled, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS club_reminders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  rule_id BIGINT UNSIGNED NOT NULL,
  kind VARCHAR(32) NOT NULL,
  due_at DATETIME NOT NULL,
  status VARCHAR(16) NOT NULL DEFAULT 'pending',
  sent_at DATETIME NULL,
  sms_log_id BIGINT UNSIGNED NULL,
  message VARCHAR(500) NULL,
  meta JSON NULL,
  dedupe_key VARCHAR(160) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_club_reminders_dedupe (dedupe_key),
  INDEX idx_cr_due (status, due_at),
  INDEX idx_cr_customer (customer_id, due_at),
  INDEX idx_cr_kind (kind, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO club_reminder_rules (id, kind, label, enabled, direction, offset_days, time_of_day, template, conditions, sort_order, created_at, updated_at) VALUES
  (1, 'birthday', 'تبریک تولد (۳ روز قبل)', 0, 'before', 3, '10:00:00', '{full_name} عزیز، تولدتان نزدیک است؛ هدیه باشگاه مشتریان منتظر شماست.', NULL, 10, NOW(), NOW()),
  (2, 'winback', 'بازگشت مشتری (۶۰ روز بی‌خرید)', 0, 'after', 60, '11:00:00', '{full_name} عزیز، دلمان برایتان تنگ شده؛ امتیاز شما هنوز در کیف پولتان است.', NULL, 20, NOW(), NOW()),
  (3, 'winback', 'بازگشت مشتری (۱۲۰ روز بی‌خرید)', 0, 'after', 120, '11:00:00', '{full_name} عزیز، {wallet_balance} ریال اعتبار شما در انتظار استفاده است.', NULL, 30, NOW(), NOW()),
  (4, 'points_expiry', 'هشدار انقضای امتیاز (۷ روز قبل)', 0, 'before', 7, '09:00:00', '{full_name} عزیز، {expiring_amount} ریال از اعتبار شما تا {expiry_date} منقضی می‌شود.', NULL, 40, NOW(), NOW()),
  (5, 'service_due', 'یادآوری سرویس دوره‌ای (۳۰ روز بعد)', 0, 'after', 30, '10:00:00', '{full_name} عزیز، وقت سرویس دوره‌ای است؛ برای رزرو وقت با ما تماس بگیرید.', NULL, 50, NOW(), NOW()),
  (6, 'contract_renewal', 'تمدید قرارداد (۵ روز قبل)', 0, 'before', 5, '09:30:00', '{full_name} عزیز، قرارداد {contract_number} تا {contract_ends_at} اعتبار دارد؛ آماده تمدید هستیم.', NULL, 60, NOW(), NOW());

SET @__cb_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sms_logs' AND COLUMN_NAME = 'event_type'
    AND DATA_TYPE = 'enum'
);
SET @__cb_sql := IF(@__cb_exists > 0, 'ALTER TABLE sms_logs MODIFY COLUMN event_type VARCHAR(40) NOT NULL', 'DO 0');
PREPARE __cb_stmt FROM @__cb_sql;
EXECUTE __cb_stmt;
DEALLOCATE PREPARE __cb_stmt;
