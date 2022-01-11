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

UPDATE `LEK_languages` SET `lcid` = '1118' WHERE `LEK_languages`.`rfc5646` = 'am-et';
UPDATE `LEK_languages` SET `lcid` = '1067' WHERE `LEK_languages`.`rfc5646` = 'hy-AM';
UPDATE `LEK_languages` SET `lcid` = '5146' WHERE `LEK_languages`.`rfc5646` = 'bs-latn-ba';
UPDATE `LEK_languages` SET `lcid` = '2108' WHERE `LEK_languages`.`rfc5646` = 'ga-IE';
UPDATE `LEK_languages` SET `lcid` = '4122' WHERE `LEK_languages`.`rfc5646` = 'hr-BA';
UPDATE `LEK_languages` SET `lcid` = '1082' WHERE `LEK_languages`.`rfc5646` = 'mt-MT';
UPDATE `LEK_languages` SET `lcid` = '1153' WHERE `LEK_languages`.`rfc5646` = 'mi-NZ';
UPDATE `LEK_languages` SET `lcid` = '1047' WHERE `LEK_languages`.`rfc5646` = 'rm-ch';
UPDATE `LEK_languages` SET `lcid` = '1083' WHERE `LEK_languages`.`rfc5646` = 'se-NO';
UPDATE `LEK_languages` SET `lcid` = '1143' WHERE `LEK_languages`.`rfc5646` = 'so-so';
UPDATE `LEK_languages` SET `lcid` = '58378' WHERE `LEK_languages`.`rfc5646` = 'es-419';
UPDATE `LEK_languages` SET `lcid` = '21514' WHERE `LEK_languages`.`rfc5646` = 'es-us';
UPDATE `LEK_languages` SET `lcid` = '1092' WHERE `LEK_languages`.`rfc5646` = 'tt-RU';
UPDATE `LEK_languages` SET `lcid` = '1074' WHERE `LEK_languages`.`rfc5646` = 'tn-ZA';
UPDATE `LEK_languages` SET `lcid` = '1076' WHERE `LEK_languages`.`rfc5646` = 'xh-ZA';
UPDATE `LEK_languages` SET `lcid` = '1077' WHERE `LEK_languages`.`rfc5646` = 'zu-ZA';

UPDATE `LEK_languages` SET `lcid` = NULL WHERE `LEK_languages`.`rfc5646` = 'az-AZ';

UPDATE `LEK_languages` SET `lcid` = '1068' WHERE `LEK_languages`.`rfc5646` = 'az-latn-az';

UPDATE `LEK_languages` SET `lcid` = NULL WHERE `LEK_languages`.`rfc5646` = 'uz-UZ';

UPDATE `LEK_languages` SET `lcid` = '1091' WHERE `LEK_languages`.`rfc5646` = 'uz-latn-uz';

