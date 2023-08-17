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

CREATE TABLE `LEK_language_number_format` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `languageId` int (11) DEFAULT NULL COMMENT 'Foreign Key to LEK_languages',
    `type` varchar(30) NOT NULL,
    `name` varchar(124) DEFAULT 'default' NOT NULL,
    `regex` varchar(1024) NOT NULL,
    `format` varchar(124) DEFAULT NULL,
    `keepAsIs` boolean NOT NULL default true,
    `priority` int(3) NOT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT FOREIGN KEY (`languageId`) REFERENCES `LEK_languages` (`id`) ON DELETE CASCADE,
    INDEX (`name`),
    INDEX (`languageId`, `type`, `name`)
);

INSERT INTO `LEK_language_number_format` (`type`, `regex`, `format`, `keepAsIs`, `priority`) VALUES
-- MAC address
('mac-address', '/\\b(?:[[:xdigit:]]{2}([-:]))(?:[[:xdigit:]]{2}\\1){4}[[:xdigit:]]{2}\\b/', null, true, 500),
-- IP address
('ip-address', '/\\b(25[0-5]|(2[0-4]|1\\d|[1-9]|)\\d)(\\.(25[0-5]|(2[0-4]|1\\d|[1-9]|)\\d)){3}\\b/', null, true, 400),
-- dates
('date', '/\\b\\d{4}\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9])\\b/', 'Y/d/m' , false, 313),
('date', '/\\b\\d{4}-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])\\b/', 'Y-d-m' , false, 312),
('date', '/\\b\\d{4}\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.(0[1-9]|1[0-2]|[1-9])\\b/', 'Y.d.m' , false, 311),
('date', '/\\b\\d{4} (0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) (0[1-9]|1[0-2]|[1-9])\\b/', 'Y d m' , false, 310),

('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9])\\/\\d{4}\\b/', 'd/m/Y' , false, 309),
('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])-\\d{4}\\b/', 'd-m-Y' , false, 319),
('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.(0[1-9]|1[0-2]|[1-9])\\.\\d{4}\\b/', 'd.m.Y' , false, 318),
('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) (0[1-9]|1[0-2]|[1-9]) \\d{4}\\b/', 'd m Y' , false, 317),

('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9])\\/\\d{2}\\b/', 'd/m/y' , false, 305),
('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])-\\d{2}\\b/', 'd-m-y' , false, 315),
('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.(0[1-9]|1[0-2]|[1-9])\\.\\d{2}\\b/', 'd.m.y' , false, 314),

('date', '/\\b\\d{4}\\/(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\b/', 'Y/m/d' , false, 325),
('date', '/\\b\\d{4}-(0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\b/', 'Y-m-d' , false, 324),
('date', '/\\b\\d{4}\\.(0[1-9]|1[0-2]|[1-9])\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\b/', 'Y.m.d' , false, 323),
('date', '/\\b\\d{4} (0[1-9]|1[0-2]|[1-9]) (0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\b/', 'Y m d' , false, 322),

('date', '/\\b(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/\\d{4}\\b/', 'm/d/Y' , false, 321),
('date', '/\\b(0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-\\d{4}\\b/', 'm-d-Y' , false, 308),
('date', '/\\b(0[1-9]|1[0-2]|[1-9])\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.\\d{4}\\b/', 'm.d.Y' , false, 307),
('date', '/\\b(0[1-9]|1[0-2]|[1-9]) (0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) \\d{4}\\b/', 'm d Y' , false, 306),

('date', '/\\b(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/\\d{2}\\b/', 'm/d/y' , false, 316),
('date', '/\\b(0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-\\d{2}\\b/', 'm-d-y' , false, 304),
('date', '/\\b(0[1-9]|1[0-2]|[1-9])\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.\\d{2}\\b/', 'm.d.y' , false, 303),

('date', '/\\b\\d{2}\\/(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\b/', 'y/m/d' , false, 302),

