--  /*
--  START LICENSE AND COPYRIGHT
--  
--  This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
--  
--  Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU General Public License version 3.0
--  as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU General Public License version 3.0 requirements will be met:
--  http://www.gnu.org/copyleft/gpl.html.
-- 
--  For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
--  General Public License version 3.0 as specified by Sencha for Ext Js. 
--  Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
--  that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
--  For further information regarding this topic please see the attached license.txt
--  of this software package.
--  
--  MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
--  brought in accordance with the ExtJs license scheme. You are welcome to support us
--  with legal support, if you are interested in this.
--  
--  
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
--              with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
--  
--  END LICENSE AND COPYRIGHT 
--  */
-- 

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
