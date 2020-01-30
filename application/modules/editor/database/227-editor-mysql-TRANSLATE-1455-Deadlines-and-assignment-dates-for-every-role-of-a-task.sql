<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

ALTER TABLE `LEK_taskUserAssoc` 
ADD COLUMN `deadlineDate` DATETIME NULL;
ALTER TABLE `LEK_taskUserAssoc` 
ADD COLUMN `assignmentDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `LEK_taskUserAssoc` 
ADD COLUMN `finishedDate` DATETIME NULL;


#update the dedline date for all reviewers from the task orderdate
UPDATE LEK_taskUserAssoc dest,
  (
    SELECT orderdate,taskGuid FROM LEK_task 
  ) src 
SET
  dest.deadlineDate = src.orderdate
WHERE dest.taskGuid = src.taskGuid 
  AND dest.role ='reviewer';
  
DELETE FROM `Zf_acl_rules` WHERE `right`='editorEditTaskOrderDate';
ALTER TABLE `LEK_task` DROP COLUMN `orderdate`;


#update the finishedDate for all reviewers from a task realDeliveryDate
UPDATE LEK_taskUserAssoc dest,
  (
    SELECT realDeliveryDate,taskGuid FROM LEK_task 
  ) src 
SET
  dest.finishedDate = src.realDeliveryDate
WHERE dest.taskGuid = src.taskGuid 
  AND dest.role ='reviewer';

UPDATE `LEK_workflow_action` SET `action`='setReviewersFinishDate' WHERE `action`='taskSetRealDeliveryDate';

DELETE FROM `Zf_acl_rules` WHERE `right`='editorEditTaskRealDeliveryDate';
ALTER TABLE `LEK_task` DROP COLUMN `realDeliveryDate`;

#daily cron notification days before overdued task assoc deadlines
INSERT INTO `LEK_workflow_action` (`workflow`,`trigger`,`inStep`,`byRole`,`userState`,`actionClass`,`action`,`parameters`,`position`)
VALUES 
('default', 'doCronDaily', null, null, null, 'editor_Plugins_Miele_Notification', 'notifyOverdueDeadline', '{"daysOffset": 2}', 0);
  