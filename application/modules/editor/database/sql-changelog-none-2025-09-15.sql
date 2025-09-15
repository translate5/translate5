
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-09-15', 'TRANSLATE-4210', 'feature', 'AI - Llama3 integration analogous to current GPT integration', ',', '15'),
('2025-09-15', 'TRANSLATE-4209', 'feature', 'InstantTranslate, VisualReview / VisualTranslation - Instant Translate PDF translations', '.', '15'),
('2025-09-15', 'TRANSLATE-4032', 'feature', 'Hotfolder Import - Specification and time estimation for COTI-Level 2 Support for translate5', ',', '15'),
('2025-09-15', 'TRANSLATE-3491', 'feature', 'Task Management - Add column "Price" to project and task grid', ',', '15'),
('2025-09-15', 'TRANSLATE-2866', 'feature', 'InstantTranslate - Add automatic language detection to InstantTranslate', ',', '15'),
('2025-09-15', 'TRANSLATE-2418', 'feature', 'Task Management - Export of all tasks of one project in one zip file', ',', '15'),
('2025-09-15', 'TRANSLATE-4704', 'change', 'InstantTranslate - segmentation rules for InstantTranslate in combination with GroupShare', '-', '15'),
('2025-09-15', 'TRANSLATE-4683', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - Upgrade translate5 to PHP 8.2', 'Updated translate5\'s Dockerfile to PHP 8.2', '15'),
('2025-09-15', 'TRANSLATE-4667', 'change', 'AI - OpenAI Terminology: Add Terminology unwanted in source but wanted in target', ',', '15'),
('2025-09-15', 'TRANSLATE-4581', 'change', 'Task Management - task-specific configs are not always used', '/', '15'),
('2025-09-15', 'TRANSLATE-4373', 'change', 'AI - OpenAI GPT Training Improvements: Improve Model Properties & add Epochs', 'Added \'Number of Epochs\' field above \'Submit training\' button in GPT training window', '15'),
('2025-09-15', 'TRANSLATE-4372', 'change', 'AI - OpenAI GPT Training Improvements: Trainings Window Adjustments', 'Added ablity to add pre-configured prompt(s) to a training', '15'),
('2025-09-15', 'TRANSLATE-4371', 'change', 'AI - OpenAI GPT Training Improvements: Prompt Management', 'Added ability to setup OpenAI GPT Prompts via \'Prompt management\' section in \'Preferences\' tab', '15'),
('2025-09-15', 'TRANSLATE-4370', 'change', 'AI - OpenAI GPT Training Improvements: Data Model', 'added database tables to store predefined prompts data', '15'),
('2025-09-15', 'TRANSLATE-4345', 'change', 'MatchAnalysis & Pretranslation - Match Analysis: Language not found error on analysis entry for de-associated language resource', 'fixed with https://jira.translate5.net/browse/TRANSLATE-4340', '15'),
('2025-09-15', 'TRANSLATE-4165', 'change', 'Editor general - TMMaintenance plugin apache aliases', 'not in changelog', '15'),
('2025-09-15', 'TRANSLATE-4130', 'change', 'Editor general - Front-end Testing Findings', '-', '15'),
('2025-09-15', 'TRANSLATE-4100', 'change', 'LanguageResources - Update OpenAI SDK recurring issue', ' ,', '15'),
('2025-09-15', 'TRANSLATE-4099', 'change', 'LanguageResources - Update DeepL SDK recurring issue', ' ,', '15'),
('2025-09-15', 'TRANSLATE-4040', 'change', 'LanguageResources - Improve system-message for GPT-4o', ',', '15'),
('2025-09-15', 'TRANSLATE-3746', 'change', 'LanguageResources - Check, if MS Azure Cloud OPenAI Service is more stable and EU-based', ',', '15'),
('2025-09-15', 'TRANSLATE-3667', 'change', 'Import/Export, t5memory - opening tags not highlighted inn grey', '-', '15'),
('2025-09-15', 'TRANSLATE-3340', 'change', 'Installation & Update, Translate5 CLI - add optional autodiscovery call to plugin:enable', 'Add an optional parameter to execute service autodiscovery on t5 plugin:enable call.', '15'),
('2025-09-15', 'TRANSLATE-2725', 'change', 'Editor general - Do not delete tags on pressing "del" or "backspace" in certain cases', ',', '15'),
('2025-09-15', 'TRANSLATE-2326', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - Change Worker invocation away from fsockopen for better error handling', 'The current invocation of the workers does not provide the possibility to read out connection warnings, what would be very helpful in debugging.', '15'),
('2025-09-15', 'TRANSLATE-1829', 'change', 'LanguageResources - NEC-TM concordance search should load more search results on scrolling', ',', '15'),
('2025-09-15', 'TRANSLATE-666', 'change', 'Test framework - Check activated plugins in API test', 'Done a long time ago', '15'),
('2025-09-15', 'TRANSLATE-365', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - remove data from session, which is not needed in session', '-', '15'),
('2025-09-15', 'TRANSLATE-4885', 'bugfix', 'Editor general - Cursor at the wrong position when inserting a tag using hotkey', 'Fixed cursor position after inserting a tag with ctrl+, hotkey', '15'),
('2025-09-15', 'TRANSLATE-4739', 'bugfix', 'TrackChanges - Track changes missing when inserting special characters', '-', '15'),
('2025-09-15', 'TRANSLATE-4628', 'bugfix', 'Editor general - FIX Overlapping Terminology and TrackChanges and Spellcheck tags', 'duplicates https://jira.translate5.net/browse/TRANSLATE-3118', '15'),
('2025-09-15', 'TRANSLATE-4573', 'bugfix', 'LanguageResources - Deepl error on different target sublanguages', 'Fixed bug when deepl failed to recognize target language for different sublanguages (en-GB - en-US)', '15'),
('2025-09-15', 'TRANSLATE-4549', 'bugfix', 'Editor general, SpellCheck (LanguageTool integration), TrackChanges - Segment can not be saved due error 400 Bad Request - invalid Markup provided', 'In special circumstances using spellcheck and track changes lead to non savable segments.', '15'),
('2025-09-15', 'TRANSLATE-4530', 'bugfix', 'usability task overview - user job list on mouse-over in task overview', 'Not implemented', '15'),
('2025-09-15', 'TRANSLATE-4528', 'bugfix', 'MatchAnalysis & Pretranslation - Wrong word count if internal fuzzies are NOT used', 'duplicate', '15'),
('2025-09-15', 'TRANSLATE-4485', 'bugfix', 'Export, LanguageResources - Out of memory on resource usage export', ',', '15'),
('2025-09-15', 'TRANSLATE-4450', 'bugfix', 'Editor general - css for tracked changes not applied', 'duplicates https://jira.translate5.net/browse/TRANSLATE-3873', '15'),
('2025-09-15', 'TRANSLATE-4386', 'bugfix', 'TermPortal - TermPortal DE generic always shown in create term dropdown', 'Not reproducible', '15'),
('2025-09-15', 'TRANSLATE-4129', 'bugfix', 'Comments - Quotation marks and apostrophes in comments are escaped', 'No longer repeats', '15'),
('2025-09-15', 'TRANSLATE-4128', 'bugfix', 'Search & Replace (editor) - Replace All Functionality Timeout Results in Partial Processing of Segments', ',', '15'),
('2025-09-15', 'TRANSLATE-4071', 'bugfix', 'Auto-QA - inconsistent tag QA in sdlxliff', 'Behaviour intended: reference field is source for pretranslated segments ', '15'),
('2025-09-15', 'TRANSLATE-4013', 'bugfix', 'Editor general - RootCause: You\'re trying to decode an invalid JSON String', '-', '15'),
('2025-09-15', 'TRANSLATE-3836', 'bugfix', 'MatchAnalysis & Pretranslation - Only one internal fuzzy per analysis', 'was done in other issue', '15'),
('2025-09-15', 'TRANSLATE-3803', 'bugfix', 'TermTagger integration - term with hypens is not recognized', '-', '15');