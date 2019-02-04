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

UPDATE `LEK_languages` SET `rfc5646` = 'ja' WHERE `LEK_languages`.`rfc5646` = 'jp';
UPDATE IGNORE `LEK_languages` SET `rfc5646` = 'nb', `langName` = 'Norwegisch (Bokmal)' WHERE `LEK_languages`.`rfc5646` = 'no';
UPDATE IGNORE `LEK_languages` SET `rfc5646` = 'sr', `langName` = 'Serbisch (Latein)' WHERE `LEK_languages`.`rfc5646` = 'sr';
UPDATE IGNORE `LEK_languages` SET `rfc5646` = 'uz', `langName` = 'Usbekisch (Latein)' WHERE `LEK_languages`.`rfc5646` = 'uz';
INSERT IGNORE INTO `LEK_languages` (`id`, `langName`, `lcid`, `rfc5646`, `rtl`) 
VALUES (NULL, 'Norwegisch (Nynorsk)', NULL, 'nn', '0'), 
(NULL, 'Serbisch (Kyrillisch)', NULL, 'sr-Cyrl', '0'), 
(NULL, 'Usbekisch (Kyrillisch)', NULL, 'uz-Cyrl', '0');

