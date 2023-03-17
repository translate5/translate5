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

-- table holding temporary segment states while processing during task-operations
CREATE TABLE `LEK_segment_processing` (
    `segmentId` int(11) NOT NULL COMMENT 'Segment id not bound as foreign key',
    `taskGuid` varchar(38) NOT NULL COMMENT 'Foreign Key to LEK_task',
    `tagsJson` longtext NOT NULL DEFAULT '',
    `termtaggerState` tinyint(1) DEFAULT 0 NOT NULL,
    `spellcheckState` tinyint(1) DEFAULT 0 NOT NULL,
    `translate24State` tinyint(1) DEFAULT 0 NOT NULL,
    PRIMARY KEY (`segmentId`),
    CONSTRAINT FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
    INDEX (`taskGuid`, `termtaggerState`),
    INDEX (`taskGuid`, `spellcheckState`),
    INDEX (`taskGuid`, `translate24State`)
);

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `typeClass`, `description`, `level`) VALUES
    ('runtimeOptions.worker.maxParallelWorkers','1','editor','worker','20','3','','integer',NULL,'Max parallel running any worker can have','1');

-- removing now obsolete segment tags model
DROP TABLE IF EXISTS `LEK_segment_tags`;

-- removing obsolete excel worker
DELETE FROM `Zf_configuration` WHERE `name` = 'runtimeOptions.worker.editor_Models_Excel_Worker.maxParallelWorkers';

-- We remove all configs where the maxParallelWorkers is 1, this is the default and there is no need to have these
DELETE FROM `Zf_configuration` WHERE `name` LIKE 'runtimeOptions.worker.%' AND `name` LIKE '%.maxParallelWorkers' AND `value` = 1 AND `default` = 1;
