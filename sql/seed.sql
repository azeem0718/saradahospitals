-- Sarada Nursing Home — seed data
-- Run AFTER schema.sql. Safe to re-run: uses INSERT IGNORE / ON DUPLICATE.
-- The admin account is NOT seeded here on purpose — no password hash is ever
-- committed to the repository. Visit /setup.php once after import to create it.

SET NAMES utf8mb4;

-- ---------------------------------------------------------------
-- Doctors
-- ---------------------------------------------------------------
INSERT INTO `doctors` (`slug`,`name`,`qualifications`,`speciality`,`bio`,`sort_order`,`is_active`)
VALUES
  ('dr-gundavarapu-venkatesh',
   'Dr. Gundavarapu Venkatesh',
   'MBBS, MD',
   'General Medicine, Diabetology & Endocrinology',
   'Dr. Gundavarapu Venkatesh completed his MD in General Medicine at SRM University, Chennai, and holds a Diploma in Endocrinology & Diabetology. He leads the Good Health Diabetic Centre at Sarada Nursing Home and has completed the Changing the Paradigm in Type 2 Diabetes Mellitus Management programme, a multidisciplinary diabetes programme based on official resources of the American Diabetes Association (ADA).',
   1, 1),
  ('dr-maddipudi-brahmani',
   'Dr. Maddipudi Brahmani',
   'MBBS, MS (OBG)',
   'Obstetrics & Gynaecology',
   'Dr. Maddipudi Brahmani is an Obstetrician and Gynaecologist with an MS in Obstetrics & Gynaecology. She looks after the maternity and women''s health services at Sarada Nursing Home, covering normal and caesarean delivery, high-risk pregnancy, laparoscopic surgery and the full range of gynaecological care.',
   2, 1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `qualifications` = VALUES(`qualifications`),
  `speciality` = VALUES(`speciality`);

-- ---------------------------------------------------------------
-- Sessions: every day, morning 09:00-13:00, evening 17:00-21:00.
-- Reception can edit times, caps and active days in Admin > Schedule.
-- ---------------------------------------------------------------
INSERT IGNORE INTO `doctor_sessions`
  (`doctor_id`,`weekday`,`session`,`start_time`,`end_time`,`token_cap`,`is_active`)
SELECT d.`id`, wd.`n`, s.`sess`, s.`st`, s.`et`, 30, 1
FROM `doctors` d
CROSS JOIN (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3
            UNION SELECT 4 UNION SELECT 5 UNION SELECT 6) wd
CROSS JOIN (SELECT 'morning' sess, '09:00:00' st, '13:00:00' et
            UNION ALL
            SELECT 'evening',      '17:00:00',    '21:00:00') s;

-- ---------------------------------------------------------------
-- Settings
-- ---------------------------------------------------------------
INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES
  ('booking_window_days',   '7'),
  ('default_token_cap',     '30'),
  ('booking_cutoff_minutes','60'),
  ('free_op_weekday',       '5'),
  ('free_op_label',         'Free OP Consultation'),
  ('bookings_enabled',      '1'),
  ('announcement',          ''),
  -- schema.sql already carries every column, so a fresh install starts current.
  -- includes/migrate.php compares against this.
  ('schema_version',        '3')
ON DUPLICATE KEY UPDATE `setting_value` = `setting_value`;
