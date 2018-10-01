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


ALTER TABLE `LEK_languageresources_tmmt` 
ADD COLUMN `autoCreatedOnImport` TINYINT(1) NULL DEFAULT 0 AFTER `labelText`,
ADD COLUMN `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `autoCreatedOnImport`,
ADD COLUMN `oldCollectionId` INT(11) NULL AFTER `timestamp`;

ALTER TABLE `LEK_languageresources_tmmt` 
CHANGE COLUMN `name` `name` VARCHAR(1024) NULL DEFAULT NULL COMMENT 'human readable name of the service' ;

INSERT INTO LEK_languageresources_tmmt(name,color,resourceId,serviceType,serviceName,autoCreatedOnImport,timestamp,oldCollectionId)
SELECT  name, 
        '19737d', 
        'editor_Services_TermCollection', 
        'editor_Services_TermCollection',
        'TermCollection',
        autoCreatedOnImport,
        timestamp,
        id
FROM LEK_term_collection;

/* Update the new collection id value to all related tables. The new value is the one in the tmmt id*/
ALTER TABLE `LEK_terms` 
DROP FOREIGN KEY `fk_LEK_terms_2`;
ALTER TABLE `LEK_terms` 
DROP INDEX `fk_LEK_terms_2_idx` ;

UPDATE LEK_terms t
INNER JOIN LEK_languageresources_tmmt tm ON tm.oldCollectionId=t.collectionId 
SET t.collectionId = tm.id;

ALTER TABLE `LEK_terms` 
ADD INDEX `fk_LEK_terms_2_idx` (`collectionId` ASC);
ALTER TABLE `LEK_terms` 
ADD CONSTRAINT `fk_LEK_terms_2`
  FOREIGN KEY (`collectionId`)
  REFERENCES `LEK_languageresources_tmmt` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
  
INSERT INTO LEK_languageresources_customerassoc(languageResourceId,customerId)
SELECT tm.id,tc.customerId FROM LEK_languageresources_tmmt tm
INNER JOIN LEK_term_collection tc ON tc.id=tm.oldCollectionId;
  
ALTER TABLE `LEK_term_entry_attributes` 
DROP FOREIGN KEY `fk_LEK_term_entry_attributes_1`;
ALTER TABLE `LEK_term_entry_attributes` 
DROP INDEX `fk_LEK_term_entry_attributes_1_idx` ;

UPDATE LEK_term_entry_attributes t
INNER JOIN LEK_languageresources_tmmt tm ON tm.oldCollectionId=t.collectionId 
SET t.collectionId = tm.id;

ALTER TABLE `LEK_term_entry_attributes` 
ADD INDEX `fk_LEK_term_entry_attributes_1_idx` (`collectionId` ASC);
ALTER TABLE `LEK_term_entry_attributes` 
ADD CONSTRAINT `fk_LEK_term_entry_attributes_1`
  FOREIGN KEY (`collectionId`)
  REFERENCES `LEK_languageresources_tmmt` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
 

ALTER TABLE `LEK_term_entry` 
DROP FOREIGN KEY `fk_LEK_term_entry_1`;
ALTER TABLE `LEK_term_entry` 
DROP INDEX `fk_LEK_term_entry_1_idx` ;

UPDATE LEK_term_entry t
INNER JOIN LEK_languageresources_tmmt tm ON tm.oldCollectionId=t.collectionId 
SET t.collectionId = tm.id;

ALTER TABLE `LEK_term_entry` 
ADD INDEX `fk_LEK_term_entry_1_idx` (`collectionId` ASC);
ALTER TABLE `LEK_term_entry` 
ADD CONSTRAINT `fk_LEK_term_entry_1`
  FOREIGN KEY (`collectionId`)
  REFERENCES `LEK_languageresources_tmmt` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
  
UPDATE LEK_term_collection_taskassoc t
INNER JOIN LEK_languageresources_tmmt tm ON tm.oldCollectionId=t.collectionId 
SET t.collectionId = tm.id;

ALTER TABLE `LEK_term_attributes` 
DROP FOREIGN KEY `fk_LEK_term_attributes_1`;
ALTER TABLE `LEK_term_attributes` 
DROP INDEX `fk_LEK_term_attributes_1_idx` ;

UPDATE LEK_term_attributes t
INNER JOIN LEK_languageresources_tmmt tm ON tm.oldCollectionId=t.collectionId 
SET t.collectionId = tm.id;

ALTER TABLE `LEK_term_attributes` 
ADD INDEX `fk_LEK_term_attributes_1_idx` (`collectionId` ASC);
ALTER TABLE `LEK_term_attributes` 
ADD CONSTRAINT `fk_LEK_term_attributes_1`
  FOREIGN KEY (`collectionId`)
  REFERENCES `LEK_languageresources_tmmt` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;
  
  
ALTER TABLE `LEK_languageresources_tmmt` 
DROP COLUMN `oldCollectionId`;
  
DROP TABLE `LEK_term_collection`;

ALTER TABLE `LEK_languageresources_tmmt` 
DROP COLUMN `defaultCustomer`;

ALTER TABLE `LEK_languageresources_customerassoc` 
ADD COLUMN `useAsDefault` TINYINT(1) NULL AFTER `customerId`;


  