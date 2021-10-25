-- MySQL dump 10.13  Distrib 5.1.73, for redhat-linux-gnu (x86_64)
--
-- Host: localhost    Database: translate5Net
-- ------------------------------------------------------
-- Server version	5.1.73

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `LEK_comments`
--

DROP TABLE IF EXISTS `LEK_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `segmentId` int(11) NOT NULL,
  `userGuid` varchar(38) NOT NULL,
  `userName` varchar(255) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `segmentId` (`segmentId`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_comments_ibfk_1` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `LEK_comments_ibfk_2` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_files`
--

DROP TABLE IF EXISTS `LEK_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `fileName` varchar(255) DEFAULT NULL,
  `sourceLang` varchar(11) NOT NULL,
  `targetLang` varchar(11) NOT NULL,
  `relaisLang` int(11) NOT NULL,
  `fileOrder` int(11) NOT NULL,
  `encoding` varchar(19) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_files_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_foldertree`
--

DROP TABLE IF EXISTS `LEK_foldertree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_foldertree` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tree` mediumtext NOT NULL,
  `referenceFileTree` text NOT NULL,
  `taskGuid` varchar(38) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_foldertree_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_internaltags`
--

DROP TABLE IF EXISTS `LEK_internaltags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_internaltags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tagsPerSegmentId` int(11) NOT NULL,
  `tagType` int(11) NOT NULL,
  `segmentId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tagsPerSegmentId` (`tagsPerSegmentId`,`tagType`,`segmentId`),
  KEY `LEK_internaltags_segmentId_FK` (`segmentId`),
  CONSTRAINT `LEK_internaltags_segmentId_FK` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_languages`
--

DROP TABLE IF EXISTS `LEK_languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_languages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `langName` varchar(255) NOT NULL,
  `lcid` int(11) DEFAULT NULL,
  `rfc5646` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `langName` (`langName`),
  UNIQUE KEY `lcid` (`lcid`),
  UNIQUE KEY `rfc5646` (`rfc5646`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_plugin_segmentstatistics`
--

