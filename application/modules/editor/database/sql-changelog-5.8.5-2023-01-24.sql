
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-01-24', 'TRANSLATE-3172', 'feature', 'file format settings - XML File Filter Settings for Figma', 'Added XML filter for Figma (collaborative software)', '15'),
('2023-01-24', 'TRANSLATE-3136', 'feature', 'MatchAnalysis & Pretranslation - Show analysis results for editor users', 'Analysis results are available for all users with editor role.', '15'),
('2023-01-24', 'TRANSLATE-3054', 'feature', 'Auto-QA - Batch-set multiple AutoQA errors of type LanguageTool or Terminology to false positive', 'It is now possible to batch-set false-positive for similar autoQA-qualities', '15'),
('2023-01-24', 'TRANSLATE-3170', 'change', 'VisualReview / VisualTranslation - Improve error-logging for pdfconverter', 'Improve logging and data clean up in external service PDFconverter', '15'),
('2023-01-24', 'TRANSLATE-3169', 'change', 'SpellCheck (LanguageTool integration) - Make \'non-conformance\' error to be counted', '\'non-conformance\' errors detected by LanguageTool are now counted by AutoQA', '15'),
('2023-01-24', 'TRANSLATE-3039', 'change', 'Editor general - Improve password rules (4.7)', 'The current password rule (just 8 characters) was to lax. The new user password roles requirements can be found in this link: https://confluence.translate5.net/x/AYBVG', '15'),
('2023-01-24', 'TRANSLATE-294', 'change', 'Editor general - Add the task guid in the task overview as hidden column for better debugging', 'Add the TaskGuid as by default hidden column to the task grid.', '15'),
('2023-01-24', 'TRANSLATE-3174', 'bugfix', 'Auto-QA, Import/Export - Ignore protected character tags (mostly whitespace) from tagcheck', 'Several fixes in context of tag check of data coming from a language resource containing several tags and whitespaces converted to translate5 space tags.', '15'),
('2023-01-24', 'TRANSLATE-3173', 'bugfix', 'file format settings - Change of file extension association does not refresh grid', 'FIX: Bconf-Grid "Extensions" column was not updated after custom filters have been added or removed', '15'),
('2023-01-24', 'TRANSLATE-3171', 'bugfix', 'LanguageResources - Additional tags from manually taken over TM match is triggering tag check', 'Tagcheck was producing a false positive on saving a manually taken over segment from LanguageResource.', '15'),
('2023-01-24', 'TRANSLATE-3168', 'bugfix', 'TermPortal - Terms transfer source language problem', 'Terms transfer sublanguage problem fixed', '15'),
('2023-01-24', 'TRANSLATE-3167', 'bugfix', 'TBX-Import - Logger is missing in TbxBinaryDataImport class', 'Fix problem with tbx import logging in binary data.', '15'),
('2023-01-24', 'TRANSLATE-3166', 'bugfix', 'TBX-Import - Missing support for TBX-standard tags on tbx-import', 'Not all descripGrp tags where imported by the TBX import.', '15'),
('2023-01-24', 'TRANSLATE-3165', 'bugfix', 'TBX-Import - TBX import ignores custom attributes within descrip tags', 'term-level attributes using <descrip>-tags are now not ignored on tbx-import', '15'),
('2023-01-24', 'TRANSLATE-3163', 'bugfix', 'Configuration - Typos system configuration texts', 'Fix typos and textual inconsistencies in configuration labels and descriptions.', '15'),
('2023-01-24', 'TRANSLATE-3161', 'bugfix', 'InstantTranslate - languageId-problem on opening term in TermPortal', 'Fix \'Open term in TermPortal\' when using sublanguages.', '15'),
('2023-01-24', 'TRANSLATE-3160', 'bugfix', 'Editor general - Keyboard shortcut for concordance search not working as described', 'Fix the field to focus on F3 shortcut usage.', '15'),
('2023-01-24', 'TRANSLATE-3159', 'bugfix', 'LanguageResources - Server Error 500 when filtering language resources', 'Fixed server error popping on filtering languageresources by name and customer', '15'),
('2023-01-24', 'TRANSLATE-3158', 'bugfix', 'SpellCheck (LanguageTool integration), TermTagger integration - Task is unusable due status error caused by a recoverable error', 'Task is not unusable anymore in case of termtagger-worker exception', '15'),
('2023-01-24', 'TRANSLATE-3155', 'bugfix', 'Task Management - Ending a task, that is currently in task state edit is possible', 'If task is opened for editing it\'s not possible to change its state to ended', '15'),
('2023-01-24', 'TRANSLATE-3154', 'bugfix', 'Auto-QA - Consistency check should be case sensitive', 'Consistency check is now case-sensitive', '15'),
('2023-01-24', 'TRANSLATE-3149', 'bugfix', 'Task Management, WebSocket Server - 403 Forbidden messages in opened task', 'Users are getting multiple 403 Forbidden error messages.
On instances with a lot of users not logging out, this might also happen often due a bug in removing such stalled sessions. This is fixed in 5.8.5.
For users with unstable internet connections this was fixed in 5.8.2.', '15'),
('2023-01-24', 'TRANSLATE-3147', 'bugfix', 'InstantTranslate - Availability time in InstantTranslate makes no sense for IP-based Auth', 'Translated file download\'s \'available until\' line is not shown for IP-based users', '15'),
('2023-01-24', 'TRANSLATE-3142', 'bugfix', 'SpellCheck (LanguageTool integration) - Improve user feedback when spellchecker is overloaded', 'Improved error message if segment save runs into a timeout.', '15'),
('2023-01-24', 'TRANSLATE-3140', 'bugfix', 'OpenTM2 integration - Evaluate t5memory status on usage', 'A new worker was introduced for pausing match analysis if t5memory is importing a file', '15'),
('2023-01-24', 'TRANSLATE-3126', 'bugfix', 'InstantTranslate, TermPortal - Logout on window close also in instanttranslate and termportal', 'logoutOnWindowClose-config triggers now logout when last tab is closed not anymore already on first tab.', '15'),
('2023-01-24', 'TRANSLATE-3052', 'bugfix', 'LanguageResources - Clean resource assignments after customer is removed', 'Removing customer from resource will be prevented in case this resource is used/assigned to a task.', '15');