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

-- replace Quality-Import with Quality-Operation worker
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'editor_Models_Import_Worker_SetTaskToOpen' AND `dependency` = 'editor_Segment_Quality_ImportFinishingWorker';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'editor_Segment_Quality_ImportWorker' AND `dependency` = 'editor_Plugins_MatchAnalysis_Worker';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'editor_Segment_Quality_ImportWorker' AND `dependency` = 'editor_Plugins_GlobalesePreTranslation_Worker';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'editor_Segment_Quality_ImportFinishingWorker' AND `dependency` = 'editor_Segment_Quality_ImportWorker';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'editor_Segment_Quality_ImportFinishingWorker' AND `dependency` = 'MittagQI\\Translate5\\Plugins\\SpellCheck\\Worker';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'editor_Segment_Quality_ImportFinishingWorker' AND `dependency` = 'MittagQI\\Translate5\\Plugins\\TermTagger\\Worker';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'editor_Segment_Quality_ImportFinishingWorker' AND `dependency` = 'MittagQI\\Translate5\\Plugins\\Translate24\\Worker';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'MittagQI\\Translate5\\Plugins\\SpellCheck\\Worker' AND `dependency` = 'editor_Segment_Quality_ImportWorker';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'MittagQI\\Translate5\\Plugins\\TermTagger\\Worker' AND `dependency` = 'editor_Segment_Quality_ImportWorker';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'MittagQI\\Translate5\\Plugins\\Translate24\\Worker' AND `dependency` = 'editor_Segment_Quality_ImportWorker';

-- SO UGLY: these SQLs are only neccessary, because the SQL-files of the main repo are executed before the Plugin-ones in case of recreating a DB for tests :-(
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'editor_Plugins_SpellCheck_Worker_Import' AND `dependency` = 'editor_Segment_Quality_ImportWorker';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'editor_Segment_Quality_ImportFinishingWorker' AND `dependency` = 'editor_Plugins_SpellCheck_Worker_Import';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'editor_Plugins_TermTagger_Worker_TermTaggerImport' AND `dependency` = 'editor_Segment_Quality_ImportWorker';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'editor_Segment_Quality_ImportFinishingWorker' AND `dependency` = 'editor_Plugins_TermTagger_Worker_TermTaggerImport';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'editor_Plugins_TermTagger_Worker_Remove' AND `dependency` = 'editor_Segment_Quality_ImportWorker';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'editor_Segment_Quality_ImportFinishingWorker' AND `dependency` = 'editor_Plugins_TermTagger_Worker_Remove';

-- replace the quality-import workers with the quality-operation workers
UPDATE `Zf_worker_dependencies` SET `worker` = 'editor_Segment_Quality_OperationWorker' WHERE `worker` = 'editor_Segment_Quality_ImportWorker';
UPDATE `Zf_worker_dependencies` SET `dependency` = 'editor_Segment_Quality_OperationWorker' WHERE `dependency` = 'editor_Segment_Quality_ImportWorker';
UPDATE `Zf_worker_dependencies` SET `worker` = 'editor_Segment_Quality_OperationFinishingWorker' WHERE `worker` = 'editor_Segment_Quality_ImportFinishingWorker';
UPDATE `Zf_worker_dependencies` SET `dependency` = 'editor_Segment_Quality_OperationFinishingWorker' WHERE `dependency` = 'editor_Segment_Quality_ImportFinishingWorker';