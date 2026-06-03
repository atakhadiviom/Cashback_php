ALTER TABLE cashback_settings
  ADD COLUMN min_redemption_amount DECIMAL(15,2) NULL AFTER max_cashback_per_purchase,
  ADD COLUMN max_redemption_percent_of_purchase DECIMAL(5,2) NULL AFTER min_redemption_amount,
  ADD COLUMN large_redemption_threshold DECIMAL(15,2) NULL AFTER max_redemption_percent_of_purchase;
