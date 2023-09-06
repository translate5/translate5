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

CREATE TABLE `LEK_number_protection_number_recognition` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `type` varchar(30) NOT NULL,
    `name` varchar(124) DEFAULT 'default' NOT NULL,
    `regex` varchar(1024) NOT NULL,
    `format` varchar(124) DEFAULT NULL,
    `keepAsIs` boolean NOT NULL default true,
    `isDefault` boolean NOT NULL default false,
    `priority` int(3) NOT NULL,
    PRIMARY KEY (`id`),
    INDEX (`type`, `name`)
);

CREATE TABLE `LEK_number_protection_input_mapping` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `languageId` int (11) NOT NULL COMMENT 'Foreign Key to LEK_languages',
    `numberFormatId` int (11) NOT NULL COMMENT 'Foreign Key to LEK_number_protection_number_recognition',
    PRIMARY KEY (`id`),
    CONSTRAINT FOREIGN KEY (`languageId`) REFERENCES `LEK_languages` (`id`) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (`numberFormatId`) REFERENCES `LEK_number_protection_number_recognition` (`id`) ON DELETE CASCADE,
    INDEX (`languageId`),
    INDEX (`numberFormatId`)
);

CREATE TABLE `LEK_number_protection_output_mapping` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `languageId` int (11) DEFAULT NULL COMMENT 'Foreign Key to LEK_languages',
    `numberFormatId` int (11) NOT NULL COMMENT 'Foreign Key to LEK_number_protection_number_recognition',
    `format` varchar(124) DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT FOREIGN KEY (`languageId`) REFERENCES `LEK_languages` (`id`) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (`numberFormatId`) REFERENCES `LEK_number_protection_number_recognition` (`id`) ON DELETE CASCADE,
    INDEX (`languageId`),
    INDEX (`numberFormatId`)
);

INSERT INTO `LEK_number_protection_number_recognition` (`type`, `regex`, `format`, `keepAsIs`, `priority`, `isDefault`) VALUES
-- MAC address
('mac-address', '/\\b(?:[[:xdigit:]]{2}([-:]))(?:[[:xdigit:]]{2}\\1){4}[[:xdigit:]]{2}\\b/', null, true, 500, true),
-- IP address
('ip-address', '/\\b(25[0-5]|(2[0-4]|1\\d|[1-9]|)\\d)(\\.(25[0-5]|(2[0-4]|1\\d|[1-9]|)\\d)){3}\\b/', null, true, 400, true),
-- dates
('date', '/\\b\\d{4}\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9])\\b/', 'Y/d/m' , false, 313, true),
('date', '/\\b\\d{4}-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])\\b/', 'Y-d-m' , false, 312, true),
('date', '/\\b\\d{4}\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.(0[1-9]|1[0-2]|[1-9])\\b/', 'Y.d.m' , false, 311, true),
('date', '/\\b\\d{4} (0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) (0[1-9]|1[0-2]|[1-9])\\b/', 'Y d m' , false, 310, true),

('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9])\\/\\d{4}\\b/', 'd/m/Y' , false, 309, true),
('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])-\\d{4}\\b/', 'd-m-Y' , false, 319, true),
('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.(0[1-9]|1[0-2]|[1-9])\\.\\d{4}\\b/', 'd.m.Y' , false, 318, true),
('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) (0[1-9]|1[0-2]|[1-9]) \\d{4}\\b/', 'd m Y' , false, 317, true),

('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9])\\/\\d{2}\\b/', 'd/m/y' , false, 305, true),
('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])-\\d{2}\\b/', 'd-m-y' , false, 315, true),
('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.(0[1-9]|1[0-2]|[1-9])\\.\\d{2}\\b/', 'd.m.y' , false, 314, true),

('date', '/\\b\\d{4}\\/(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\b/', 'Y/m/d' , false, 325, true),
('date', '/\\b\\d{4}-(0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\b/', 'Y-m-d' , false, 324, true),
('date', '/\\b\\d{4}\\.(0[1-9]|1[0-2]|[1-9])\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\b/', 'Y.m.d' , false, 323, true),
('date', '/\\b\\d{4} (0[1-9]|1[0-2]|[1-9]) (0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\b/', 'Y m d' , false, 322, true),

('date', '/\\b(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/\\d{4}\\b/', 'm/d/Y' , false, 321, true),
('date', '/\\b(0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-\\d{4}\\b/', 'm-d-Y' , false, 308, true),
('date', '/\\b(0[1-9]|1[0-2]|[1-9])\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.\\d{4}\\b/', 'm.d.Y' , false, 307, true),
('date', '/\\b(0[1-9]|1[0-2]|[1-9]) (0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) \\d{4}\\b/', 'm d Y' , false, 306, true),

