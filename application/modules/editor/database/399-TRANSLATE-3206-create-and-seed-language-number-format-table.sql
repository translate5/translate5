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

CREATE TABLE `LEK_number_protection_number_recognition` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `type` varchar(30) NOT NULL,
    `name` varchar(128) COLLATE 'utf8mb4_bin' NOT NULL,
    `regex` varchar(1024) NOT NULL,
    `format` varchar(124) DEFAULT NULL,
    `keepAsIs` boolean NOT NULL default true,
    `enabled` boolean NOT NULL default true,
    `isDefault` boolean NOT NULL default false,
    `priority` int(3) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE (`type`, `name`)
);

CREATE TABLE `LEK_number_protection_input_mapping` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `languageId` int (11) NOT NULL COMMENT 'Foreign Key to LEK_languages',
    `numberRecognitionId` int (11) NOT NULL COMMENT 'Foreign Key to LEK_number_protection_number_recognition',
    PRIMARY KEY (`id`),
    CONSTRAINT FOREIGN KEY (`languageId`) REFERENCES `LEK_languages` (`id`) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (`numberRecognitionId`) REFERENCES `LEK_number_protection_number_recognition` (`id`) ON DELETE CASCADE,
    UNIQUE (`languageId`, `numberRecognitionId`)
);

CREATE TABLE `LEK_number_protection_output_mapping` (
    `id` int (11) NOT NULL AUTO_INCREMENT,
    `languageId` int (11) DEFAULT NULL COMMENT 'Foreign Key to LEK_languages',
    `numberRecognitionId` int (11) NOT NULL COMMENT 'Foreign Key to LEK_number_protection_number_recognition',
    `format` varchar(124) DEFAULT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT FOREIGN KEY (`languageId`) REFERENCES `LEK_languages` (`id`) ON DELETE CASCADE,
    CONSTRAINT FOREIGN KEY (`numberRecognitionId`) REFERENCES `LEK_number_protection_number_recognition` (`id`) ON DELETE CASCADE,
    UNIQUE (`languageId`, `numberRecognitionId`)
);

INSERT INTO `LEK_number_protection_number_recognition` (`type`, `name`,`regex`, `format`, `keepAsIs`, `priority`, `isDefault`) VALUES
-- MAC address
('mac-address', 'default', '/\\b(?:[[:xdigit:]]{2}([-:]))(?:[[:xdigit:]]{2}\\1){4}[[:xdigit:]]{2}\\b/', null, true, 500, true),
-- IP address
('ip-address', 'default', '/\\b(25[0-5]|(2[0-4]|1\\d|[1-9]|)\\d)(\\.(25[0-5]|(2[0-4]|1\\d|[1-9]|)\\d)){3}\\b/', null, true, 400, true),
-- dates
('date', 'default Y/d/m', '/\\b\\d{4}\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9])\\b/', 'Y/d/m' , false, 313, true),
('date', 'default Y-d-m', '/\\b\\d{4}-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])\\b/', 'Y-d-m' , false, 312, true),
('date', 'default Y.d.m', '/\\b\\d{4}\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.(0[1-9]|1[0-2]|[1-9])\\b/', 'Y.d.m' , false, 311, true),
('date', 'default Y d m', '/\\b\\d{4} (0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) (0[1-9]|1[0-2]|[1-9])\\b/', 'Y d m' , false, 310, true),

