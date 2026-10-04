-- Fixes the GWA scale on the 4 sample scholarships.
-- They were stored as 85-100 (percentage scale), but the rest of the
-- site uses a 1.00-5.00 scale (1.00 = best). Run this once in phpMyAdmin.
-- Safe to run even if you already imported phase3_seed_scholarships.sql.

USE schoolar_db;

-- DOST-SEI: needed "85 or better" -> needs GWA of 2.00 or better
UPDATE eligibility_criteria ec
JOIN scholarships s ON s.id = ec.scholarship_id
SET ec.min_gwa = NULL, ec.max_gwa = 2.00
WHERE s.name = 'DOST-SEI Scholarship';

-- CHED Merit: needed "90 or better" (more selective) -> 1.75 or better
UPDATE eligibility_criteria ec
JOIN scholarships s ON s.id = ec.scholarship_id
SET ec.min_gwa = NULL, ec.max_gwa = 1.75
WHERE s.name = 'CHED Merit Scholarship Program';

-- ISEASP: needed "80 or better" -> 2.50 or better
UPDATE eligibility_criteria ec
JOIN scholarships s ON s.id = ec.scholarship_id
SET ec.min_gwa = NULL, ec.max_gwa = 2.50
WHERE s.name = 'ISEASP Scholarship';

-- Presidential: needed "88 or better" (most prestigious) -> 1.50 or better
UPDATE eligibility_criteria ec
JOIN scholarships s ON s.id = ec.scholarship_id
SET ec.min_gwa = NULL, ec.max_gwa = 1.50
WHERE s.name = 'Presidential Scholarship';
