-- Cashback expiry is opt-in: the shipped default is now 0 months (never expires).
-- Migration 019 originally defaulted to 12 months, so this corrects installs that
-- already ran it before an admin had a chance to choose a period.

ALTER TABLE cashback_settings ALTER COLUMN cashback_expiry_months SET DEFAULT 0;

-- Only touch installs still sitting on the untouched 12-month default from 019.
-- If an admin has already chosen a period, their choice is left alone.
SET @__cb_was_default := (SELECT cashback_expiry_months FROM cashback_settings WHERE id = 1);

UPDATE cashback_settings
SET cashback_expiry_months = 0, updated_at = NOW()
WHERE id = 1 AND cashback_expiry_months = 12;

-- Those expiry dates were assigned by our default, not by an admin decision, so the
-- lots they were stamped on should not expire either. Clearing warned_at as well means
-- customers are warned properly if expiry is switched on later.
UPDATE cashback_lots
SET expires_at = NULL, warned_at = NULL, updated_at = NOW()
WHERE @__cb_was_default = 12 AND expires_at IS NOT NULL;
