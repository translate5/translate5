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


CREATE TABLE `LEK_match_analysis_taskassoc` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `taskGuid` VARCHAR(38) NULL,
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_LEK_match_analysis_taskassoc_1_idx` (`taskGuid` ASC),
  CONSTRAINT `fk_LEK_match_analysis_taskassoc_1`
    FOREIGN KEY (`taskGuid`)
    REFERENCES `LEK_task` (`taskGuid`)
    ON DELETE CASCADE 
    ON UPDATE CASCADE);


CREATE TABLE `LEK_match_analysis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskGuid` varchar(38) DEFAULT NULL,
  `segmentId` int(11) DEFAULT NULL,
  `segmentNrInTask` int(11) DEFAULT NULL,
  `languageResourceid` int(11) DEFAULT NULL,
  `matchRate` int(11) DEFAULT NULL,
  `wordCount` int(11) DEFAULT NULL,
  `analysisId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_LEK_match_analysis_1_idx` (`taskGuid`),
  KEY `index3` (`segmentId`),
  KEY `index4` (`languageResourceid`),
  KEY `fk_LEK_match_analysis_2_idx` (`analysisId`),
  CONSTRAINT `fk_LEK_match_analysis_1` FOREIGN KEY (`taskGuid`) REFERENCES `LEK_task` (`taskGuid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_match_analysis_2` FOREIGN KEY (`analysisId`) REFERENCES `LEK_match_analysis_taskassoc` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);



INSERT INTO  `Zf_worker_dependencies` (`worker`,`dependency`) VALUES 
('editor_Plugins_MatchAnalysis_Worker',  'editor_Models_Import_Worker'),
('editor_Plugins_TermTagger_Worker_TermTaggerImport',  'editor_Plugins_MatchAnalysis_Worker'),
('editor_Models_Import_Worker_SetTaskToOpen',  'editor_Plugins_MatchAnalysis_Worker');


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES
('runtimeOptions.worker.editor_Plugins_MatchAnalysis_Worker.maxParallelWorkers', 1, 'editor', 'worker', 1, 1, '', 'integer', 'Max parallel running workers of the MatchAnalysis worker');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'pluginMatchAnalysisMatchAnalysis');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_plugins_matchanalysis_matchanalysis', 'all');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'editorAnalysisTask');

INSERT INTO `Zf_worker_dependencies` (`worker`, `dependency`) VALUES ('editor_Plugins_GlobalesePreTranslation_Worker', 'editor_Plugins_MatchAnalysis_Worker');

