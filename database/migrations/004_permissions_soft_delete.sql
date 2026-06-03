ALTER TABLE users
  ADD COLUMN permissions JSON NULL AFTER role;

UPDATE users SET permissions = JSON_OBJECT(
  'purchase', true, 'reduce_wallet', true, 'export', true, 'void_purchase', true,
  'manage_settings', true, 'manage_users', true, 'import_customers', true, 'manage_api', true
) WHERE role = 'admin' AND permissions IS NULL;

UPDATE users SET permissions = JSON_OBJECT(
  'purchase', true, 'reduce_wallet', true, 'export', true, 'void_purchase', false,
  'manage_settings', false, 'manage_users', false, 'import_customers', false, 'manage_api', false
) WHERE role = 'operator' AND permissions IS NULL;

ALTER TABLE customers
  ADD COLUMN deleted_at DATETIME NULL AFTER updated_at,
  ADD COLUMN referred_by_customer_id BIGINT UNSIGNED NULL AFTER deleted_at,
  ADD COLUMN tier_id BIGINT UNSIGNED NULL AFTER referred_by_customer_id;

CREATE INDEX idx_customers_deleted_at ON customers (deleted_at);
