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

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES
 ('runtimeOptions.worker.editor_Models_Export_ExportedWorker.maxParallelWorkers', 1, 'editor', 'worker', 1, 1, '', 'integer', 'Max parallel running workers of the export completed notification worker.'),
('runtimeOptions.worker.editor_Models_Import_Worker_SetTaskToOpen.maxParallelWorkers', 1, 'editor', 'worker', 1, 1, '', 'integer', 'Max parallel running workers of the Import completed notification worker'),
('runtimeOptions.worker.editor_Plugins_MtComparEval_Worker.maxParallelWorkers', 1, 'editor', 'worker', 1, 1, '', 'integer', 'Max parallel running workers of the MtComparEval communication worker'),
('runtimeOptions.worker.editor_Plugins_MtComparEval_CheckStateWorker.maxParallelWorkers', 1, 'editor', 'worker', 1, 1, '', 'integer', 'Max parallel running workers of MtComparEval check state worker'),
('runtimeOptions.worker.editor_Plugins_LockSegmentsBasedOnConfig_Worker.maxParallelWorkers', 1, 'editor', 'worker', 1, 1, '', 'integer', 'Max parallel running workers of the LockSegmentsBasedOnConfig plugin worker'),
('runtimeOptions.worker.editor_Plugins_SegmentStatistics_Worker.maxParallelWorkers', 1, 'editor', 'worker', 3, 3, '', 'integer', 'Max parallel running workers of the SegmentStatistics creation worker'),
('runtimeOptions.worker.editor_Plugins_SegmentStatistics_WriteStatisticsWorker.maxParallelWorkers', 1, 'editor', 'worker', 3, 3, '', 'integer', 'Max parallel running workers of the SegmentStatistics writer worker'),
('runtimeOptions.worker.editor_Plugins_NoMissingTargetTerminology_Worker.maxParallelWorkers', 1, 'editor', 'worker', 1, 1, '', 'integer', 'Max parallel running workers of the NoMissingTargetTerminology plugin worker'),
('runtimeOptions.worker.editor_Plugins_TermTagger_Worker_TermTagger.maxParallelWorkers', 1, 'editor', 'worker', 1, 1, '', 'integer', 'Max parallel running workers of the termTagger worker for GUI tagging'),
('runtimeOptions.worker.editor_Plugins_TermTagger_Worker_TermTaggerImport.maxParallelWorkers', 1, 'editor', 'worker', 1, 1, '', 'integer', 'Max parallel running workers of the termTagger import worker'),
('runtimeOptions.worker.ZfExtended_Worker_Callback.maxParallelWorkers', 1, 'app', 'worker', 1, 1, '', 'integer', 'Max parallel running workers of the generic callback worker');