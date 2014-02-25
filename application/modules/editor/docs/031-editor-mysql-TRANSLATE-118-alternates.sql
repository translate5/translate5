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
CREATE TABLE IF NOT EXISTS `LEK_segment_field` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL COMMENT 'Foreign Key to LEK_task',
  `name` varchar(300) NOT NULL COMMENT 'contains the label without invalid chars',
  `type` varchar(60) not null default 'target',
  `label` varchar(300) NOT NULL COMMENT 'field label as provided by CSV / directory',
  `rankable` tinyint(1) NOT NULL default 0 COMMENT 'defines if this field is rankable in the ranker',
  `editable` tinyint(1) NOT NULL default 0 COMMENT 'defines if only the readOnly Content column is provided',
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `LEK_segment_history_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `segmentHistoryId` int(11) NOT NULL,
  `name` varchar(300) NOT NULL,
  `edited` longtext,
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`segmentHistoryId`) REFERENCES `LEK_segment_history` (`id`) ON DELETE CASCADE,
  KEY `taskGuid` (`taskGuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `LEK_segment_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `name` varchar(300) NOT NULL,
  `segmentId` int(11) NOT NULL,
  `mid` varchar(1000) DEFAULT NULL,
  `original` longtext NOT NULL,
  `originalMd5` varchar(32) NOT NULL,
  `originalToSort` varchar(300) DEFAULT NULL,
  `edited` longtext,
  `editedMd5` varchar(32) NOT NULL,
  `editedToSort` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE,
  KEY `taskGuid` (`taskGuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

alter table LEK_segment_history add column taskGuid varchar(38) not null after segmentId;
update LEK_segment_history h, LEK_segments s set h.taskGuid = s.taskGuid where s.id = h.segmentId;

alter table LEK_qmsubsegments MODIFY fieldedited varchar(300) not null default 'target';