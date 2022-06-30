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

CREATE TABLE `LEK_languageresources_taskpivotassoc` (
    `id` int NOT NULL AUTO_INCREMENT,
    `languageResourceId` int DEFAULT NULL,
    `taskGuid` varchar(38) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `fk_LEK_languageresources_taskpivotassoc_1_idx` (`languageResourceId`),
    KEY `fk_LEK_languageresources_taskpivotassoc_2` (`taskGuid`),
    CONSTRAINT `fk_LEK_languageresources_taskpivotassoc_1` FOREIGN KEY (`languageResourceId`) REFERENCES `LEK_languageresources` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_LEK_languageresources_taskpivotassoc_2` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO `Zf_acl_rules` (`role`, `module`,`resource`,`right`) VALUES
('editor', 'editor', 'editor_languageresourcetaskpivotassoc', 'index'),
('editor', 'instantTranslate', 'editor_languageresourcetaskpivotassoc', 'post'),
('editor', 'pm', 'editor_languageresourcetaskpivotassoc', 'all'),
('editor', 'pm', 'frontend', 'languageResourcesTaskPivotAssoc'),
('editor', 'pmlight', 'editor_languageresourcetaskpivotassoc', 'all'),
('editor', 'pmlight', 'frontend', 'languageResourcesTaskPivotAssoc'),
('editor', 'termPM', 'editor_languageresourcetaskpivotassoc', 'post'),
('editor', 'termPM_allClients', 'editor_languageresourcetaskpivotassoc', 'post'),
('editor', 'instantTranslate', 'editor_languageresourcetaskpivotassoc', 'pretranslationOperation'),
('editor', 'pm', 'editor_languageresourcetaskpivotassoc', 'pretranslationOperation'),
('editor', 'pmlight', 'editor_languageresourcetaskpivotassoc', 'pretranslationOperation'),
('editor', 'termPM', 'editor_languageresourcetaskpivotassoc', 'pretranslationOperation'),
('editor', 'termPM_allClients', 'editor_languageresourcetaskpivotassoc', 'pretranslationOperation');

ALTER TABLE `LEK_languageresources_customerassoc`
    ADD COLUMN `pivotAsDefault` TINYINT(1) NULL
    COMMENT 'If set to 1, the assigned tasks to this customer will have this language resource used to pre-translate pivot language'
    AFTER `writeAsDefault`;

ALTER TABLE `LEK_match_analysis_batchresults` RENAME TO  `LEK_languageresources_batchresults` ;

UPDATE `Zf_configuration`
SET `name` = 'runtimeOptions.LanguageResources.Pretranslation.enableBatchQuery',
    `description` = 'Enables batch query requests for pretranslations only for the associated language resource that support batch query. Batch query is much faster for many language resources for imports and InstantTranslate'
WHERE (`name` = 'runtimeOptions.plugins.MatchAnalysis.enableBatchQuery');

INSERT INTO `Zf_worker_dependencies` (`worker`, `dependency`)
VALUES ('editor_Models_Import_Worker_SetTaskToOpen', 'MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PivotWorker');
INSERT INTO `Zf_worker_dependencies` (`worker`, `dependency`)
VALUES ('MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PivotWorker', 'editor_Models_Import_Worker');
INSERT INTO `Zf_worker_dependencies` (`worker`, `dependency`)
VALUES ('MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PivotWorker', 'editor_Task_Operation_StartingWorker');
INSERT INTO `Zf_worker_dependencies` (`worker`, `dependency`)
VALUES ('MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PivotWorker', 'editor_Plugins_MatchAnalysis_Worker');
INSERT INTO `Zf_worker_dependencies` (`worker`, `dependency`)
VALUES ('MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PivotWorker', 'editor_Plugins_MatchAnalysis_BatchWorker');


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`)
VALUES ('runtimeOptions.worker.MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PivotWorker.maxParallelWorkers', '1', 'editor', 'worker', '1', '1', '', 'integer', 'Max parallel running workers for pivot pre-translation');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`)
VALUES ('runtimeOptions.LanguageResources.Pretranslation.pivot.pretranslateMtDefault', '1', 'editor', 'plugins', '1', '1', '', 'boolean', 'Should TM be used in pivot pre-translation	', '4', 'Use MT for pivot pre-translation', 'Language resources');
