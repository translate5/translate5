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
-- 	            http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

INSERT INTO `Zf_worker_dependencies` (`worker`, `dependency`) VALUES
    ('MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PivotWorker',  'MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PausePivotWorker');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES
    ('runtimeOptions.worker.MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PausePivotWorker.maxParallelWorkers', '1', 'app', 'system', '1', '1', '', 'integer', 'Max parallel running pause workers', 1, 'How many workers of this type can run simultaneously', 'System setup: Load balancing', ''),
    ('runtimeOptions.worker.MittagQI\\Translate5\\LanguageResource\\Pretranslation\\PausePivotWorker.maxPauseTime', '1', 'app', 'system', '300', '300', '', 'integer', 'Max wait time in seconds', 1, 'How much time this worker can wait until released', 'System setup: Load balancing', '');
