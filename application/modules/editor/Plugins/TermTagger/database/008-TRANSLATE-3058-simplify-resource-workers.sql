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
--  included in the packaging of this file.  Please review the following information2
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

UPDATE `Zf_configuration` SET `name` = 'runtimeOptions.worker.MittagQI\\Translate5\\Plugins\\TermTagger\\Worker.maxParallelWorkers' WHERE `name` = 'runtimeOptions.worker.editor_Plugins_TermTagger_Worker_TermTaggerImport.maxParallelWorkers';
UPDATE `Zf_worker_dependencies` SET `worker` = 'MittagQI\\Translate5\\Plugins\\TermTagger\\Worker' WHERE `worker` = 'editor_Plugins_TermTagger_Worker_TermTaggerImport';
UPDATE `Zf_worker_dependencies` SET `dependency` = 'MittagQI\\Translate5\\Plugins\\TermTagger\\Worker' WHERE `dependency` = 'editor_Plugins_TermTagger_Worker_TermTaggerImport';

INSERT INTO  `Zf_worker_dependencies` (`worker`,`dependency`) VALUES
    ('MittagQI\\Translate5\\Plugins\\TermTagger\\Worker', 'editor_Segment_Quality_ImportWorker'),
    ('MittagQI\\Translate5\\Plugins\\TermTagger\\Worker', 'editor_Segment_Quality_OperationWorker');

DELETE FROM `Zf_configuration` WHERE `name` = 'runtimeOptions.worker.editor_Plugins_TermTagger_Worker_TermTagger.maxParallelWorkers';
DELETE FROM `Zf_configuration` WHERE `name` = 'runtimeOptions.worker.editor_Plugins_TermTagger_Worker_Remove.maxParallelWorkers';
DELETE FROM `Zf_configuration` WHERE `name` = 'runtimeOptions.worker.editor_Plugins_TermTagger_Worker_SetTaskToOpen.maxParallelWorkers';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` IN ('editor_Plugins_TermTagger_Worker_TermTagger', 'editor_Plugins_TermTagger_Worker_Remove', 'editor_Plugins_TermTagger_Worker_SetTaskToOpen');
DELETE FROM `Zf_worker_dependencies` WHERE `dependency` IN ('editor_Plugins_TermTagger_Worker_TermTagger', 'editor_Plugins_TermTagger_Worker_Remove', 'editor_Plugins_TermTagger_Worker_SetTaskToOpen');

-- remove now unneccessary states in segments meta
ALTER TABLE `LEK_segments_meta` DROP COLUMN `termtagState`;
