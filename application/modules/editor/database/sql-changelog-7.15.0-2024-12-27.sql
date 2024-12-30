
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-12-27', 'TRANSLATE-4312', 'feature', 'InstantTranslate - Introduce model type setting for DeepL API calls', 'Introduce model_type setting for DeepL API calls', '15'),
('2024-12-27', 'TRANSLATE-4273', 'feature', 'InstantTranslate - New API endpoint for synchronous file translation', 'Added new endpoint for synchronous file translation:
editor/instanttranslateapi/filepretranslatenow', '15'),
('2024-12-27', 'TRANSLATE-4221', 'feature', 'Import/Export - Save analysis from xliff', '- Added analysis generation out of segment matchrate from the imported segments (if enabled by Task-import specific "readImportAnalysis" config and if matchrates are detected on import)
- Added new client specific config: hideWordCount column', '15'),
('2024-12-27', 'TRANSLATE-3198', 'feature', 'LanguageResources, Task Management - Define a penalty for matches', 'Added ability to define customer- and task-specific penalties for matchrates per languareresources, to be applied in general and for sublanguages mismatches', '15'),
('2024-12-27', 'TRANSLATE-2971', 'feature', 'Task Management, Workflows - Set job deadlines for multiple projects or tasks at the same time', 'Added ability to set job deadlines for multiple projects or tasks at the same time', '15'),
('2024-12-27', 'TRANSLATE-4329', 'change', 't5memory - Create a command for creating language resource duplicates', 'Added a new command for creating Language resource duplicates ', '15'),
('2024-12-27', 'TRANSLATE-4326', 'change', 'VisualReview / VisualTranslation - "Go-To page" functionality for visual pages', 'Enhancement Visual: Improve functionality of pager for multipage-reviews, which is now a dropdown with select-by-typeing', '15'),
('2024-12-27', 'TRANSLATE-4317', 'change', 'InstantTranslate - SysAdmins see now InstantTranslate tasks', 'SysAdmins see now InstantTranslate tasks in project and task list for support reasons.', '15'),
('2024-12-27', 'TRANSLATE-4307', 'change', 'Configuration - Make index column editable in newly added "mapping values" rows in config', 'Added ability to define index(key/name) values in "Edit mapping values" modal popup windows', '15'),
('2024-12-27', 'TRANSLATE-4306', 'change', 'LanguageResources - Allow option strip framing tags for zipped files', 'The "Strip framing tags" option is now allowed for .zip files', '15'),
('2024-12-27', 'TRANSLATE-4287', 'change', 'Workflows - Replace whitespace and brackets in export backup filename', 'When creating backup zips of translate5 task whites space square and normal brackets are now also replaced in the filename to a hyphen character. Multiple hyphens are condensed to a single hyphen.', '15'),
('2024-12-27', 'TRANSLATE-4281', 'change', 'MatchAnalysis & Pretranslation - Pricing presets: REST API and UI fix', 'Adjustments for API usage.', '15'),
('2024-12-27', 'TRANSLATE-4277', 'change', 'Import/Export - SFTP TermImport: dirs should be kept despite files moved between them', 'All dirs on remote sftp directory can now be permanent, so only their contents are moved between those dirs', '15'),
('2024-12-27', 'TRANSLATE-4276', 'change', 't5memory - Add a command for exporting memories', 'Added CLI command for exporting memories from t5memory', '15'),
('2024-12-27', 'TRANSLATE-4275', 'change', 'Configuration - Clickable URLs in System config', 'URLs in descriptions of System config were made clickable', '15'),
('2024-12-27', 'TRANSLATE-4274', 'change', 'InstantTranslate - InstantTranslate: speedup by resources list browser-side caching', 'request for available resources list is not anymore done each time source text changed', '15'),
('2024-12-27', 'TRANSLATE-4269', 'change', 't5memory - Add retry if creating empty memory request failed', 'Fixed error when t5memory failed to create an empty memory', '15'),
('2024-12-27', 'TRANSLATE-4267', 'change', 'Content Protection - Fix cache in Content Protection', 'Change cache for Content protection rule fetching', '15'),
('2024-12-27', 'TRANSLATE-4262', 'change', 'Workflows - Workflow clone: allow hyphens in the technical name of the target workflow', 'Fixed error when hyphens ("-") were part of the technical name of the cloned workflow', '15'),
('2024-12-27', 'TRANSLATE-4244', 'change', 'LanguageResources - Fix reimport segments modal width', 'UI: Increased reimport segments modal window width', '15'),
('2024-12-27', 'TRANSLATE-4133', 'change', 'Hotfolder Import - Hotfolder: Notification email for failed task import', 'Send e-mail to PM if folder can not be processed', '15'),
('2024-12-27', 'TRANSLATE-4106', 'change', 'VisualReview / VisualTranslation - Further harmonize sequences of list-elements (lists / list-tables)', 'IMPROVEMENT Visual: Make sequences of list-elements having the same max. text-width inherited from the longest item', '15'),
('2024-12-27', 'TRANSLATE-4104', 'change', 'VisualReview / VisualTranslation - Reflow: DEV-App Enhancements', 'IMPROVEMENT Visual: Improve development-app for the Reflow', '15'),
('2024-12-27', 'TRANSLATE-4102', 'change', 'VisualReview / VisualTranslation - Improve detection of justified Text coming from MS Office', 'IMPROVEMENT Visual: Justified text (e.g. coming from MS Office formats) often was not properly detected', '15'),
('2024-12-27', 'TRANSLATE-4078', 'change', 'Import/Export, usability task overview - Show error and warning icon in new column in tasks and projects', 'New log info column is added which lists the number of the important task logs for projects and tasks.', '15'),
('2024-12-27', 'TRANSLATE-4344', 'bugfix', 'MatchAnalysis & Pretranslation - Penalties: match-analysis error', 'Fix error when running match analyis.', '15'),
('2024-12-27', 'TRANSLATE-4342', 'bugfix', 'Editor general - Segments are saved to TM with trackchanges', 'Fixed bug when segments were saved to TM with trackchanges during reimport', '15'),
('2024-12-27', 'TRANSLATE-4334', 'bugfix', 'Import/Export - Import SDLXLIFF x-html c-type tags', 'SDLXLIFF: Add support for g tags with ctype "x-html-%"', '15'),
('2024-12-27', 'TRANSLATE-4333', 'bugfix', 'Content Protection - Content protection produces corrupt TMX', 'Content protection: Fix full segment protection', '15'),
('2024-12-27', 'TRANSLATE-4332', 'bugfix', 't5memory - t5memory: memory split works wrong', 't5memory: Fix memory split', '15'),
('2024-12-27', 'TRANSLATE-4331', 'bugfix', 'Editor general - Wrong workflow rendered in taskGrid', 'Fix workflow column value rendered wrong.', '15'),
('2024-12-27', 'TRANSLATE-4327', 'bugfix', 'usability task overview - Remove special workflow step filter values', 'Removed filtering options "No workflow", "PM check" and "Workflow finished" from the "Workflow step" drop-down in "Advanced Filters" window', '15'),
('2024-12-27', 'TRANSLATE-4322', 'bugfix', 'MatchAnalysis & Pretranslation - When comparing full match timestamps exclude fuzzy TM matches', 'In some special cases internal fuzzy TM entries are considered as best match and therefore the segment is not pre-translated even if there is a 100% match in the normal TMs.', '15'),
('2024-12-27', 'TRANSLATE-4319', 'bugfix', 'Auto-QA - AutoQA "Not-edited Fuzzy Match" does not work as it should', 'FIXED: gaps in logic for assign/remove of \'Unedited fuzzy match\'-quality', '15'),
('2024-12-27', 'TRANSLATE-4318', 'bugfix', 'GroupShare integration - GroupShare: error on empty search text in segmented search in instant-translate', 'Fix for an error where if segmented search in instant translate contains empty text in between.', '15'),
('2024-12-27', 'TRANSLATE-4316', 'bugfix', 'Editor general, TrackChanges - MQM tag can not be deleted', 'Fixed trackchanges behavior for MQM tags', '15'),
('2024-12-27', 'TRANSLATE-4315', 'bugfix', 'VisualReview / VisualTranslation - Task Config not respected in Visual', 'In the Visual the Task specific Configuration values may have not been respected during import', '15'),
('2024-12-27', 'TRANSLATE-4314', 'bugfix', 'LanguageResources - DeepL glossaries not in sync', 'DeepL: Add logging for glossary related manipulations', '15'),
('2024-12-27', 'TRANSLATE-4310', 'bugfix', 'LanguageResources - Handle HTML entities on language resources result processing', 'New option to encode html entities for resources which are using the html tag handler.', '15'),
('2024-12-27', 'TRANSLATE-4304', 'bugfix', 'Editor general - Remove task deadline date as job autoclose condition', 'Task deadline date is removed as mandatory condition when collecting jobs to auto-close.', '15'),
('2024-12-27', 'TRANSLATE-4303', 'bugfix', 'Hotfolder Import - across hotfolder should handle all visual file formats', 'Hotfolder: Import all visual files and not only pdf', '15'),
('2024-12-27', 'TRANSLATE-4284', 'bugfix', 'LanguageResources, t5memory - task not saved to TM after finishing workflow due to locked task state', 'Task into TM reimport mechanism is changed so now it works in two steps: take a snapshot of a task and actual reimport', '15'),
('2024-12-27', 'TRANSLATE-4282', 'bugfix', 'LanguageResources - Sort roles in term export to fix failing tests', 'Sort role attribute in tbx export is needed for test cases.', '15'),
('2024-12-27', 'TRANSLATE-4279', 'bugfix', 'Editor general - UI error: Cannot read properties of undefined (reading \'getSelection\')', 'Fix for UI error when leaving the task with save segment in process.', '15'),
('2024-12-27', 'TRANSLATE-4272', 'bugfix', 'VisualReview / VisualTranslation - Add User-Agent to Visual URL-checks & WGET downloads', 'FIX: Add common user-agent to CURL & WGET when checking/downloading webpages', '15'),
('2024-12-27', 'TRANSLATE-4271', 'bugfix', 'InstantTranslate - InstantTranslate doing nothing when t5memory URL points to non responding server', 'When t5memory server config points to a non reachable URL, InstantTranslate is not working properly even if there is no TM used.', '15'),
('2024-12-27', 'TRANSLATE-4265', 'bugfix', 'Task Management - Task properties check-box "edit unchanged 100 % TM matches" faulty', 'TermCollection pre-translations can not be re-protected again after enabling edit100%matches', '15'),
('2024-12-27', 'TRANSLATE-4264', 'bugfix', 'Editor general - UI error: go to task action produces error when state filter is active', 'Fix for UI error produced by "go to task" action. ', '15'),
('2024-12-27', 'TRANSLATE-4263', 'bugfix', 'Import/Export - Self closing target in alt-trans matchrate crashes the import', 'Fix for crashing import when importing match-rate from alt-trans element.', '15'),
('2024-12-27', 'TRANSLATE-4261', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - sleep looping workers instead delay for smaller tasks', 'IMPROVEMENT: instead of being delayed blocked segment-loopers will be paused for smaller tasks', '15'),
('2024-12-27', 'TRANSLATE-4259', 'bugfix', 'Import/Export - List only errors in Info error summary email', 'Error summary email on import will only send errors with log level fatal and error.', '15'),
('2024-12-27', 'TRANSLATE-4258', 'bugfix', 'LanguageResources - Terminology Integration into OpenAI is incomplete', 'FIX: Terminology for OpenAI worked only for Batches, not for single segments', '15'),
('2024-12-27', 'TRANSLATE-4253', 'bugfix', 'VisualReview / VisualTranslation - VTT import does not work via GUI', 'FIX: *vtt files could not be used via the GUI to create visual video-translations', '15'),
('2024-12-27', 'TRANSLATE-4249', 'bugfix', 'TM Maintenance - TMMaintenance detects changes for segments with special characters', 'FIXED: special characters encoding by itself is not treated as segment change anymore leading to unwated segment save-request', '15'),
('2024-12-27', 'TRANSLATE-4248', 'bugfix', 'TM Maintenance - TMMaintenance showing nothin when no results found for filter', 'FIXED: results grid header misleading behaviour when zero results found', '15'),
('2024-12-27', 'TRANSLATE-4245', 'bugfix', 'Workflows - Email notifications on auto-close', 'When a job is auto-closed the associated users and the PM get now an e-mail notification about the auto close.', '15'),
('2024-12-27', 'TRANSLATE-4224', 'bugfix', 'Import/Export - Usage of "target at import time" as source for tags: wrong evaluation of pretrans flag', 'Evaluate match-rate type for the column used for tag check compare.', '15'),
('2024-12-27', 'TRANSLATE-4144', 'bugfix', 'Import/Export - SDLXLIFF tags imported as InternalReference tags without unique ID', 'SDLXLIFF: Handle multi mid tags with uuid', '15');