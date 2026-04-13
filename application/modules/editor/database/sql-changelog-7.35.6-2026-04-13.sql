
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-04-13', 'TRANSLATE-5407', 'bugfix', 't5memory - Retry request on "Transfer closed with outstanding read data remaining" error', 'Improve t5memory network handling', '15'),
('2026-04-13', 'TRANSLATE-5387', 'bugfix', 'translate5 AI - TQE: encoded html special character affects score', 'Fix problem where the HTML characters where not handled correctly in TQE evaluation.', '15'),
('2026-04-13', 'TRANSLATE-5376', 'bugfix', 't5memory - Incorrect handling of utf-16 tmx files', 'Re-enable processing of UTF-16 TMX files', '15'),
('2026-04-13', 'TRANSLATE-5373', 'bugfix', 'Editor general - RootCause: currentDate.getHours is not a function', 'FIXED: error on Deadline-field\'s datetime picker expand in User assignment form at the 2nd step of project import wizard', '15'),
('2026-04-13', 'TRANSLATE-5371', 'bugfix', 'TermPortal - Improve Typing in Search Filter in TermPortal', 'FIXED: dropdown search with keyword now works for any matches rather than only for matches at the beginning of dropdown choice label', '15'),
('2026-04-13', 'TRANSLATE-5312', 'bugfix', 'InstantTranslate - Instant-Translate: empty translation from resource not processed correctly', 'Fix for a problem where when empty translation was returned and the requested translation is part of the segmented query, the empty result is rendered even if some other resource delivers non empty result.', '15'),
('2026-04-13', 'TRANSLATE-5296', 'bugfix', 'TrackChanges - Checked state not preserved for \'Display tracked changes\' menu item', 'FIXED: checked state is now preserved for  \'Display tracked changes\' menu item, by default TrackChanges are always visible', '15'),
('2026-04-13', 'TRANSLATE-5285', 'bugfix', 'Task Management - Excel-exported task meta data shows language id instead of short-cut', 'Display a properly formatted language filter in the task Excel export.', '15'),
('2026-04-13', 'TRANSLATE-5224', 'bugfix', 'Repetition editor - RootCause: null is not an object (evaluating \'alikes\')', 'FIXED: prevented duplicated processing of repetitions', '15');