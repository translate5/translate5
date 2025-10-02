
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-10-02', 'TRANSLATE-4797', 'feature', 'Export - Add batch operation for task export "original format, translated/reviewed"', '7.30.0: Added batch operation for task export "original format, translated/reviewed"', '15'),
('2025-10-02', 'TRANSLATE-4700', 'feature', 'InstantTranslate - InstantTranslate: Add form fields to "Send to human revision" for more info for PM', 'InstantTranslate: When creating a task for human revision, users can set task fields before the task is created', '15'),
('2025-10-02', 'TRANSLATE-5016', 'change', 't5memory - Redo reorganise call', 'T5Memory: replaced reorganize with export/import to avoid potential data losses', '15'),
('2025-10-02', 'TRANSLATE-5014', 'change', 't5memory - Improve commands for working with t5memory', 'Improved t5memory CLI commands to provide more functionality', '15'),
('2025-10-02', 'TRANSLATE-4896', 'change', 'Editor general - Check keyboard shortcuts', 'Made sure all keyboard shortcuts work in new richtext editor, except trackchanges-related ones', '15'),
('2025-10-02', 'TRANSLATE-4877', 'change', 't5memory - Make re-try calls on update of segments in t5memory', 'Updates of segments in t5memory will re-try calls now in case of service down time', '15'),
('2025-10-02', 'TRANSLATE-4876', 'change', 't5memory - Make re-try calls on fuzzy search with t5memory', 'Fuzzy searches with t5memory will re-try calls in case when it is down', '15'),
('2025-10-02', 'TRANSLATE-4821', 'change', 'ConnectWorldserver - Plugin ConnectWorldserver: assign customer to auto-created user', 'Plugin ConnectWorldserver:
assign the customer of the task the auto-created user', '15'),
('2025-10-02', 'TRANSLATE-4771', 'change', 'AI - Use XLIFF as exchange-format for LLM-batches optionally', 'Improvement: Optionally XLIFF can be used as agreed format with OpenAI/LLM to translate batches apart from JSON to improve the reliability of batches', '15'),
('2025-10-02', 'TRANSLATE-3760', 'change', 'InstantTranslate - InstantTranslate: End user switch to select, which MT language resource should be used to translate a file', 'InstantTranslate: New button to select which resource to use when file pre-translation is used', '15'),
('2025-10-02', 'TRANSLATE-5024', 'bugfix', 'InstantTranslate - & escaped in InstantTranslate text field DeepL translations', 'InstantTranslate: ampersand chars are not escaped anymore for single-segment translations', '15'),
('2025-10-02', 'TRANSLATE-5003', 'bugfix', 'TM Maintenance - TM maintenance: in DE UI the delete all button stays inactive', 'TM maintenance: Fixed "Delete all" button being inactive in DE UI', '15'),
('2025-10-02', 'TRANSLATE-5000', 'bugfix', 'Editor general - TrackChanges Tags: The new Editor may creates nested track-changes tags', 'New editor: fixed possible creation of nested TrackChanges-tags (inserts in deletions) that may create false tag-errors in the AutoQA and problems with task-export', '15'),
('2025-10-02', 'TRANSLATE-4967', 'bugfix', 'TrackChanges - Track changes removed incorrectly on segment save', 'Fixed track changes removal logic', '15');