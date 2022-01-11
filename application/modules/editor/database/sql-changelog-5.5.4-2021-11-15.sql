
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-11-15', 'TRANSLATE-2638', 'feature', 'Implement new layout for InstantTranslate', 'Implement new layout for InstantTranslate as discussed with the consortium members.', '15'),
('2021-11-15', 'TRANSLATE-2683', 'change', 'Editor Embedded: export may be started while last edited segment still is saving', 'For translate5 embedded usage: the JS API function Editor.util.TaskActions.isTaskExportable() returns true or false if the currently opened task can be exported regarding the last segment save call.', '15'),
('2021-11-15', 'TRANSLATE-2649', 'change', 'Small fixes for TermPortal', 'A number of fixes/improvements implemented', '15'),
('2021-11-15', 'TRANSLATE-2632', 'change', 'TermPortal code refactoring', 'Termportal code and related tests are now refactored for better maintainability.', '15'),
('2021-11-15', 'TRANSLATE-2489', 'change', 'Change of attribute label in GUI', 'Added ability to edit attribute labels', '15'),
('2021-11-15', 'TRANSLATE-2701', 'bugfix', 'Source term from InstantTranslate not saved along with target term', 'TermPortal: In case the source term, that had been translated in InstantTranslate was not contained in the TermCollection, only the target term was added, the new source term not. This is fixed.', '15'),
('2021-11-15', 'TRANSLATE-2699', 'bugfix', 'Add missing ID column to task overview and fix date type in meta data excel', 'Add missing ID column to task overview and fix date type in meta data excel export.', '15'),
('2021-11-15', 'TRANSLATE-2696', 'bugfix', 'Malicious segments may lead to endless loop while term tagging', 'Segments with specific / malicious content may lead to endless loops while term tagging so that the task import is running forever.', '15'),
('2021-11-15', 'TRANSLATE-2695', 'bugfix', 'JS error task is null', 'Due unknown conditions there might be an error task is null in the GUI. Since the reason could not be determined, we just fixed the symptoms. As a result a user might click twice on the menu action item to get all items.', '15'),
('2021-11-15', 'TRANSLATE-2694', 'bugfix', 'Improve GUI logging for false positive "Not all repeated segments could be saved" messages', 'Improve GUI logging for message like: Not all repeated segments could be saved. With the advanced logging should it be possible to detect the reason behind.', '15'),
('2021-11-15', 'TRANSLATE-2691', 'bugfix', 'SDLXLIFF diff export is failing with an endless loop', 'The SDLXLIFF export with diff fails by hanging in an endless loop if the segment content has a specific form. This is fixed by updating the underlying diff library.', '15'),
('2021-11-15', 'TRANSLATE-2690', 'bugfix', 'task is null: User association in import wizard', 'Fix for "task is null" error in import user-assoc wizard', '15'),
('2021-11-15', 'TRANSLATE-2689', 'bugfix', 'TBX import fails because of some ID error', 'Terminology containing string based IDs could not be imported if the same ID was used one time lower case and one time uppercase.', '15'),
('2021-11-15', 'TRANSLATE-2688', 'bugfix', 'For many languages the lcid is missing in LEK_languages', 'Added some missing LCID values in the language table.', '15'),
('2021-11-15', 'TRANSLATE-2687', 'bugfix', 'Wrong texts in system config options', 'Improve description and GUI-text for system configurations.', '15'),
('2021-11-15', 'TRANSLATE-2686', 'bugfix', 'TermTagging does not work after import', 'If term tagging is started along with analysis on an already imported task, nothing gets tagged.', '15'),
('2021-11-15', 'TRANSLATE-2404', 'bugfix', 'There is no way to run only the terminology check only after import', 'There is no way to start the terminology check only from the language resource association panel, a analysis is always started as well. This is changed now.', '15');