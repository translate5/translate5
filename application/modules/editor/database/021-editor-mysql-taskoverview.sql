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
SET foreign_key_checks = 0;

CREATE TABLE LEK_task_OLD LIKE LEK_task;
INSERT LEK_task_OLD SELECT * FROM LEK_task;

DROP TABLE LEK_task;

CREATE TABLE LEK_task (
  `id` int (11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `taskNr` varchar (120) NOT NULL default '',
  `taskName` varchar (255) NOT NULL default '',
  `sourceLang` int (11) NOT NULL,
  `targetLang` int (11) NOT NULL,
  `relaisLang` int (11) NOT NULL,
  `lockedInternalSessionUniqId` char(32) DEFAULT NULL,
  `locked` datetime DEFAULT NULL,
  `lockingUser` varchar(38) DEFAULT NULL,
  `state` varchar(38) NOT NULL DEFAULT 'open',
  `workflowStep` int (11) NOT NULL default 1,
  `pmGuid` varchar(38) NOT NULL,
  `wordCount` int (11) NOT NULL,
  `targetDeliveryDate` datetime DEFAULT NULL,
  `realDeliveryDate` datetime DEFAULT NULL,
  `referenceFiles` tinyint(1) NOT NULL DEFAULT '0',
  `terminologie` tinyint(1) NOT NULL DEFAULT '0',
  `ordered` datetime NULL DEFAULT NULL,
  `enableSourceEditing` tinyint(1) NOT NULL DEFAULT '0',
  `edit100PercentMatch` tinyint(1) NOT NULL DEFAULT '0',
  `qmSubsegmentFlags` mediumtext,
  PRIMARY KEY (`id`),
  INDEX (`taskGuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

SELECT count(*)
INTO @exist
FROM information_schema.columns 
WHERE table_schema = database()
and COLUMN_NAME = 'finished'
AND table_name = 'LEK_task_OLD';

set @query = IF(@exist <= 0, 'alter table LEK_task_OLD add column finished datetime DEFAULT NULL', 
'select \'Column finished exists, all OK\' status');

prepare stmt from @query;

EXECUTE stmt;

insert into LEK_task (id, taskGuid, taskName, qmSubsegmentFlags, enableSourceEditing, realDeliveryDate, state) select id, taskGuid, taskName, qmSubsegmentFlags, enableSourceEditing, finished, if(finished, 'end', 'open') from LEK_task_OLD;

update LEK_task t, (select taskGuid,sourceLang,targetLang,relaisLang from LEK_files group by taskGuid) dat set t.sourceLang = dat.sourceLang, t.targetLang = dat.targetLang, t.relaisLang = dat.relaisLang where t.taskGuid = dat.taskGuid;
update LEK_task t, (SELECT taskGuid, IF (referenceFileTree = '', 0, 1) referenceFiles from LEK_foldertree) dat set t.referenceFiles = dat.referenceFiles where t.taskGuid = dat.taskGuid;
update LEK_task t, (select distinct taskGuid from LEK_terms) dat set t.terminologie = 1 where t.taskGuid = dat.taskGuid;

SET foreign_key_checks = 1;

DROP TABLE LEK_task_OLD;
