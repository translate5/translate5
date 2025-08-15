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

DELETE FROM `Zf_worker_dependencies`
WHERE `worker` = 'editor_Plugins_Okapi_Worker'
   OR `dependency` = 'editor_Plugins_Okapi_Worker';

DELETE FROM `Zf_configuration`
WHERE `name` = 'runtimeOptions.worker.editor_Plugins_Okapi_Worker.maxParallelWorkers';

INSERT INTO `Zf_worker_dependencies` (`worker`, `dependency`) VALUES
('MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiImportWorker', 'editor_Models_Import_Worker_FileTree'),
('editor_Segment_Quality_OperationWorker', 'MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiImportWorker'),
('editor_Models_Import_Worker_ReferenceFileTree', 'MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiImportWorker'),
('editor_Models_Import_Worker_SetTaskToOpen', 'MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiImportWorker'),
('editor_Models_Import_Worker', 'MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiImportWorker');

INSERT INTO `Zf_worker_dependencies` (`worker`, `dependency`) VALUES
('MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiExportWorker', 'MittagQI\\Translate5\\Task\\Export\\Package\\Worker'),
('MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiExportWorker', 'editor_Models_Export_Worker'),
('MittagQI\\Translate5\\Plugins\\CotiHotfolder\\Worker\\UploadFinishedTask', 'MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiExportWorker'),
('MittagQI\\Translate5\\Plugins\\AcrossHotfolder\\Worker\\UploadFinishedTask', 'MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiExportWorker'),
('MittagQI\\Translate5\\L10n\\ExportWorker', 'MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiExportWorker'),
('MittagQI\\Translate5\\Task\\Export\\Exported\\PackageWorker', 'MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiExportWorker'),
('editor_Models_Export_Exported_ZipDefaultWorker', 'MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiExportWorker'),
('editor_Models_Export_Exported_FiletranslationWorker', 'MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiExportWorker'),
('editor_Models_Export_Exported_TransferWorker', 'MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiExportWorker'),
('editor_Models_Export_Exported_Worker', 'MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiExportWorker');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `typeClass`, `description`, `level`, `accessRestriction`, `guiName`, `guiGroup`, `comment`) VALUES
('runtimeOptions.worker.MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiImportWorker.maxParallelWorkers', 1, 'editor', 'worker', '3', '3', '', 'integer', NULL, 'How many parallel processes are allowed for the package export. This value depends on what your hardware can serve. Please consult translate5s team, if you change this.', 2, 'none', 'Package export: Max. parallel import processes', 'System setup: Load balancing', NULL),
('runtimeOptions.worker.MittagQI\\Translate5\\Plugins\\Okapi\\Worker\\OkapiExportWorker.maxParallelWorkers', 1, 'editor', 'worker', '3', '3', '', 'integer', NULL, 'How many parallel processes are allowed for the package export. This value depends on what your hardware can serve. Please consult translate5s team, if you change this.', 2, 'none', 'Package export: Max. parallel import processes', 'System setup: Load balancing', NULL);

