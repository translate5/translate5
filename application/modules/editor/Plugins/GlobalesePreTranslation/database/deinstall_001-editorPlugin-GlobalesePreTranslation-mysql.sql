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

delete from `Zf_worker_dependencies` where `worker` =  'editor_Plugins_GlobalesePreTranslation_Worker' AND `dependency` = 'editor_Models_Import_Worker';
delete from `Zf_worker_dependencies` where `worker` =  'editor_Plugins_TermTagger_Worker_TermTaggerImport' AND `dependency` = 'editor_Plugins_GlobalesePreTranslation_Worker';
delete from `Zf_worker_dependencies` where `worker` =  'editor_Models_Import_Worker_SetTaskToOpen' AND `dependency` = 'editor_Plugins_GlobalesePreTranslation_Worker';

DELETE FROM `Zf_configuration` 
WHERE `name` = 'runtimeOptions.worker.editor_Plugins_GlobalesePreTranslation_Worker.maxParallelWorkers';

DELETE FROM `Zf_configuration` 
WHERE `name` = 'runtimeOptions.plugins.GlobalesePreTranslation.api.username';

DELETE FROM `Zf_configuration` 
WHERE `name` = 'runtimeOptions.plugins.GlobalesePreTranslation.api.apiKey';

DELETE FROM `Zf_configuration` 
WHERE `name` = 'runtimeOptions.plugins.GlobalesePreTranslation.api.url';

DELETE FROM `Zf_acl_rules` 
WHERE `right` = 'pluginGlobalesePreTranslationGlobalese' 
AND `resource` = 'frontend' 
AND `role` = 'pm'
AND `module` = 'editor';

DELETE FROM `Zf_acl_rules` 
WHERE `right` = 'all' 
AND `resource` = 'editor_plugins_globalesepretranslation_globalese' 
AND `role` = 'pm'
AND `module` = 'editor';
