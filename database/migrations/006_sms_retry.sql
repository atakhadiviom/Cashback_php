ALTER TABLE sms_logs
  ADD COLUMN retry_count TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER status,
  ADD COLUMN next_retry_at DATETIME NULL AFTER retry_count;

ALTER TABLE sms_settings
  ADD COLUMN purchase_void_template TEXT NULL AFTER otp_template,
  ADD COLUMN referral_template TEXT NULL AFTER purchase_void_template;

UPDATE sms_settings SET purchase_void_template = 'سلام {full_name}، خرید شما به مبلغ {purchase_amount} ابطال شد و مبلغ {cashback_amount} ریال از کیف پول کسر شد.' WHERE id = 1 AND purchase_void_template IS NULL;
UPDATE sms_settings SET referral_template = 'سلام {full_name}، مبلغ {cashback_amount} ریال بابت معرفی مشتری جدید به کیف پول شما اضافه شد.' WHERE id = 1 AND referral_template IS NULL;

ALTER TABLE sms_logs
  MODIFY COLUMN event_type ENUM('purchase','birthday','wallet_reduction','welcome','manual','otp','purchase_void','referral_bonus') NOT NULL;
