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

CREATE TABLE `LEK_segment_quality` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `segmentId` int(11) NOT NULL,
  `taskGuid` varchar(38) NOT NULL,
  `fields` varchar(300) NOT NULL,
  `type` varchar(10) NOT NULL,
  `msgkey` varchar(64) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `startIndex` int(11) NOT NULL DEFAULT 0,
  `endIndex` int(11) NOT NULL DEFAULT -1,
  `falsePositive` int(1) NOT NULL DEFAULT 0,
  `qmtype` int(11) NOT NULL DEFAULT -1,
  `severity` varchar(255) DEFAULT NULL,
  `comment` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE,
  CONSTRAINT FOREIGN KEY (`segmentId`) REFERENCES `LEK_segments` (`id`) ON DELETE CASCADE
);

INSERT INTO `LEK_segment_quality` (`segmentId`, `taskGuid`, `fields`, `type`, `qmtype`, `severity`, `comment`)
SELECT `segmentId`,  `taskGuid`, `fieldedited`, 'mqm', `qmtype`, `severity`, `comment` FROM `LEK_qmsubsegments`;

-- DROP TABLE `LEK_qmsubsegments`;
