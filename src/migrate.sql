-- Migration: Add checkout_request_id to contributions table
-- Run this once against the `my_proforma` database.

ALTER TABLE contributions
    ADD COLUMN IF NOT EXISTS checkout_request_id VARCHAR(100) NULL DEFAULT NULL AFTER transaction_id,
    ADD INDEX idx_checkout_request_id (checkout_request_id);

-- Also ensure new settings keys exist (INSERT IGNORE won't overwrite existing values)
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('mpesa_sandbox_secret', ''),
    ('mpesa_live_secret',    ''),
    ('mpesa_passkey',        ''),
    ('mpesa_shortcode',      '');
