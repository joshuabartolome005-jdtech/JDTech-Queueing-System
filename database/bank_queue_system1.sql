-- Bank Queue System (version 2) - fresh install
-- Gumawa muna ng database na `bank_queue_system` sa phpMyAdmin, piliin ito, tapos i-Import ang file na ito.
-- Default logins (PALITAN AGAD): admin 'csc' / 123456, tellers 'teller_1'...'teller_7' / 654321

/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `username` varchar(50) NOT NULL,
  `attempted_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ip_time` (`ip`,`attempted_at`),
  KEY `user_time` (`username`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_number` int(11) NOT NULL,
  `status` enum('waiting','serving','served') NOT NULL DEFAULT 'waiting',
  `teller` varchar(50) DEFAULT NULL,
  `client_type` varchar(20) NOT NULL DEFAULT 'Regular',
  `created_at` datetime DEFAULT NULL,
  `called_at` datetime DEFAULT NULL,
  `served_at` datetime DEFAULT NULL,
  `skip_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `no_show` tinyint(1) NOT NULL DEFAULT 0,
  `recall_count` int(10) unsigned NOT NULL DEFAULT 0,
  `kiosk_ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `queue` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `queue_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_date` date NOT NULL,
  `customer_number` int(11) NOT NULL,
  `client_type` varchar(20) NOT NULL,
  `teller` varchar(50) DEFAULT NULL,
  `status` enum('waiting','serving','served') NOT NULL,
  `no_show` tinyint(1) NOT NULL DEFAULT 0,
  `skip_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `slip_type` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `called_at` datetime DEFAULT NULL,
  `served_at` datetime DEFAULT NULL,
  `archived_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_date` (`ticket_date`),
  KEY `teller` (`teller`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `queue_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `queue_history` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `queue_slips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue_id` int(11) NOT NULL,
  `slip_type` enum('cash_deposit','withdrawal','check_deposit','ecpay') NOT NULL,
  `slip_data` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `queue_id` (`queue_id`),
  CONSTRAINT `queue_slips_ibfk_1` FOREIGN KEY (`queue_id`) REFERENCES `queue` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `queue_slips` DISABLE KEYS */;
/*!40000 ALTER TABLE `queue_slips` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','employee','developer') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_token` varchar(255) DEFAULT NULL,
  `last_seen` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `session_token`, `last_seen`) VALUES (2,'csc','$2y$10$mYaFx.c/NdenTaGOemHxneoSfjlJFto7cmHUJRz9c4mFBM2RxssXq','admin','2025-01-10 02:08:37',NULL,NULL),
(16,'teller_6','$2y$10$4o/NocJvJsLMJqFZ6qVKd.zx1eHyZnC4vf7/yu/K4x1IADe3kL4/a','employee','2025-01-20 02:09:33',NULL,NULL),
(18,'teller_2','$2y$10$ftFj4cyeWdSM8iiXKABNIuc5CuUjQllg57wWHjSJ7FbOvxaBBY2Uq','employee','2025-01-20 06:02:09',NULL,NULL),
(19,'teller_3','$2y$10$51zcGa.tJvRyjsIFO22eKulkkwHrRDiSKdFUaTjDENtsAnxqgvtOW','employee','2025-01-20 06:02:09',NULL,NULL),
(20,'teller_4','$2y$10$VqvXKRpI0uOYkUsoW320NeJCk5nJUMEe5Udw7lUJ/4wLtIQYOm25u','employee','2025-01-20 06:02:09',NULL,NULL),
(21,'teller_5','$2y$10$H./JUw22Dt1AMJ.2Am6Fg.zg5EII9gwcvagYsSydiY8OGM9Aarq.C','employee','2025-01-20 06:02:09',NULL,NULL),
(22,'teller_1','$2y$10$h3rNZI8woCi4QjKaITLnR.7VeL43.NnzsveBlMmDXDEsWqwYWPdbW','employee','2025-02-11 00:35:42',NULL,NULL),
(23,'teller_7','$2y$10$RigVnTPYl5lxqp57gih7FuuvrJICi0EaT62ImCDYuR8cbj8IaYzPG','employee','2026-02-26 10:09:50',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `videos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `video_path` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `uploaded_at` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `videos` DISABLE KEYS */;
/*!40000 ALTER TABLE `videos` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

