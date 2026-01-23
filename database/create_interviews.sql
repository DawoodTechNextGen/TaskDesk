CREATE TABLE IF NOT EXISTS `interviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `registration_id` int(11) NOT NULL,
  `interview_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `platform` varchar(50) NOT NULL,
  `status` enum('scheduled','rescheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `registration_id` (`registration_id`),
  KEY `interview_date_time` (`interview_date`,`start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
