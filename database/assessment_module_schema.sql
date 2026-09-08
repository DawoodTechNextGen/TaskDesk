-- Assessment stage for the Internship registration pipeline.
-- Run once against the live `task_management` database (phpMyAdmin or `mysql` CLI).
-- The registrations.status ALTER below is safe to re-run (idempotent no-op if already applied).

ALTER TABLE registrations MODIFY status ENUM('new','contact','interview','hire','rejected','assessment') NOT NULL;

CREATE TABLE IF NOT EXISTS assessments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  technology_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  difficulty ENUM('easy','medium','hard') NOT NULL DEFAULT 'medium',
  duration_minutes INT NOT NULL DEFAULT 30,
  passing_percentage INT NOT NULL DEFAULT 60,
  status TINYINT NOT NULL DEFAULT 1,
  created_by INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_assessments_tech FOREIGN KEY (technology_id) REFERENCES technologies(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assessment_questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  assessment_id INT NOT NULL,
  question_html TEXT NOT NULL,
  points INT NOT NULL DEFAULT 1,
  order_index INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_aq_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assessment_options (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question_id INT NOT NULL,
  option_text VARCHAR(500) NOT NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  order_index INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_ao_question FOREIGN KEY (question_id) REFERENCES assessment_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS candidate_assessments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  registration_id INT NOT NULL,
  assessment_id INT NOT NULL,
  user_id INT NOT NULL,
  status ENUM('pending','in_progress','pass','fail') NOT NULL DEFAULT 'pending',
  score INT DEFAULT NULL,
  total_marks INT DEFAULT NULL,
  percentage DECIMAL(5,2) DEFAULT NULL,
  started_at DATETIME DEFAULT NULL,
  expires_at DATETIME DEFAULT NULL,
  completed_at DATETIME DEFAULT NULL,
  violation_count INT NOT NULL DEFAULT 0,
  fail_reason VARCHAR(30) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ca_registration FOREIGN KEY (registration_id) REFERENCES registrations(id),
  CONSTRAINT fk_ca_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(id),
  CONSTRAINT fk_ca_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS candidate_assessment_answers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  candidate_assessment_id INT NOT NULL,
  question_id INT NOT NULL,
  selected_option_id INT DEFAULT NULL,
  is_correct TINYINT(1) DEFAULT NULL,
  CONSTRAINT fk_caa_ca FOREIGN KEY (candidate_assessment_id) REFERENCES candidate_assessments(id) ON DELETE CASCADE,
  CONSTRAINT fk_caa_question FOREIGN KEY (question_id) REFERENCES assessment_questions(id),
  UNIQUE KEY uniq_ca_question (candidate_assessment_id, question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
