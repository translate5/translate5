-- phpMyAdmin SQL Dump
-- version 3.5.8.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 10, 2014 at 08:46 AM
-- Server version: 5.5.32
-- PHP Version: 5.4.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `translate5`
--

-- --------------------------------------------------------

--
-- Table structure for table `LEK_segment_field`
--

CREATE TABLE IF NOT EXISTS `LEK_segment_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL COMMENT 'Foreign Key to LEK_task',
  `name` varchar(300) NOT NULL COMMENT 'contains the label without invalid chars',
  `label` varchar(300) NOT NULL COMMENT 'field label as provided by CSV / directory',
  `rankable` tinyint(1) NOT NULL COMMENT 'defines if this field is rankable in the ranker',
  `editable` tinyint(1) NOT NULL COMMENT 'defines if only the readOnly Content column is provided',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
