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

ALTER TABLE `terms_transacgrp` 
CHANGE COLUMN `date` `date` DATETIME NULL DEFAULT NULL ;

ALTER TABLE `terms_images` 
ADD COLUMN `uniqueName` VARCHAR(100) NULL AFTER `name`,
ADD UNIQUE INDEX `uniqueName_UNIQUE` (`uniqueName` ASC);

ALTER TABLE `terms_images`
ADD CONSTRAINT `fk_terms_images_languageresources`
    FOREIGN KEY (`collectionId`)
        REFERENCES `LEK_languageresources` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE;

# attribute data type attribute is not tbx basic. No need to have this field in the database
ALTER TABLE `terms_attributes` 
DROP COLUMN `dataType`;

ALTER TABLE `terms_attributes` 
ADD COLUMN `termTbxId` VARCHAR(100) NULL AFTER `termId`;

ALTER TABLE `terms_attributes`
 ADD INDEX `termTbxId_idx` (`termTbxId` ASC);

ALTER TABLE `terms_term`
 ADD INDEX `termTbxId_idx` (`termTbxId` ASC);

ALTER TABLE `terms_transacgrp`
 ADD INDEX `termTbxId` (`termTbxId` ASC);


