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

INSERT INTO `LEK_languages` (`langName`, `lcid`, `rfc5646`, `iso3166Part1alpha2`, `sublanguage`, `rtl`, `iso6393`)
VALUES ('Bengalisch (Bangladesh)', '2117', 'bn-BD', 'bd', 'bn-BD', '0', NULL);

INSERT INTO `LEK_languages` (`langName`, `lcid`, `rfc5646`, `iso3166Part1alpha2`, `sublanguage`, `rtl`, `iso6393`)
VALUES ('Bengalisch (Indien)', '1093', 'bn-IN', 'in', 'bn-IN', '0', NULL);

UPDATE `LEK_languages`
SET `iso3166Part1alpha2` = NULL, `sublanguage` = NULL, `iso6393` = 'ben'
WHERE `LEK_languages`.`rfc5646` = 'bn';


