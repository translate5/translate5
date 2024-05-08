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


-- floats
UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()(([1-9]|[1-9]\\d+)\\.\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u' WHERE `type` = 'float' AND `name` = 'default generic';
UPDATE `LEK_content_protection_content_recognition` SET `name` = 'default generic with dot' WHERE `type` = 'float' AND `name` = 'default generic';

INSERT INTO `LEK_content_protection_content_recognition` (`type`, `name`, `description`, `regex`, `matchId`, `format`, `keepAsIs`, `priority`, `isDefault`, `enabled`) VALUES
('float', 'default generic with comma', '123,567', '/(\\s|^|\\()(([1-9]|[1-9]\\d+),\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u', 2, null, false, 151, true, false),
('float', 'default generic with middle dot', '123·567', '/(\\s|^|\\()(([1-9]|[1-9]\\d+)·\\d+)((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u', 2, null, false, 152, true, false);

-- integer
UPDATE `LEK_content_protection_content_recognition` SET `regex` = "/(\\s|^|\\()([1-9]\\d{0,3}(,)(\\d{4}\\3)*\\d{4})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u" WHERE `type` = 'integer' AND `name` = 'default chinese with comma thousand';
UPDATE `LEK_content_protection_content_recognition` SET `regex` = "/(\\s|^|\\()([1-9]\\d{0,2}(·|˙|'|\\x{2009}|\\x{202F}|٬)(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u" WHERE `type` = 'integer' AND `name` = 'default generic with not standard separator';

UPDATE `LEK_content_protection_content_recognition` SET `regex` = '/(\\s|^|\\()([1-9]\\d{0,2}(\\.)(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/' WHERE `type` = 'integer' AND `name` = 'default generic with separator';
UPDATE `LEK_content_protection_content_recognition` SET `name` = 'default generic with dot' WHERE `type` = 'integer' AND `name` = 'default generic with separator';

INSERT INTO `LEK_content_protection_content_recognition` (`type`, `name`, `description`, `regex`, `matchId`, `format`, `keepAsIs`, `priority`, `isDefault`, `enabled`) VALUES
('integer', 'default generic with comma', '123,567,890', '/(\\s|^|\\()([1-9]\\d{0,2}(\\,)(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/', 2, null, false, 110, true, false),
('integer', 'default generic with whitespace', '123 567 890', '/(\\s|^|\\()([1-9]\\d{0,2}(\\s)(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/', 2, null, false, 111, true, false);

UPDATE `LEK_content_protection_content_recognition` SET `regex` = "/(\\s|^|\\()([1-9]\\d{0,1}(,)(\\d{2}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$|\\))/u" WHERE `type` = 'integer' AND `name` = 'default indian with comma thousand';
;

