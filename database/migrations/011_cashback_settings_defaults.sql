UPDATE cashback_settings
SET min_purchase_amount = 0
WHERE min_purchase_amount IS NULL;

ALTER TABLE cashback_settings
  MODIFY min_purchase_amount DECIMAL(15,2) NOT NULL DEFAULT 0;
