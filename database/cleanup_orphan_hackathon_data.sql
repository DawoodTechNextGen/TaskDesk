-- One-time cleanup: remove hackathon data left behind by hackathons (and
-- registrations) that were deleted before the delete actions started cleaning
-- up after themselves. The live hackathon tables have no foreign keys, so
-- nothing cascaded and these rows were orphaned.
--
-- Run on the live `dawoodte_task_desk` database (phpMyAdmin SQL tab or the
-- `mysql` CLI). Safe to run more than once - a second run finds nothing.
-- Rows that still belong to an existing hackathon are never touched.

-- 1) Preview: how many orphaned rows each step will remove.
SELECT
    (SELECT COUNT(*) FROM hackathon_registrations r
        WHERE NOT EXISTS (SELECT 1 FROM hackathons h WHERE h.id = r.hackathon_id)) AS orphan_registrations,
    (SELECT COUNT(*) FROM hackathon_team_members m
        WHERE NOT EXISTS (SELECT 1 FROM hackathon_registrations r
                          JOIN hackathons h ON h.id = r.hackathon_id
                          WHERE r.id = m.registration_id)) AS orphan_team_members,
    (SELECT COUNT(*) FROM hackathon_leaderboard_entries e
        WHERE NOT EXISTS (SELECT 1 FROM hackathons h WHERE h.id = e.hackathon_id)) AS orphan_leaderboard_entries,
    (SELECT COUNT(*) FROM hackathon_leaderboard_entries e
        WHERE e.registration_id IS NOT NULL
        AND EXISTS (SELECT 1 FROM hackathons h WHERE h.id = e.hackathon_id)
        AND NOT EXISTS (SELECT 1 FROM hackathon_registrations r WHERE r.id = e.registration_id)) AS dangling_leaderboard_links;

-- 2) Clean up, all or nothing.
START TRANSACTION;

-- Team members whose registration is gone or whose registration's hackathon is gone.
DELETE m FROM hackathon_team_members m
LEFT JOIN hackathon_registrations r ON r.id = m.registration_id
LEFT JOIN hackathons h ON h.id = r.hackathon_id
WHERE h.id IS NULL;

-- Registrations of hackathons that no longer exist.
DELETE r FROM hackathon_registrations r
LEFT JOIN hackathons h ON h.id = r.hackathon_id
WHERE h.id IS NULL;

-- Leaderboard entries of hackathons that no longer exist.
DELETE e FROM hackathon_leaderboard_entries e
LEFT JOIN hackathons h ON h.id = e.hackathon_id
WHERE h.id IS NULL;

-- Leaderboard entries of existing hackathons that point at a deleted
-- registration: keep the entry (name/project/prize), drop the dead link.
UPDATE hackathon_leaderboard_entries e
LEFT JOIN hackathon_registrations r ON r.id = e.registration_id
SET e.registration_id = NULL
WHERE e.registration_id IS NOT NULL AND r.id IS NULL;

COMMIT;
