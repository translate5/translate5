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

-- Clear existing mappings
TRUNCATE `terms_collection_attribute_datatype`;

-- Add boolean columns
ALTER TABLE `terms_collection_attribute_datatype`
  ADD COLUMN `exists` BOOL DEFAULT 0 NOT NULL AFTER `dataTypeId`,
  ADD COLUMN `enabled` BOOL DEFAULT 0 NOT NULL AFTER `exists`;

-- Insert empty mapping records for [each coollectionId * each dataTypeId]
-- For isTbxBasic-dataTypes set `enabled` = 1
INSERT INTO `terms_collection_attribute_datatype` (
  `collectionId`, 
  `dataTypeId`, 
  `enabled`
) SELECT 
  `c`.`id` AS `collectionId`, 
  `d`.`id` AS `dataTypeId`,
  `d`.`isTbxBasic` AS `enabled`
FROM 
  `LEK_languageresources` `c`,
  `terms_attributes_datatype` `d`
WHERE 
  `c`.`resourceType` = "termcollection";

-- Set `exists` = 1 for mappings related to existing attributes
-- For sure, existing attributes should be enabled by default, so `enabled` = 1 is set
INSERT INTO `terms_collection_attribute_datatype` (`collectionId`,`dataTypeId`) ( 
  SELECT `collectionId`, `dataTypeId`
  FROM `terms_attributes`
  WHERE `dataTypeId` IS NOT NULL
  GROUP BY `collectionId`, `dataTypeId`
) ON DUPLICATE KEY UPDATE `exists` = 1, `enabled` = 1;
