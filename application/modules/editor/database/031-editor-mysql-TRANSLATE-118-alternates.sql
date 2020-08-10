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

CREATE TABLE IF NOT EXISTS `LEK_segment_field` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL COMMENT 'Foreign Key to LEK_task',
  `name` varchar(120) NOT NULL COMMENT 'contains the label without invalid chars',
  `type` varchar(60) not null default 'target',
  `label` varchar(300) NOT NULL COMMENT 'field label as provided by CSV / directory',
  `rankable` tinyint(1) NOT NULL default 0 COMMENT 'defines if this field is rankable in the ranker',
  `editable` tinyint(1) NOT NULL default 0 COMMENT 'defines if only the readOnly Content column is provided',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`taskGuid`,`name`),
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
  `editedToSort` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE,
  KEY `taskGuid` (`taskGuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

alter table LEK_segment_history add column taskGuid varchar(38) not null after segmentId;
update LEK_segment_history h, LEK_segments s set h.taskGuid = s.taskGuid where s.id = h.segmentId;

alter table LEK_qmsubsegments MODIFY fieldedited varchar(300) not null default 'target';

alter table LEK_segment_history modify `timestamp` datetime not null comment 'This is the old segment mod time';
alter table LEK_segment_history add column `created` timestamp not null default CURRENT_TIMESTAMP comment 'This is the DB save time of the History entry!' after id;
