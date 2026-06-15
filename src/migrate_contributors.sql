-- Migration: public_contributors table for offline + displayed contributions
-- Run once against the `my_proforma` database.

CREATE TABLE IF NOT EXISTS public_contributors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount_pledged DECIMAL(12,2) NOT NULL DEFAULT 0,
    notes VARCHAR(500) NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seed with existing hardcoded contributor list (only if table is empty)
INSERT INTO public_contributors (name, amount_paid, amount_pledged, display_order)
SELECT * FROM (
    SELECT 'Capt. Rose & John Munangwe' AS name, 25000 AS amount_paid, 0 AS amount_pledged, 1 AS display_order UNION ALL
    SELECT 'Ian Aluda', 5000, 5000, 2 UNION ALL
    SELECT 'Vinic Nyabuti', 0, 10000, 3 UNION ALL
    SELECT 'Dennis Kegode', 0, 5000, 4 UNION ALL
    SELECT 'Faith Kithua', 0, 5000, 5 UNION ALL
    SELECT 'Noah Mugaya', 0, 3000, 6 UNION ALL
    SELECT 'Alice Nelima', 0, 1000, 7 UNION ALL
    SELECT '1st Challenge', 600, 0, 8 UNION ALL
    SELECT '2nd Challenge', 500, 0, 9 UNION ALL
    SELECT 'Irene Besani', 0, 10000, 10 UNION ALL
    SELECT '3rd Challenge', 1100, 0, 11 UNION ALL
    SELECT 'Mercy Munangwe', 0, 3000, 12 UNION ALL
    SELECT '4th Challenge', 700, 0, 13 UNION ALL
    SELECT 'Dickson Kegode', 2000, 0, 14 UNION ALL
    SELECT '5th Challenge', 300, 0, 15 UNION ALL
    SELECT '6th Challenge (Round 1)', 200, 0, 16 UNION ALL
    SELECT 'Major Robert & Aniefiok Robert', 5000, 0, 17 UNION ALL
    SELECT 'RASTO KILASI', 500, 1000, 18 UNION ALL
    SELECT '6th Challenge (Round 2)', 1000, 0, 19
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM public_contributors LIMIT 1);

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('campaign_target', '550000');
