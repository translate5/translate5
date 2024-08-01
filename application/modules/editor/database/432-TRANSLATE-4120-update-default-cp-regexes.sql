-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - '.(dateUPDATE `LEK_content_protection_content_recognition` SET `regex` = UPDATE `LEK_content_protection_content_recognition` SET `format` = '' WHERE `type` = 'Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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
--  translate5: Please see http://www.net/plugin-exception.txt or
--  plugin-exception.txt in the root folder of 
--
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT

UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2}\\.){1}(\\d{3}\\.)*\\d{3}\'\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default with "\'" separator';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?(\\d,)?(\\d{2},)+(\\d{3})\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u'  WHERE  type = 'float' AND name = 'default indian';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2},){1}(\\d{3},)*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default with comma thousand decimal dot';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?(\\d{1,4},){1}(\\d{4},)*\\d{4}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default chinese';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2},){1}(\\d{3},)*\\d{3}·\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default with comma thousand decimal middle dot';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2} ){1}(\\d{3} )*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default with whitespace thousand decimal dot';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2}\\x{2009}){1}(\\d{3}\\x{2009})*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default with [THSP] thousand decimal dot';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2}\\x{202F}){1}(\\d{3}\\x{202F})*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default with [NNBSP] thousand decimal dot';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2}˙){1}(\\d{3}˙)*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default with "˙" thousand decimal dot';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2}\'){1}(\\d{3}\')*\\d{3}\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default with "\'" thousand decimal dot';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2}\\.){1}(\\d{3}\\.)*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default with dot thousand decimal comma';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2} ){1}(\\d{3} )*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default with whitespace thousand decimal comma';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2}\\x{2009}){1}(\\d{3}\\x{2009})*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u' WHERE type = 'float' AND name = 'default with [THSP] thousand decimal comma';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2}\\x{202F}){1}(\\d{3}\\x{202F})*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default with [NNBSP] thousand decimal comma';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2}˙){1}(\\d{3}˙)*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default with "˙" thousand decimal comma';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2}\'){1}(\\d{3}\')*\\d{3},\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default with "\'" thousand decimal comma';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]|[1-9]\\d+|0)\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default generic with dot';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?[1-9]\\d{0,3}(,)?(\\d{4}\\3)*(?<=,)\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'integer' AND name = 'default chinese with comma thousand';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2}(·))?(\\d{3}\\4)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u' WHERE type = 'integer' AND name = 'default generic with Middle dot separator';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,2}(\\.))?(\\d{3}\\4)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE type = 'integer' AND name = 'default generic with dot';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d{0,1}(,))?(\\d{2}\\4)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'integer' AND name = 'default indian with comma thousand';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?[١٢٣٤٥٦٧٨٩]{0,2}٬?([١٢٣٤٥٦٧٨٩]{3}٬)*[١٢٣٤٥٦٧٨٩]{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'integer' AND name = 'default arabian with separator';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]\\d+|\\d))((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'integer' AND name = 'default simple';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]|[1-9]\\d+|0),\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default generic with comma';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?([1-9]|[1-9]\\d+|0)·\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'float' AND name = 'default generic with middle dot';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?[1-9]\\d{0,2}(\\s)?(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE type = 'integer' AND name = 'default generic with whitespace';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?[1-9]\\d{0,2}(bullshit)(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u' WHERE type = 'integer' AND name = 'default generic with bullshit separator';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?[1-9]\\d{0,2}(,)(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'integer' AND name = 'default generic with comma separator';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?[1-9]\\d{0,2}(˙)(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'integer' AND name = 'default generic with dot above separator';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?[1-9]\\d{0,2}(\')(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'integer' AND name = 'default generic with apostrophe separator';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?[1-9]\\d{0,2}(\\x{2009})(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'integer' AND name = 'default generic with thin space separator';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?[1-9]\\d{0,2}(\\x{202F})(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'integer' AND name = 'default generic with NNBSP separator';
UPDATE LEK_content_protection_content_recognition SET regex = '/(\\s|^|\\()([-+]?[1-9]\\d{0,2}(٬)(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE type = 'integer' AND name = 'default generic with arabic thousands separator';
