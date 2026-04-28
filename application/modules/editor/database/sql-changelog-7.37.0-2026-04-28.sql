
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-04-28', 'TRANSLATE-4440', 'feature', 'Editor general - Simple filters in editor UI', 'Added filters toolbar at the very top of segments grid, toggleable with CTRL+ALT+F keyboard shortcut', '15'),
('2026-04-28', 'TRANSLATE-5383', 'change', 'Okapi integration - Import Across abbreviations or language segmentation rules', 'OKAPI: Add CLI command to import Across Segmentation Settings regarding Abbrevation ("terms") to an SRX as used in translate5', '15'),
('2026-04-28', 'TRANSLATE-5365', 'change', 'SpellCheck (LanguageTool integration), TermTagger integration - No languagetool error inside term mark-up', 'In case spellcheck error is inside the existing terminology entry it is now skipped.', '15'),
('2026-04-28', 'TRANSLATE-3707', 'change', 'usability editor - Enhance handling of spelling errors', 'Added a spellcheck window for working with the spellcheck errors for all segments. Added capability to apply spellcheck replacement for closed segment. ', '15');