DROP TABLE IF EXISTS `LEK_plugin_segmentstatistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_plugin_segmentstatistics` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL COMMENT 'Foreign Key to LEK_task',
  `segmentId` int(11) NOT NULL COMMENT 'Foreign Key to LEK_segments',
  `fileId` int(11) NOT NULL COMMENT 'Foreign Key to segment source file, needed for grouping',
  `fieldName` varchar(120) NOT NULL COMMENT 'name of the segment field',
  `fieldType` varchar(120) NOT NULL COMMENT 'type of the segment field',
  `charCount` int(11) NOT NULL COMMENT 'number of chars (incl. whitespace) in the segment field',
  `termNotFound` int(11) NOT NULL COMMENT 'number of terms not translated in the target',
  `type` enum('import','export') DEFAULT 'import',
  `termFound` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `segmentIdFieldName` (`segmentId`,`fieldName`,`type`),
  KEY `fileId` (`fileId`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_plugin_segmentstatistics_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT `LEK_plugin_segmentstatistics_ibfk_2` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_qmsubsegments`
--

DROP TABLE IF EXISTS `LEK_qmsubsegments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_qmsubsegments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fieldedited` varchar(300) NOT NULL DEFAULT 'target',
  `taskGuid` varchar(38) NOT NULL,
  `segmentId` int(11) NOT NULL,
  `qmtype` int(11) NOT NULL,
  `severity` varchar(255) DEFAULT NULL,
  `comment` mediumtext,
  PRIMARY KEY (`id`),
  KEY `LEK_qmsubsegments_taskGuid_FK` (`taskGuid`),
  CONSTRAINT `LEK_qmsubsegments_taskGuid_FK` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segment_data`
--

DROP TABLE IF EXISTS `LEK_segment_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `duration` int(11) DEFAULT '0',
  `name` varchar(300) NOT NULL,
  `segmentId` int(11) NOT NULL,
  `mid` varchar(60) DEFAULT NULL,
  `original` longtext NOT NULL,
  `originalMd5` varchar(32) NOT NULL,
  `originalToSort` varchar(30) DEFAULT NULL,
  `edited` longtext,
  `editedToSort` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `segmentId` (`segmentId`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_segment_data_ibfk_1` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segment_field`
--

DROP TABLE IF EXISTS `LEK_segment_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_field` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL COMMENT 'Foreign Key to LEK_task',
  `name` varchar(120) NOT NULL COMMENT 'contains the label without invalid chars',
  `type` varchar(60) NOT NULL DEFAULT 'target',
  `label` varchar(300) NOT NULL COMMENT 'field label as provided by CSV / directory',
  `rankable` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'defines if this field is rankable in the ranker',
  `editable` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'defines if only the readOnly Content column is provided',
  `width` int(11) NOT NULL DEFAULT '0' COMMENT 'sets the width of the column in the GUI. Default 0, because actual max value is set with runtimeOptions.editor.columns.maxWidth and calculation needs to start at 0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuid` (`taskGuid`,`name`),
  CONSTRAINT `LEK_segment_field_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segment_history`
--

DROP TABLE IF EXISTS `LEK_segment_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'This is the DB save time of the History entry!',
  `segmentId` int(11) NOT NULL,
  `taskGuid` varchar(38) NOT NULL,
  `userGuid` varchar(38) NOT NULL,
  `userName` varchar(255) NOT NULL DEFAULT '',
  `timestamp` datetime NOT NULL COMMENT 'This is the old segment mod time',
  `editable` tinyint(1) NOT NULL,
  `pretrans` tinyint(1) NOT NULL,
  `qmId` varchar(255) DEFAULT NULL,
  `stateId` int(11) DEFAULT NULL,
  `autoStateId` int(11) NOT NULL DEFAULT '0',
  `workflowStepNr` int(11) NOT NULL DEFAULT '0',
  `workflowStep` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `LEK_segment_history_segmentId_FK` (`segmentId`),
  CONSTRAINT `LEK_segment_history_segmentId_FK` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segment_history_data`
--

DROP TABLE IF EXISTS `LEK_segment_history_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_history_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `duration` int(11) DEFAULT '0',
  `segmentHistoryId` int(11) NOT NULL,
  `name` varchar(300) NOT NULL,
  `edited` longtext,
  PRIMARY KEY (`id`),
  KEY `segmentHistoryId` (`segmentHistoryId`),
  CONSTRAINT `LEK_segment_history_data_ibfk_1` FOREIGN KEY (`segmentHistoryId`) REFERENCES `LEK_segment_history` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segment_view_02e4529d6aa9076830c7c3108d47fef2`
--

DROP TABLE IF EXISTS `LEK_segment_view_02e4529d6aa9076830c7c3108d47fef2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_view_02e4529d6aa9076830c7c3108d47fef2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentNrInTask` int(11) NOT NULL,
  `fileId` int(11) NOT NULL,
  `mid` varchar(1000) DEFAULT NULL,
  `userGuid` varchar(38) NOT NULL,
  `userName` varchar(255) NOT NULL DEFAULT '',
  `taskGuid` varchar(38) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `pretrans` tinyint(1) NOT NULL DEFAULT '0',
  `matchRate` int(11) NOT NULL DEFAULT '0',
  `qmId` varchar(255) DEFAULT NULL,
  `stateId` int(11) NOT NULL DEFAULT '0',
  `autoStateId` int(11) NOT NULL DEFAULT '0',
  `fileOrder` int(11) DEFAULT NULL,
  `comments` text,
  `workflowStepNr` int(11) NOT NULL DEFAULT '0',
  `workflowStep` varchar(60) DEFAULT NULL,
  `source` longtext NOT NULL,
  `sourceMd5` varchar(32) NOT NULL,
  `sourceToSort` varchar(30) DEFAULT NULL,
  `target` longtext NOT NULL,
  `targetMd5` varchar(32) NOT NULL,
  `targetToSort` varchar(30) DEFAULT NULL,
  `targetEdit` longtext,
  `targetEditToSort` varchar(30) DEFAULT NULL,
  `target1` longtext NOT NULL,
  `target1Md5` varchar(32) NOT NULL,
  `target1ToSort` varchar(30) DEFAULT NULL,
  `target1Edit` longtext,
  `target1EditToSort` varchar(30) DEFAULT NULL,
  `target2` longtext NOT NULL,
  `target2Md5` varchar(32) NOT NULL,
  `target2ToSort` varchar(30) DEFAULT NULL,
  `target2Edit` longtext,
  `target2EditToSort` varchar(30) DEFAULT NULL,
  `target3` longtext NOT NULL,
  `target3Md5` varchar(32) NOT NULL,
  `target3ToSort` varchar(30) DEFAULT NULL,
  `target3Edit` longtext,
  `target3EditToSort` varchar(30) DEFAULT NULL,
  `target4` longtext NOT NULL,
  `target4Md5` varchar(32) NOT NULL,
  `target4ToSort` varchar(30) DEFAULT NULL,
  `target4Edit` longtext,
  `target4EditToSort` varchar(30) DEFAULT NULL,
  `target5` longtext NOT NULL,
  `target5Md5` varchar(32) NOT NULL,
  `target5ToSort` varchar(30) DEFAULT NULL,
  `target5Edit` longtext,
  `target5EditToSort` varchar(30) DEFAULT NULL,
  `target6` longtext NOT NULL,
  `target6Md5` varchar(32) NOT NULL,
  `target6ToSort` varchar(30) DEFAULT NULL,
  `target6Edit` longtext,
  `target6EditToSort` varchar(30) DEFAULT NULL,
  `target7` longtext NOT NULL,
  `target7Md5` varchar(32) NOT NULL,
  `target7ToSort` varchar(30) DEFAULT NULL,
  `target7Edit` longtext,
  `target7EditToSort` varchar(30) DEFAULT NULL,
  `target8` longtext NOT NULL,
  `target8Md5` varchar(32) NOT NULL,
  `target8ToSort` varchar(30) DEFAULT NULL,
  `target8Edit` longtext,
  `target8EditToSort` varchar(30) DEFAULT NULL,
  `target9` longtext NOT NULL,
  `target9Md5` varchar(32) NOT NULL,
  `target9ToSort` varchar(30) DEFAULT NULL,
  `target9Edit` longtext,
  `target9EditToSort` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `LEK_segments_fileId_FK` (`fileId`),
  KEY `taskGuid` (`taskGuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segment_view_0618560fef824700904afad49134ad65`
--

DROP TABLE IF EXISTS `LEK_segment_view_0618560fef824700904afad49134ad65`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_view_0618560fef824700904afad49134ad65` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentNrInTask` int(11) NOT NULL,
  `fileId` int(11) NOT NULL,
  `mid` varchar(1000) DEFAULT NULL,
  `userGuid` varchar(38) NOT NULL,
  `userName` varchar(255) NOT NULL DEFAULT '',
  `taskGuid` varchar(38) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `pretrans` tinyint(1) NOT NULL DEFAULT '0',
  `matchRate` int(11) NOT NULL DEFAULT '0',
  `qmId` varchar(255) DEFAULT NULL,
  `stateId` int(11) NOT NULL DEFAULT '0',
  `autoStateId` int(11) NOT NULL DEFAULT '0',
  `fileOrder` int(11) DEFAULT NULL,
  `comments` text,
  `workflowStepNr` int(11) NOT NULL DEFAULT '0',
  `workflowStep` varchar(60) DEFAULT NULL,
  `source` longtext NOT NULL,
  `sourceMd5` varchar(32) NOT NULL,
  `sourceToSort` varchar(30) DEFAULT NULL,
  `target` longtext NOT NULL,
  `targetMd5` varchar(32) NOT NULL,
  `targetToSort` varchar(30) DEFAULT NULL,
  `targetEdit` longtext,
  `targetEditToSort` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `LEK_segments_fileId_FK` (`fileId`),
  KEY `taskGuid` (`taskGuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segment_view_557168d674fe16c9df3de257e1e0c992`
--

DROP TABLE IF EXISTS `LEK_segment_view_557168d674fe16c9df3de257e1e0c992`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_view_557168d674fe16c9df3de257e1e0c992` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentNrInTask` int(11) NOT NULL,
  `fileId` int(11) NOT NULL,
  `mid` varchar(1000) DEFAULT NULL,
  `userGuid` varchar(38) NOT NULL,
  `userName` varchar(255) NOT NULL DEFAULT '',
  `taskGuid` varchar(38) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `pretrans` tinyint(1) NOT NULL DEFAULT '0',
  `matchRate` int(11) NOT NULL DEFAULT '0',
  `qmId` varchar(255) DEFAULT NULL,
  `stateId` int(11) NOT NULL DEFAULT '0',
  `autoStateId` int(11) NOT NULL DEFAULT '0',
  `fileOrder` int(11) DEFAULT NULL,
  `comments` text,
  `workflowStepNr` int(11) NOT NULL DEFAULT '0',
  `workflowStep` varchar(60) DEFAULT NULL,
  `source` longtext NOT NULL,
  `sourceMd5` varchar(32) NOT NULL,
  `sourceToSort` varchar(30) DEFAULT NULL,
  `sourceEdit` longtext,
  `sourceEditToSort` varchar(30) DEFAULT NULL,
  `target` longtext NOT NULL,
  `targetMd5` varchar(32) NOT NULL,
  `targetToSort` varchar(30) DEFAULT NULL,
  `targetEdit` longtext,
  `targetEditToSort` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `LEK_segments_fileId_FK` (`fileId`),
  KEY `taskGuid` (`taskGuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segment_view_7814bdcd96f332fbade310d2ce7bc067`
--

DROP TABLE IF EXISTS `LEK_segment_view_7814bdcd96f332fbade310d2ce7bc067`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_view_7814bdcd96f332fbade310d2ce7bc067` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentNrInTask` int(11) NOT NULL,
  `fileId` int(11) NOT NULL,
  `mid` varchar(1000) DEFAULT NULL,
  `userGuid` varchar(38) NOT NULL,
  `userName` varchar(255) NOT NULL DEFAULT '',
  `taskGuid` varchar(38) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `pretrans` tinyint(1) NOT NULL DEFAULT '0',
  `matchRate` int(11) NOT NULL DEFAULT '0',
  `qmId` varchar(255) DEFAULT NULL,
  `stateId` int(11) NOT NULL DEFAULT '0',
  `autoStateId` int(11) NOT NULL DEFAULT '0',
  `fileOrder` int(11) DEFAULT NULL,
  `comments` text,
  `workflowStepNr` int(11) NOT NULL DEFAULT '0',
  `workflowStep` varchar(60) DEFAULT NULL,
  `source` longtext NOT NULL,
  `sourceMd5` varchar(32) NOT NULL,
  `sourceToSort` varchar(30) DEFAULT NULL,
  `target` longtext NOT NULL,
  `targetMd5` varchar(32) NOT NULL,
  `targetToSort` varchar(30) DEFAULT NULL,
  `targetEdit` longtext,
  `targetEditToSort` varchar(30) DEFAULT NULL,
  `target1` longtext NOT NULL,
  `target1Md5` varchar(32) NOT NULL,
  `target1ToSort` varchar(30) DEFAULT NULL,
  `target1Edit` longtext,
  `target1EditToSort` varchar(30) DEFAULT NULL,
  `target2` longtext NOT NULL,
  `target2Md5` varchar(32) NOT NULL,
  `target2ToSort` varchar(30) DEFAULT NULL,
  `target2Edit` longtext,
  `target2EditToSort` varchar(30) DEFAULT NULL,
  `target3` longtext NOT NULL,
  `target3Md5` varchar(32) NOT NULL,
  `target3ToSort` varchar(30) DEFAULT NULL,
  `target3Edit` longtext,
  `target3EditToSort` varchar(30) DEFAULT NULL,
  `target4` longtext NOT NULL,
  `target4Md5` varchar(32) NOT NULL,
  `target4ToSort` varchar(30) DEFAULT NULL,
  `target4Edit` longtext,
  `target4EditToSort` varchar(30) DEFAULT NULL,
  `target5` longtext NOT NULL,
  `target5Md5` varchar(32) NOT NULL,
  `target5ToSort` varchar(30) DEFAULT NULL,
  `target5Edit` longtext,
  `target5EditToSort` varchar(30) DEFAULT NULL,
  `target6` longtext NOT NULL,
  `target6Md5` varchar(32) NOT NULL,
  `target6ToSort` varchar(30) DEFAULT NULL,
  `target6Edit` longtext,
  `target6EditToSort` varchar(30) DEFAULT NULL,
  `target7` longtext NOT NULL,
  `target7Md5` varchar(32) NOT NULL,
  `target7ToSort` varchar(30) DEFAULT NULL,
  `target7Edit` longtext,
  `target7EditToSort` varchar(30) DEFAULT NULL,
  `target8` longtext NOT NULL,
  `target8Md5` varchar(32) NOT NULL,
  `target8ToSort` varchar(30) DEFAULT NULL,
  `target8Edit` longtext,
  `target8EditToSort` varchar(30) DEFAULT NULL,
  `target9` longtext NOT NULL,
  `target9Md5` varchar(32) NOT NULL,
  `target9ToSort` varchar(30) DEFAULT NULL,
  `target9Edit` longtext,
  `target9EditToSort` varchar(30) DEFAULT NULL,
  `target10` longtext NOT NULL,
  `target10Md5` varchar(32) NOT NULL,
  `target10ToSort` varchar(30) DEFAULT NULL,
  `target10Edit` longtext,
  `target10EditToSort` varchar(30) DEFAULT NULL,
  `target11` longtext NOT NULL,
  `target11Md5` varchar(32) NOT NULL,
  `target11ToSort` varchar(30) DEFAULT NULL,
  `target11Edit` longtext,
  `target11EditToSort` varchar(30) DEFAULT NULL,
  `target12` longtext NOT NULL,
  `target12Md5` varchar(32) NOT NULL,
  `target12ToSort` varchar(30) DEFAULT NULL,
  `target12Edit` longtext,
  `target12EditToSort` varchar(30) DEFAULT NULL,
  `target13` longtext NOT NULL,
  `target13Md5` varchar(32) NOT NULL,
  `target13ToSort` varchar(30) DEFAULT NULL,
  `target13Edit` longtext,
  `target13EditToSort` varchar(30) DEFAULT NULL,
  `target14` longtext NOT NULL,
  `target14Md5` varchar(32) NOT NULL,
  `target14ToSort` varchar(30) DEFAULT NULL,
  `target14Edit` longtext,
  `target14EditToSort` varchar(30) DEFAULT NULL,
  `target15` longtext NOT NULL,
  `target15Md5` varchar(32) NOT NULL,
  `target15ToSort` varchar(30) DEFAULT NULL,
  `target15Edit` longtext,
  `target15EditToSort` varchar(30) DEFAULT NULL,
  `target16` longtext NOT NULL,
  `target16Md5` varchar(32) NOT NULL,
  `target16ToSort` varchar(30) DEFAULT NULL,
  `target16Edit` longtext,
  `target16EditToSort` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `LEK_segments_fileId_FK` (`fileId`),
  KEY `taskGuid` (`taskGuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segment_view_8c66d6b5d54a526ea3fdc0a9c40f188e`
--

DROP TABLE IF EXISTS `LEK_segment_view_8c66d6b5d54a526ea3fdc0a9c40f188e`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_view_8c66d6b5d54a526ea3fdc0a9c40f188e` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentNrInTask` int(11) NOT NULL,
  `fileId` int(11) NOT NULL,
  `mid` varchar(1000) DEFAULT NULL,
  `userGuid` varchar(38) NOT NULL,
  `userName` varchar(255) NOT NULL DEFAULT '',
  `taskGuid` varchar(38) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `pretrans` tinyint(1) NOT NULL DEFAULT '0',
  `matchRate` int(11) NOT NULL DEFAULT '0',
  `qmId` varchar(255) DEFAULT NULL,
  `stateId` int(11) NOT NULL DEFAULT '0',
  `autoStateId` int(11) NOT NULL DEFAULT '0',
  `fileOrder` int(11) DEFAULT NULL,
  `comments` text,
  `workflowStepNr` int(11) NOT NULL DEFAULT '0',
  `workflowStep` varchar(60) DEFAULT NULL,
  `source` longtext NOT NULL,
  `sourceMd5` varchar(32) NOT NULL,
  `sourceToSort` varchar(30) DEFAULT NULL,
  `target` longtext NOT NULL,
  `targetMd5` varchar(32) NOT NULL,
  `targetToSort` varchar(30) DEFAULT NULL,
  `targetEdit` longtext,
  `targetEditToSort` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `LEK_segments_fileId_FK` (`fileId`),
  KEY `taskGuid` (`taskGuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segment_view_8ee5dd81f1ceba880e9cd1ce77585f68`
--

DROP TABLE IF EXISTS `LEK_segment_view_8ee5dd81f1ceba880e9cd1ce77585f68`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_view_8ee5dd81f1ceba880e9cd1ce77585f68` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentNrInTask` int(11) NOT NULL,
  `fileId` int(11) NOT NULL,
  `mid` varchar(1000) DEFAULT NULL,
  `userGuid` varchar(38) NOT NULL,
  `userName` varchar(255) NOT NULL DEFAULT '',
  `taskGuid` varchar(38) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `pretrans` tinyint(1) NOT NULL DEFAULT '0',
  `matchRate` int(11) NOT NULL DEFAULT '0',
  `qmId` varchar(255) DEFAULT NULL,
  `stateId` int(11) NOT NULL DEFAULT '0',
  `autoStateId` int(11) NOT NULL DEFAULT '0',
  `fileOrder` int(11) DEFAULT NULL,
  `comments` text,
  `workflowStepNr` int(11) NOT NULL DEFAULT '0',
  `workflowStep` varchar(60) DEFAULT NULL,
  `source` longtext NOT NULL,
  `sourceMd5` varchar(32) NOT NULL,
  `sourceToSort` varchar(30) DEFAULT NULL,
  `target` longtext NOT NULL,
  `targetMd5` varchar(32) NOT NULL,
  `targetToSort` varchar(30) DEFAULT NULL,
  `targetEdit` longtext,
  `targetEditToSort` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `LEK_segments_fileId_FK` (`fileId`),
  KEY `taskGuid` (`taskGuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segment_view_9ddb288e2d9a2c0988a8f5ea1aaa00bb`
--

DROP TABLE IF EXISTS `LEK_segment_view_9ddb288e2d9a2c0988a8f5ea1aaa00bb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_view_9ddb288e2d9a2c0988a8f5ea1aaa00bb` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentNrInTask` int(11) NOT NULL,
  `fileId` int(11) NOT NULL,
  `mid` varchar(1000) DEFAULT NULL,
  `userGuid` varchar(38) NOT NULL,
  `userName` varchar(255) NOT NULL DEFAULT '',
  `taskGuid` varchar(38) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `pretrans` tinyint(1) NOT NULL DEFAULT '0',
  `matchRate` int(11) NOT NULL DEFAULT '0',
  `qmId` varchar(255) DEFAULT NULL,
  `stateId` int(11) NOT NULL DEFAULT '0',
  `autoStateId` int(11) NOT NULL DEFAULT '0',
  `fileOrder` int(11) DEFAULT NULL,
  `comments` text,
  `workflowStepNr` int(11) NOT NULL DEFAULT '0',
  `workflowStep` varchar(60) DEFAULT NULL,
  `source` longtext NOT NULL,
  `sourceMd5` varchar(32) NOT NULL,
  `sourceToSort` varchar(30) DEFAULT NULL,
  `target` longtext NOT NULL,
  `targetMd5` varchar(32) NOT NULL,
  `targetToSort` varchar(30) DEFAULT NULL,
  `targetEdit` longtext,
  `targetEditToSort` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `LEK_segments_fileId_FK` (`fileId`),
  KEY `taskGuid` (`taskGuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segment_view_c8feb53a021809d61635c29449c3756a`
--

DROP TABLE IF EXISTS `LEK_segment_view_c8feb53a021809d61635c29449c3756a`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_view_c8feb53a021809d61635c29449c3756a` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentNrInTask` int(11) NOT NULL,
  `fileId` int(11) NOT NULL,
  `mid` varchar(1000) DEFAULT NULL,
  `userGuid` varchar(38) NOT NULL,
  `userName` varchar(255) NOT NULL DEFAULT '',
  `taskGuid` varchar(38) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `pretrans` tinyint(1) NOT NULL DEFAULT '0',
  `matchRate` int(11) NOT NULL DEFAULT '0',
  `qmId` varchar(255) DEFAULT NULL,
  `stateId` int(11) NOT NULL DEFAULT '0',
  `autoStateId` int(11) NOT NULL DEFAULT '0',
  `fileOrder` int(11) DEFAULT NULL,
  `comments` text,
  `workflowStepNr` int(11) NOT NULL DEFAULT '0',
  `workflowStep` varchar(60) DEFAULT NULL,
  `source` longtext NOT NULL,
  `sourceMd5` varchar(32) NOT NULL,
  `sourceToSort` varchar(30) DEFAULT NULL,
  `sourceEdit` longtext,
  `sourceEditToSort` varchar(30) DEFAULT NULL,
  `target` longtext NOT NULL,
  `targetMd5` varchar(32) NOT NULL,
  `targetToSort` varchar(30) DEFAULT NULL,
  `targetEdit` longtext,
  `targetEditToSort` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `LEK_segments_fileId_FK` (`fileId`),
  KEY `taskGuid` (`taskGuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segment_view_cb74eafa6191ed8f67fa518a4a759484`
--

DROP TABLE IF EXISTS `LEK_segment_view_cb74eafa6191ed8f67fa518a4a759484`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segment_view_cb74eafa6191ed8f67fa518a4a759484` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentNrInTask` int(11) NOT NULL,
  `fileId` int(11) NOT NULL,
  `mid` varchar(1000) DEFAULT NULL,
  `userGuid` varchar(38) NOT NULL,
  `userName` varchar(255) NOT NULL DEFAULT '',
  `taskGuid` varchar(38) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `pretrans` tinyint(1) NOT NULL DEFAULT '0',
  `matchRate` int(11) NOT NULL DEFAULT '0',
  `qmId` varchar(255) DEFAULT NULL,
  `stateId` int(11) NOT NULL DEFAULT '0',
  `autoStateId` int(11) NOT NULL DEFAULT '0',
  `fileOrder` int(11) DEFAULT NULL,
  `comments` text,
  `workflowStepNr` int(11) NOT NULL DEFAULT '0',
  `workflowStep` varchar(60) DEFAULT NULL,
  `source` longtext NOT NULL,
  `sourceMd5` varchar(32) NOT NULL,
  `sourceToSort` varchar(30) DEFAULT NULL,
  `sourceEdit` longtext,
  `sourceEditToSort` varchar(30) DEFAULT NULL,
  `target` longtext NOT NULL,
  `targetMd5` varchar(32) NOT NULL,
  `targetToSort` varchar(30) DEFAULT NULL,
  `targetEdit` longtext,
  `targetEditToSort` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `LEK_segments_fileId_FK` (`fileId`),
  KEY `taskGuid` (`taskGuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segmentmetadata`
--

DROP TABLE IF EXISTS `LEK_segmentmetadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segmentmetadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentId` int(11) NOT NULL,
  `additionalInfo` text NOT NULL,
  `orderNumber` text NOT NULL,
  `taskId` int(11) NOT NULL,
  `vendor` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `segmentId` (`segmentId`),
  CONSTRAINT `LEK_segmentmetadata_segmentId_FK` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segments`
--

DROP TABLE IF EXISTS `LEK_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentNrInTask` int(11) NOT NULL,
  `fileId` int(11) NOT NULL,
  `mid` varchar(1000) DEFAULT NULL,
  `userGuid` varchar(38) NOT NULL,
  `userName` varchar(255) NOT NULL DEFAULT '',
  `taskGuid` varchar(38) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `pretrans` tinyint(1) NOT NULL DEFAULT '0',
  `matchRate` int(11) NOT NULL DEFAULT '0',
  `qmId` varchar(255) DEFAULT NULL,
  `stateId` int(11) NOT NULL DEFAULT '0',
  `autoStateId` int(11) NOT NULL DEFAULT '0',
  `fileOrder` int(11) DEFAULT NULL,
  `comments` text,
  `workflowStepNr` int(11) NOT NULL DEFAULT '0',
  `workflowStep` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `LEK_segments_fileId_FK` (`fileId`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_segments_fileId_FK` FOREIGN KEY (`fileId`) REFERENCES `LEK_files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `LEK_segments_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segments2terms`
--

DROP TABLE IF EXISTS `LEK_segments2terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segments2terms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentId` int(11) NOT NULL,
  `isSource` tinyint(1) NOT NULL DEFAULT '1',
  `used` tinyint(1) NOT NULL DEFAULT '0',
  `termId` int(11) NOT NULL,
  `transFound` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `termId` (`termId`),
  KEY `segmentId` (`segmentId`),
  CONSTRAINT `LEK_segments2terms_ibfk_1` FOREIGN KEY (`termId`) REFERENCES `LEK_terms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `LEK_segments2terms_ibfk_2` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_segments_meta`
--

DROP TABLE IF EXISTS `LEK_segments_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_segments_meta` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL COMMENT 'Foreign Key to LEK_task',
  `segmentId` int(11) NOT NULL COMMENT 'Foreign Key to LEK_segments',
  `notTranslated` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'defines, if segment is marked in imported file as locked not translated - or is acutally empty, but the source is not empty.',
  `termtagState` varchar(36) DEFAULT 'untagged' COMMENT 'Contains the TermTagger-state for this segment while importing',
  PRIMARY KEY (`id`),
  UNIQUE KEY `segmentId` (`segmentId`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_segments_meta_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT `LEK_segments_meta_ibfk_2` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_skeletonfiles`
--

DROP TABLE IF EXISTS `LEK_skeletonfiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_skeletonfiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fileId` int(11) NOT NULL,
  `fileName` varchar(255) NOT NULL,
  `file` longblob NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fileId` (`fileId`),
  CONSTRAINT `LEK_skeletonfiles_fileId_FK` FOREIGN KEY (`fileId`) REFERENCES `LEK_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_task`
--

DROP TABLE IF EXISTS `LEK_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entityVersion` int(11) NOT NULL DEFAULT '0',
  `taskGuid` varchar(38) NOT NULL,
  `taskNr` varchar(120) NOT NULL DEFAULT '',
  `taskName` varchar(255) NOT NULL DEFAULT '',
  `sourceLang` int(11) NOT NULL,
  `targetLang` int(11) NOT NULL,
  `relaisLang` int(11) NOT NULL,
  `lockedInternalSessionUniqId` char(32) DEFAULT NULL,
  `locked` datetime DEFAULT NULL,
  `lockingUser` varchar(38) DEFAULT NULL,
  `state` varchar(38) NOT NULL DEFAULT 'import',
  `workflow` varchar(60) NOT NULL DEFAULT 'default',
  `workflowStep` int(11) NOT NULL DEFAULT '1',
  `pmGuid` varchar(38) NOT NULL,
  `pmName` varchar(512) NOT NULL,
  `wordCount` int(11) NOT NULL,
  `userCount` int(11) NOT NULL DEFAULT '0',
  `targetDeliveryDate` datetime DEFAULT NULL,
  `realDeliveryDate` datetime DEFAULT NULL,
  `referenceFiles` tinyint(1) NOT NULL DEFAULT '0',
  `terminologie` tinyint(1) NOT NULL DEFAULT '0',
  `orderdate` datetime DEFAULT NULL,
  `enableSourceEditing` tinyint(1) NOT NULL DEFAULT '0',
  `edit100PercentMatch` tinyint(1) NOT NULL DEFAULT '0',
  `qmSubsegmentFlags` mediumtext,
  `exportRunning` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuid` (`taskGuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `LEK_task_versioning` BEFORE UPDATE ON `LEK_task`
 FOR EACH ROW IF OLD.entityVersion = NEW.entityVersion THEN 
          SET NEW.entityVersion = OLD.entityVersion + 1;
        ELSE 
          CALL raise_version_conflict; 
        END IF */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `LEK_taskUserAssoc`
--

DROP TABLE IF EXISTS `LEK_taskUserAssoc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_taskUserAssoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `userGuid` varchar(38) NOT NULL,
  `state` varchar(60) NOT NULL DEFAULT 'open',
  `role` varchar(60) NOT NULL DEFAULT 'lector',
  `usedState` varchar(60) DEFAULT NULL,
  `usedInternalSessionUniqId` char(32) DEFAULT NULL,
  `isPmOverride` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuid` (`taskGuid`,`userGuid`,`role`),
  KEY `userGuid` (`userGuid`),
  CONSTRAINT `LEK_taskUserAssoc_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT `LEK_taskUserAssoc_ibfk_2` FOREIGN KEY (`userGuid`) REFERENCES `Zf_users` (`userGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `LEK_taskUserAssoc_versioning_ins` BEFORE INSERT ON `LEK_taskUserAssoc`
 FOR EACH ROW IF not @`entityVersion` is null THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = NEW.taskGuid;
          SET @`entityVersion` := null;
        END IF */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `LEK_taskUserAssoc_versioning_up` BEFORE UPDATE ON `LEK_taskUserAssoc`
 FOR EACH ROW IF not @`entityVersion` is null THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = NEW.taskGuid;
          SET @`entityVersion` := null;
        END IF */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `LEK_taskUserAssoc_versioning_del` BEFORE DELETE ON `LEK_taskUserAssoc`
 FOR EACH ROW IF not @`entityVersion` is null THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = OLD.taskGuid;
          SET @`entityVersion` := null;
        END IF */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `LEK_task_log`
--

DROP TABLE IF EXISTS `LEK_task_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_task_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `state` varchar(60) NOT NULL,
  `authUserGuid` varchar(38) NOT NULL,
  `authUserLogin` varchar(255) NOT NULL,
  `authUserName` varchar(512) NOT NULL,
  `userGuid` varchar(38) DEFAULT NULL,
  `userLogin` varchar(255) DEFAULT NULL,
  `userName` varchar(512) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_task_log_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_task_meta`
--

DROP TABLE IF EXISTS `LEK_task_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_task_meta` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL COMMENT 'Foreign Key to LEK_task',
  `tbxHash` varchar(36) NOT NULL COMMENT 'TBX Hash value',
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_task_meta_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_terminstances`
--

DROP TABLE IF EXISTS `LEK_terminstances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_terminstances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentId` int(11) NOT NULL,
  `term` varchar(21000) NOT NULL DEFAULT '',
  `termId` int(11) NOT NULL,
  `projectTerminstanceId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `termId` (`termId`),
  KEY `segmentId` (`segmentId`),
  CONSTRAINT `LEK_terminstances_ibfk_1` FOREIGN KEY (`termId`) REFERENCES `LEK_terms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `LEK_terminstances_ibfk_2` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_terms`
--

DROP TABLE IF EXISTS `LEK_terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_terms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `term` varchar(19000) NOT NULL DEFAULT '',
  `mid` varchar(60) DEFAULT NULL,
  `status` varchar(255) NOT NULL,
  `definition` varchar(255) DEFAULT NULL,
  `groupId` varchar(255) NOT NULL,
  `language` int(32) NOT NULL,
  `tigId` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `taskGuid` (`taskGuid`,`mid`),
  KEY `groupId` (`groupId`),
  KEY `taskGuid_2` (`taskGuid`),
  KEY `tigId` (`tigId`),
  CONSTRAINT `LEK_terms_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_workflow_log`
--

DROP TABLE IF EXISTS `LEK_workflow_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_workflow_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `userGuid` varchar(38) NOT NULL,
  `stepName` varchar(60) NOT NULL,
  `stepNr` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_workflow_log_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `LEK_workflow_userpref`
--

DROP TABLE IF EXISTS `LEK_workflow_userpref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `LEK_workflow_userpref` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL COMMENT 'Foreign Key to LEK_task',
  `workflow` varchar(60) NOT NULL COMMENT 'links to the used workflow for this ',
  `workflowStep` varchar(60) DEFAULT NULL COMMENT 'the workflow step which is affected by the settings, optional, null to affect all steps',
  `anonymousCols` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'should the column names be rendered anonymously',
  `visibility` enum('show','hide','disable') DEFAULT 'show' COMMENT 'visibility of non-editable target columns',
  `userGuid` varchar(38) DEFAULT NULL COMMENT 'Foreign Key to Zf_users, optional, constrain the prefs to this user',
  `fields` varchar(300) NOT NULL COMMENT 'field names as used in LEK_segment_fields',
  PRIMARY KEY (`id`),
  KEY `taskGuid` (`taskGuid`,`userGuid`),
  CONSTRAINT `LEK_workflow_userpref_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT `LEK_workflow_userpref_ibfk_2` FOREIGN KEY (`taskGuid`, `userGuid`) REFERENCES `LEK_taskUserAssoc` (`taskGuid`, `userGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `LEK_workflow_userpref_versioning_ins` BEFORE INSERT ON `LEK_workflow_userpref`
 FOR EACH ROW IF not @`entityVersion` is null THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = NEW.taskGuid;
          SET @`entityVersion` := null;
        END IF */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `LEK_workflow_userpref_versioning_up` BEFORE UPDATE ON `LEK_workflow_userpref`
 FOR EACH ROW IF not @`entityVersion` is null THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = NEW.taskGuid;
          SET @`entityVersion` := null;
        END IF */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `LEK_workflow_userpref_versioning_del` BEFORE DELETE ON `LEK_workflow_userpref`
 FOR EACH ROW IF not @`entityVersion` is null THEN
          UPDATE LEK_task SET entityVersion = @`entityVersion` WHERE taskGuid = OLD.taskGuid;
          SET @`entityVersion` := null;
        END IF */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `Zf_acl_rules`
--

DROP TABLE IF EXISTS `Zf_acl_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_acl_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(100) DEFAULT NULL COMMENT 'the PHP module this acl rule was defined for',
  `role` varchar(100) NOT NULL COMMENT 'the name of the role which has the defined rule',
  `resource` varchar(100) NOT NULL COMMENT 'the resource to be allowed',
  `right` varchar(100) NOT NULL COMMENT 'the single right to be allowed',
  PRIMARY KEY (`id`),
  UNIQUE KEY `module` (`module`,`role`,`resource`,`right`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Zf_configuration`
--

DROP TABLE IF EXISTS `Zf_configuration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_configuration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'corresponds to the old INI key',
  `confirmed` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'used for new values, 0 not confirmed by user, 1 confirmed',
  `module` varchar(100) DEFAULT NULL COMMENT 'the PHP module this config value was defined for',
  `category` varchar(100) NOT NULL DEFAULT 'other' COMMENT 'field to categorize the config values',
  `value` varchar(1024) DEFAULT NULL COMMENT 'the config value, if data exceeds 1024byte (especially for list and map) data should be stored in a own table',
  `default` varchar(1024) DEFAULT NULL COMMENT 'the system default value for this config',
  `defaults` varchar(1024) DEFAULT NULL COMMENT 'a comma separated list of default values, only one of this value is possible to be set by the GUI',
  `type` enum('string','integer','boolean','list','map','absolutepath') NOT NULL DEFAULT 'string' COMMENT 'the type of the config value is needed also for GUI',
  `description` varchar(1024) NOT NULL COMMENT 'contains a human readable description for what this config is for',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Zf_dbversion`
--

DROP TABLE IF EXISTS `Zf_dbversion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_dbversion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `origin` varchar(512) NOT NULL DEFAULT '',
  `filename` varchar(512) NOT NULL DEFAULT '',
  `md5` varchar(32) NOT NULL,
  `appVersion` varchar(32) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Zf_invalidlogin`
--

DROP TABLE IF EXISTS `Zf_invalidlogin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_invalidlogin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Zf_passwdreset`
--

DROP TABLE IF EXISTS `Zf_passwdreset`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_passwdreset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resetHash` varchar(32) DEFAULT NULL,
  `userId` int(11) DEFAULT NULL,
  `expiration` int(11) DEFAULT NULL,
  `internalSessionUniqId` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `Zf_passwdreset_userId_FK` (`userId`),
  CONSTRAINT `Zf_passwdreset_userId_FK` FOREIGN KEY (`userId`) REFERENCES `Zf_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Zf_users`
--

DROP TABLE IF EXISTS `Zf_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userGuid` varchar(38) NOT NULL,
  `firstName` varchar(255) NOT NULL,
  `surName` varchar(255) NOT NULL,
  `gender` char(1) NOT NULL,
  `login` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `roles` varchar(255) DEFAULT NULL,
  `passwd` varchar(38) DEFAULT NULL,
  `editable` tinyint(1) NOT NULL DEFAULT '1',
  `locale` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userGuid` (`userGuid`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `Zf_worker`
--

DROP TABLE IF EXISTS `Zf_worker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Zf_worker` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `state` varchar(38) NOT NULL DEFAULT 'waiting',
  `worker` varchar(255) NOT NULL DEFAULT '',
  `resource` varchar(255) NOT NULL DEFAULT '',
  `slot` varchar(255) NOT NULL DEFAULT '',
  `maxParallelProcesses` int(11) NOT NULL DEFAULT '1',
  `taskGuid` varchar(38) DEFAULT '',
  `parameters` longtext,
  `pid` int(11) DEFAULT NULL,
  `starttime` varchar(255) NOT NULL DEFAULT '',
  `endtime` varchar(255) DEFAULT NULL,
  `maxRuntime` varchar(255) NOT NULL DEFAULT '',
  `hash` varchar(255) NOT NULL DEFAULT '',
  `blockingType` varchar(38) NOT NULL DEFAULT 'slot',
  PRIMARY KEY (`id`),
  KEY `worker` (`worker`),
  KEY `slot` (`slot`),
  KEY `taskGuid` (`taskGuid`),
  KEY `starttime` (`starttime`),
  KEY `hash` (`hash`),
  KEY `state` (`state`),
  KEY `resource` (`resource`),
  KEY `maxRuntime` (`maxRuntime`),
  KEY `endtime` (`endtime`),
  CONSTRAINT `zf_worker_ibfk_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `session` (
  `session_id` char(32) NOT NULL,
  `name` varchar(32) NOT NULL DEFAULT '',
  `modified` int(11) DEFAULT NULL,
  `lifetime` int(11) DEFAULT NULL,
  `session_data` longtext,
  PRIMARY KEY (`session_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessionMapInternalUniqId`
--

DROP TABLE IF EXISTS `sessionMapInternalUniqId`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessionMapInternalUniqId` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `internalSessionUniqId` varchar(32) DEFAULT NULL,
  `session_id` varchar(32) DEFAULT NULL,
  `modified` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `internalSessionUniqId` (`internalSessionUniqId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sessionUserLock`
--

DROP TABLE IF EXISTS `sessionUserLock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessionUserLock` (
  `login` varchar(255) NOT NULL,
  `internalSessionUniqId` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`login`),
  KEY `userlock_sessionid_fk` (`internalSessionUniqId`),
  CONSTRAINT `sessionUserLock_ibfk_1` FOREIGN KEY (`internalSessionUniqId`) REFERENCES `sessionMapInternalUniqId` (`internalSessionUniqId`) ON DELETE CASCADE,
  CONSTRAINT `sessionUserLock_ibfk_2` FOREIGN KEY (`login`) REFERENCES `Zf_users` (`login`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-05-18 11:08:20
