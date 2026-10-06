-- One-time cleanup: delete the assessment-login accounts (role 6 = Candidate)
-- of candidates who were already rejected before rejection started deleting
-- them automatically. Their assessment attempts/reports stay, with user_id
-- set to NULL.
--
-- Run AFTER database/candidate_assessments_user_nullable.sql, on the live
-- `task_management` database (phpMyAdmin or `mysql` CLI).

-- 1) Preview: the accounts that will be deleted.
SELECT u.id, u.name, u.email
FROM users u
WHERE u.user_role = 6
AND u.id IN (
    SELECT ca.user_id FROM candidate_assessments ca
    JOIN registrations r ON r.id = ca.registration_id
    WHERE r.status = 'rejected' AND ca.user_id IS NOT NULL
);

-- 2) Delete them (candidate_assessments.user_id becomes NULL automatically).
DELETE FROM users
WHERE user_role = 6
AND id IN (
    SELECT user_id FROM (
        SELECT ca.user_id FROM candidate_assessments ca
        JOIN registrations r ON r.id = ca.registration_id
        WHERE r.status = 'rejected' AND ca.user_id IS NOT NULL
    ) rejected_candidates
);
