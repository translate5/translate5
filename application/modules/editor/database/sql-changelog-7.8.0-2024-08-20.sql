
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-08-20', 'TRANSLATE-4132', 'feature', 'Main back-end mechanisms (Worker, Logging, etc.) - Auto-close jobs by task deadline', 'New date field project deadline date available for task.', '15'),
('2024-08-20', 'TRANSLATE-3898', 'feature', 'LanguageResources - Change tmx import to be able to use html multipart fileupload', 'Change TMX import to be able to use multipart file-upload', '15'),
('2024-08-20', 'TRANSLATE-2270', 'feature', 'LanguageResources - Translation Memory Maintenance', 'translate5 - 7.7.0 : New plugin TMMaintenance for managing segments in t5memory
translate5 - 7.8.0 : Improved UI error handling and display', '15'),
('2024-08-20', 'TRANSLATE-4137', 'change', 't5memory, Translate5 CLI - Improve t5memory reorganize command', 'Added capability to process language resources in batches to t5memory:reorganize command', '15'),
('2024-08-20', 'TRANSLATE-4135', 'change', 'TM Maintenance - TMMaintenance: segments loading usability', 'Added \'Loading...\'-row to the bottom of the results grid and amended grid title so loading progress is shown', '15'),
('2024-08-20', 'TRANSLATE-4123', 'change', 'ConnectWorldserver - Plugin ConnectWorldserver: add reviewer to entry in task-history', 'Sample:
assigned person(s):
- User1 MittagQI [User1@translate5.net] (reviewing: finished)
- User2 MittagQI [User2@translate5.net] (reviewing: finished)', '15'),
('2024-08-20', 'TRANSLATE-4094', 'change', 'VisualReview / VisualTranslation - Use VTT files for Video Imports', 'Visual: Enable the import of .vtt-files as workfile for a video-based visual', '15'),
('2024-08-20', 'TRANSLATE-4062', 'change', 'Workflows - Add archive config to use import date instead task modified date', 'translate5 - 7.7.0: Extend task archiving functionality to filter for created timestamp also, instead only modified timestamp. Configurable in Workflow configuration.
translate5 - 7.8.0: add ftps support', '15'),
('2024-08-20', 'TRANSLATE-4057', 'change', 'Auto-QA - Wrong error count in autoQA after collapsing and re-expanding autoQA panel', 'Qualities filter type is now preserved on collapse/expand of filter panel', '15'),
('2024-08-20', 'TRANSLATE-4022', 'change', 'Auto-QA, SNC - SNC: add new error previously unknown to translate5', '"(Possibly erroneous) separator from SRC found unchanged in TRG" reported by SNC-lib is now added to the list of known by Translate5 and is now counted as AutoQA-quality under Numbers category group', '15'),
('2024-08-20', 'TRANSLATE-3936', 'change', 'Editor general - Ensure that default plug-ins without config produce no errors', 'Ensure that plug-ins enabled by default are not producing errors when no configuration is given', '15'),
('2024-08-20', 'TRANSLATE-3883', 'change', 'Import/Export - Make TMX export run as stream', 'Fix issues with export of large TMs', '15'),
('2024-08-20', 'TRANSLATE-4150', 'bugfix', 'Installation & Update - General error: 1270 Illegal mix of collations', 'Fixing an older DB change file not compatible to latest DB system', '15'),
('2024-08-20', 'TRANSLATE-4147', 'bugfix', 'Content Protection - Content protection: Tag alike render, meta-info in tag', 'Fix render of tag like protected entities
Store meta info into tag itself for state independency.', '15'),
('2024-08-20', 'TRANSLATE-4146', 'bugfix', 't5memory, TM Maintenance - Special characters are not treated properly in TM Maintenance', 'Fixed special characters processing in search fields and in editor', '15'),
('2024-08-20', 'TRANSLATE-4145', 'bugfix', 'InstantTranslate - Enable aborting InstantTranslate Requests independently from maxRequestDuration', 'Min time a ranslation-request can be aboted to trigger the next one is now independant from maxRequestDuration', '15'),
('2024-08-20', 'TRANSLATE-4143', 'bugfix', 'Auto-QA - AutoQA Filter is not correctly updated when segment-interdependent qualities change', 'Fix AutoQA filter-panel is not updated when certain segment qualities change', '15'),
('2024-08-20', 'TRANSLATE-4140', 'bugfix', 't5memory, TM Maintenance - Reorganize is triggered when memory not loaded to RAM', 'Fixed triggering reorganization on the memory which is not loaded into RAM yet', '15'),
('2024-08-20', 'TRANSLATE-4139', 'bugfix', 't5memory - wrong timestamp in matches saved with option "time of segment saving"', 'Fix segment timestamp when reimporting task to TM and "Time of segment saving" is chosen as an option', '15'),
('2024-08-20', 'TRANSLATE-4131', 'bugfix', 'LanguageResources - Language resource data is not updated when edit form is opened', 'Fix bug when language resource becomes not editable', '15'),
('2024-08-20', 'TRANSLATE-4127', 'bugfix', 'Auto-QA - RootCause: this.getView() is null', 'translate - 7.8.0 : added logging for further investigation of a problem with AutoQA filters', '15'),
('2024-08-20', 'TRANSLATE-4122', 'bugfix', 'Import/Export - Import wizard default assignment: source language not selectable', 'Fix for a problem where target language is not selectable in the user assignment panel in the import wizard.', '15'),
('2024-08-20', 'TRANSLATE-4120', 'bugfix', 'Content Protection - improve content protection rules float generic comma and float generic dot', 'Content protection for floats and integers will try to protect + and - sign before number as default behaviour.', '15'),
('2024-08-20', 'TRANSLATE-4118', 'bugfix', 'TermPortal - Search results scrolling problem in Firefox', 'FIXED: scrollbar not available for search results grid in Firefox', '15'),
('2024-08-20', 'TRANSLATE-4117', 'bugfix', 'MatchAnalysis & Pretranslation - Race condition in Segment Processing functionality', 'Fix race condition in segment processing', '15'),
('2024-08-20', 'TRANSLATE-4083', 'bugfix', 'Content Protection - Content Protection: Rule not working', 'Fixed tag attribute parsing in XmlParser.', '15'),
('2024-08-20', 'TRANSLATE-4082', 'bugfix', 'Content Protection - Content Protection: duplicate key error and priority value not taken over', 'Fix issue with Content protection rule creation.
Change the way validation error delivered to end user.', '15'),
('2024-08-20', 'TRANSLATE-4063', 'bugfix', 'Editor general - UI error in pricing pre-set grid', 'FIXED: error popping due to incorrect tooltip render for checkbox-column in pricing preset grid', '15'),
('2024-08-20', 'TRANSLATE-4051', 'bugfix', 'InstantTranslate - Instant translate help window does not remember close state', 'Fix for a problem where the visibility state of help button in Instant-Translate  is not remembered.', '15'),
('2024-08-20', 'TRANSLATE-4050', 'bugfix', 'TermTagger integration - correct target term not recognized', 'FIXED: several problems and logic gaps with terminology recognition', '15'),
('2024-08-20', 'TRANSLATE-4019', 'bugfix', 'Editor general - Error in UI filters', 'Fix error when using string filters with special characters in grids.', '15'),
('2024-08-20', 'TRANSLATE-3946', 'bugfix', 'Repetition editor - Repetition\'s first occurrence should keep its initial match rate', 'First occurrence of repetition is now kept untouched, while others are set 102 or higher', '15'),
('2024-08-20', 'TRANSLATE-3899', 'bugfix', 'LanguageResources - Overwritten DeepL API key produces error with related Term Collection', ' DeepL API key provided on customer lvl now applied as for DeepL Language Resource as to Term Collection related to it', '15'),
('2024-08-20', 'TRANSLATE-3602', 'bugfix', 'Globalese integration - CSRF-protection causes Globalese plug-in to fail', 'Fixed Plugin auth handling', '15');