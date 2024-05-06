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

DELETE FROM `LEK_number_protection_number_recognition` WHERE `isDefault` = true;
ALTER TABLE `LEK_number_protection_number_recognition` AUTO_INCREMENT = 1;
INSERT INTO `LEK_number_protection_number_recognition` (`type`, `name`, `description`, `regex`, `matchId`, `format`, `keepAsIs`, `priority`, `isDefault`, `enabled`) VALUES
-- MAC address
('mac-address', 'default', 'MAC address', '/(\\s|^)((?:[[:xdigit:]]{2}([-:]))(?:[[:xdigit:]]{2}\\3){4}[[:xdigit:]]{2})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, null, true, 500, true, false),
-- IP address
('ip-address', 'default', 'IP address','/(\\s|^)((25[0-5]|(2[0-4]|1\\d|[1-9]|)\\d)(\\.(25[0-5]|(2[0-4]|1\\d|[1-9]|)\\d)){3})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, null, true, 400, true, false),
-- dates
('date', 'default Y/d/m', 'Year with 4 digits, month and day with 2.<br/>example: 1989/29/05', '/(\\s|^)(\\d{4}\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'Y/d/m' , false, 313, true, false),
('date', 'default Y-d-m', 'Year with 4 digits, month and day with 2.<br/>example: 1989-29-05', '/(\\s|^)(\\d{4}-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'Y-d-m' , false, 312, true, false),
('date', 'default Y.d.m', 'Year with 4 digits, month and day with 2.<br/>example: 1989.29.05', '/(\\s|^)(\\d{4}\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.(0[1-9]|1[0-2]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'Y.d.m' , false, 311, true, false),
('date', 'default Y d m', 'Year with 4 digits, month and day with 2.<br/>example: 1989 29 05', '/(\\s|^)(\\d{4} (0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) (0[1-9]|1[0-2]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'Y d m' , false, 310, true, false),

('date', 'default d/m/Y', 'Day and month with 2 digits, year with 4.<br/>example: 29/05/1989', '/(\\s|^)((0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9])\\/\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'd/m/Y' , false, 309, true, true),
('date', 'default d-m-Y', 'Day and month with 2 digits, year with 4.<br/>example: 1989-29-05', '/(\\s|^)((0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])-\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'd-m-Y' , false, 319, true, true),
('date', 'default d.m.Y', 'Day and month with 2 digits, year with 4.<br/>example: 29.05.1989', '/(\\s|^)((0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.(0[1-9]|1[0-2]|[1-9])\\.\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'd.m.Y' , false, 318, true, true),
('date', 'default d m Y', 'Day and month with 2 digits, year with 4.<br/>example: 29 05 1989', '/(\\s|^)((0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) (0[1-9]|1[0-2]|[1-9]) \\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'd m Y' , false, 317, true, false),

('date', 'default d/m/y', 'Day, month and year with 2 digits.<br/>example: 29/05/89', '/(\\s|^)((0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9])\\/\\d{2})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'd/m/y' , false, 305, true, true),
('date', 'default d-m-y', 'Day, month and year with 2 digits.<br/>example: 29-05-89', '/(\\s|^)((0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])-\\d{2})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'd-m-y' , false, 315, true, true),
('date', 'default d.m.y', 'Day, month and year with 2 digits.<br/>example: 29.05.89','/(\\s|^)((0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.(0[1-9]|1[0-2]|[1-9])\\.\\d{2})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'd.m.y' , false, 314, true, true),

('date', 'default Y/m/d', 'Year with 4 digits, month and day with 2.<br/>example: 1989/05/29', '/(\\s|^)(\\d{4}\\/(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'Y/m/d' , false, 325, true, true),
('date', 'default Y-m-d', 'Year with 4 digits, month and day with 2.<br/>example: 1989-05-29', '/(\\s|^)(\\d{4}-(0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'Y-m-d' , false, 324, true, true),
('date', 'default Y.m.d', 'Year with 4 digits, month and day with 2.<br/>example: 1989.05.29', '/(\\s|^)(\\d{4}\\.(0[1-9]|1[0-2]|[1-9])\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'Y.m.d' , false, 323, true, false),
('date', 'default Y m d', 'Year with 4 digits, month and day with 2.<br/>example: 1989 05 29', '/(\\s|^)(\\d{4} (0[1-9]|1[0-2]|[1-9]) (0[1-9]|[1-2][0-9]|3[0-1]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'Y m d' , false, 322, true, false),

('date', 'default m/d/Y', 'Month and day with 2 digits, year with 4.<br/>example: 05/29/1989', '/(\\s|^)((0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'm/d/Y' , false, 321, true, true),
('date', 'default m-d-Y', 'Month and day with 2 digits, year with 4.<br/>example: 05-29-1989', '/(\\s|^)((0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'm-d-Y' , false, 308, true, true),
('date', 'default m.d.Y', 'Month and day with 2 digits, year with 4.<br/>example: 05.29.1989', '/(\\s|^)((0[1-9]|1[0-2]|[1-9])\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'm.d.Y' , false, 307, true, false),
('date', 'default m d Y', 'Month and day with 2 digits, year with 4.<br/>example: 05 29 1989', '/(\\s|^)((0[1-9]|1[0-2]|[1-9]) (0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) \\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'm d Y' , false, 306, true, false),

('date', 'default m/d/y', 'Month, day and year with 2 digits.<br/>example: 05/29/89', '/(\\s|^)((0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/\\d{2})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'm/d/y' , false, 316, true, true),
('date', 'default m-d-y', 'Month, day and year with 2 digits.<br/>example: 05-29-89', '/(\\s|^)((0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-\\d{2})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'm-d-y' , false, 304, true, true),
('date', 'default m.d.y', 'Month, day and year with 2 digits.<br/>example: 05.29.89', '/(\\s|^)((0[1-9]|1[0-2]|[1-9])\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.\\d{2})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'm.d.y' , false, 303, true, false),

('date', 'default y/m/d', 'Year, month and day with 2 digits.<br/>example: 89/05/29', '/(\\s|^)(\\d{2}\\/(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'y/m/d' , false, 302, true, true),

('date', 'default Ymd', 'Year with 4 digits, month and day with 2.<br/>example: 19890529', '/(\\s|^)(\\d{4}(0[1-9]|1[0-2])(0[1-9]|[1-2][0-9]|3[0-1]))((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'Ymd' , false, 301, true, false),

('date', 'default d/m Y', 'Day and month with 2 digits, year with 4.<br/>example: 29/05 1989', '/(\\s|^)((0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9]) \\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, 'd/m Y' , false, 300, true, false),

-- floats
('float', 'default exponent', 'example: 2.1e-5 or 2,1e-5', '/(\\s|^)(\\d*(,|\\.)\\d+[eE]-?\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/', 2, null , true, 218, true, false),
('float', 'default arabian', 'example: ١٬٢٣٤٬٥٦٧٫٨٩', '/(\\s|^)(([١٢٣٤٥٦٧٨٩]{1,3}٬){1}(\\d{3}٬)*\\d{3}٫\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, '#٬##0٫###', false, 217, true, false),

('float', 'default with "''" separator', 'Apostrophe as decimal separator and dot as thousand separator.<br/>example: 120.450''23', "/(\\s|^)(([1-9]\\d{0,2}\\.){1}(\\d{3}\\.)*\\d{3}'\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u", 2, "#.##0'###", false, 216, true, false),
('float', 'default indian', 'First thousands are separated then hundreds by comma and dot as decimal separator.<br/>example: 1,20,450.23','/(\\s|^)((\\d,)?(\\d{2},)+(\\d{3})\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, '#,##,##0.###', false, 215, true, false),

('float', 'default with comma thousand decimal dot', 'Dot as decimal separator, comma as thousand separator.<br/>example: 120,450.23', '/(\\s|^)(([1-9]\\d{0,2},){1}(\\d{3},)*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, '#,##0.###', false, 214, true, true),
('float', 'default chinese', 'Dot as decimal separator, comma as ten-thousand separator.<br/>example: 12,0450.23', '/(\\s|^)((\\d{1,4},){1}(\\d{4},)*\\d{4}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, '#,###0.###', false, 213, true, false),
('float', 'default with comma thousand decimal middle dot', 'Middle dot as decimal separator, comma as thousand separator.<br/>example: 120,450·23', '/(\\s|^)(([1-9]\\d{0,2},){1}(\\d{3},)*\\d{3}·\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, '#,##0·###', false, 212, true, false),
('float', 'default with whitespace thousand decimal dot', 'White space as thousand separator and dot as decimal separator.<br/>example: 120 450.23', '/(\\s|^)(([1-9]\\d{0,2} ){1}(\\d{3} )*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, '# ##0.###', false, 211, true, true),
('float', 'default with [THSP] thousand decimal dot', 'Thin space as thousand separator and dot as decimal separator.<br/>example: 120 450.23', '/(\\s|^)(([1-9]\\d{0,2}\\x{2009}){1}(\\d{3}\\x{2009})*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, '# ##0.###', false, 210, true, true),
('float', 'default with [NNBSP] thousand decimal dot', 'Nonbreakable space as thousand separator and dot as decimal separator.<br/>example: 120 450.23', '/(\\s|^)(([1-9]\\d{0,2}\\x{202F}){1}(\\d{3}\\x{202F})*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, '# ##0.###', false, 209, true, true),
('float', 'default with "˙" thousand decimal dot', 'Dot above as thousand separator and dot as decimal separator.<br/>example: 120˙450.23', '/(\\s|^)(([1-9]\\d{0,2}˙){1}(\\d{3}˙)*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, '#˙##0.###', false, 208, true, false),
('float', 'default with "''" thousand decimal dot', 'Apostrophe as thousand separator and dot as decimal separator.<br/>example: 120''450.23', "/(\\s|^)(([1-9]\\d{0,2}'){1}(\\d{3}')*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u", 2, "#'##0.###", false, 207, true, false),

('float', 'default with dot thousand decimal comma', 'Dot as thousand separator and comma as decimal separator.<br/>example: 120.450,23', '/(\\s|^)(([1-9]\\d{0,2}\\.){1}(\\d{3}\\.)*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, '#.##0,###', false, 206, true, true),
('float', 'default with whitespace thousand decimal comma', 'Whitespace as thousand separator and comma as decimal separator.<br/>example: 120 450,23','/(\\s|^)(([1-9]\\d{0,2} ){1}(\\d{3} )*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, '# ##0,###', false, 205, true, true),
('float', 'default with [THSP] thousand decimal comma', 'Thin space as thousand separator and comma as decimal separator.<br/>example: 120 450,23', '/(\\s|^)(([1-9]\\d{0,2}\\x{2009}){1}(\\d{3}\\x{2009})*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, '# ##0,###', false, 204, true, true),
('float', 'default with [NNBSP] thousand decimal comma', 'Nonbreakable space as thousand separator and comma as decimal separator.<br/>example: 120 450,23', '/(\\s|^)(([1-9]\\d{0,2}\\x{202F}){1}(\\d{3}\\x{202F})*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, '# ##0,###', false, 203, true, true),
('float', 'default with "˙" thousand decimal comma', 'Dot above as thousand separator and comma as decimal separator.<br/>example: 120˙450,23', '/(\\s|^)(([1-9]\\d{0,2}˙){1}(\\d{3}˙)*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, '#˙##0,###', false, 202, true, false),
('float', 'default with "''" thousand decimal comma', 'Apostrophe as thousand separator and comma as decimal separator.<br/>example: 120''450,23', "/(\\s|^)(([1-9]\\d{0,2}'){1}(\\d{3}')*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u", 2, "#'##0,###", false, 201, true, false),
('float', 'default generic', 'Without thousand separator, dot/comma/middle dot as decimal separator.<br/>example: 120450,23 or 120450.23 or 120450·23', '/(\\s|^)(([1-9]|[1-9]\\d+)(\\.|,|·)\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, null, false, 150, true, true),

-- integer
('integer', 'default chinese with comma thousand', 'Comma as separator for ten-thousand.<br/>example: 12,0450', "/(\\s|^)([1-9]\\d{0,3}(,)?(\\d{4}\\3)+\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u", 2, '#,###0', false, 200, true, false),
('integer', 'default generic with not standard separator', 'One of the following as thousand separator: Middle dot, dot above, apostrophe.<br/>example: 120·450 or 120˙450 or 120''450', "/(\\s|^)([1-9]\\d{0,2}(·|˙|'|\\x{2009}|\\x{202F}|٬)(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u", 2, null, false, 180, true, true),
('integer', 'default generic with separator', 'Comma or dot as thousand separator.<br/>example: 120,450 or 120.450', "/(\\s|^)([1-9]\\d{0,2}(,|\\.)?(\\d{3}\\3)+\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/", 2, null, false, 175, true, true),

('integer', 'default indian with comma thousand', 'First thousands are separated then hundreds by comma.<br/>example: 1,20,450', "/(\\s|^)([1-9]\\d{0,1}(,)?(\\d{2}\\3)+\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u", 2, '#,##,##0', false, 170, true, false),
('integer', 'default arabian with separator', '', "/(\\s|^)([١٢٣٤٥٦٧٨٩]{0,2}٬?([١٢٣٤٥٦٧٨٩]{3}٬)*[١٢٣٤٥٦٧٨٩]{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u", 2, null, false, 120, true, false),
('integer', 'default simple', 'No thousand separator.<br/>example: 3543657435743574', '/(\\s|^)(([1-9]\\d+|\\d))((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u', 2, '#', false, 100, true, true)
;

