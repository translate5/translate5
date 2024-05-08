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
--  translate5: Please see http://www.translate5.net/plugin-exception.txt or
--  plugin-exception.txt in the root folder of translate5.
--
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT

DELETE FROM `LEK_content_protection_content_recognition` WHERE `isDefault` = true AND `type` = 'float' AND `name` = 'default arabian with separator';

-- floats
UPDATE `LEK_content_protection_content_recognition` SET `format` = '#.#' WHERE `type` = 'float' AND `name` = 'default generic with dot';
UPDATE `LEK_content_protection_content_recognition` SET `format` = '#,#' WHERE `type` = 'float' AND `name` = 'default generic with comma';
UPDATE `LEK_content_protection_content_recognition` SET `format` = '#·#' WHERE `type` = 'float' AND `name` = 'default generic with middle dot';

-- integer
UPDATE `LEK_content_protection_content_recognition` SET
    `format` = '#·###',
    `name` = 'default generic with Middle dot separator',
    `description` = 'Thousand separator is Middle dot<br/>example: 120·450',
    `regex` = "/(\\s|^)([1-9]\\d{0,2}(·)(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u"
WHERE `type` = 'integer' AND `name` = 'default generic with not standard separator';

UPDATE `LEK_content_protection_content_recognition` SET
    `format` = '#.###',
    `name` = 'default generic with dot separator',
    `description` = 'Thousand separator is dot<br/>example: 120.450',
    `regex` = "/(\\s|^)([1-9]\\d{0,2}(.)(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u"
WHERE `type` = 'integer' AND `name` = 'default generic with not standard separator';

INSERT INTO `LEK_content_protection_content_recognition` (`type`, `name`, `description`, `regex`, `matchId`, `format`, `keepAsIs`, `isDefault`, `enabled`) VALUES
('integer', 'default generic with comma separator', 'Thousand separator is comma.<br/>example: 120,450', "/(\\s|^)([1-9]\\d{0,2}(,)(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u", 2, '#,###', false, true, true),
('integer', 'default generic with dot above separator', 'Thousand separator is dot above.<br/>example: 120˙450', "/(\\s|^)([1-9]\\d{0,2}(˙)(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u", 2, '#˙###', false, true, true),
('integer', 'default generic with apostrophe separator', 'Thousand separator is apostrophe.<br/>example: 120''450', "/(\\s|^)([1-9]\\d{0,2}(')(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u", 2, "#'###", false, true, true),
('integer', 'default generic with thin space separator', 'Thousand separator is thin space.<br/>example: 120 450', "/(\\s|^)([1-9]\\d{0,2}(\\x{2009})(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u", 2, '# ###', false, true, true),
('integer', 'default generic with NNBSP separator', 'Thousand separator is NNBSP.<br/>example: 120 450', "/(\\s|^)([1-9]\\d{0,2}(\\x{202F})(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u", 2, '# ###', false, true, true),
('integer', 'default generic with arabic thousands separator', 'Thousand separator is arabic thousands separator<br/>example: 120٬450', "/(\\s|^)([1-9]\\d{0,2}(٬)(\\d{3}\\3)*\\d{3})((\\.(\\s|$))|(,(\\s|$))|\\s|$)/u", 2, '#٬###', false, true, true);

