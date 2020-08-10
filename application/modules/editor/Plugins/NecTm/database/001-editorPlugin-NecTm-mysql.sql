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
--  translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
--  Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
--  folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */


-- enable NecTm-Plugin for front-end user
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) 
VALUES ('editor', 'editor', 'frontend', 'pluginNecTm'),
('editor', 'admin', 'frontend', 'pluginNecTm'),
('editor', 'pm', 'frontend', 'pluginNecTm');

-- configs for NecTm-Plugin
INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES
('runtimeOptions.plugins.NecTm.server', 1, 'editor', 'plugins', '[]', '[]', '', 'list', 'NEC-TM Api Server; format: ["SCHEME://HOST:PORT"]'),
('runtimeOptions.plugins.NecTm.credentials', 1, 'editor', 'editor', '[]', '[]', '', 'list', 'Credentials (licenses) to the NEC-TM API; format: ["username:password"]'),
('runtimeOptions.plugins.NecTm.topLevelCategoriesIds', 1, 'editor', 'editor', '[]', '[]', '', 'list', 'Only TM data below the top-level categories can be accessed (plus all public data). Enter the NEC-TM\'s tag-ids here, not their tag-names! Example: ["tag391","tag840"]');

-- synch with NecTm-Categories (there: "tags") is handled by worker
INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES
('runtimeOptions.worker.editor_Plugins_NecTm_Worker.maxParallelWorkers', 1, 'editor', 'worker', 3, 3, '', 'integer', 'Max parallel running workers of the NEC-TM-categories-synchronization worker');
