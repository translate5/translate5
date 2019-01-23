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

-- new table for import of pixel-mapping.xls
CREATE TABLE `LEK_pixel_mapping` (
  `id` int(11) AUTO_INCREMENT,
  `customerId` int (11) NOT NULL COMMENT 'Foreign Key to LEK_customer',
  `font` VARCHAR (255) NOT NULL,
  `fontsize` int (3) NOT NULL,
  `unicodeChar` VARCHAR (7) NOT NULL COMMENT '(numeric)',
  `pixelWidth` int (4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`customerId`,`font`,`fontsize`,`unicodeChar`),
  CONSTRAINT `fk_LEK_pixel_mapping_1`
    FOREIGN KEY (`customerId`)
    REFERENCES `LEK_customer` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- additions for segment's meta
ALTER TABLE `LEK_segments_meta`
ADD `sizeUnit` VARCHAR(6) NOT NULL DEFAULT '' COMMENT 'char or pixel' AFTER `maxWidth`,
ADD `font` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'font-family' AFTER `sizeUnit`,
ADD `fontSize` INT(3) NOT NULL DEFAULT 0 AFTER `font`;

-- Migration-script: Segments might have been imported without saving their meta-data-information,
-- hence we need to make sure that from now on their (empty) meta-data-information exists.
INSERT INTO `LEK_segments_meta` (`taskGuid`, `segmentId`, `termtagState`, `transitLockedForRefMat`, `noMissingTargetTermOnImport`) 
SELECT `LEK_segments`.`taskGuid`, `LEK_segments`.`id` `segmentId`, 'untagged' `termtagState`, 0 `transitLockedForRefMat`, 0 `noMissingTargetTermOnImport`
FROM `LEK_segments`
LEFT OUTER JOIN `LEK_segments_meta` ON `LEK_segments_meta`.`segmentId` = `LEK_segments`.`id`
WHERE `LEK_segments_meta`.`segmentId` IS NULL;

-- default values for pixel-widths for font-size
INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES 
('runtimeOptions.pixelMapping.defaultPixelWidths', '1', 'editor', 'system', '', '', '', 'map', 'Default pixel-widths for font-sizes, example: {"12":"3", "13":"4", "14":"5"}');

