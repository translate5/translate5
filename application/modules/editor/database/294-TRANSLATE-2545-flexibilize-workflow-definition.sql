-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - '.(date('Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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


-- delete old workflow list, not needed anymore
DELETE FROM `Zf_configuration` WHERE `name` like 'runtimeOptions.workflows._';
DELETE FROM `Zf_configuration` WHERE `name` = 'runtimeOptions.import.taskWorkflow';

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`)
VALUES ('runtimeOptions.workflow.initialWorkflow', '1', 'editor', 'workflow', 'default', 'default', '', 'string', 'The name of the workflow which should be used by default on task creation.', 4, 'Initial workflow on task creation', 'Workflow');

ALTER TABLE `LEK_taskUserAssoc` ADD COLUMN `workflow` varchar(64) NOT NULL DEFAULT 'default' COMMENT 'the workflow to which this job belongs' AFTER `workflowStepName`;

ALTER TABLE `LEK_taskUserAssoc` DROP FOREIGN KEY `fk_LEK_taskUserAssoc_2`;
ALTER TABLE `LEK_taskUserAssoc` DROP INDEX `taskGuid`;

ALTER TABLE `LEK_taskUserAssoc` ADD UNIQUE INDEX `taskGuid` (`taskGuid`,`userGuid`,`workflow`,`workflowStepName`);
ALTER TABLE `LEK_taskUserAssoc` ADD CONSTRAINT `fk_LEK_taskUserAssoc_2` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE;

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`)
VALUES ('runtimeOptions.workflow.notifyAllUsersAboutTask', '1', 'editor', 'workflow', '1', '1', '', 'boolean', 'Defines if the associated users of a task should be notified about the association (after a successfull import of a task).', 8, 'Workflow notifications: Notify associated users', 'Workflow');

UPDATE LEK_workflow_action 
SET `trigger` = 'handleAfterImport' 
WHERE `trigger` = 'handleDirect::notifyAllUsersAboutTaskAssociation';

