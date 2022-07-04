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

UPDATE LEK_languages SET rfc5646 = 'sr-Latn-RS' WHERE rfc5646 = 'sr-latn-rs';
UPDATE LEK_languages SET rfc5646 = 'so-SO' WHERE rfc5646 = 'so-so';
UPDATE LEK_languages SET rfc5646 = 'am-ET' WHERE rfc5646 = 'am-et';
UPDATE LEK_languages SET rfc5646 = 'rm-CH' WHERE rfc5646 = 'rm-ch';
UPDATE LEK_languages SET rfc5646 = 'es-US' WHERE rfc5646 = 'es-us';
UPDATE LEK_languages SET rfc5646 = 'az-Latn-AZ' WHERE rfc5646 = 'az-latn-az';
UPDATE LEK_languages SET rfc5646 = 'uz-Latn-UZ' WHERE rfc5646 = 'uz-latn-uz';
UPDATE LEK_languages SET rfc5646 = 'bs-Latn-BA' WHERE rfc5646 = 'bs-latn-ba';

insert into LEK_languages (id, langName, lcid, rfc5646, iso3166Part1alpha2, sublanguage, rtl, iso6393)
values (null, 'Serbisch (Latein) (Montenegro)', 11290, 'sr-Latn-ME', 'sr', 'Latn-ME', 0, 'cnr');
