
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-08-29', 'TRANSLATE-1763', 'feature', 'Import comments from SDLXLIFF to translate5', 'Comments in SDLXLIFF files can now be imported. This feature must be activated in the config, otherwise the comments will be deleted.', '12'),
('2019-08-29', 'TRANSLATE-1776', 'feature', 'Terminology in meta panel is also shown on just clicking on a segment', 'This is also useful if a task is opened read-only, when segments could not be opened for editing.', '14'),
('2019-08-29', 'TRANSLATE-1730', 'bugfix', 'Delete change markers from SDLXLIFF', 'If enabled in config, the marked changes are applied and the change-marks are deleted.', '12'),
('2019-08-29', 'TRANSLATE-1778', 'bugfix', 'TrackChanges fail cursor-position in Firefox', 'The cursor position is now at the correct place.', '14'),
('2019-08-29', 'TRANSLATE-1781', 'bugfix', 'TrackChanges: reset in combination with matches is buggy', 'This problem is fixed.', '14'),
('2019-08-29', 'TRANSLATE-1770', 'bugfix', 'TrackChanges: reset to initial content must not mark own changes as as change', 'On other words: If a user resets his changes, no change-marks should be applied.', '14'),
('2019-08-29', 'TRANSLATE-1765', 'bugfix', 'TrackChanges: Content marked as insert produces problems with SpellChecker', 'Now the spellcheck markup is correct with enabled track-changes.', '14'),
('2019-08-29', 'TRANSLATE-1767', 'bugfix', 'Cloning of task where assigned TBX language resource has been deleted leads to failed import', 'This was happening only, if the task had associated an terminology language-resource which was deleted in the meantime.', '12');