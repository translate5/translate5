-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'editor_Plugins_MatchAnalysis_Worker' AND `dependency` = 'editor_Plugins_MatchAnalysis_MatchrateWorker';
DELETE FROM `Zf_worker_dependencies` WHERE `worker` = 'editor_Plugins_MatchAnalysis_MatchrateWorker' AND `dependency` = 'editor_Models_Import_Worker';

DELETE FROM `Zf_configuration` WHERE `name` IN('runtimeOptions.plugins.MatchAnalysis.readImportAnalysis');

ALTER TABLE `LEK_match_analysis_taskassoc` DROP COLUMN `isExternal`,  DROP COLUMN `name`;