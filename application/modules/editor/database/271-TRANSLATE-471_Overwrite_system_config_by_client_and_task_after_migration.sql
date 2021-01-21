-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2020 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

-- change fieldsDefaultValue to edit100PercentMatch. This will migrate the old value to the new config
UPDATE `Zf_configuration` 
SET `name`='runtimeOptions.frontend.importTask.edit100PercentMatch', 
`value`=(select 0 or if((SELECT CASE WHEN JSON_VALID(value) THEN JSON_EXTRACT(value, "$.edit100PercentMatch") ELSE null END),1,0 )), 
`default`='0', 
`type`='boolean' 
WHERE `name`='runtimeOptions.frontend.importTask.fieldsDefaultValue';

-- for now all map configs should be configurable only on db level. After we implement better json editor for the frontend. Those
-- configs levels should be set back to the defined value
UPDATE `Zf_configuration` 
SET `level`=1 
WHERE `type`='map'
AND `name` NOT LIKE 'runtimeOptions.frontend.defaultState.%';

-- set all default states to user level
UPDATE `Zf_configuration` 
SET `level`=32 
WHERE `name` LIKE 'runtimeOptions.frontend.defaultState.%';

-- this config does not exist in the code
DELETE FROM `Zf_configuration` WHERE `name`='runtimeOptions.plugins.Okapi.customFileExtensions';

-- this config was used only in okapi plugin. Change it as boolean flag.
UPDATE `Zf_configuration` 
SET `name`='runtimeOptions.plugins.Okapi.import.fileconverters.attachOriginalFileAsReference', 
`value`=(select 0 or if((SELECT CASE WHEN JSON_VALID(value) THEN JSON_EXTRACT(value, "$.okapi") ELSE null END),1,0 )), 
`default`='1', 
`type`='boolean' 
WHERE `name`='runtimeOptions.import.fileconverters.attachOriginalFileAsReference';

-- set doNotShowAgain flag as boolean for all default state help windows
UPDATE `Zf_configuration` 
SET `default`='{"doNotShowAgain":false}' 
WHERE `name` LIKE 'runtimeOptions.frontend.defaultState.helpWindow.%';

-- migrate all anonymized customer to customer specific config
INSERT INTO `LEK_customer_config` (`customerId`, `name`, `value`) 
SELECT id, "runtimeOptions.customers.anonymizeUsers", 1 
FROM `LEK_customer`
WHERE `anonymizeUsers` = 1;

ALTER TABLE `LEK_customer` 
DROP COLUMN `anonymizeUsers`;
