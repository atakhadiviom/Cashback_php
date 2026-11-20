-- 026: indexes for the customer club reports (RFM, liability, reminders)

ALTER TABLE purchases ADD INDEX idx_purchases_status_created (status, created_at);

ALTER TABLE wallet_transactions ADD INDEX idx_wallet_tx_customer_created (customer_id, created_at);

ALTER TABLE cashback_lots ADD INDEX idx_cashback_lots_expiry (expires_at, consumed_amount);

ALTER TABLE customers ADD INDEX idx_customers_tier (tier_id);
