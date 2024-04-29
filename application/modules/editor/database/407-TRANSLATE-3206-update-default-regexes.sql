-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - '.(dateUPDATE `LEK_content_protection_content_recognition` SET `regex` = ('Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

-- MAC address
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((?:[[:xdigit:]]{2}([-:]))(?:[[:xdigit:]]{2}\\3){4}[[:xdigit:]]{2})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'mac-address' AND `name` = 'default';
-- IP address
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((25[0-5]|(2[0-4]|1\\d|[1-9]|)\\d)(\\.(25[0-5]|(2[0-4]|1\\d|[1-9]|)\\d)){3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'ip-address' AND `name` = 'default';
-- dates
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(\\d{4}\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default Y/d/m';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(\\d{4}-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default Y-d-m';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(\\d{4}\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.(0[1-9]|1[0-2]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default Y.d.m';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(\\d{4} (0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) (0[1-9]|1[0-2]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default Y d m';

UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9])\\/\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default d/m/Y';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])-\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default d-m-Y';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.(0[1-9]|1[0-2]|[1-9])\\.\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default d.m.Y';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) (0[1-9]|1[0-2]|[1-9]) \\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default d m Y';

UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9])\\/\\d{2})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default d/m/y';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-(0[1-9]|1[0-2]|[1-9])-\\d{2})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default d-m-y';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.(0[1-9]|1[0-2]|[1-9])\\.\\d{2})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default d.m.y';

UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(\\d{4}\\/(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default Y/m/d';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(\\d{4}-(0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default Y-m-d';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(\\d{4}\\.(0[1-9]|1[0-2]|[1-9])\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default Y.m.d';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(\\d{4} (0[1-9]|1[0-2]|[1-9]) (0[1-9]|[1-2][0-9]|3[0-1]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default Y m d';

UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default m/d/Y';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default m-d-Y';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((0[1-9]|1[0-2]|[1-9])\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default m.d.Y';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((0[1-9]|1[0-2]|[1-9]) (0[1-9]|[1-2][0-9]|3[0-1]|[1-9]) \\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default m d Y';

UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/\\d{2})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default m/d/y';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((0[1-9]|1[0-2]|[1-9])-(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])-\\d{2})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default m-d-y';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((0[1-9]|1[0-2]|[1-9])\\.(0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\.\\d{2})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default m.d.y';

UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(\\d{2}\\/(0[1-9]|1[0-2]|[1-9])\\/(0[1-9]|[1-2][0-9]|3[0-1]|[1-9]))((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default y/m/d';

UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(\\d{4}(0[1-9]|1[0-2])(0[1-9]|[1-2][0-9]|3[0-1]))((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default Ymd';

UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((0[1-9]|[1-2][0-9]|3[0-1]|[1-9])\\/(0[1-9]|1[0-2]|[1-9]) \\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'date' AND `name` = 'default d/m Y';

-- floats
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(\\d*(,|\\.)\\d+[eE]-?\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'float' AND `name` = 'default exponent';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(([١٢٣٤٥٦٧٨٩]{1,3}٬){1}(\\d{3}٬)*\\d{3}٫\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default arabian';

UPDATE `LEK_content_protection_content_recognition` SET `regex` = "/(\\s|^|\\()(([1-9]\\d{0,2}\\.){1}(\\d{3}\\.)*\\d{3}'\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u" WHERE `type` = 'float' AND `name` = 'default with "''" separator';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((\\d,)?(\\d{2},)+(\\d{3})\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default indian';

UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(([1-9]\\d{0,2},){1}(\\d{3},)*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default with comma thousand decimal dot';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()((\\d{1,4},){1}(\\d{4},)*\\d{4}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default chinese';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(([1-9]\\d{0,2},){1}(\\d{3},)*\\d{3}·\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default with comma thousand decimal middle dot';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(([1-9]\\d{0,2} ){1}(\\d{3} )*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default with whitespace thousand decimal dot';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(([1-9]\\d{0,2}\\x{2009}){1}(\\d{3}\\x{2009})*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default with [THSP] thousand decimal dot';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(([1-9]\\d{0,2}\\x{202F}){1}(\\d{3}\\x{202F})*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default with [NNBSP] thousand decimal dot';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(([1-9]\\d{0,2}˙){1}(\\d{3}˙)*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default with "˙" thousand decimal dot';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = "/(\\s|^|\\()(([1-9]\\d{0,2}'){1}(\\d{3}')*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u" WHERE `type` = 'float' AND `name` = 'default with "''" thousand decimal dot';

UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(([1-9]\\d{0,2}\\.){1}(\\d{3}\\.)*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default with dot thousand decimal comma';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(([1-9]\\d{0,2} ){1}(\\d{3} )*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default with whitespace thousand decimal comma';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(([1-9]\\d{0,2}\\x{2009}){1}(\\d{3}\\x{2009})*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default with [THSP] thousand decimal comma';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(([1-9]\\d{0,2}\\x{202F}){1}(\\d{3}\\x{202F})*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default with [NNBSP] thousand decimal comma';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(([1-9]\\d{0,2}˙){1}(\\d{3}˙)*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default with "˙" thousand decimal comma';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = "/(\\s|^|\\()(([1-9]\\d{0,2}'){1}(\\d{3}')*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u" WHERE `type` = 'float' AND `name` = 'default with "''" thousand decimal comma';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(([1-9]|[1-9]\\d+)(\\.|,|·)\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default generic';

-- integer
UPDATE `LEK_content_protection_content_recognition` SET `regex` = "/(\\s|^|\\()([1-9]\\d{0,3}(,)?(\\d{4}\\3)+\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u" WHERE `type` = 'integer' AND `name` = 'default chinese with comma thousand';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = "/(\\s|^|\\()([1-9]\\d{0,2}(·|˙|'|\\x{2009}|\\x{202F}|٬)(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u" WHERE `type` = 'integer' AND `name` = 'default generic with not standard separator';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = "/(\\s|^|\\()([1-9]\\d{0,2}(,|\\.)?(\\d{3}\\3)+\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/" WHERE `type` = 'integer' AND `name` = 'default generic with separator';

UPDATE `LEK_content_protection_content_recognition` SET `regex` = "/(\\s|^|\\()([1-9]\\d{0,1}(,)?(\\d{2}\\3)+\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u" WHERE `type` = 'integer' AND `name` = 'default indian with comma thousand';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = "/(\\s|^|\\()([١٢٣٤٥٦٧٨٩]{0,2}٬?([١٢٣٤٥٦٧٨٩]{3}٬)*[١٢٣٤٥٦٧٨٩]{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u" WHERE `type` = 'integer' AND `name` = 'default arabian with separator';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(([1-9]\\d+|\\d))((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'integer' AND `name` = 'default simple';
;

