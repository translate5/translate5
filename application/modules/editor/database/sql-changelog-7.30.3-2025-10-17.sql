
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-10-17', 'TRANSLATE-5057', 'change', 'Configuration - Increase deadlock retries', 'Increase retries on DB queries after a deadlock occurred.', '15'),
('2025-10-17', 'TRANSLATE-5056', 'change', 'MatchAnalysis & Pretranslation - Refactor t5memory communication in pre-translation scope', 'Improve speed of pre-translation by t5emory', '15'),
('2025-10-17', 'TRANSLATE-5047', 'change', 't5memory - Error 500 on saving a segment with empty target (into T5Memory)', 'Don\'t send segments with empty targets to t5memory on update in editor', '15'),
('2025-10-17', 'TRANSLATE-5045', 'change', 't5memory - Retry t5memory API calls on not acquired lock error', 'Retry t5memory API calls on not acquired lock error', '15'),
('2025-10-17', 'TRANSLATE-5043', 'change', 't5memory - Look only for best match in t5memory fuzzy query in pre-translation', 'Look only for best match in t5memory fuzzy query in pre-translation', '15'),
('2025-10-17', 'TRANSLATE-5027', 'change', 'TM Maintenance - Improve batch delete in TM Maintenance', 'Improve batch delete in TM Maintenance', '15'),
('2025-10-17', 'TRANSLATE-5049', 'bugfix', 'Content Protection - Name of rule put into tag incorrectly', 'Fix rule name escaping in tag compose process', '15'),
('2025-10-17', 'TRANSLATE-5044', 'bugfix', 't5memory - WipeService generates next memory name incorrectly', 'Fix how WipeService generates next memory name', '15'),
('2025-10-17', 'TRANSLATE-5041', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Text selection not working in system log', 'FIXED: text selection is now working in row expander in system log', '15'),
('2025-10-17', 'TRANSLATE-5031', 'bugfix', 'Editor general - Number protection tags are not selectable in source text', 'FIXED: number-protection tags are now also selectable', '15'),
('2025-10-17', 'TRANSLATE-4981', 'bugfix', 'Editor general - Error when trying to replace a text with search/replace', '[🐞 Fix] Fixed Search/Replace error when replacing a word at the end of the segment to a shorter word, fixed error when replacing a word inside the trackchanges', '15'),
('2025-10-17', 'TRANSLATE-4673', 'bugfix', 'TermPortal - No DB index used when searching for plain terms', 'No proper DB index is used when using terminology for pre translations leading to slow performance there.', '15');