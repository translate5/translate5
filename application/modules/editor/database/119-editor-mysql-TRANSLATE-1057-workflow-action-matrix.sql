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

CREATE TABLE `LEK_workflow_action` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `workflow` VARCHAR(60) NOT NULL DEFAULT 'default',
  `trigger` VARCHAR(160) NOT NULL COMMENT 'triggering function in the workflow',
  `inStep` VARCHAR(160) NULL COMMENT 'action is only triggered if caused in the given step, null for all steps',  
  `byRole` VARCHAR(160) NULL COMMENT 'action is only triggered if caused by the given role, null for all roles',
  `userState` VARCHAR(160) NULL COMMENT 'action os only triggered for the given state, null for all states',
  `actionClass` VARCHAR(160) NOT NULL COMMENT 'class to be called',
  `action` VARCHAR(160) NOT NULL COMMENT 'class method to be called',
  `parameters` VARCHAR(255) NULL COMMENT 'parameters given to the action, stored as JSON here',
  `position` INT(11) NOT NULL DEFAULT 0 COMMENT 'defines the sort order of actions with same conditions',
  PRIMARY KEY (`id`),
  INDEX (`workflow` ASC, `trigger` ASC, `position` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `LEK_workflow_action` (`workflow`,`trigger`,`inStep`,`byRole`,`userState`,`actionClass`,`action`,`parameters`,`position`)
VALUES 
('default', 'handleAllFinishOfARole', 'lectoring', 'lector', 'finished', 'editor_Workflow_Actions', 'segmentsSetUntouchedState', null, 0),
('default', 'handleAllFinishOfARole', 'lectoring', 'lector', 'finished', 'editor_Workflow_Actions', 'taskSetRealDeliveryDate', null, 1),
('default', 'handleAllFinishOfARole', null, null, 'finished', 'editor_Workflow_Notification', 'notifyAllFinishOfARole', null, 2),
('default', 'handleUnfinish', null, 'lector', null, 'editor_Workflow_Actions', 'segmentsSetInitialState', null, 0),
('default', 'handleBeforeImport', null, null, null, 'editor_Workflow_Actions', 'autoAssociateTaskPm', null, 0),
('default', 'handleImport', null, null, null, 'editor_Workflow_Notification', 'notifyNewTaskForPm', null, 0),
('default', 'handleImport', null, null, null, 'editor_Workflow_Actions', 'autoAssociateEditorUsers', null, 1);