('date', 'default d/m/Y', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9])\\/\\d{4}\\b/', 'd/m/Y' , false, 309, true),
('date', 'default d-m-Y', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])-\\d{4}\\b/', 'd-m-Y' , false, 319, true),
('date', 'default d.m.Y', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.(0[1-9]|1[0-2]|[1-9])\\.\\d{4}\\b/', 'd.m.Y' , false, 318, true),
('date', 'default d m Y', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) (0[1-9]|1[0-2]|[1-9]) \\d{4}\\b/', 'd m Y' , false, 317, true),

('date', 'default d/m/y', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9])\\/\\d{2}\\b/', 'd/m/y' , false, 305, true),
('date', 'default d-m-y', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])-\\d{2}\\b/', 'd-m-y' , false, 315, true),
('date', 'default d.m.y', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.(0[1-9]|1[0-2]|[1-9])\\.\\d{2}\\b/', 'd.m.y' , false, 314, true),

('date', 'default Y/m/d', '/\\b\\d{4}\\/(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\b/', 'Y/m/d' , false, 325, true),
('date', 'default Y-m-d', '/\\b\\d{4}-(0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\b/', 'Y-m-d' , false, 324, true),
('date', 'default Y.m.d', '/\\b\\d{4}\\.(0[1-9]|1[0-2]|[1-9])\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\b/', 'Y.m.d' , false, 323, true),
('date', 'default Y m d', '/\\b\\d{4} (0[1-9]|1[0-2]|[1-9]) (0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\b/', 'Y m d' , false, 322, true),

('date', 'default m/d/Y', '/\\b(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/\\d{4}\\b/', 'm/d/Y' , false, 321, true),
('date', 'default m-d-Y', '/\\b(0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-\\d{4}\\b/', 'm-d-Y' , false, 308, true),
('date', 'default m.d.Y', '/\\b(0[1-9]|1[0-2]|[1-9])\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.\\d{4}\\b/', 'm.d.Y' , false, 307, true),
('date', 'default m d Y', '/\\b(0[1-9]|1[0-2]|[1-9]) (0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) \\d{4}\\b/', 'm d Y' , false, 306, true),

('date', 'default m/d/y', '/\\b(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/\\d{2}\\b/', 'm/d/y' , false, 316, true),
('date', 'default m-d-y', '/\\b(0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-\\d{2}\\b/', 'm-d-y' , false, 304, true),
('date', 'default m.d.y', '/\\b(0[1-9]|1[0-2]|[1-9])\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.\\d{2}\\b/', 'm.d.y' , false, 303, true),

('date', 'default y/m/d', '/\\b\\d{2}\\/(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\b/', 'y/m/d' , false, 302, true),

('date', 'default Ymd', '/\\b\\d{4}(0[1-9]|1[0-2])(0[1-9]|[1-2][0-9]|3[0-1])\\b/', 'Ymd' , false, 301, true),

('date', 'default d/m Y', '/\\b(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9]) \\d{4}\\b/', 'd/m Y' , false, 300, true),

-- floats
('float', 'default exponent', '/\\b\\d*(,|\\.)\\d+[eE]-?\\d+\\b/', null , true, 218, true),
('float', 'default arabian', '/\\b([١٢٣٤٥٦٧٨٩]{1,3}٬){1}(\\d{3}٬)*\\d{3}٫\\d+\\b/u', '#٬##0٫###', false, 217, true),

('float', 'default with "''" separator', "/\\b([1-9]\\d{0,2}\\.){1}(\\d{3}\\.)*\\d{3}'\\d+\\b/u", "#.##0'###", false, 216, true),
('float', 'default indian', '/\\b(\\d,)?(\\d{2},)+(\\d{3})\\.\\d+\\b/u', '#,##,##0.###', false, 215, true),

('float', 'default with comma thousand decimal dot', '/\\b([1-9]\\d{0,2},){1}(\\d{3},)*\\d{3}\\.\\d+\\b/u', '#,##0.###', false, 214, true),
('float', 'default chinese', '/\\b(\\d{1,4},){1}(\\d{4},)*\\d{4}\\.\\d+\\b/u', '#,###0.###', false, 213, true),
('float', 'default with comma thousand decimal middle dot', '/\\b([1-9]\\d{0,2},){1}(\\d{3},)*\\d{3}·\\d+\\b/u', '#,##0·###', false, 212, true),
('float', 'default with whitespace thousand decimal dot', '/\\b([1-9]\\d{0,2} ){1}(\\d{3} )*\\d{3}\\.\\d+\\b/u', '# ##0.###', false, 211, true),
('float', 'default with [THSP] thousand decimal dot', '/\\b([1-9]\\d{0,2}\\x{2009}){1}(\\d{3}\\x{2009})*\\d{3}\\.\\d+\\b/u', '# ##0.###', false, 210, true),
('float', 'default with [NNBSP] thousand decimal dot', '/\\b([1-9]\\d{0,2}\\x{202F}){1}(\\d{3}\\x{202F})*\\d{3}\\.\\d+\\b/u', '# ##0.###', false, 209, true),
('float', 'default with "˙" thousand decimal dot', '/\\b([1-9]\\d{0,2}˙){1}(\\d{3}˙)*\\d{3}\\.\\d+\\b/u', '#˙##0.###', false, 208, true),
('float', 'default with "''" thousand decimal dot', "/\\b([1-9]\\d{0,2}'){1}(\\d{3}')*\\d{3}\\.\\d+\\b/u", "#'##0.###", false, 207, true),

('float', 'default with dot thousand decimal comma', '/\\b([1-9]\\d{0,2}\\.){1}(\\d{3}\\.)*\\d{3},\\d+\\b/u', '#.##0,###', false, 206, true),
('float', 'default with whitespace thousand decimal comma', '/\\b([1-9]\\d{0,2} ){1}(\\d{3} )*\\d{3},\\d+\\b/u', '# ##0,###', false, 205, true),
('float', 'default with [THSP] thousand decimal comma', '/\\b([1-9]\\d{0,2}\\x{2009}){1}(\\d{3}\\x{2009})*\\d{3},\\d+\\b/u', '# ##0,###', false, 204, true),
('float', 'default with [NNBSP] thousand decimal comma', '/\\b([1-9]\\d{0,2}\\x{202F}){1}(\\d{3}\\x{202F})*\\d{3},\\d+\\b/u', '# ##0,###', false, 203, true),
('float', 'default with "˙" thousand decimal comma', '/\\b([1-9]\\d{0,2}˙){1}(\\d{3}˙)*\\d{3},\\d+\\b/u', '#˙##0,###', false, 202, true),
('float', 'default with "''" thousand decimal comma', "/\\b([1-9]\\d{0,2}'){1}(\\d{3}')*\\d{3},\\d+\\b/u", "#'##0,###", false, 201, true),
('float', 'default generic', '/\\b([1-9]|[1-9]\\d+)(\\.|,|·)\\d+\\b/u', null, false, 150, true),

-- integer
('integer', 'default chinese with comma thousand', "/\\b[1-9]\\d{0,3}(,)?(\\d{4}\\1)+\\d{4}\\b/u", '#,###0', false, 200, true),
('integer', 'default generic with not standard separator', "/\\b[1-9]\\d{0,2}(·|˙|'|\\x{2009}|\\x{202F}|٬)(\\d{3}\\1)*\\d{3}\\b/u", null, false, 180, true),
('integer', 'default generic with separator', "/\\b[1-9]\\d{0,2}(,|\\.)?(\\d{3}\\1)+\\d{3}\\b/", null, false, 175, true),

('integer', 'default indian with comma thousand', "/\\b[1-9]\\d{0,1}(,)?(\\d{2}\\1)+\\d{3}\\b/u", '#,##,##0', false, 170, true),
('integer', 'default arabian with separator',"/\\b[١٢٣٤٥٦٧٨٩]{0,2}٬?([١٢٣٤٥٦٧٨٩]{3}٬)*[١٢٣٤٥٦٧٨٩]{3}\\b/u", null, false, 120, true),
('integer', 'default simple', '/\\b(\\d|[1-9]\\d+)\\b/u', '#', false, 100, true)
;

