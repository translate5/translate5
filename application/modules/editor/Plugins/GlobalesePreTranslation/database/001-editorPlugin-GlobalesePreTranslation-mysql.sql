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

INSERT INTO  `Zf_worker_dependencies` (`worker`,`dependency`) VALUES 
('editor_Plugins_GlobalesePreTranslation_Worker',  'editor_Models_Import_Worker'),
('editor_Plugins_TermTagger_Worker_TermTaggerImport',  'editor_Plugins_GlobalesePreTranslation_Worker'),
('editor_Models_Import_Worker_SetTaskToOpen',  'editor_Plugins_GlobalesePreTranslation_Worker');


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) 
VALUES
('runtimeOptions.worker.editor_Plugins_GlobalesePreTranslation_Worker.maxParallelWorkers', 1, 'editor', 'worker', 1, 1, '', 'integer', 'Max parallel running workers of the Globalese pre-translation worker');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'frontend', 'pluginGlobalesePreTranslationGlobalese');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_plugins_globalesepretranslation_globalese', 'all');


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES ('runtimeOptions.plugins.GlobalesePreTranslation.api.username', '1', 'editor', 'plugins', '', '', '', 'string', 'Username for Globalese API authentication');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES ('runtimeOptions.plugins.GlobalesePreTranslation.api.url', '1', 'editor', 'plugins', 'https://translate5.globalese-mt.com/api/v2/', 'https://translate5.globalese-mt.com/api/v2/', '', 'string', 'Url used for Globalese api');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES ('runtimeOptions.plugins.GlobalesePreTranslation.api.apiKey', '1', 'editor', 'plugins', '', '', '', 'string', 'Api key for Globalese authentication');

