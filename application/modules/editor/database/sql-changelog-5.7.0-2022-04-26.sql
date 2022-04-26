
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-04-26', 'TRANSLATE-2949', 'feature', 'Configuration, User Management - Make settings for new users pre-configurable', 'Enable setting default pre-selected source and target languages in instant translate. For more info how this can be configured, please check the config option runtimeOptions.InstantTranslate.user.defaultLanguages in this link
https://confluence.translate5.net/display/TAD/InstantTranslate', '15'),
('2022-04-26', 'TRANSLATE-2869', 'feature', 'Import/Export, Task Management - Export of editing history of a task', 'Provide for PMs the possibility to download the tasks content as spreadsheet containing all segments, with the pre-translated target and the target content after each workflow step.', '15'),
('2022-04-26', 'TRANSLATE-2822', 'feature', 'MatchAnalysis & Pretranslation - Match Analysis on a character basis', 'Match analysis now can be displayed on character or word base.', '15'),
('2022-04-26', 'TRANSLATE-2779', 'feature', 'Auto-QA - QA check for leading/trailing white space in segments', 'Added check for 3 different kinds of leading/trailing whitespaces within a segment', '15'),
('2022-04-26', 'TRANSLATE-2762', 'feature', 'InstantTranslate - Enable tags in InstantTranslate text field', 'Instant Translate now supports using HTML markup in the text to translate. Tag-errors maybe caused by the used services (e.g. DeepL) are automatically repaired when markup is submitted. Please note, that for the time, the typed markup is incomplete or the markup is syntactically incorrect, an error hinting at the invalidity of the markup is shown.', '15'),
('2022-04-26', 'TRANSLATE-2952', 'change', 'Editor general - Automated workflow and user roles video', 'Integrates the automated workflow and user roles in translate5 help page.', '15'),
('2022-04-26', 'TRANSLATE-2902', 'change', 'Configuration, Task Management, TermPortal - Send e-mail to specific PM on creation of project through TermTranslation Workflow', 'Added system config to specify user to be assigned as PM for termtranslation-projects by default, and to send an email notification to that user on termtranslation-project creation', '15'),
('2022-04-26', 'TRANSLATE-2958', 'bugfix', 'TermPortal - TermCollection not updateable after deleting the initial import user', 'If a user was deleted, and this user has imported a TBX, the resulting term collection could no be updated by re-importing a TBX anymore. This is fixed.', '15'),
('2022-04-26', 'TRANSLATE-2955', 'bugfix', 'LanguageResources, OpenTM2 integration - Segment can not be saved if language resource is writable and not available', 'If a language resource is assigned writable to a task and the same language resource is not available, the segment can not be saved.', '15'),
('2022-04-26', 'TRANSLATE-2954', 'bugfix', 'Import/Export - If Import reaches PHP max_file_uploads limit there is no understandable error message', 'If the amount of files reaches the configured max_file_uploads in PHP there is no understandable error message for the user what is the underlying reason why the upload is failing. ', '15'),
('2022-04-26', 'TRANSLATE-2953', 'bugfix', 'Import/Export - Create task without selecting file', 'Fixes a problem where the import wizard form could be submitted without selecting a valid workfile.', '15'),
('2022-04-26', 'TRANSLATE-2951', 'bugfix', 'API, InstantTranslate - Instant-translate filelist does not return the taskId', 'Fixes a problem where the task-id was not returned as parameter in the instant-translate filelist api call.', '15'),
('2022-04-26', 'TRANSLATE-2947', 'bugfix', 'Import/Export - Can not import SDLXLIFF where sdl-def tags are missing', 'For historical reasons sdl-def tags were mandatory in SDLXLIFF trans-units, which is not necessary anymore.', '15'),
('2022-04-26', 'TRANSLATE-2924', 'bugfix', 'InstantTranslate - translate file not usable in InstantTranslate', 'Improved GUI behaviour, file translation is always selectable and shows an Error-message if no translation service is available for the selected languages. Also, when changing languages the mode is not automatically reset to "text translation" anymore', '15'),
('2022-04-26', 'TRANSLATE-2862', 'bugfix', 'InstantTranslate - Issue with the usage of "<" in InstantTranslate', 'BUGFIX InstantTranslate Plugin: Translated text is not terminated anymore after a single "<" in the original text', '15'),
('2022-04-26', 'TRANSLATE-2850', 'bugfix', 'Import/Export - File review.html created in import-zip, even if not necessary', 'reviewHtml.txt will be no longer created when there are no visual-urls defined on import.', '15'),
('2022-04-26', 'TRANSLATE-2843', 'bugfix', 'Import/Export - translate5 requires target language in xliff-file', 'Xml based files where no target language is detected on import(import wizard), will be imported as non-bilingual files.', '15'),
('2022-04-26', 'TRANSLATE-2799', 'bugfix', 'LanguageResources - DeepL API - some languages missing compared to https://www.deepl.com/translator', 'All DeepL resources where the target language is EN or PT, will be changed from EN -> EN-GB and PT to PT-PT. The reason for this is a recent DeepL api change.', '15'),
('2022-04-26', 'TRANSLATE-2534', 'bugfix', 'Editor general - Enable opening multiple tasks in multiple tabs', 'Multiple tasks can now be opened in different browser tabs within the same user session at the same time. This is especially interesting for embedded usage of translate5 where tasks are opened via custom links instead of the translate5 internal task overview.', '15');