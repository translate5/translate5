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

CREATE TABLE `LEK_pixel_mapping` (
  `mappingId` VARCHAR (127) NOT NULL COMMENT 'Unique md5-Key from customerId, font, fontsize, unicodeChar',
  `customerId` int (11) NOT NULL COMMENT 'Foreign Key to LEK_customer',
  `font` VARCHAR (255) NOT NULL,
  `fontsize` int (3) NOT NULL,
  `unicodeChar` VARCHAR (4) NOT NULL COMMENT '(numeric)',
  `pixelWidth` int (4) NOT NULL,
  PRIMARY KEY (`mappingId`),
  UNIQUE KEY `mappingId` (`mappingId`),
  CONSTRAINT `fk_LEK_pixel_mapping_1`
    FOREIGN KEY (`customerId`)
    REFERENCES `LEK_customer` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `LEK_segments_meta`
ADD `sizeUnit` VARCHAR(6) NULL AFTER `maxWidth` COMMENT 'char or pixel',
ADD `font` VARCHAR(255) NULL AFTER `sizeUnit` COMMENT 'font-family',
ADD `fontSize` INT(3) NULL AFTER `font`;