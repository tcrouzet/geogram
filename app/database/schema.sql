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
-- Table structure for table `connectors`
--

DROP TABLE IF EXISTS `connectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `connectors` (
  `conid` bigint(20) NOT NULL AUTO_INCREMENT,
  `conrouteid` bigint(20) NOT NULL,
  `conuserid` bigint(20) NOT NULL,
  `contime` timestamp NOT NULL DEFAULT current_timestamp(),
  `constatus` int(2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`conid`),
  UNIQUE KEY `conrouteid` (`conrouteid`,`conuserid`)
) ENGINE=InnoDB AUTO_INCREMENT=755 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `context`
--

DROP TABLE IF EXISTS `context`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `context` (
  `contextid` bigint(20) NOT NULL AUTO_INCREMENT,
  `lat_grid` decimal(8,5) NOT NULL,
  `lon_grid` decimal(8,5) NOT NULL,
  `city_name` varchar(255) DEFAULT NULL,
  `weather_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`weather_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`contextid`),
  UNIQUE KEY `grid_coords` (`lat_grid`,`lon_grid`),
  KEY `cleanup_index` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=671 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
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
-- Table structure for table `rgpx`
--

DROP TABLE IF EXISTS `rgpx`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rgpx` (
  `gpxroute` bigint(20) NOT NULL,
  `gpxpoint` int(11) NOT NULL,
  `gpxlongitude` decimal(11,8) NOT NULL,
  `gpxlatitude` decimal(10,8) NOT NULL,
  `gpxkm` mediumint(9) NOT NULL,
  `gpxdev` mediumint(9) NOT NULL,
  `gpxtrack` tinyint(4) NOT NULL DEFAULT 0,
  UNIQUE KEY `gpxroute` (`gpxroute`,`gpxpoint`,`gpxtrack`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rlogs`
--

DROP TABLE IF EXISTS `rlogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rlogs` (
  `logid` bigint(20) NOT NULL AUTO_INCREMENT,
  `logroute` bigint(20) NOT NULL,
  `loguser` bigint(20) NOT NULL,
  `logtelegramid` bigint(20) NOT NULL DEFAULT 0,
  `loglatitude` float DEFAULT NULL,
  `loglongitude` float DEFAULT NULL,
  `loggpxpoint` int(11) DEFAULT NULL,
  `logkm` int(11) DEFAULT NULL,
  `logdev` int(11) DEFAULT NULL,
  `logcomment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `logphoto` tinyint(1) NOT NULL DEFAULT 0,
  `logcontext` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`logcontext`)),
  `logtime` timestamp NOT NULL DEFAULT current_timestamp(),
  `loginsertime` timestamp NOT NULL DEFAULT current_timestamp(),
  `logupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`logid`),
  UNIQUE KEY `logroute` (`logroute`,`loguser`,`loglatitude`,`loglongitude`,`logphoto`,`logtime`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=6403 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
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
  `routestart` timestamp NULL DEFAULT NULL,
  `routestop` timestamp NULL DEFAULT NULL,
  `routelastdays` smallint(6) NOT NULL DEFAULT 0,
  `gpx` tinyint(1) NOT NULL DEFAULT 0,
  `total_km` int(11) NOT NULL DEFAULT 0,
  `total_dev` int(11) NOT NULL DEFAULT 0,
  `routeunit` tinyint(1) NOT NULL DEFAULT 0,
  `routetimediff` tinyint(4) NOT NULL DEFAULT 0,
  `routephoto` tinyint(1) NOT NULL DEFAULT 0,
  `routetime` timestamp NOT NULL DEFAULT current_timestamp(),
  `routeupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `routemode` tinyint(1) NOT NULL DEFAULT 0,
  `routerealtime` tinyint(1) NOT NULL DEFAULT 0,
  `routetelegram` bigint(20) DEFAULT NULL,
  `routeverbose` tinyint(4) NOT NULL DEFAULT 0,
  `routelocationduration` tinyint(4) NOT NULL DEFAULT 12,
  PRIMARY KEY (`routeid`),
  UNIQUE KEY `routename` (`routename`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `telegram`
--

DROP TABLE IF EXISTS `telegram`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `telegram` (
  `channel_id` bigint(20) NOT NULL,
  `channel_title` varchar(50) DEFAULT NULL,
  `channel_admin` bigint(20) NOT NULL,
  `channel_status` varchar(20) DEFAULT NULL,
  `channel_updated` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`channel_id`),
  UNIQUE KEY `channel_id` (`channel_id`,`channel_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
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
  `useremail` varchar(255) DEFAULT NULL,
  `usercreation` timestamp NOT NULL DEFAULT current_timestamp(),
  `userupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `userroute` bigint(20) DEFAULT NULL,
  `userphoto` tinyint(1) NOT NULL DEFAULT 0,
  `usertelegram` bigint(20) DEFAULT NULL,
  `usertoken` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=381 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-12 18:36:25