('date', '/\\b\\d{4}(0[1-9]|1[0-2])(0[1-9]|[1-2][0-9]|3[0-1])\\b/', 'Ymd' , false, 301),

('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9]) \\d{4}\\b/', 'd/m Y' , false, 300),

-- floats
('float', '/\\b\\d*(,|\\.)\\d+[eE]-?\\d+\\b/',null , true, 218),
('float', '/\\b([١٢٣٤٥٦٧٨٩]{1,3}٬){1}(\\d{3}٬)*\\d{3}٫\\d+\\b/u', null, false, 217),

('float', "/\\b([1-9]\\d{0,2}\\.){1}(\\d{3}\\.)*\\d{3}'\\d+\\b/u", null, false, 216),
('float', '/\\b(\\d,)?(\\d{2},)+(\\d{3})\\.\\d+\\b/u', null, false, 215),

('float', '/\\b([1-9]\\d{0,2},){1}(\\d{3},)*\\d{3}\\.\\d+\\b/u', null, false, 214),
('float', '/\\b(\\d{1,4},){1}(\\d{4},)*\\d{4}\\.\\d+\\b/u', null, false, 213),
('float', '/\\b([1-9]\\d{0,2},){1}(\\d{3},)*\\d{3}·\\d+\\b/u', null, false, 212),
('float', '/\\b([1-9]\\d{0,2} ){1}(\\d{3} )*\\d{3}\\.\\d+\\b/u', null, false, 211),
('float', '/\\b([1-9]\\d{0,2}\\x{2009}){1}(\\d{3}\\x{2009})*\\d{3}\\.\\d+\\b/u', null, false, 210),
('float', '/\\b([1-9]\\d{0,2}\\x{202F}){1}(\\d{3}\\x{202F})*\\d{3}\\.\\d+\\b/u', null, false, 209),
('float', '/\\b([1-9]\\d{0,2}˙){1}(\\d{3}˙)*\\d{3}\\.\\d+\\b/u', null, false, 208),
('float', "/\\b([1-9]\\d{0,2}'){1}(\\d{3}')*\\d{3}\\.\\d+\\b/u", null, false, 207),

('float', '/\\b([1-9]\\d{0,2}\\.){1}(\\d{3}\\.)*\\d{3},\\d+\\b/u', null, false, 206),
('float', '/\\b([1-9]\\d{0,2} ){1}(\\d{3} )*\\d{3},\\d+\\b/u', null, false, 205),
('float', '/\\b([1-9]\\d{0,2}\\x{2009}){1}(\\d{3}\\x{2009})*\\d{3},\\d+\\b/u', null, false, 204),
('float', '/\\b([1-9]\\d{0,2}\\x{202F}){1}(\\d{3}\\x{202F})*\\d{3},\\d+\\b/u', null, false, 203),
('float', '/\\b([1-9]\\d{0,2}˙){1}(\\d{3}˙)*\\d{3},\\d+\\b/u', null, false, 202),
('float', "/\\b([1-9]\\d{0,2}'){1}(\\d{3}')*\\d{3},\\d+\\b/u", null, false, 201),
('float', '/\\b([1-9]|[1-9]\\d+)(\\.|,|·)\\d+\\b/u', null, false, 197),

-- integer
('integer', "/\\b[1-9]\\d{0,3}(,)?(\\d{4}\\1)+\\d{4}\\b/u", null, false, 200),
('integer', "/\\b[1-9]\\d{0,2}(,|\\.|·|˙|'|\\x{2009}|\\x{202F}|٬)?(\\d{3}\\1)+\\d{3}\\b/u", null, false, 199),
('integer', "/\\b[1-9]\\d{0,1}(,)?(\\d{2}\\1)+\\d{3}\\b/u", null, false, 198),
('integer', "/\\b[١٢٣٤٥٦٧٨٩]{0,2}٬?(\\d{3}٬)*\\d{3}\\b/u", null, false, 101),
('integer', '/\\b(\\d|[1-9]\\d+)\\b/u', null, false, 100)
;

