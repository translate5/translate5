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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

ALTER TABLE `LEK_languageresources` DROP COLUMN `projectTm`;

CREATE TABLE `LEK_task_tm_task_association` (
    `id` int NOT NULL AUTO_INCREMENT,
    `languageResourceId` int NOT NULL,
    `taskId` int NOT NULL,
    `serviceType` VARCHAR(45) NOT NULL,
    `taskGuid` VARCHAR(38) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tasktm` (`languageResourceId`,`taskId`,`serviceType`),
    CONSTRAINT `LEK_task_tm_task_association_ibfk_1` FOREIGN KEY (`languageResourceId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `LEK_task_tm_task_association_ibfk_2` FOREIGN KEY (`taskId`) REFERENCES `LEK_task` (`id`) ON DELETE RESTRICT
);

UPDATE LEK_workflow_action SET actionClass = '\\MittagQI\\Translate5\\LanguageResource\\TaskTm\\Workflow\\Actions\\ReimportSegmentsAction'
WHERE actionClass = '\\MittagQI\\Translate5\\LanguageResource\\ProjectTm\\Workflow\\Actions\\ReimportSegmentsAction';
