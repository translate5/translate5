
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-03-31', 'TRANSLATE-2412', 'feature', 'Create a shortcut to directly get into the concordance search bar', 'New editor shortcut (F3) to get the cursor in "concordance search" source field.', '15'),
('2021-03-31', 'TRANSLATE-2375', 'feature', 'Set default deadline per workflow step in configuration', 'Define default deadline date for task-user association', '15'),
('2021-03-31', 'TRANSLATE-2342', 'feature', 'Show progress of document translation', 'Import progress bar in instant translate file translation and in the task overview.', '15'),


FIX: Newlines may have been rendered twice in case of internal tags representing newlines', '15'),
('2021-03-31', 'TRANSLATE-2446', 'change', 'Fonts Management for Visual: Add search capabilities by name / taskname', 'ENHANCEMENT: Added search-field to search for fonts by task name in the font management', '15'),
('2021-03-31', 'TRANSLATE-2440', 'change', 'Project task backend tests', 'Implement API tests testing the import of multiple tasks bundled in a project (one source language, multiple target languages).', '15'),
('2021-03-31', 'TRANSLATE-2424', 'change', 'Add Language as label under Language Flag image', 'TermPortal - added language label to language flag to display RFC language.', '15'),
('2021-03-31', 'TRANSLATE-2350', 'change', 'Make configurable if pivot language should be available in add task wizard', 'The availability / visibility of the pivot language in the add task wizard can be configured in the configuration for each customer now.', '15'),
('2021-03-31', 'TRANSLATE-2248', 'change', 'Change name of "visualReview" folder to "visual"', 'The "visualReview" folder in the zip import package is deprecated from now on. In the future please always use the new folder "visual" instead. All files that need to be reviewed or translated will have to be placed in the new folder "visual" from now on. In some future version of translate5 the support for "visualReview" folder will be completely removed. Currently it still is supported, but will write a "deprecated" message to the php error-log.', '15'),
('2021-03-31', 'TRANSLATE-1925', 'change', 'BUG: Workers running parallelism is not implemented correctly', 'Enhancement: Setting more workers to "waiting" in the "wakeupScheduled" call independently of the calling worker to improve the parallelism of running workers', '15'),
('2021-03-31', 'TRANSLATE-1596', 'change', 'Change name of "proofRead" folder to "workfiles"', 'The "proofRead" folder in the zip import package is deprecated from now on. In the future please always use the new folder "workfiles" instead. All files that need to be reviewed or translated will have to be placed in the new folder "workfiles" from now on. In some future version of translate5 the support for "proofRead" folder will be completely removed. Currently it still is supported, but will write a "deprecated" message to the php error-log.', '15'),
('2021-03-31', 'TRANSLATE-2456', 'bugfix', 'Quote in task name produces error', 'Fixed problem with language resources to task association when the task name contains single or double quotes.', '15'),
('2021-03-31', 'TRANSLATE-2454', 'bugfix', 'Configuration userCanModifyWhitespaceTags is not loaded properly', 'Users were not able to save segments with changed whitespace tags, since the corresponding configuration which allows this was not loaded properly.', '15'),
('2021-03-31', 'TRANSLATE-2453', 'bugfix', 'Fix unescaped control characters in language resource answers', 'Solving the the following error coming from OpenTM2: ERROR in editor.languageresource.service.connector: E1315 - JSON decode error: Control character error, possibly incorrectly encoded', '15'),
('2021-03-31', 'TRANSLATE-2451', 'bugfix', 'Fix description text of lock segment checkbox and task column', 'Clarify that feature "Locked segments in the imported file are also locked in translate5" is for SDLXLIFF files only.', '15'),
('2021-03-31', 'TRANSLATE-2449', 'bugfix', 'Grid grouping feature collapse/expand error', 'Fixes error with collapse/expand in locally filtered config grid.', '15'),
('2021-03-31', 'TRANSLATE-2448', 'bugfix', 'Unable to refresh entity after save', 'Fixing an error which may occur when using pre-translation with enabled batch mode of language resources.', '15'),
('2021-03-31', 'TRANSLATE-2445', 'bugfix', 'Unknown bullet prevents proper segmentation', 'FIX: Added some more bullet characters to better filter out list markup during segmentation
FIX: Priorize longer segments during segmentation to prevent segments containing each other (e.g. "Product XYZ", "Product XYZ is good") can not be found properly.', '15'),
('2021-03-31', 'TRANSLATE-2442', 'bugfix', 'Disabled connectors and repetitions', 'Fixing a problem with repetitions in match analysis and pre-translation context, also a repetition column is added in resource usage log excel export.', '15'),
('2021-03-31', 'TRANSLATE-2441', 'bugfix', 'HTML Cleanup in Visual Review way structurally changed internal tags', 'FIXED: Segments with interleaving term-tags and internal-tags may were not shown properly in the visual review (parts of the text missing).', '15'),
('2021-03-31', 'TRANSLATE-2438', 'bugfix', 'Fix plug-in XlfExportTranslateByAutostate for hybrid usage of translate5', 'The XlfExportTranslateByAutostate plug-in was designed for t5connect only, a hybrid usage of tasks directly uploaded and exported to and from translate5 was not possible. This is fixed now.', '15'),
('2021-03-31', 'TRANSLATE-2435', 'bugfix', 'Add reply-to with project-manager mail to all automated workflow-mails', 'In all workflow mails, the project manager e-mail address is added as reply-to mail address.', '15'),
('2021-03-31', 'TRANSLATE-2433', 'bugfix', 'file extension XLF can not be handled - xlf can', 'Uppercase file extensions (XLF instead xlf) were not imported. This is fixed now.', '15'),
('2021-03-31', 'TRANSLATE-2432', 'bugfix', 'Make default bconf path configurable', 'More flexible configuration for Okapi import/export .bconf files changeable per task import.', '15'),
('2021-03-31', 'TRANSLATE-2428', 'bugfix', 'Blocked segments and task word count', 'Include or exclude the blocked segments from task total word count and match-analysis when enabling or disabling  "100% matches can be edited" task flag.', '15'),
('2021-03-31', 'TRANSLATE-2427', 'bugfix', 'Multiple problems with worker related to match analsis and pretranslation', 'A combination of multiple problems led to hanging workers when importing a project with multiple targets and activated pre-translation.', '15'),
('2021-03-31', 'TRANSLATE-2426', 'bugfix', 'Term-tagging with default term-collection', 'term-tagging was not done with term collection assigned as default for the project-task customer', '15'),
('2021-03-31', 'TRANSLATE-2425', 'bugfix', 'HTML Import does not work properly when directPublicAccess not set', 'FIX: Visual Review does not show files from subfolders of the review-directory when directPublicAccess is not active (Proxy-access)', '15'),
('2021-03-31', 'TRANSLATE-2423', 'bugfix', 'Multicolumn CSV import was not working anymore in some special cases', 'Multicolumn CSV import with multiple files and different target columns was not working anymore, this is fixed now.', '15'),
('2021-03-31', 'TRANSLATE-2421', 'bugfix', 'Worker not started due maintenance should log to the affected task', 'If a worker is not started due maintenance, this should be logged to the affected task if possible.', '15'),
('2021-03-31', 'TRANSLATE-2420', 'bugfix', 'Spelling mistake: Task finished, E-mail template', 'Spelling correction.', '15'),
('2021-03-31', 'TRANSLATE-2413', 'bugfix', 'Wrong E-Mail encoding leads to SMTP error with long segments on some mail servers', 'When finishing a task an email is sent to the PM containing all edited segments. If there are contained long segments, or segments with a lot of tags with long content, this may result on some mail servers in an error. ', '15'),
('2021-03-31', 'TRANSLATE-2411', 'bugfix', 'Self closing g tags coming from Globalese pretranslation can not be resolved', 'Globalese receives a segment with a <g>tag</g> pair, but returns it as self closing <g/> tag, which is so far valid XML but could not be resolved by the reimport of the data.', '15'),
('2021-03-31', 'TRANSLATE-2325', 'bugfix', 'TermPortal: Do not show unknown tag name in the attribute header.', 'Do not show tag name any more in TermPortal for unkown type-attribute values and other attribute values', '15'),
('2021-03-31', 'TRANSLATE-2256', 'bugfix', 'Always activate button "Show/Hide TrackChanges"', 'Show/hide track changes checkbox will always be available (no matter on the workflow step)', '15'),
('2021-03-31', 'TRANSLATE-198', 'bugfix', 'Open different tasks if editor is opened in multiple tabs', 'The user will no longer be allowed to edit 2 different tasks using 2 browser tabs. ', '15');