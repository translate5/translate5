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


ALTER TABLE `LEK_terms` 
DROP FOREIGN KEY `fk_LEK_terms_2`;
ALTER TABLE `LEK_terms` 
CHANGE COLUMN `collectionId` `collectionId` INT(11) NOT NULL ;
ALTER TABLE `LEK_terms` 
ADD CONSTRAINT `fk_LEK_terms_2`
  FOREIGN KEY (`collectionId`)
  REFERENCES `LEK_languageresources` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;


ALTER TABLE `LEK_term_attributes` 
DROP FOREIGN KEY `fk_LEK_term_attributes_1`;
ALTER TABLE `LEK_term_attributes` 
CHANGE COLUMN `collectionId` `collectionId` INT(11) NOT NULL ;
ALTER TABLE `LEK_term_attributes` 
ADD CONSTRAINT `fk_LEK_term_attributes_1`
  FOREIGN KEY (`collectionId`)
  REFERENCES `LEK_languageresources` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;



ALTER TABLE `LEK_term_entry` 
DROP FOREIGN KEY `fk_LEK_term_entry_1`;
ALTER TABLE `LEK_term_entry` 
CHANGE COLUMN `collectionId` `collectionId` INT(11) NOT NULL ;
ALTER TABLE `LEK_term_entry` 
ADD CONSTRAINT `fk_LEK_term_entry_1`
  FOREIGN KEY (`collectionId`)
  REFERENCES `LEK_languageresources` (`id`)
  ON DELETE CASCADE
  ON UPDATE NO ACTION;


ALTER TABLE `LEK_term_entry_attributes` 
DROP FOREIGN KEY `fk_LEK_term_entry_attributes_1`;
ALTER TABLE `LEK_term_entry_attributes` 
CHANGE COLUMN `collectionId` `collectionId` INT(11) NOT NULL ;
ALTER TABLE `LEK_term_entry_attributes` 
ADD CONSTRAINT `fk_LEK_term_entry_attributes_1`
  FOREIGN KEY (`collectionId`)
  REFERENCES `LEK_languageresources` (`id`)
  ON DELETE NO ACTION
  ON UPDATE NO ACTION;
  
  
DELETE FROM `Zf_configuration` WHERE `name` = 'runtimeOptions.LanguageResources.groupshare.matchrate';

DELETE FROM `Zf_configuration` WHERE `name` = 'runtimeOptions.LanguageResources.termcollection.matchrate';

/* remove the match resources from the active plugin */
UPDATE `Zf_configuration` SET `value` = 
REPLACE(`value`, ',"editor_Plugins_MatchResource_Init"', '') WHERE `name` = 'runtimeOptions.plugins.active' AND `value` != '[]';

UPDATE `Zf_configuration` SET `value` = 
REPLACE(`value`, '"editor_Plugins_MatchResource_Init",', '') WHERE `name` = 'runtimeOptions.plugins.active' AND `value` != '[]';