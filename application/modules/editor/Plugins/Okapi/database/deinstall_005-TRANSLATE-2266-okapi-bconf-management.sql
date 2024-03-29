-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

DELETE FROM `Zf_acl_rules` WHERE `right` IN ('pluginOkapiBconfPrefs');
DELETE FROM `Zf_acl_rules` WHERE `resource` = 'editor_plugins_okapi_bconf';
DELETE FROM `Zf_acl_rules` WHERE `resource` = 'editor_plugins_okapi_bconffilter';


DELETE FROM `Zf_configuration`
WHERE `name` IN('runtimeOptions.plugins.Okapi.dataDir');

-- QUIRK: There may be files remaining in the configured directory
UPDATE LEK_customer_meta SET defaultBconfId = NULL;
UPDATE LEK_task_meta SET bconfId = NULL;
ALTER TABLE LEK_customer_meta DROP FOREIGN KEY `fk-customer_meta-okapi_bconf`;
ALTER TABLE LEK_task_meta DROP FOREIGN KEY `fk-task_meta-okapi_bconf`;

DROP TABLE IF EXISTS `LEK_okapi_bconf_default_filter`;
DROP TABLE IF EXISTS `LEK_okapi_bconf_filter`;
DROP TABLE IF EXISTS `LEK_okapi_bconf`; -- last so dependent tables are deleted before
