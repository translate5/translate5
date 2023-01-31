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


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`,`guiName`,`guiGroup`,`level`) 
VALUES
 ('runtimeOptions.worker.MittagQI\\Translate5\\Task\\Export\\Package\\Worker.maxParallelWorkers', 1, 'editor', 'worker', 3, 3, '', 'integer', 'How many parallel processes are allowed for the package export. This value depends on what your hardware can serve. Please consult translate5s team, if you change this.','Package export: Max. parallel import processes','System setup: Load balancing','2');
 
 
INSERT INTO `Zf_worker_dependencies` (`id`, `worker`, `dependency`) VALUES (NULL, 'editor_Models_Export_ExportedWorker', 'MittagQI\\Translate5\\Task\\Export\\Package\\Worker');
INSERT INTO `Zf_worker_dependencies` (`id`, `worker`, `dependency`) VALUES (NULL, 'editor_Plugins_SegmentStatistics_CleanUpWorker', 'MittagQI\\Translate5\\Task\\Export\\Package\\Worker');
INSERT INTO `Zf_worker_dependencies` (`id`, `worker`, `dependency`) VALUES (NULL, 'editor_Plugins_SegmentStatistics_Worker', 'MittagQI\\Translate5\\Task\\Export\\Package\\Worker');
INSERT INTO `Zf_worker_dependencies` (`id`, `worker`, `dependency`) VALUES (NULL, 'editor_Plugins_SegmentStatistics_WriteStatisticsWorker', 'MittagQI\\Translate5\\Task\\Export\\Package\\Worker');
INSERT INTO `Zf_worker_dependencies` (`id`, `worker`, `dependency`) VALUES (NULL, 'editor_Plugins_Okapi_Worker', 'MittagQI\\Translate5\\Task\\Export\\Package\\Worker');
INSERT INTO `Zf_worker_dependencies` (`id`, `worker`, `dependency`) VALUES (NULL, 'MittagQI\\Translate5\\Workflow\\ArchiveWorker', 'MittagQI\\Translate5\\Task\\Export\\Package\\Worker');


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `typeClass`, `description`, `level`, `guiName`, `guiGroup`, `comment`) 
VALUES('runtimeOptions.worker.MittagQI\\Translate5\\Task\\Export\\Exported\\PackageWorker.maxParallelWorkers','1','editor','worker','1','1','','integer',NULL,'Max parallel running workers of the export package completed notification worker.','1','','','');

INSERT INTO `Zf_worker_dependencies` (`id`, `worker`, `dependency`) VALUES (NULL, 'MittagQI\\Translate5\\Task\\Export\\Exported\\PackageWorker', 'MittagQI\\Translate5\\Task\\Export\\Package\\Worker');
INSERT INTO `Zf_worker_dependencies` (`id`, `worker`, `dependency`) VALUES (NULL, 'MittagQI\\Translate5\\Task\\Export\\Exported\\PackageWorker', 'editor_Models_Export_Worker');
INSERT INTO `Zf_worker_dependencies` (`id`, `worker`, `dependency`) VALUES (NULL, 'MittagQI\\Translate5\\Task\\Export\\Exported\\PackageWorker', 'editor_Models_Export_Xliff2Worker');
INSERT INTO `Zf_worker_dependencies` (`id`, `worker`, `dependency`) VALUES (NULL, 'MittagQI\\Translate5\\Task\\Export\\Exported\\PackageWorker', 'editor_Plugins_Okapi_Worker');
INSERT INTO `Zf_worker_dependencies` (`id`, `worker`, `dependency`) VALUES (NULL, 'MittagQI\\Translate5\\Task\\Export\\Exported\\PackageWorker', 'editor_Plugins_SegmentStatistics_CleanUpWorker');
INSERT INTO `Zf_worker_dependencies` (`id`, `worker`, `dependency`) VALUES (NULL, 'MittagQI\\Translate5\\Task\\Export\\Exported\\PackageWorker', 'editor_Plugins_SegmentStatistics_Worker');
INSERT INTO `Zf_worker_dependencies` (`id`, `worker`, `dependency`) VALUES (NULL, 'MittagQI\\Translate5\\Task\\Export\\Exported\\PackageWorker', 'editor_Plugins_SegmentStatistics_WriteStatisticsWorker');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`)
VALUES ('editor', 'editor', 'frontend', 'editorPackageExport');

INSERT INTO `Zf_acl_rules` (`id`, `module`, `role`, `resource`, `right`)
VALUES (NULL, 'editor', 'editor', 'editor_task', 'export');
