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
--  translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
--  Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
--  folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */


CREATE TABLE IF NOT EXISTS `LEK_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `origin` varchar(8) DEFAULT NULL COMMENT 'Where does this category come from / belong to?',
  `label` varchar(255) DEFAULT NULL COMMENT 'Name; can be original, but reliable reference to the original category is the originalCategoryId',
  `originalCategoryId` varchar(255) DEFAULT NULL COMMENT 'Original id',
  `specificData` VARCHAR(1024) DEFAULT NULL COMMENT 'Category specific info data',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Categories serve the concept of labels and classifications (sometimes also referred to as "tags").';

INSERT INTO Zf_acl_rules (`module`, `role`, `resource`, `right`) VALUES 
('editor', 'editor', 'editor_category', 'all');

CREATE TABLE IF NOT EXISTS `LEK_languageresources_category_assoc` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `languageResourceId` int(11) NOT NULL,
  `categoryId` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_LEK_languageresources_category_assoc_1`
    FOREIGN KEY (`languageResourceId`)
    REFERENCES `LEK_languageresources` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_LEK_languageresources_category_assoc_2`
    FOREIGN KEY (`categoryId`)
    REFERENCES `LEK_categories` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

