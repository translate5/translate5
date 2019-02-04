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

INSERT IGNORE INTO `LEK_languages` (`langName`, `lcid`, `rfc5646`, `rtl`, `iso3166Part1alpha2`, `sublanguage`, `iso6393`) 
VALUES ('Serbisch (Kyrillisch) (Serbien)', 10266, 'sr-Cyrl-RS', '0', 'rs', 'sr-Cyrl-RS', 'srp');

INSERT IGNORE INTO `LEK_languages` (`langName`, `lcid`, `rfc5646`, `rtl`, `iso3166Part1alpha2`, `sublanguage`, `iso6393`) 
VALUES ('Serbisch (Latein) (Serbien)', 9242, 'sr-RS', '0', 'rs', 'sr-RS', 'srp');

DELETE FROM `LEK_languages` 
WHERE `langName` = 'Koreanisch' AND `rfc5646` = 'kr';

DELETE FROM `LEK_languages` 
WHERE `langName` = 'Koreanisch (Korea)' AND `rfc5646` = 'ko';

INSERT IGNORE INTO `LEK_languages` (`langName`, `lcid`, `rfc5646`, `rtl`, `iso3166Part1alpha2`, `sublanguage`, `iso6393`) 
VALUES ('Koreanisch', NULL, 'ko', '0', 'kr', 'ko-KR', null);

INSERT IGNORE INTO `LEK_languages` (`langName`, `lcid`, `rfc5646`, `rtl`, `iso3166Part1alpha2`, `sublanguage`, `iso6393`) 
VALUES ('Koreanisch (Korea)', 1042, 'ko-KR', '0', 'kr', 'ko-KR', 'kor');
