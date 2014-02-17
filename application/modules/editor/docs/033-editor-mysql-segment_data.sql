-- phpMyAdmin SQL Dump
-- version 3.5.8.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 13, 2014 at 12:46 PM
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
-- Table structure for table `LEK_segment_data`
--

CREATE TABLE IF NOT EXISTS `LEK_segment_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `name` varchar(300) NOT NULL,
  `segmentId` int(11) NOT NULL,
  `mid` varchar(1000) DEFAULT NULL,
  `origina` longtext NOT NULL,
  `originalMd5` varchar(32) NOT NULL,
  `originalToSort` varchar(300) DEFAULT NULL,
  `edited` longtext,
  `editedMd5` varchar(32) NOT NULL,
  `editedToSort` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `taskGuid` (`taskGuid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
