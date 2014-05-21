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

CREATE TABLE IF NOT EXISTS `LEK_workflow_userpref` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL COMMENT 'Foreign Key to LEK_task',
  `workflow` varchar(60) NOT NULL COMMENT 'FIXME comment',
  `workflowStep` varchar(60) DEFAULT NULL COMMENT 'FIXME comment',
  `userGuid` varchar(38) DEFAULT NULL COMMENT 'Foreign Key to Zf_users',
  `fields` varchar(300) NOT NULL COMMENT 'field names as used in LEK_segment_fields',
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT FOREIGN KEY (`userGuid`) REFERENCES `Zf_users` (`userGuid`) ON DELETE CASCADE
-- FIXME unique of taskGuid and userGuid???
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `LEK_workflow_userpref` (`taskGuid`, `workflow`, `fields`) 
select taskGuid, 'default', GROUP_CONCAT(name ORDER BY id SEPARATOR ',') from LEK_segment_field GROUP BY taskGuid;

ALTER TABLE `LEK_task` ADD COLUMN `workflow` VARCHAR(60) NOT NULL DEFAULT 'default' AFTER `state`;