-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--   
--  There is a plugin exception available for use with this release of translate5 for
--  translate5: Please see http://www.translate5.net/plugin-exception.txt or 
--  plugin-exception.txt in the root folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

CREATE TABLE `LEK_terms` (
  `id` int (11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `term` varchar (255) NOT NULL default '',
  `termId` varchar (255) NOT NULL default '',
  `status` varchar (255) NOT NULL,
  `definition` varchar (255) NOT NULL,
  `groupId` varchar (255) NOT NULL,
  `language` varchar (32) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX (`groupId`),
  UNIQUE (`taskGuid`,`termId`),
  INDEX (`taskGuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `LEK_segments2terms` (
  `id` int (11) NOT NULL AUTO_INCREMENT,
  `segmentId` int (11) NOT NULL,
  `lang` varchar (6) NOT NULL default '',
  `used` tinyint (1) NOT NULL default 0,
  `termId` int (11) NOT NULL,
  `transFound` tinyint (1) NOT NULL default 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`termId`) REFERENCES `LEK_terms` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE `LEK_terminstances` (
  `id` int (11) NOT NULL AUTO_INCREMENT,
  `segmentId` int (11) NOT NULL,
  `term` varchar (255) NOT NULL default '',
  `termId` int (11) NOT NULL,
  `projectTerminstanceId` int (11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`termId`) REFERENCES `LEK_terms` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE `LEK_segmentterms`;

-- test data:
-- DROP all: 
-- DROP table LEK_segments2terms; DROP table `LEK_terminstances`; drop table `LEK_terms`;
