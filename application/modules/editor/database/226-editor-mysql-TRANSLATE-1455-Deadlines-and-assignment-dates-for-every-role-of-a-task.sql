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

ALTER TABLE `LEK_taskUserAssoc` ADD COLUMN `deadlineDate` datetime NULL DEFAULT NULL;
ALTER TABLE `LEK_taskUserAssoc` ADD COLUMN `assignmentDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `LEK_taskUserAssoc` ADD COLUMN `finishedDate` datetime NULL DEFAULT NULL;

#update the dedline date for all reviewers from the task targetDeliveryDate
UPDATE LEK_taskUserAssoc dest,
  (
    SELECT targetDeliveryDate,taskGuid FROM LEK_task 
  ) src 
SET
  dest.deadlineDate = src.targetDeliveryDate
WHERE dest.taskGuid = src.taskGuid 
  AND dest.role ='reviewer';
  
  
#update the assignmentDate from the task modified date
UPDATE LEK_taskUserAssoc dest,
  (
    SELECT modified,taskGuid FROM LEK_task 
  ) src 
SET
  dest.assignmentDate = src.modified
WHERE dest.taskGuid = src.taskGuid;

#update all other assigment dates based on the tasklog job created entry
UPDATE LEK_taskUserAssoc dest,
  (
    select tua.id,tl.created from LEK_task_log tl
	inner join LEK_taskUserAssoc tua ON tl.taskGuid=tua.taskGuid
	where tl.message='job created'
	and tl.extra like 
	concat('%','"userGuid":"',tua.userGuid,'"','%')
    group by tua.id
  ) src 
SET
  dest.assignmentDate = src.created
WHERE dest.id = src.id;

DELETE FROM `Zf_acl_rules` WHERE `right`='editorEditTaskDeliveryDate';
ALTER TABLE `LEK_task` DROP COLUMN `targetDeliveryDate`;


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
('default', 'doCronDaily', null, null, null, 'editor_Workflow_Notification', 'notifyDeadlineApproaching', '{"daysOffset": 2}', 0);


ALTER TABLE `LEK_workflow_action` 
ADD COLUMN `description` VARCHAR(1024) NULL COMMENT 'contains a human readable description for the row' AFTER `position`;

UPDATE `LEK_workflow_action` SET `description`='For the separate delivery date of every role a reminder mail is send by the translate5 cronController. How much days before the deadline a reminder will be send is defined in the parameters column. Example of 2 days before deadline reminder: {\"daysOffset\": 2}' 
WHERE `action`='notifyDeadlineApproaching';
  