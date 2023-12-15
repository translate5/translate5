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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */
ALTER TABLE `Zf_configuration` CHANGE `type` `type` ENUM('string','integer','float','boolean','list','map','absolutepath','markup','json','regex','regexlist','xpath','xpathlist') NOT NULL DEFAULT 'string' COMMENT 'the type of the config value is needed also for GUI';
INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES ('runtimeOptions.import.xlf.placeablesXpathes', '1', 'editor', 'import', '[]', '[]', '', 'xpathlist', 'A list of XPathes that will search regular internal tags if they contain placeables. Placeables will always be shown with their text-content and not as traditional internal tags.', 8, 'XLF Import placebles: Detection XPathes', 'File parser options', '');

-- bundle existing parser options
UPDATE `Zf_configuration` SET `guiGroup` = 'File parser options' WHERE `name` = 'runtimeOptions.import.fileparser.csv.options.regexes.afterTagParsing.regex';
UPDATE `Zf_configuration` SET `guiGroup` = 'File parser options' WHERE `name` = 'runtimeOptions.import.fileparser.options.protectTags';
UPDATE `Zf_configuration` SET `guiGroup` = 'File parser options', `guiName` = 'XLF Import: sub element length is included in overall transunit-length' WHERE `name` = 'runtimeOptions.import.xlf.includedSubElementInLengthCalculation';
