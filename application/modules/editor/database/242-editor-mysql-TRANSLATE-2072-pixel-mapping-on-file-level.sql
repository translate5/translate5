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

-- we can start from afresh and without any migration-scripts because so far no data exists
DROP TABLE `LEK_pixel_mapping`;

CREATE TABLE `LEK_pixel_mapping` (
  `id` int(11) AUTO_INCREMENT,
  `taskGuid` varchar (38) NOT NULL COMMENT 'Foreign Key to LEK_task',
  `fileId` int (11) DEFAULT NULL COMMENT 'Foreign Key to LEK_files',
  `font` varchar (255) NOT NULL,
  `fontsize` int (3) NOT NULL,
  `unicodeChar` varchar (7) NOT NULL COMMENT '(numeric)',
  `pixelWidth` int (4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`fileId`,`font`,`fontsize`,`unicodeChar`),
  CONSTRAINT `fk_LEK_pixel_mapping_1`
    FOREIGN KEY (`taskGuid`)
    REFERENCES `LEK_task` (`taskGuid`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_LEK_pixel_mapping_2`
    FOREIGN KEY (`fileId`)
    REFERENCES `LEK_files` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