('date', '/\\b(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/\\d{2}\\b/', 'm/d/y' , false, 316, true),
('date', '/\\b(0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-\\d{2}\\b/', 'm-d-y' , false, 304, true),
('date', '/\\b(0[1-9]|1[0-2]|[1-9])\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.\\d{2}\\b/', 'm.d.y' , false, 303, true),

('date', '/\\b\\d{2}\\/(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\b/', 'y/m/d' , false, 302, true),

('date', '/\\b\\d{4}(0[1-9]|1[0-2])(0[1-9]|[1-2][0-9]|3[0-1])\\b/', 'Ymd' , false, 301, true),

('date', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9]) \\d{4}\\b/', 'd/m Y' , false, 300, true),

-- floats
('float', '/\\b\\d*(,|\\.)\\d+[eE]-?\\d+\\b/', null , true, 218, true),
('float', '/\\b([١٢٣٤٥٦٧٨٩]{1,3}٬){1}(\\d{3}٬)*\\d{3}٫\\d+\\b/u', '#٬##0٫###', false, 217, true),

('float', "/\\b([1-9]\\d{0,2}\\.){1}(\\d{3}\\.)*\\d{3}'\\d+\\b/u", "#.##0'###", false, 216, true),
('float', '/\\b(\\d,)?(\\d{2},)+(\\d{3})\\.\\d+\\b/u', '#,##,##0.###', false, 215, true),

('float', '/\\b([1-9]\\d{0,2},){1}(\\d{3},)*\\d{3}\\.\\d+\\b/u', '#,##0.###', false, 214, true),
('float', '/\\b(\\d{1,4},){1}(\\d{4},)*\\d{4}\\.\\d+\\b/u', '#,###0.###', false, 213, true),
('float', '/\\b([1-9]\\d{0,2},){1}(\\d{3},)*\\d{3}·\\d+\\b/u', '#,##0·###', false, 212, true),
('float', '/\\b([1-9]\\d{0,2} ){1}(\\d{3} )*\\d{3}\\.\\d+\\b/u', '# ##0.###', false, 211, true),
('float', '/\\b([1-9]\\d{0,2}\\x{2009}){1}(\\d{3}\\x{2009})*\\d{3}\\.\\d+\\b/u', '# ##0.###', false, 210, true),
('float', '/\\b([1-9]\\d{0,2}\\x{202F}){1}(\\d{3}\\x{202F})*\\d{3}\\.\\d+\\b/u', '# ##0.###', false, 209, true),
('float', '/\\b([1-9]\\d{0,2}˙){1}(\\d{3}˙)*\\d{3}\\.\\d+\\b/u', '#˙##0.###', false, 208, true),
('float', "/\\b([1-9]\\d{0,2}'){1}(\\d{3}')*\\d{3}\\.\\d+\\b/u", "#'##0.###", false, 207, true),

('float', '/\\b([1-9]\\d{0,2}\\.){1}(\\d{3}\\.)*\\d{3},\\d+\\b/u', '#.##0,###', false, 206, true),
('float', '/\\b([1-9]\\d{0,2} ){1}(\\d{3} )*\\d{3},\\d+\\b/u', '# ##0,###', false, 205, true),
('float', '/\\b([1-9]\\d{0,2}\\x{2009}){1}(\\d{3}\\x{2009})*\\d{3},\\d+\\b/u', '# ##0,###', false, 204, true),
('float', '/\\b([1-9]\\d{0,2}\\x{202F}){1}(\\d{3}\\x{202F})*\\d{3},\\d+\\b/u', '# ##0,###', false, 203, true),
('float', '/\\b([1-9]\\d{0,2}˙){1}(\\d{3}˙)*\\d{3},\\d+\\b/u', '#˙##0,###', false, 202, true),
('float', "/\\b([1-9]\\d{0,2}'){1}(\\d{3}')*\\d{3},\\d+\\b/u", "#'##0,###", false, 201, true),
('float', '/\\b([1-9]|[1-9]\\d+)(\\.|,|·)\\d+\\b/u', null, false, 197, true),

-- integer
('integer', "/\\b[1-9]\\d{0,3}(,)?(\\d{4}\\1)+\\d{4}\\b/u", '#,###0', false, 200, true),
('integer', "/\\b[1-9]\\d{0,2}(,|\\.|·|˙|'|\\x{2009}|\\x{202F}|٬)?(\\d{3}\\1)+\\d{3}\\b/u", null, false, 199, true),
('integer', "/\\b[1-9]\\d{0,1}(,)?(\\d{2}\\1)+\\d{3}\\b/u", '#,##,##0', false, 198, true),
('integer', "/\\b[١٢٣٤٥٦٧٨٩]{0,2}٬?(\\d{3}٬)*\\d{3}\\b/u", null, false, 101, true),
('integer', '/\\b(\\d|[1-9]\\d+)\\b/u', '#', false, 100, true)
;

