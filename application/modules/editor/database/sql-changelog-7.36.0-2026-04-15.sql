
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-04-15', 'TRANSLATE-5260', 'feature', 'translate5 AI - Add TQE as filter option in task grid and for KPI calculation', 'New advance filter TQE: translation quality estimate score is available.', '15'),
('2026-04-15', 'TRANSLATE-4289', 'feature', 'VisualReview / VisualTranslation - Optionally hide the visual in editor', 'Visual review now have a stateful show/hide functionality: toggle button for simple segments grid, and a menu item for settings dropdown in normal/detailed segments grid.', '15'),
('2026-04-15', 'TRANSLATE-3536', 'feature', 'Editor general, usability editor - Make optionally space visible (like in MS Word)', 'In the target text editor spaces visibility can now be toggled on/of', '15'),
('2026-04-15', 'TRANSLATE-5384', 'change', 't5memory - Introduce TMX processor to delete prop tags from seg tags', 'Introduce TMX processor to delete prop tags from seg tags', '15'),
('2026-04-15', 'TRANSLATE-5381', 'change', 'Editor general, Security Related - Add headers suggested by HTTP Observatory', 'Some new headers were added for security reasons.
Content-Security-Policy and X-Frame-Options may influence how the application works if you have custom scripts or styles loaded from different source or you load translate5 in <iframe> or <embed>
To make headers be configurable we added the following config values, which can be added to installation.ini: 
runtimeOptions.headers.enableXFrameHeader
runtimeOptions.headers.defaultSrcUrls
runtimeOptions.headers.scriptSrcUrls
runtimeOptions.headers.connectSrcUrls
runtimeOptions.headers.styleSrcUrls
runtimeOptions.headers.imgSrcUrls
runtimeOptions.headers.fontSrcUrls

Please check the reference in the application.ini file', '15'),
('2026-04-15', 'TRANSLATE-5370', 'change', 't5memory - Process author, document and creation time from TUV', 'Process author, document and creation time from TUV', '15'),
('2026-04-15', 'TRANSLATE-5343', 'change', 'Task Management - Group advanced filters in field sets', 'Rearranged filters in Task grid\'s advanced filters window', '15'),
('2026-04-15', 'TRANSLATE-5325', 'change', 'Auto-QA - Reimport of Translator Package: Add AutoQA after reimport, Fix diffing strategy / target at import time', 'Enhancement: Add AutoQA after translator-package reimport to show accurate QA and show & fix potential tag-errors', '15'),
('2026-04-15', 'TRANSLATE-5306', 'change', 'Import/Export, LanguageResources - TMX-Import: further segmentation may lead to empty entries in TM', 'Fix re-segmentation of TMX segments', '15'),
('2026-04-15', 'TRANSLATE-5250', 'change', 'LanguageResources - Adding new languages (bo, za, ug)', 'add new language \'Zhuang\', existing languages \'Tibetisch\' and \'Uigurisch\'', '15'),
('2026-04-15', 'TRANSLATE-5230', 'change', 't5memory - Log non-tmx files in zip import', 'Log non-tmx files in zip import', '15'),
('2026-04-15', 'TRANSLATE-5217', 'change', 'Workflows - improve message to user when assigning same step to same user twice', 'Improve message to user when assigning same step to same user twice', '15'),
('2026-04-15', 'TRANSLATE-5196', 'change', 'localization - Localization Workflow tools', '7.36.0 Finalize refactoring of Localization:
* replace most german localizations with the english targets to clean up the source code
* Add several tools to enable a localzation workflow independant from developers (CLI tools to extract, update and create tasks for the localization of t5)
* JSON based localizations now also will be exported/imported as XLIFF in the localization tasks to enable the proper use of TMs
7.34.0: First set of internal CLI tools for internal application translation management released', '15'),
('2026-04-15', 'TRANSLATE-5195', 'change', 'file format settings - Improve File Format Segmentation Rules after colons, fix OKAPI quirk', 'File Format Settings: Improve Segmentation after Colons: Take quotes into account', '15'),
('2026-04-15', 'TRANSLATE-5189', 'change', 'Okapi integration - Make OKAPI-1.48-snapshot-6 the new default in translate5', 'new default Okapi version Okapi-1.48-snapshot-6', '15'),
('2026-04-15', 'TRANSLATE-5076', 'change', 'LanguageResources, Task Management - Change language filters in project, task and language resource overviews for better usability', 'Added a new filter type langtagfield and used it for language filtering in the projects/tasks/language resources tabs', '15'),
('2026-04-15', 'TRANSLATE-5052', 'change', 'TM Maintenance - Search for entries with quotes in TM-Maintenance', 'Search for entries with quotes in TM-Maintenance', '15'),
('2026-04-15', 'TRANSLATE-4987', 'change', 'Editor general - Allow to save incorrect tags structure if it is incorrect in source', 'Now if reference field contains incorrect tags structure editor will allow to save target with incorrect tags structure too', '15'),
('2026-04-15', 'TRANSLATE-4720', 'change', 't5memory - Escape UTF characters that are not allowed by XML', 'Added escaping UTF characters that are not allowed in XML 1.0 or 1.1 in comunication with t5memory', '15'),
('2026-04-15', 'TRANSLATE-4233', 'change', 'Content Protection, LanguageResources - Exclude task TMs from Content Protection\'s "Convert all" TM conversion', 'Exclude task TMs from Content Protection\'s "Convert all" TM conversion', '15'),
('2026-04-15', 'TRANSLATE-5399', 'bugfix', 'translate5 AI - Rag promt: UI error on loading multiple prompts', 'Fix for a problem where UI error was triggered if the user loads a lot of prompts for RAG.', '15'),
('2026-04-15', 'TRANSLATE-5389', 'bugfix', 'LanguageResources - remove git commit from tmx header', 'On TMX export remove git commit from tmx header', '15'),
('2026-04-15', 'TRANSLATE-5366', 'bugfix', 'file format settings - File Format Settings: Save Export-Bconf at the time the task is imported in the okapi-data dir and use it from there', 'File Format Settings: Save BCONF to be used on export in the task\'s okapi-data dir to be able to delete existing BCONF\'s at any time and be immune against quirks when bconfs are updated', '15'),
('2026-04-15', 'TRANSLATE-5355', 'bugfix', 'kpi - KPI (Levenshtein and Post-editinig time): Unchanged segments are not taken into account in current active workflow step', 'Completly reworked the levenshtein and postediting time statistic feature due several bugs and changes in conception', '15'),
('2026-04-15', 'TRANSLATE-5354', 'bugfix', 'kpi, Repetition editor - Repetitions are not correctly handled with task statistics feature', 'For repetitions the postediting time and levenshtein distances statistical data was not recorded.', '15'),
('2026-04-15', 'TRANSLATE-5353', 'bugfix', 'Workflows - Workflow jumps on finished, when last step is finished, but other one not', 'Fix workflow behaviour.', '15'),
('2026-04-15', 'TRANSLATE-5338', 'bugfix', 'Content Protection, Editor general - adding a protected number in source editing will not save it', 'Fix source editing', '15'),
('2026-04-15', 'TRANSLATE-5308', 'bugfix', 'LanguageResources - TMX import sometimes does not delete temporary files', 'Implemented automatic deletion of temporary files created on TMX import', '15'),
('2026-04-15', 'TRANSLATE-5294', 'bugfix', 'Okapi integration - IDML default settings: Field "Protect listed whitespace characters" should be empty for translate5', 'If this field is not empty, all special whitespacse characters from Indesign are normal tags in translate5, because Okapi then protects them as such. To enable translate5 to treat them as special whitespace, this setting must be empty.', '15'),
('2026-04-15', 'TRANSLATE-5248', 'bugfix', 'Export - Check user permissions in task export action', 'Use permission checks in task export action', '15'),
('2026-04-15', 'TRANSLATE-5245', 'bugfix', 'VisualReview / VisualTranslation - Visual: Annotation in non-document encodings are scrambled', 'FIX: The visual export may rendered annotations in non-document charsets with broken characters', '15'),
('2026-04-15', 'TRANSLATE-5198', 'bugfix', 'LanguageResources - add warning on tmx import when languages not matching TM', 'Add to log on tmx import when translation unit languages not matching TM languages', '15'),
('2026-04-15', 'TRANSLATE-5179', 'bugfix', 'Editor general - Multiple strange behaviors of segment with tags in new editor when editing the segment', '[🐞 Fix] Improved experience in the Track Changes plugin so now it handles changes more correct', '15'),
('2026-04-15', 'TRANSLATE-5156', 'bugfix', 'LanguageResources - Language Resource glossary synchronisation window improvements', 'Make glossary synchronisation grid scrollable', '15'),
('2026-04-15', 'TRANSLATE-5137', 'bugfix', 'VisualReview / VisualTranslation - Visual segmentation does not take protected content into account', 'Visual: Protected Content was not respected correctly in the segmentation or WYSIWYG leading to e.g. doubling of numbers in the visual export', '15'),
('2026-04-15', 'TRANSLATE-5096', 'bugfix', 'Auto-QA - run AutoQA tag check for all InstantTranslate tasks', 'InstantTranslate: For all InstantTranslate File translation tasks, the tag-check of the AutoQA is active so tag-errors are repaired automatically. When sending such tasks to Humanevision, a full AutoQA will be added.', '15'),
('2026-04-15', 'TRANSLATE-5093', 'bugfix', 'VisualReview / VisualTranslation - Office conversion with more than 20 files fails', 'FIX: Visual office conversion or PDF import with more than 20 files fails', '15'),
('2026-04-15', 'TRANSLATE-4868', 'bugfix', 'VisualReview / VisualTranslation - Remove "blue overflow background" when printing the visual as PDF', 'Improvement: Remove "blue background" hinting at longer pages when printing the Visual to PDF', '15'),
('2026-04-15', 'TRANSLATE-4865', 'bugfix', 'Configuration - reset customer-specific config is not possible', 'Allow to reset customer-specific config', '15');