-- MySQL dump 10.13  Distrib 5.5.40, for Linux (x86_64)
--
-- Host: mittagqi    Database: tmerp
-- ------------------------------------------------------
-- Server version	5.5.40-log

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
-- Table structure for table `ERP_customer`
--

DROP TABLE IF EXISTS `ERP_customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ERP_customer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `number` varchar(255) DEFAULT NULL,
  `shortcut` varchar(255) DEFAULT NULL,
  `keyaccount` int(11) DEFAULT NULL,
  `taxPercent` decimal(6,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number` (`number`),
  KEY `name` (`name`),
  KEY `shortcut` (`shortcut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ERP_keyaccount`
--

DROP TABLE IF EXISTS `ERP_keyaccount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ERP_keyaccount` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ERP_order`
--

DROP TABLE IF EXISTS `ERP_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ERP_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entityVersion` int(11) DEFAULT '0',
  `debitNumber` varchar(9) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `offerDate` datetime DEFAULT NULL,
  `billDate` datetime DEFAULT NULL,
  `paidDate` datetime DEFAULT NULL,
  `releaseDate` datetime DEFAULT NULL,
  `modifiedDate` datetime DEFAULT NULL,
  `conversionMonth` int(11) DEFAULT NULL,
  `conversionYear` varchar(4) DEFAULT NULL,
  `keyAccount` varchar(255) DEFAULT NULL,
  `customerId` int(11) DEFAULT NULL,
  `customerName` varchar(255) NOT NULL DEFAULT '',
  `customerNumber` varchar(255) DEFAULT NULL,
  `customerOrder` varchar(255) DEFAULT NULL,
  `pmId` int(11) DEFAULT NULL,
  `pmName` varchar(255) NOT NULL DEFAULT '',
  `offerNetValue` decimal(19,4) DEFAULT NULL,
  `offerTaxValue` decimal(19,4) DEFAULT NULL,
  `offerGrossValue` decimal(19,4) DEFAULT NULL,
  `offerMargin` decimal(19,4) DEFAULT NULL,
  `billNetValue` decimal(19,4) DEFAULT NULL,
  `billTaxValue` decimal(19,4) DEFAULT NULL,
  `billGrossValue` decimal(19,4) DEFAULT NULL,
  `billMargin` decimal(19,4) DEFAULT NULL,
  `taxPercent` decimal(6,4) DEFAULT NULL,
  `state` enum('proforma','offered','declined','ordered','cancelled','billed','paid') NOT NULL DEFAULT 'offered',
  `comments` text,
  `checked` tinyint(1) DEFAULT NULL,
  `checkerId` int(11) DEFAULT NULL,
  `checkerName` varchar(255) DEFAULT NULL,
  `editorId` int(11) DEFAULT NULL,
  `editorName` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `debitNumber` (`debitNumber`),
  KEY `name` (`name`),
  KEY `conversionMonth` (`conversionMonth`),
  KEY `conversionYear` (`conversionYear`),
  KEY `keyAccount` (`keyAccount`),
  KEY `customerId` (`customerId`),
  KEY `customerName` (`customerName`),
  KEY `customerNumber` (`customerNumber`),
  KEY `customerOrder` (`customerOrder`),
  KEY `pmName` (`pmName`),
  KEY `state` (`state`),
  KEY `checked` (`checked`),
  KEY `pmId` (`pmId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `ERP_order_versioning` BEFORE UPDATE ON `ERP_order`
		FOR EACH ROW
			IF OLD.entityVersion = NEW.entityVersion THEN 
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
-- Table structure for table `ERP_order_comment`
--

DROP TABLE IF EXISTS `ERP_order_comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ERP_order_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `userName` varchar(255) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `orderId` (`orderId`),
  KEY `userId` (`userId`),
  CONSTRAINT `erp_order_comment_ibfk_1` FOREIGN KEY (`orderId`) REFERENCES `ERP_order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ERP_order_history`
--

DROP TABLE IF EXISTS `ERP_order_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ERP_order_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderId` int(11) NOT NULL,
  `historyCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `debitNumber` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `offerDate` datetime DEFAULT NULL,
  `billDate` datetime DEFAULT NULL,
  `paidDate` datetime DEFAULT NULL,
  `releaseDate` datetime DEFAULT NULL,
  `modifiedDate` datetime DEFAULT NULL,
  `conversionMonth` int(11) DEFAULT NULL,
  `conversionYear` varchar(4) DEFAULT NULL,
  `keyAccount` varchar(255) DEFAULT NULL,
  `customerId` int(11) DEFAULT NULL,
  `customerName` varchar(255) NOT NULL DEFAULT '',
  `customerNumber` varchar(255) DEFAULT NULL,
  `customerOrder` varchar(255) DEFAULT NULL,
  `pmId` int(11) DEFAULT NULL,
  `pmName` varchar(255) NOT NULL DEFAULT '',
  `offerNetValue` decimal(19,4) DEFAULT NULL,
  `offerTaxValue` decimal(19,4) DEFAULT NULL,
  `offerGrossValue` decimal(19,4) DEFAULT NULL,
  `offerMargin` decimal(19,4) DEFAULT NULL,
  `billNetValue` decimal(19,4) DEFAULT NULL,
  `billTaxValue` decimal(19,4) DEFAULT NULL,
  `billGrossValue` decimal(19,4) DEFAULT NULL,
  `billMargin` decimal(19,4) DEFAULT NULL,
  `taxPercent` decimal(6,4) DEFAULT NULL,
  `state` enum('proforma','offered','declined','ordered','cancelled','billed','paid') NOT NULL DEFAULT 'offered',
  `comments` text,
  `checked` tinyint(1) DEFAULT NULL,
  `checkerId` int(11) DEFAULT NULL,
  `checkerName` varchar(255) DEFAULT NULL,
  `editorId` int(11) DEFAULT NULL,
  `editorName` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orderId` (`orderId`),
  KEY `historyCreated` (`historyCreated`),
  CONSTRAINT `erp_order_history_ibfk_1` FOREIGN KEY (`orderId`) REFERENCES `ERP_order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ERP_purchaseOrder`
--

DROP TABLE IF EXISTS `ERP_purchaseOrder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ERP_purchaseOrder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entityVersion` int(11) DEFAULT '0',
  `orderId` int(11) DEFAULT NULL,
  `number` int(11) DEFAULT NULL,
  `creationDate` datetime DEFAULT NULL,
  `customerName` varchar(255) DEFAULT NULL,
  `pmId` int(11) DEFAULT NULL,
  `pmName` varchar(255) DEFAULT NULL,
  `vendorId` int(11) DEFAULT NULL,
  `vendorName` varchar(255) DEFAULT NULL,
  `vendorNumber` varchar(255) DEFAULT NULL,
  `netValue` decimal(19,4) DEFAULT NULL,
  `taxValue` decimal(19,4) DEFAULT NULL,
  `grossValue` decimal(19,4) DEFAULT NULL,
  `taxPercent` decimal(6,4) DEFAULT NULL,
  `vendorCurrency` varchar(10) DEFAULT NULL,
  `originalNetValue` decimal(19,4) DEFAULT NULL,
  `originalTaxValue` decimal(19,4) DEFAULT NULL,
  `originalGrossValue` decimal(19,4) DEFAULT NULL,
  `state` enum('created','billed','paid','cancelled') DEFAULT 'created',
  `billDate` datetime DEFAULT NULL,
  `billReceivedDate` datetime DEFAULT NULL,
  `paymentTerm` int(11) DEFAULT NULL,
  `checked` tinyint(1) DEFAULT NULL,
  `checkerId` int(11) DEFAULT NULL,
  `checkerName` varchar(255) DEFAULT NULL,
  `paidDate` datetime DEFAULT NULL,
  `billNumber` varchar(255) DEFAULT NULL,
  `comments` text,
  `editorId` int(11) DEFAULT NULL,
  `editorName` varchar(255) DEFAULT NULL,
  `modifiedDate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orderId` (`orderId`),
  KEY `number` (`number`),
  KEY `creationDate` (`creationDate`),
  KEY `customerName` (`customerName`),
  KEY `vendorId` (`vendorId`),
  KEY `vendorName` (`vendorName`),
  KEY `state` (`state`),
  KEY `checked` (`checked`),
  KEY `pmId` (`pmId`),
  CONSTRAINT `erp_purchaseorder_ibfk_1` FOREIGN KEY (`orderId`) REFERENCES `ERP_order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `ERP_purchadeOrder_versioning` BEFORE UPDATE ON `ERP_purchaseOrder`
		FOR EACH ROW
			IF OLD.entityVersion = NEW.entityVersion THEN 
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
-- Table structure for table `ERP_purchaseOrder_comment`
--

DROP TABLE IF EXISTS `ERP_purchaseOrder_comment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ERP_purchaseOrder_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchaseOrderId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `userName` varchar(255) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `purchaseOrderId` (`purchaseOrderId`),
  KEY `userId` (`userId`),
  CONSTRAINT `erp_purchaseorder_comment_ibfk_1` FOREIGN KEY (`purchaseOrderId`) REFERENCES `ERP_purchaseOrder` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ERP_purchaseOrder_history`
--

DROP TABLE IF EXISTS `ERP_purchaseOrder_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ERP_purchaseOrder_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchaseOrderId` int(11) NOT NULL,
  `historyCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `orderId` int(11) NOT NULL,
  `number` int(11) DEFAULT NULL,
  `creationDate` datetime DEFAULT NULL,
  `customerName` varchar(255) DEFAULT NULL,
  `pmId` int(11) DEFAULT NULL,
  `pmName` varchar(255) DEFAULT NULL,
  `vendorId` int(11) DEFAULT NULL,
  `vendorName` varchar(255) DEFAULT NULL,
  `vendorNumber` varchar(255) DEFAULT NULL,
  `netValue` decimal(19,4) DEFAULT NULL,
  `taxValue` decimal(19,4) DEFAULT NULL,
  `grossValue` decimal(19,4) DEFAULT NULL,
  `taxPercent` decimal(6,4) DEFAULT NULL,
  `vendorCurrency` varchar(10) DEFAULT NULL,
  `originalNetValue` decimal(19,4) DEFAULT NULL,
  `originalTaxValue` decimal(19,4) DEFAULT NULL,
  `originalGrossValue` decimal(19,4) DEFAULT NULL,
  `state` enum('created','billed','paid','cancelled') DEFAULT 'created',
  `billDate` datetime DEFAULT NULL,
  `billReceivedDate` datetime DEFAULT NULL,
  `paymentTerm` int(11) DEFAULT NULL,
  `checked` tinyint(1) DEFAULT NULL,
  `checkerId` int(11) DEFAULT NULL,
  `checkerName` varchar(255) DEFAULT NULL,
  `paidDate` datetime DEFAULT NULL,
  `billNumber` varchar(255) DEFAULT NULL,
  `comments` text,
  `editorId` int(11) DEFAULT NULL,
  `editorName` varchar(255) DEFAULT NULL,
  `modifiedDate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchaseOrderId` (`purchaseOrderId`),
  KEY `historyCreated` (`historyCreated`),
  CONSTRAINT `erp_purchaseorder_history_ibfk_1` FOREIGN KEY (`purchaseOrderId`) REFERENCES `ERP_purchaseOrder` (`id`) ON DELETE CASCADE
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

-- Dump completed on 2015-05-18 11:54:35
