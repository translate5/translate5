
-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-09-16', 'TRANSLATE-4982', 'bugfix', 'Editor general - Error if segment has locked tag', '[🐞 Fix] Fixed error that appeared on a try to edit a segment that contains a "locked" tag', '15'),
('2025-09-16', 'TRANSLATE-4980', 'bugfix', 'Editor general - Error on unhandled promise rejection when trying to log the error', '[🐞 Fix] Fixed error that might happen on unsuccessful spellcheck request in certain cases', '15'),
('2025-09-16', 'TRANSLATE-4977', 'bugfix', 'LanguageResources - OpenAI service-state evaluation produces PHP-warnings flooding logs', '[🐞 Fix] OpenAI service evaluation could create PHP-warnings flooding logs', '15'),
('2025-09-16', 'TRANSLATE-4972', 'bugfix', 'Editor general - Error on processing trackchanges', '[🐞 Fix] Fixed error that was happening sometimes when trying to apply trackchanges on typing in editor', '15');