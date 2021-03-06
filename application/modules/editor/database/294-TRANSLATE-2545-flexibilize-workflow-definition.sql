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

UPDATE `LEK_workflow_action` 
SET `parameters` = '{"role": "reviewer"}'
WHERE `action` = 'notifyAllAssociatedUsers' AND JSON_CONTAINS(`parameters`, '"lector"', '$.role');

UPDATE `LEK_workflow_action` `action`,
(
    SELECT `action`.id, `step`.`name`
    FROM (
        SELECT `id`, `workflow`, JSON_UNQUOTE(JSON_EXTRACT(`parameters`, '$.role')) `role` 
        FROM `LEK_workflow_action`
        WHERE `action` = 'notifyAllAssociatedUsers' and JSON_VALID(`parameters`)
    ) `action`
    JOIN `LEK_workflow_step` `step` ON `step`.`workflowName` = `action`.`workflow` AND `action`.`role` = `step`.`role` 
    ORDER BY `step`.`position`
    limit 1
) `data`
SET `parameters` = CONCAT('{"step": "', `data`.`name`, '"}')
WHERE `action`.id = `data`.id;

UPDATE LEK_workflow_action 
SET `trigger` = 'handleFirstConfirmOfAStep' 
WHERE `trigger` = 'handleFirstConfirmOfARole';

UPDATE LEK_workflow_action 
SET `trigger` = 'handleAllConfirmOfAStep' 
WHERE `trigger` = 'handleAllConfirmOfARole';

UPDATE LEK_task SET workflow = 'default' WHERE workflow = 'dummy';

ALTER TABLE LEK_workflow MODIFY COLUMN name varchar(64) NOT NULL COMMENT 'technical workflow name, use alphanumeric chars only (refresh app cache!)';
ALTER TABLE LEK_workflow MODIFY COLUMN label varchar(128) NOT NULL COMMENT 'human readable workflow name (goes through the translator, refresh app cache!)';
ALTER TABLE LEK_workflow_step MODIFY COLUMN name varchar(64) NOT NULL COMMENT 'technical workflow step name, use alphanumeric chars only (refresh app cache!)';
ALTER TABLE LEK_workflow_step MODIFY COLUMN label varchar(128) NOT NULL COMMENT 'human readable workflow step name (goes through the translator,  refresh app cache!)';

INSERT INTO `LEK_workflow` (`name`, `label`)
VALUES('complex', 'Complex workflow');

INSERT INTO `LEK_workflow_step` (`workflowName`, `name`, `label`, `role`, `position`, `flagInitiallyFiltered`)
VALUES
('complex', 'firsttranslation', 'Erste Übersetzung', 'translator', 1, 0),
('complex', 'review1stlanguage', '1st revision - language', 'reviewer', 2, 0),
('complex', 'review1sttechnical', '1st revision - technical', 'reviewer', 3, 0),
('complex', 'review2ndlanguage', '2nd revision - language', 'reviewer', 4, 0),
('complex', 'review2ndtechnical', '2nd revision - technical', 'reviewer', 5, 0),
('complex', 'textapproval', 'text approval', 'reviewer', 6, 0),
('complex', 'graphicimplementation', 'graphic implementation', 'reviewer', 7, 0),
('complex', 'finaltextapproval', 'final text approval', 'reviewer', 8, 0);

INSERT INTO `LEK_workflow_action` (workflow, `trigger`, inStep, byRole, userState, actionClass, action, parameters, position, description)
SELECT "complex" as workflow, `trigger`, inStep, byRole, userState, actionClass, action, parameters, position, description
FROM LEK_workflow_action WHERE workflow = 'default';