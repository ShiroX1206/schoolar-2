-- Phase 3 sample scholarship data.
-- This only adds scholarship records and eligibility criteria.
-- It does NOT recreate schools or courses.
-- Run this only if your scholarships table is empty or you want the sample records.

USE schoolar_db;

INSERT INTO scholarships
(name, provider_name, benefits, requirements, deadline, status, municipality_code, barangay_code, municipality_name, barangay_name, contact_email, contact_phone, created_by)
SELECT
'DOST-SEI Scholarship',
'Department of Science and Technology',
'Monthly stipend\nSchool fees support\nBook allowance',
'Good academic standing\nFilipino citizen\nRequired documents',
'2026-11-30',
'available',
NULL,
NULL,
'Nationwide',
'Any Barangay',
'sei@dost.gov.ph',
'(02) 8837-2071',
(SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM scholarships WHERE name = 'DOST-SEI Scholarship');

INSERT INTO eligibility_criteria
(scholarship_id, min_gwa, max_gwa, max_annual_income, min_age, max_age, year_levels, course_scope, residency_required)
SELECT id, NULL, 2.00, NULL, NULL, NULL,
'["1st Year","2nd Year","3rd Year","4th Year"]',
'["Science","Technology","Engineering","Mathematics"]',
0
FROM scholarships
WHERE name = 'DOST-SEI Scholarship'
AND NOT EXISTS (SELECT 1 FROM eligibility_criteria WHERE scholarship_id = scholarships.id);

INSERT INTO scholarships
(name, provider_name, benefits, requirements, deadline, status, municipality_code, barangay_code, municipality_name, barangay_name, contact_email, contact_phone, created_by)
SELECT
'CHED Merit Scholarship Program',
'Commission on Higher Education',
'Financial assistance\nAllowance support',
'Academic records\nProof of income\nRequired CHED documents',
'2026-10-31',
'available',
NULL,
NULL,
'Nationwide',
'Any Barangay',
'info@ched.gov.ph',
'(02) 8988-0001',
(SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM scholarships WHERE name = 'CHED Merit Scholarship Program');

INSERT INTO eligibility_criteria
(scholarship_id, min_gwa, max_gwa, max_annual_income, min_age, max_age, year_levels, course_scope, residency_required)
SELECT id, NULL, 1.75, 400000.00, NULL, NULL,
'["1st Year"]', '[]', 0
FROM scholarships
WHERE name = 'CHED Merit Scholarship Program'
AND NOT EXISTS (SELECT 1 FROM eligibility_criteria WHERE scholarship_id = scholarships.id);

INSERT INTO scholarships
(name, provider_name, benefits, requirements, deadline, status, municipality_code, barangay_code, municipality_name, barangay_name, contact_email, contact_phone, created_by)
SELECT
'ISEASP Scholarship',
'International Educational Assistance Scholarship Program',
'Educational assistance\nStudent support',
'Student profile\nAcademic record\nApplication form',
'2026-12-15',
'available',
NULL,
NULL,
'Nationwide',
'Any Barangay',
'scholarship@example.com',
'Contact the scholarship office',
(SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM scholarships WHERE name = 'ISEASP Scholarship');

INSERT INTO eligibility_criteria
(scholarship_id, min_gwa, max_gwa, max_annual_income, min_age, max_age, year_levels, course_scope, residency_required)
SELECT id, NULL, 2.50, 500000.00, NULL, NULL,
'["1st Year","2nd Year","3rd Year","4th Year"]', '[]', 0
FROM scholarships
WHERE name = 'ISEASP Scholarship'
AND NOT EXISTS (SELECT 1 FROM eligibility_criteria WHERE scholarship_id = scholarships.id);

INSERT INTO scholarships
(name, provider_name, benefits, requirements, deadline, status, municipality_code, barangay_code, municipality_name, barangay_name, contact_email, contact_phone, created_by)
SELECT
'Presidential Scholarship',
'Office of the President',
'Tuition assistance\nAllowance',
'Academic documents\nIdentification documents',
'2026-09-30',
'not available',
NULL,
NULL,
'Nationwide',
'Any Barangay',
'scholarship@example.gov.ph',
'Contact the scholarship office',
(SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM scholarships WHERE name = 'Presidential Scholarship');

INSERT INTO eligibility_criteria
(scholarship_id, min_gwa, max_gwa, max_annual_income, min_age, max_age, year_levels, course_scope, residency_required)
SELECT id, NULL, 1.50, 350000.00, NULL, NULL,
'["1st Year","2nd Year","3rd Year","4th Year"]', '[]', 0
FROM scholarships
WHERE name = 'Presidential Scholarship'
AND NOT EXISTS (SELECT 1 FROM eligibility_criteria WHERE scholarship_id = scholarships.id);
