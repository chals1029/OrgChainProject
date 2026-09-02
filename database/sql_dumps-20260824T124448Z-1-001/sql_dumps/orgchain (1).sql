-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: orgchain
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `orgchain`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `orgchain` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `orgchain`;

--
-- Table structure for table `analytics_reports`
--

DROP TABLE IF EXISTS `analytics_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_reports` (
  `analytics_id` int NOT NULL,
  `generated_by` int DEFAULT NULL,
  `report_name` varchar(200) DEFAULT NULL,
  `generated_date` datetime DEFAULT NULL,
  `summary_data` text,
  PRIMARY KEY (`analytics_id`),
  KEY `generated_by` (`generated_by`),
  CONSTRAINT `analytics_reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `user_accounts` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analytics_reports`
--

LOCK TABLES `analytics_reports` WRITE;
/*!40000 ALTER TABLE `analytics_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `analytics_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approvals`
--

DROP TABLE IF EXISTS `approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approvals` (
  `approval_id` int NOT NULL,
  `proposal_id` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `decision` varchar(50) DEFAULT NULL,
  `decision_date` datetime DEFAULT NULL,
  `remarks` text,
  PRIMARY KEY (`approval_id`),
  KEY `proposal_id` (`proposal_id`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `approvals_ibfk_1` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`proposal_id`),
  CONSTRAINT `approvals_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `user_accounts` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approvals`
--

LOCK TABLES `approvals` WRITE;
/*!40000 ALTER TABLE `approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `archived_reports`
--

DROP TABLE IF EXISTS `archived_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `archived_reports` (
  `archive_id` int NOT NULL,
  `report_id` int DEFAULT NULL,
  `archived_date` datetime DEFAULT NULL,
  `archive_location` varchar(255) DEFAULT NULL,
  `archive_status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`archive_id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `archived_reports_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archived_reports`
--

LOCK TABLES `archived_reports` WRITE;
/*!40000 ALTER TABLE `archived_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `archived_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_trails`
--

DROP TABLE IF EXISTS `audit_trails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_trails` (
  `audit_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `report_id` int DEFAULT NULL,
  `action_type` varchar(150) DEFAULT NULL,
  `hash_value` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL,
  PRIMARY KEY (`audit_id`),
  KEY `user_id` (`user_id`),
  KEY `report_id` (`report_id`),
  CONSTRAINT `audit_trails_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user_accounts` (`user_id`),
  CONSTRAINT `audit_trails_ibfk_2` FOREIGN KEY (`report_id`) REFERENCES `reports` (`report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_trails`
--

LOCK TABLES `audit_trails` WRITE;
/*!40000 ALTER TABLE `audit_trails` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_trails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_comments`
--

DROP TABLE IF EXISTS `community_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `community_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_comments`
--

LOCK TABLES `community_comments` WRITE;
/*!40000 ALTER TABLE `community_comments` DISABLE KEYS */;
INSERT INTO `community_comments` VALUES (1,1,3,'test','2026-07-27 00:25:30','2026-07-27 00:25:30');
/*!40000 ALTER TABLE `community_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_likes`
--

DROP TABLE IF EXISTS `community_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `community_likes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `community_likes_post_id_student_id_unique` (`post_id`,`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_likes`
--

LOCK TABLES `community_likes` WRITE;
/*!40000 ALTER TABLE `community_likes` DISABLE KEYS */;
INSERT INTO `community_likes` VALUES (1,1,3,'2026-07-27 00:25:25','2026-07-27 00:25:25');
/*!40000 ALTER TABLE `community_likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_posts`
--

DROP TABLE IF EXISTS `community_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `community_posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `activity_id` bigint unsigned DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `likes_count` int unsigned NOT NULL DEFAULT '0',
  `comments_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_posts`
--

LOCK TABLES `community_posts` WRITE;
/*!40000 ALTER TABLE `community_posts` DISABLE KEYS */;
INSERT INTO `community_posts` VALUES (1,3,NULL,'test',NULL,1,1,'2026-07-27 00:25:18','2026-07-27 00:25:30'),(2,3,NULL,'test',NULL,0,0,'2026-07-27 22:28:44','2026-07-27 22:28:44');
/*!40000 ALTER TABLE `community_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proposals`
--

DROP TABLE IF EXISTS `proposals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proposals` (
  `proposal_id` int NOT NULL,
  `org_id` int DEFAULT NULL,
  `submitted_by` int DEFAULT NULL,
  `proposal_title` varchar(200) DEFAULT NULL,
  `coa_reference` varchar(100) DEFAULT NULL,
  `description` text,
  `date_submitted` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`proposal_id`),
  KEY `org_id` (`org_id`),
  KEY `submitted_by` (`submitted_by`),
  CONSTRAINT `proposals_ibfk_1` FOREIGN KEY (`org_id`) REFERENCES `student_organizations` (`org_id`),
  CONSTRAINT `proposals_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `user_accounts` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proposals`
--

LOCK TABLES `proposals` WRITE;
/*!40000 ALTER TABLE `proposals` DISABLE KEYS */;
/*!40000 ALTER TABLE `proposals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reports` (
  `report_id` int NOT NULL,
  `org_id` int DEFAULT NULL,
  `proposal_id` int DEFAULT NULL,
  `submitted_by` int DEFAULT NULL,
  `report_type` varchar(50) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `submitted_date` datetime DEFAULT NULL,
  `verification_status` varchar(50) DEFAULT NULL,
  `remarks` text,
  PRIMARY KEY (`report_id`),
  KEY `org_id` (`org_id`),
  KEY `proposal_id` (`proposal_id`),
  KEY `submitted_by` (`submitted_by`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`org_id`) REFERENCES `student_organizations` (`org_id`),
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`proposal_id`) REFERENCES `proposals` (`proposal_id`),
  CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`submitted_by`) REFERENCES `user_accounts` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports`
--

LOCK TABLES `reports` WRITE;
/*!40000 ALTER TABLE `reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_organizations`
--

DROP TABLE IF EXISTS `student_organizations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_organizations` (
  `org_id` int NOT NULL,
  `org_name` varchar(150) DEFAULT NULL,
  `adviser_name` varchar(150) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`org_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_organizations`
--

LOCK TABLES `student_organizations` WRITE;
/*!40000 ALTER TABLE `student_organizations` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_organizations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_accounts`
--

DROP TABLE IF EXISTS `user_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_accounts` (
  `user_id` int NOT NULL,
  `org_id` int DEFAULT NULL,
  `sr_code` varchar(100) DEFAULT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `college` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `account_status` varchar(50) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  KEY `org_id` (`org_id`),
  CONSTRAINT `user_accounts_ibfk_1` FOREIGN KEY (`org_id`) REFERENCES `student_organizations` (`org_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_accounts`
--

LOCK TABLES `user_accounts` WRITE;
/*!40000 ALTER TABLE `user_accounts` DISABLE KEYS */;
INSERT INTO `user_accounts` VALUES (1,NULL,'21-00001','Charles Samotanez','College of Informatics and Computing Sciences','BS Information Technology','4th Year',NULL,'21-00001@g.batstate-u.edu.ph','student','active',NULL,'2026-07-27 07:53:41'),(2,NULL,'21-00002','Maria Santos','College of Arts and Sciences','BS Psychology','3rd Year',NULL,'21-00002@g.batstate-u.edu.ph','student','active',NULL,'2026-07-27 07:53:41'),(3,NULL,'23-73068','SAMONTAÑEZ, CHARLES D.','College of Informatics and Computing Sciences','Bachelor of Science in Information Technology Major in Business Analytics Track','Third',NULL,'23-73068@g.batstate-u.edu.ph','student','active','RpjNJJ6RpQXDTq20Sfc9bVPQ14xEZeiaFTxpXY8dR6fkxFsx0YatJjjO216q',NULL);
/*!40000 ALTER TABLE `user_accounts` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-24 21:02:13
