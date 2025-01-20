
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
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-01-17', 'TRANSLATE-3952', 'feature', 'Import/Export - Use resname from xliff and if not set segment id from xliff in task as context for t5memory', 'XLF: Use resname from trans-unit attributes as segment descriptor and context for t5memory queries', '15'),
('2025-01-17', 'TRANSLATE-4377', 'change', 'Import/Export - Placeables: Replaced content is not escaped leading to truncated segments', 'FIX: Placeables detection may leads to truncated segments', '15'),
('2025-01-17', 'TRANSLATE-4367', 'change', 'Editor general - Enable the rootcause feedback button for specific users', 'A feedback button (same system as the error logging in the UI) can be enabled for specific users so that they can send detailed informations about problems in the UI even without real error occurrence.', '15'),
('2025-01-17', 'TRANSLATE-4363', 'change', 'Hotfolder Import - Hotfolder: import not working', 'Hotfolder: Fix import', '15'),
('2025-01-17', 'TRANSLATE-4355', 'change', 'Task Management - Tooltips for "batch set properties"', 'Added tooltip and info panel for the batch set properties feature', '15'),
('2025-01-17', 'TRANSLATE-4349', 'change', 't5memory - Add new error code for memory overflow', 'Added new error code that is handled as memory overflow', '15'),
('2025-01-17', 'TRANSLATE-4340', 'change', 'Import/Export, MatchAnalysis & Pretranslation - Left overs of "Define penalties for matches"', 'Improved sublanguages mismatch delection logic and fixed Language Resources tab in Clients overview for ClientPM-only users', '15'),
('2025-01-17', 'TRANSLATE-4328', 'change', 'SpellCheck (LanguageTool integration) - Introduce task level config for setting the LanguageTool config value level', 'Introduce a a way to pass additional configuration parameters to SpellCheck Languagetool and added the rule level parameter.', '15'),
('2025-01-17', 'TRANSLATE-4379', 'bugfix', 'Hotfolder Import, TBX-Import - Allow empty TBX files and fix in ini structure', 'Merge-related configs in instruction.ini files are now also recognized if they\'re not in the root but in the custom section within that ini file. Also the import of TBX files with no terms (for example for automated processes) is now allowed.', '15'),
('2025-01-17', 'TRANSLATE-4356', 'bugfix', 'Editor general - Find next prev segment in workflow calculation was done wrong on server', 'translate5 - 7.15.2: The prev / next segment in workflow calculation was producing wrong results.
translate5 - 7.17.0: Additional UI fixes regarding this problem', '15'),
('2025-01-17', 'TRANSLATE-4298', 'bugfix', 'Auto-QA - AutoQA: Ignoring multiple errors not working', 'FIXED: problem in logic of spreading false positivity flag on similar qualities ', '15'),
('2025-01-17', 'TRANSLATE-4294', 'bugfix', 'Task Management - Fix custom fields handling on metadata export', 'Fixed custom fields handling on metadata export', '15');