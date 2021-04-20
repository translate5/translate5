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

# remove all cached languages
DELETE FROM Zf_memcache where id like 'getFuzzyLanguages_%';

INSERT INTO `LEK_languages` (`langName`, `rfc5646`,`iso3166Part1alpha2`, `sublanguage`, `rtl`) 
VALUES ('Serbisch (Latein Serbien)', 'sr-latn-rs','rs', 'sr-RS', '0')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO `LEK_languages` (`langName`, `rfc5646`,`iso3166Part1alpha2`, `sublanguage`, `rtl`) 
VALUES ('Somali (Somalia)', 'so-so','so', 'so', '0')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO `LEK_languages` (`langName`, `rfc5646`,`iso3166Part1alpha2`, `sublanguage`, `rtl`) 
VALUES ('Amharisch (Äthiopien)', 'am-et','am', 'am', '0')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO `LEK_languages` (`langName`, `rfc5646`,`iso3166Part1alpha2`, `sublanguage`, `rtl`) 
VALUES ('Rätoromanisch (Schweiz)', 'rm-ch','rm-ch', 'rm-ch', '0')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO `LEK_languages` (`langName`, `rfc5646`,`iso3166Part1alpha2`, `sublanguage`, `rtl`) 
VALUES ('Spanisch (Vereinigte Staaten)', 'es-us','es', 'es', '0')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO `LEK_languages` (`langName`, `rfc5646`,`iso3166Part1alpha2`, `sublanguage`, `rtl`) 
VALUES ('Spanisch (Lateinamerika und Karibik)', 'es-419','es', 'es', '0')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO `LEK_languages` (`langName`, `rfc5646`,`iso3166Part1alpha2`, `sublanguage`, `rtl`) 
VALUES ('Aserbaidschanisch (Latein Aserbaidschan)', 'az-latn-az','az-AZ', 'az-AZ', '0')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO `LEK_languages` (`langName`, `rfc5646`,`iso3166Part1alpha2`, `sublanguage`, `rtl`) 
VALUES ('Usbekisch (Latein Usbekistan)', 'uz-latn-uz','uz', 'uz-UZ', '0')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO `LEK_languages` (`langName`, `rfc5646`,`iso3166Part1alpha2`, `sublanguage`, `rtl`) 
VALUES ('Bosnisch (Latein Bosnien und Herzegowina)', 'bs-latn-ba','ba', 'bs-BA', '0')
ON DUPLICATE KEY UPDATE id=id;

