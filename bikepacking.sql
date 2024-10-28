-- MariaDB dump 10.19  Distrib 10.5.23-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: bikepacking
-- ------------------------------------------------------
-- Server version	10.5.23-MariaDB

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

--
-- Table structure for table `chats`
--

DROP TABLE IF EXISTS `chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chats` (
  `chatid` bigint(20) NOT NULL,
  `description` varchar(256) DEFAULT NULL,
  `link` varchar(100) DEFAULT NULL,
  `chatname` varchar(100) DEFAULT NULL,
  `start` int(11) DEFAULT 0,
  `stop` int(11) DEFAULT 0,
  `gpx` tinyint(1) NOT NULL DEFAULT 0,
  `total_km` int(11) NOT NULL DEFAULT 0,
  `total_dev` int(11) NOT NULL DEFAULT 0,
  `unit` tinyint(4) NOT NULL DEFAULT 0,
  `timediff` tinyint(4) NOT NULL DEFAULT 0,
  `adminid` bigint(20) NOT NULL DEFAULT 0,
  `photo` tinyint(1) NOT NULL DEFAULT 0,
  `creationdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `mode` tinyint(4) NOT NULL DEFAULT 0,
  `menuid` int(11) DEFAULT NULL,
  `real_time` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`chatid`),
  UNIQUE KEY `chatname` (`chatname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `connectors`
--

DROP TABLE IF EXISTS `connectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `connectors` (
  `conid` bigint(20) NOT NULL AUTO_INCREMENT,
  `conrouteid` bigint(20) DEFAULT NULL,
  `conuserid` bigint(20) DEFAULT NULL,
  `contime` timestamp NOT NULL DEFAULT current_timestamp(),
  `constatus` int(2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`conid`),
  UNIQUE KEY `conrouteid` (`conrouteid`,`conuserid`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gpx`
--

DROP TABLE IF EXISTS `gpx`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gpx` (
  `chatid` bigint(20) NOT NULL,
  `point` int(11) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `km` mediumint(9) NOT NULL,
  `dev` mediumint(9) NOT NULL,
  `track` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`chatid`,`point`,`track`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `logs`
--

DROP TABLE IF EXISTS `logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chatid` bigint(20) NOT NULL DEFAULT -1001534542906,
  `userid` bigint(20) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `timestamp` int(11) DEFAULT NULL,
  `latitude` float DEFAULT NULL,
  `longitude` float DEFAULT NULL,
  `gpx_point` int(11) DEFAULT NULL,
  `km` int(11) DEFAULT NULL,
  `dev` int(11) NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7266 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `options`
--

DROP TABLE IF EXISTS `options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `options` (
  `name` varchar(50) NOT NULL,
  `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `routes`
--

DROP TABLE IF EXISTS `routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `routes` (
  `routeid` bigint(20) NOT NULL AUTO_INCREMENT,
  `routeuserid` bigint(20) NOT NULL,
  `routerem` varchar(256) DEFAULT NULL,
  `routepublisherlink` varchar(100) DEFAULT NULL,
  `routeviewerlink` varchar(100) DEFAULT NULL,
  `routename` varchar(100) DEFAULT NULL,
  `routeinitials` varchar(2) DEFAULT NULL,
  `routeslug` varchar(100) DEFAULT NULL,
  `routestatus` tinyint(1) NOT NULL DEFAULT 2,
  `start` int(11) DEFAULT 0,
  `stop` int(11) DEFAULT 0,
  `gpx` tinyint(1) NOT NULL DEFAULT 0,
  `total_km` int(11) NOT NULL DEFAULT 0,
  `total_dev` int(11) NOT NULL DEFAULT 0,
  `unit` tinyint(4) NOT NULL DEFAULT 0,
  `timediff` tinyint(4) NOT NULL DEFAULT 0,
  `routephoto` tinyint(1) NOT NULL DEFAULT 0,
  `creationdate` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `mode` tinyint(4) NOT NULL DEFAULT 0,
  `real_time` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`routeid`),
  UNIQUE KEY `routename` (`routename`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `userid` bigint(20) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `userinitials` varchar(2) DEFAULT NULL,
  `usercolor` varchar(7) DEFAULT NULL,
  `userpsw` varchar(255) DEFAULT NULL,
  `useremail` varchar(255) DEFAULT NULL,
  `usercreation` timestamp NOT NULL DEFAULT current_timestamp(),
  `userupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `userroute` bigint(20) DEFAULT NULL,
  `auth_token` varchar(255) DEFAULT NULL,
  `userphoto` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-10-28  6:32:00
