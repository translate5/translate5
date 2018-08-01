/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/


RENAME TABLE `LEK_matchresource_tmmt` TO `LEK_languageresources_tmmt`;

RENAME TABLE `LEK_matchresource_taskassoc` TO `LEK_languageresources_taskassoc`;

/* rename the acl resources from the plugin to the core code classes */
UPDATE `Zf_acl_rules` SET `resource`='editor_resource' WHERE `resource`='editor_plugins_matchresource_resource';

UPDATE `Zf_acl_rules` SET `resource`='editor_taskassoc' WHERE `resource`='editor_plugins_matchresource_taskassoc';

UPDATE `Zf_acl_rules` SET `resource`='editor_tmmt' WHERE `resource`='editor_plugins_matchresource_tmmt';


/* rename from the acl rules the plugin specific rights names */
UPDATE `Zf_acl_rules`
SET `right` = REPLACE (`right`, 'pluginMatchResources', 'LanguageResources')
WHERE `right` LIKE '%pluginMatchResources%'
AND `resource` = 'frontend';

UPDATE `Zf_acl_rules`
SET `right` = REPLACE (`right`, 'pluginMatchResource', 'LanguageResources')
WHERE `right` LIKE '%pluginMatchResource%'
AND `resource` = 'frontend';

/* rename from the zf configuration the plugin specific config names */
UPDATE `Zf_configuration`
SET `name` = REPLACE (`name`, '.plugins.MatchResource.', '.LanguageResources.'),
`category` = 'editor'
WHERE `name` LIKE '%.plugins.MatchResource.%';

UPDATE `Zf_configuration` SET `name`='runtimeOptions.worker.editor_Models_LanguageResourcesWorker.maxParallelWorkers' WHERE `name`='runtimeOptions.worker.editor_Plugins_MatchResource_Worker.maxParallelWorkers';

/* rename the plugin specific match resources class names from the tmmt table */
UPDATE `LEK_languageresources_tmmt`
SET `resourceId` = REPLACE (`resourceId`, '_Plugins_MatchResource_', '_')
WHERE `resourceId` LIKE '%_Plugins_MatchResource_%';
            
UPDATE `LEK_languageresources_tmmt`
SET `serviceType` = REPLACE (`serviceType`, '_Plugins_MatchResource_', '_')
WHERE `serviceType` LIKE '%_Plugins_MatchResource_%';

