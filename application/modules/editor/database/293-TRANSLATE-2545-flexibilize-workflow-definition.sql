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

CREATE TABLE `LEK_workflow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL COMMENT 'technical workflow name, use alphanumeric chars only',
  `label` varchar(128) NOT NULL COMMENT 'human readable workflow name (goes through the translator)',
  PRIMARY KEY (`id`),
  UNIQUE (`name`)
);

INSERT INTO `LEK_workflow` (`name`, `label`)
VALUES('default', 'Standard Workflow (Übersetzung, Lektorat, Zweites Lektorat)');

CREATE TABLE `LEK_workflow_step` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `workflowName` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL COMMENT 'technical workflow step name, use alphanumeric chars only',
  `label` varchar(128) NOT NULL COMMENT 'human readable workflow step name (goes through the translator)',
  `role` varchar(64) NOT NULL COMMENT 'one of the available roles, by default review|translator|translatorCheck|visitor can be extended by customized PHP workflows',
  `position` int(11) NULL COMMENT 'the position of the step in the workflow, may be null if not in chain (for visitor for example), steps with same position are ordered by name then',
  `flagInitiallyFiltered` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'define if the segments of the previous step should be filtered in the GUI when reaching this step',
  PRIMARY KEY (`id`),
  INDEX (`workflowName`, `name`),
  CONSTRAINT FOREIGN KEY (`workflowName`) REFERENCES `LEK_workflow` (`name`) ON DELETE CASCADE
);

INSERT INTO `LEK_workflow_step` (`workflowName`, `name`, `label`, `role`, `position`, `flagInitiallyFiltered`)
VALUES
('default', 'translation', 'Übersetzung', 'translator', 1, 0),
('default', 'reviewing', 'Lektorat', 'reviewer', 2, 0),
('default', 'translatorCheck', 'Zweites Lektorat', 'translatorCheck', 3, 1),
('default', 'visiting', 'Nur anschauen', 'visitor', null, 0);




ALTER TABLE `LEK_taskUserAssoc` ADD COLUMN `workflowStepName` varchar(64) NOT NULL DEFAULT 'reviewing' COMMENT 'workflow step which is used for this job entry' AFTER `role`;
UPDATE `LEK_taskUserAssoc` SET `workflowStepName` = 'translation' WHERE `role` = 'translator';
UPDATE `LEK_taskUserAssoc` SET `workflowStepName` = 'reviewing' WHERE `role` = 'reviewer';
UPDATE `LEK_taskUserAssoc` SET `workflowStepName` = 'translatorCheck' WHERE `role` = 'translatorCheck';
UPDATE `LEK_taskUserAssoc` SET `workflowStepName` = 'visiting' WHERE `role` = 'visitor';
