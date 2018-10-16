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


RENAME TABLE `LEK_matchresource_tmmt` TO `LEK_languageresources`;

RENAME TABLE `LEK_matchresource_taskassoc` TO `LEK_languageresources_taskassoc`;

/* rename the acl resources from the plugin to the core code classes */
UPDATE `Zf_acl_rules` SET `resource`='editor_languageresourceresource' WHERE `resource`='editor_plugins_matchresource_resource';

UPDATE `Zf_acl_rules` SET `resource`='editor_languageresourcetaskassoc' WHERE `resource`='editor_plugins_matchresource_taskassoc';

UPDATE `Zf_acl_rules` SET `resource`='editor_languageresourceinstance' WHERE `resource`='editor_plugins_matchresource_tmmt';


/* rename from the acl rules the plugin specific rights names */
UPDATE `Zf_acl_rules`
SET `right` = REPLACE (`right`, 'pluginMatchResources', 'languageResources')
WHERE `right` LIKE '%pluginMatchResources%'
AND `resource` = 'frontend';

UPDATE `Zf_acl_rules`
SET `right` = REPLACE (`right`, 'pluginMatchResource', 'languageResources')
WHERE `right` LIKE '%pluginMatchResource%'
AND `resource` = 'frontend';

/* rename from the zf configuration the plugin specific config names */
UPDATE `Zf_configuration`
SET `name` = REPLACE (`name`, '.plugins.MatchResource.', '.LanguageResources.'),
`category` = 'editor'
WHERE `name` LIKE '%.plugins.MatchResource.%';

UPDATE `Zf_configuration` SET `name`='runtimeOptions.worker.editor_Models_LanguageResourcesWorker.maxParallelWorkers' WHERE `name`='runtimeOptions.worker.editor_Plugins_MatchResource_Worker.maxParallelWorkers';

/* rename the plugin specific match resources class names from the tmmt table */
UPDATE `LEK_languageresources`
SET `resourceId` = REPLACE (`resourceId`, '_Plugins_MatchResource_', '_')
WHERE `resourceId` LIKE '%_Plugins_MatchResource_%';
            
UPDATE `LEK_languageresources`
SET `serviceType` = REPLACE (`serviceType`, '_Plugins_MatchResource_', '_')
WHERE `serviceType` LIKE '%_Plugins_MatchResource_%';

ALTER TABLE `LEK_languageresources_taskassoc` 
DROP FOREIGN KEY `LEK_languageresources_taskassoc_ibfk_1`;
ALTER TABLE `LEK_languageresources_taskassoc` 
CHANGE COLUMN `tmmtId` `languageResourceId` INT(11) NULL DEFAULT NULL ;
ALTER TABLE `LEK_languageresources_taskassoc` 
ADD CONSTRAINT `LEK_languageresources_taskassoc_ibfk_1`
  FOREIGN KEY (`languageResourceId`)
  REFERENCES `LEK_languageresources` (`id`)
  ON DELETE CASCADE;

