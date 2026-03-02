
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-03-02', 'TRANSLATE-5326', 'change', 'Editor general - Segment markup: Allow whitespace-tags in terminology-tags', 'FIX: allow whitespace-tags in terminology tags as valid segment markup', '15'),
('2026-03-02', 'TRANSLATE-5313', 'change', 't5memory - T5Memory &nbsp; crashes import', 'Decode html entities on TMX import to prevent import errors', '15'),
('2026-03-02', 'TRANSLATE-5330', 'bugfix', 't5memory - Concordance search fails in certain cases', '[🐞 Fix] Fix concordance search error which rarely might have occurred in certain cases', '15'),
('2026-03-02', 'TRANSLATE-5329', 'bugfix', 'translate5 AI - Do not relay on no alikes on TQE segment check', 'Fix for a problem where noAllikes flag was evaluated on single segment TQE check.', '15'),
('2026-03-02', 'TRANSLATE-5327', 'bugfix', 'Editor general - Cannot read properties of undefined (reading \'replace\') error', '[🐞 Fix] Fixed warning in console when saving segment', '15'),
('2026-03-02', 'TRANSLATE-5321', 'bugfix', 'Workflows - Adjust Email Headers for certain Outlook Versions having Problems with encoded headers', 'ENHANCEMENT: Avoid typographical quotes in mail-subjects to avoid encoding the headers which lead to problems in some outlook versions', '15'),
('2026-03-02', 'TRANSLATE-5316', 'bugfix', 'Auto-QA - RootCause: can\'t access property "set", rec is undefined', 'FIXED: improved segment grid record detection (to apply false-positivity styling for when needed)', '15'),
('2026-03-02', 'TRANSLATE-5315', 'bugfix', 'Editor general - Multiple issues with copy/paste content with tags', '[🐞 Fix] Fixed issues with copy/paste with tags ', '15'),
('2026-03-02', 'TRANSLATE-5314', 'bugfix', 'Authentication - Session overflow on heavy API access', 'On installations with API usage (for example with t5connect) to much sessions are produced with to long lifetime leading to problems with the session table.', '15'),
('2026-03-02', 'TRANSLATE-5309', 'bugfix', 'TermTagger integration - BUG in Terminology Provider for OpenAI and InstantTranslate', 'FIX: Terminology in certain cases was not evalueted completely for a segment in OpenAI & InstantTranslate requests', '15'),
('2026-03-02', 'TRANSLATE-5286', 'bugfix', 'Task Management - live updateing of levenshtein and post-editing time statistics does not seem to work', 'Fix the calculation of the levenshtein distance of segments with internal tags.', '15'),
('2026-03-02', 'TRANSLATE-5277', 'bugfix', 'Task Management - improve info message about deleted task', 'Made error message when task is not found more user-friendly', '15'),
('2026-03-02', 'TRANSLATE-5206', 'bugfix', 'LanguageResources, translate5 AI - Adding examples to a prompt faulty', 'FIXED: problem with discarding changes, validating examplesets and made blue Save-button to save examplesets-changes (if any) as well', '15'),
('2026-03-02', 'TRANSLATE-5155', 'bugfix', 'translate5 AI - angle brackets in prompts not shown in UI and break other prompts', 'Encode html entities in prompt messages.', '15');