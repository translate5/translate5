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

CREATE TABLE `LEK_match_analysis` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `taskGuid` VARCHAR(38) NULL,
  `segmentId` INT(11) NULL,
  `segmentNrInTask` INT(11) NULL,
  `tmmtid` INT(11) NULL,
  `matchRate` INT(11) NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_LEK_match_analysis_1_idx` (`taskGuid` ASC),
  UNIQUE INDEX `segmentId_UNIQUE` (`segmentId` ASC),
  UNIQUE INDEX `tmmtid_UNIQUE` (`tmmtid` ASC),
  UNIQUE INDEX `taskGuid_UNIQUE` (`taskGuid` ASC),
  CONSTRAINT `fk_LEK_match_analysis_1`
    FOREIGN KEY (`taskGuid`)
    REFERENCES `translate5`.`LEK_task` (`taskGuid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE);


INSERT INTO  `Zf_worker_dependencies` (`worker`,`dependency`) VALUES 
('editor_Plugins_MatchAnalysis_Worker',  'editor_Models_Import_Worker'),
('editor_Plugins_TermTagger_Worker_TermTaggerImport',  'editor_Plugins_MatchAnalysis_Worker'),
('editor_Models_Import_Worker_SetTaskToOpen',  'editor_Plugins_MatchAnalysis_Worker');


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES
('runtimeOptions.worker.editor_Plugins_MatchAnalysis_Worker.maxParallelWorkers', 1, 'editor', 'worker', 1, 1, '', 'integer', 'Max parallel running workers of the MatchAnalysis worker');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'pluginMatchAnalysisMatchAnalysis');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_plugins_matchanalysis_matchanalysis', 'all');
