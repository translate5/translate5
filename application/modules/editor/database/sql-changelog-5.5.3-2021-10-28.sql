
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-10-28', 'TRANSLATE-2613', 'feature', 'Add Locaria Logo to Website and App', 'Added Locaria logo to the app', '15'),
('2021-10-28', 'TRANSLATE-2076', 'feature', 'Define analysis fuzzy match ranges', 'The ranges of the match rates for the analysis can now be defined in the configuration: runtimeOptions.plugins.MatchAnalysis.fuzzyBoundaries', '15'),
('2021-10-28', 'TRANSLATE-2652', 'change', 'Add keyboard short-cuts for Accept/Reject TrackChanges', 'ENHANCEMENT: Keyboard Shortcuts for TrackChanges accept/reject feature', '15'),
('2021-10-28', 'TRANSLATE-2625', 'change', 'Solve tag errors automatically on export', 'Internal Tag Errors (faulty structure) will be fixed automatically when exporting a task: Orphan opening/closing tags will be removed, structurally broken tag pairs will be corrected. The errors in the task itself will remain.', '15'),
('2021-10-28', 'TRANSLATE-2681', 'bugfix', 'Language naming mismatch regarding the chinese languages', 'The languages zh-Hans and zh-Hant were missing. Currently zh-CN was named "Chinese simplified", this is changed now to Chinese (China).', '15'),
('2021-10-28', 'TRANSLATE-2680', 'bugfix', 'Okapi empty target fix was working only for tasks with editable source', 'The Okapi export fix TRANSLATE-2384 was working only for tasks with editable source. Now it works in general. Also in case of an export error, the XLF in the export zip was named as original file (so file.docx was containing XLF). This is changed, so that the XLF is named now file.docx.xlf). Additionally a export-error.txt is created which explains the problem.
', '15'),
('2021-10-28', 'TRANSLATE-2679', 'bugfix', 'Microsoft translator connection language code mapping is not case insensitive', 'Microsoft translator returns zh-Hans for simplified Chinese, we have configured zh-hans in our language table. Therefore the language can not be used. This is fixed now.', '15'),
('2021-10-28', 'TRANSLATE-2672', 'bugfix', 'UI theme selection may be wrong if system default is not triton theme', 'The users selected theme may be resetted to triton theme instead to the system default theme.', '15'),
('2021-10-28', 'TRANSLATE-2664', 'bugfix', 'Fix TermPortal client-specific favicon and CSS usage', 'The technical possibilities to customize the TermPortal layout were not fully migrated from the old termportal.', '15'),
('2021-10-28', 'TRANSLATE-2658', 'bugfix', 'Wrong tag numbering between source and target in imported MemoQ XLF files', 'For MemoQ XLF files it may happen that tag numbering between source and target was wrong. This is corrected now.', '15'),
('2021-10-28', 'TRANSLATE-2657', 'bugfix', 'Missing term roles for legacy admin users', 'Activate the term portal roles for admin users not having them.', '15'),
('2021-10-28', 'TRANSLATE-2656', 'bugfix', 'Notify associated users checkbox is not effective', 'The bug is fixed where the "notify associated users checkbox" in the import wizard does not take effect when disabled.', '15'),
('2021-10-28', 'TRANSLATE-2592', 'bugfix', 'Reduce and by default hide use of TrackChanges in the translation step', 'Regarding translation and track changes: changes are only recorded for pre-translated segments and changes are hidden by default for translators (and can be activated by the user in the view modes drop-down of the editor)

', '15');