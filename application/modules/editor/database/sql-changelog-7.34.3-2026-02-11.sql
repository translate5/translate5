
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-02-11', 'TRANSLATE-5262', 'change', 'InstantTranslate - Improve instant-translate segmentation config description', 'Config description improvement.', '15'),
('2026-02-11', 'TRANSLATE-5276', 'bugfix', 'InstantTranslate - InstantTranslate: Language DropDown Empty', 'FIX InstantTranslate: Standalone language dropdown empty in standalone mode', '15'),
('2026-02-11', 'TRANSLATE-5265', 'bugfix', 'Editor general - Project description limit is 500 chars however error appears on 1000', '[🐞 Fix] Increase check for project description max length to the allowed value', '15'),
('2026-02-11', 'TRANSLATE-5259', 'bugfix', 'SpellCheck (LanguageTool integration) - PHP error when trying to access non existent languages in spellchecker', 'PHP error in context of spellcheck fixed.', '15'),
('2026-02-11', 'TRANSLATE-5037', 'bugfix', 'Editor general - copying from editor will insert terminology markup', 'FIXED: improved detection of whether copying is done from opened richtext editor', '15'),
('2026-02-11', 'TRANSLATE-4957', 'bugfix', 'Editor general - RootCause: No access on job anymore', '[DEBUG] Added logging to trace job access problem origin', '15');