/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.5.2-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: school
-- ------------------------------------------------------
-- Server version	11.5.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `lesson_dates`
--

DROP TABLE IF EXISTS `lesson_dates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lesson_dates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lesson_date` date NOT NULL,
  `lesson_type` enum('lesson','exam') NOT NULL DEFAULT 'lesson',
  PRIMARY KEY (`id`),
  UNIQUE KEY `lesson_date` (`lesson_date`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lesson_dates`
--

LOCK TABLES `lesson_dates` WRITE;
/*!40000 ALTER TABLE `lesson_dates` DISABLE KEYS */;
INSERT INTO `lesson_dates` VALUES
(1,'2024-09-03','lesson'),
(2,'2024-09-10','lesson'),
(3,'2024-09-17','lesson'),
(4,'2024-09-24','lesson'),
(5,'2024-10-01','lesson'),
(7,'2024-10-15','lesson'),
(8,'2024-10-22','lesson'),
(9,'2024-10-29','lesson'),
(10,'2024-11-05','lesson'),
(11,'2024-11-12','lesson'),
(13,'2024-11-26','lesson'),
(14,'2024-12-03','lesson'),
(15,'2024-12-10','lesson'),
(16,'2024-12-17','lesson'),
(17,'2024-12-24','lesson'),
(18,'2024-12-31','lesson'),
(19,'2025-01-07','lesson'),
(20,'2025-01-14','lesson'),
(21,'2025-01-21','lesson'),
(22,'2025-01-28','lesson'),
(23,'2025-02-04','lesson'),
(24,'2025-02-11','lesson'),
(25,'2025-02-18','lesson'),
(26,'2025-02-25','lesson'),
(27,'2025-03-04','lesson'),
(28,'2025-03-11','lesson'),
(29,'2025-03-18','lesson'),
(30,'2025-03-25','lesson'),
(31,'2025-04-01','lesson'),
(32,'2025-04-08','lesson'),
(33,'2025-04-15','lesson'),
(34,'2025-04-22','lesson'),
(35,'2025-04-29','lesson'),
(36,'2025-05-06','lesson'),
(37,'2025-05-13','lesson'),
(38,'2025-05-20','lesson'),
(39,'2025-05-27','lesson');
/*!40000 ALTER TABLE `lesson_dates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `was_present_before` tinyint(1) NOT NULL DEFAULT 0,
  `is_present_now` tinyint(1) NOT NULL DEFAULT 0,
  `marks` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES
(1,'Агасиев Александр',1,1,1),
(2,'Базеев Иван',1,1,1),
(3,'Березина Серафима',1,1,1),
(4,'Волков Николай',0,1,0),
(5,'Воротова Николь',1,1,0),
(6,'Выскребенцева Кристина',1,1,0),
(7,'Галяева Виктория',1,1,0),
(8,'Гнездилов Денис',1,1,0),
(9,'Гудович Адам',1,1,1),
(10,'Заворочаев Лев',1,1,1),
(11,'Калинин Тимофей',1,1,0),
(12,'Карелина Александра',1,1,0),
(13,'Кастрикина Марина',1,1,0),
(14,'Кобзев Иван',1,0,0),
(15,'Кочетов Вячеслав',0,0,0),
(16,'Остриковская Муза',1,1,0),
(17,'Погорельский Иван',1,1,1),
(18,'Рамбугер Мария',1,1,0),
(19,'Семин Максим',1,1,0),
(20,'Сергеева Дарья',1,1,0),
(21,'Солодовников Илья',1,1,0),
(22,'Солопов Ярослав',1,1,0),
(23,'Стариков Александр',1,1,0),
(24,'Токарева Варвара',1,1,0);
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_tokens`
--

DROP TABLE IF EXISTS `user_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token_hash` (`token_hash`),
  CONSTRAINT `user_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_tokens`
--

LOCK TABLES `user_tokens` WRITE;
/*!40000 ALTER TABLE `user_tokens` DISABLE KEYS */;
INSERT INTO `user_tokens` VALUES
(7,6,'26af154ee21747870f5452032d21d0b220df07b41e469247d32c203a761dbfa1','2024-10-31 09:52:33'),
(24,2,'8c5fef83f961fb8917cc414e33ee47dd888141cd07cc6240d44e271c56c6bff9','2024-11-01 01:27:40');
/*!40000 ALTER TABLE `user_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `telegram` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `api_key` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `student_unique` (`student_id`),
  UNIQUE KEY `api_token` (`api_key`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(2,21,'ilya','Создатель','@solodovnikovilya','$2y$10$SIoGz2d9KLUebirdToFMv.vv1b2wtLt27k.JmeVExhaeR8mzgOasq',1,'2024-09-28 20:43:08','aa8ffd2aa4d7e9ad89e4c8252a113dd5'),
(3,10,'lew',NULL,NULL,'$2y$10$IQPXnFJyKdIU/FPi.IxQoeQ5sccSOPcVeHNaVSynWB3C8AKfizZ6y',0,'2024-09-28 21:16:09',NULL),
(4,1,'sasha','Санек',NULL,'$2y$10$7uHFa9dxkI2nNiFVpnyW8ecBW90befYw9Z7t..0gjyPc/AdYhk9gS',0,'2024-09-29 08:44:03',NULL),
(6,2,'vanya',NULL,NULL,'$2y$10$5WZAde8nY9a9cRqLcuR8Ze6fkJ72N4tYwtTf.2ImS6nXQgDT0K54O',0,'2024-10-01 09:43:40','80496291edff729fc31f5276fe9ab9ba'),
(7,3,'sima',NULL,NULL,'$2y$10$fJuMBZndxCTn0bZ4TdCE0ODiXR3GWg8VgNZrduNBu3sPgzCCS/AfG',0,'2024-10-01 11:39:22',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2024-10-02 13:31:47
