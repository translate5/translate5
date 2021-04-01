-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2020 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

INSERT INTO  `Zf_worker_dependencies` (`worker`,`dependency`) VALUES
	('editor_Segment_Quality_ImportWorker',  'editor_Models_Import_Worker'),
    ('editor_Segment_Quality_ImportFinishingWorker',  'editor_Segment_Quality_ImportWorker'),
    ('editor_Models_Import_Worker_SetTaskToOpen',  'editor_Segment_Quality_ImportFinishingWorker'),
    ('editor_Segment_Quality_ImportFinishingWorker',  'editor_Plugins_TermTagger_Worker_TermTaggerImport');
    
INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`) VALUES
    ('runtimeOptions.worker.editor_Segment_Quality_ImportFinishingWorker.maxParallelWorkers', 1, 'editor', 'worker', '1', '1', '', 'integer', 'Max parallel running workers of the global quality check finishing worker', 1),
    ('runtimeOptions.worker.editor_Segment_Quality_ImportWorker.maxParallelWorkers', 1, 'editor', 'worker', '1', '1', '', 'integer', 'Max parallel running workers of the global quality check import worker.', 1);
    
CREATE TABLE IF NOT EXISTS `LEK_segment_tags` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `taskGuid` varchar(38) NOT NULL COMMENT 'Foreign Key to LEK_task',
    `segmentId` int(11) NOT NULL COMMENT 'Foreign Key to LEK_segments',
    `tags` longtext NOT NULL default '',
    `status_term` int(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE (`segmentId`),
    CONSTRAINT FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
);
-- model for AutoQA
CREATE TABLE `LEK_segment_quality` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) NOT NULL,
  `segmentId` int(11) DEFAULT NULL,
  `field` varchar(120) NOT NULL,
  `type` varchar(10) NOT NULL,
  `category` varchar(64) NOT NULL,
  `startIndex` int(11) NOT NULL DEFAULT 0,
  `endIndex` int(11) NOT NULL DEFAULT -1,
  `falsePositive` int(1) NOT NULL DEFAULT 0,
  `categoryIndex` int(2) NOT NULL DEFAULT -1,
  `severity` varchar(255) DEFAULT NULL,
  `comment` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `segmentId` (`segmentId`),
  KEY `taskGuid` (`taskGuid`),
  CONSTRAINT `LEK_segment_quality_ibfk_1` FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `LEK_segment_quality_ibfk_2` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE
);
-- migrate data from LEK_qmsubsegments. We need to keep the id's as they're referenced in the MQM-tags in the DB
INSERT INTO `LEK_segment_quality` (`id`, `segmentId`, `taskGuid`, `field`, `type`, `category`, `categoryIndex`, `severity`, `comment`)
SELECT `id`, `segmentId`,  `taskGuid`, `fieldedited`, 'mqm', CONCAT('mqm_', `qmtype`), `qmtype`, `severity`, `comment` FROM `LEK_qmsubsegments`;

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`) 
VALUES ('runtimeOptions.frontend.defaultState.editor.westPanelQualityFilter', '1', 'editor', 'system', '{}', '{}', '', 'map', 'Default state configuration for the editor west panel quality filter panel. If this field value is empty ({} is not an empty value!), no state will be applied/saved for this component.', 32, 'Editor left panel quality filter default configuration', 'Editor: UI layout & more', '');

-- ACL for quality
INSERT INTO Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES 
('editor', 'basic', 'editor_quality', 'all');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`) VALUES
('runtimeOptions.autoQA.enableInternalTagCheck', 1, 'editor', 'system', 1, 1, '', 'boolean', 'If activated (default), AutoQA covers checking invalid internal tags', 8, 'Enable internal tag integrity check', 'Editor: QA', '');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`) VALUES
('runtimeOptions.autoQA.enableEdited100MatchCheck', 1, 'editor', 'system', 1, 1, '', 'boolean', 'If activated (default), AutoQA covers checking edited 100% matches', 8, 'Enable edited 100% match check', 'Editor: QA', '');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`) VALUES
('runtimeOptions.autoQA.enableUneditedFuzzyMatchCheck', 1, 'editor', 'system', 1, 1, '', 'boolean', 'If activated (default), AutoQA covers checking not edited fuzzy matches', 8, 'Enable not edited fuzzy match check', 'Editor: QA', '');

UPDATE `Zf_configuration` SET `name` = 'runtimeOptions.autoQA.enableMqmTags', `description` = 'If activated (default), the quality management covers MQM', `guiName` = 'Enable MQM in the quality management' 
WHERE name = 'runtimeOptions.editor.enableQmSubSegments';

UPDATE `Zf_configuration` SET `name` = 'runtimeOptions.autoQA.enableQm', `description` = 'If activated (default), the quality management for whole segments is active', `guiName` = 'Enable segment QM in the quality management' 
WHERE name = 'runtimeOptions.segments.showQM';


-- Remove old statistics Endpoint
DELETE FROM Zf_acl_rules WHERE `resource` = 'editor_qmstatistics';
-- Remove old MQM model
DROP TABLE `LEK_qmsubsegments`;
