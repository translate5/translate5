
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-11-13', 'TRANSLATE-5116', 'bugfix', 'Editor general - Add proper support of the right-to-left languages to the editor', '[🐞 Fix] Added proper support of right-to-left languages', '15'),
('2025-11-13', 'TRANSLATE-5113', 'bugfix', 'MatchAnalysis & Pretranslation, TermPortal - Termcollection pretranslation doesn\'t regard sublanguage', 'Fixed that the sub-language penalty is respected for pure term translations: e.g. for en-gb task the en-us term is used for pretranslation. When there are multiple target terms the best one is used.', '15'),
('2025-11-13', 'TRANSLATE-5112', 'bugfix', 'Installation & Update - Fix logged deprecation messages', 'Several smaller fixes to prevent PHP deprecation messages in the log and be ready for the next PHP version.', '15'),
('2025-11-13', 'TRANSLATE-5109', 'bugfix', 'Editor general - copy-paste in comment not possible with opened segment', 'FIXED: custom logic for pasting from clipboard is now skipped when pasting into comment field', '15'),
('2025-11-13', 'TRANSLATE-5085', 'bugfix', 'Import/Export - Excel ex and re-import does not care about non editable segments', 'Excel re-import is now respecting non editable columns. A new column in excel to show that its not editable is added.', '15'),
('2025-11-13', 'TRANSLATE-5059', 'bugfix', 'Export - Prevent formulas to be used in different excel exports', '7.32.3: Fixed proper rendering of currency columns
7.31.1: Updated used spreadsheet generation libraries, ensured that now malicious formulas could be generated', '15'),
('2025-11-13', 'TRANSLATE-4477', 'bugfix', 'User Management - Emailadresses with the valid TLD marketing can not be created in the UI', 'FIXED: updated email validation to support newer and longer top level domains like .marketing', '15');