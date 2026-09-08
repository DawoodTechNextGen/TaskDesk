-- Webcam proctoring captures for the Assessment stage.
-- Run once against the live `task_management` database (phpMyAdmin or `mysql` CLI).

CREATE TABLE IF NOT EXISTS candidate_assessment_captures (
  id INT AUTO_INCREMENT PRIMARY KEY,
  candidate_assessment_id INT NOT NULL,
  registration_id INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  captured_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cac_ca FOREIGN KEY (candidate_assessment_id) REFERENCES candidate_assessments(id) ON DELETE CASCADE,
  CONSTRAINT fk_cac_registration FOREIGN KEY (registration_id) REFERENCES registrations(id),
  INDEX idx_cac_registration (registration_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
