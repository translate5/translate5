
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-08-18', 'TRANSLATE-2380', 'change', 'VisualReview / VisualTranslation - Visual: Also connect segments, that contain variables with the layout', 'Visual: Segmentation of PDF/HTML based reviews now finds segments containing variables in the layout
FIX: The Segmentation result is now calculated for all visual files together
FIX: Alike Segments may have been not updated in the layout when changing the master', '15'),
('2022-08-18', 'TRANSLATE-3025', 'bugfix', 'OpenTM2 integration - OpenTM2 returns sometimes empty source language', 'On TMX export from OpenTM2 the source xml:lang attribute of a segment was sometimes empty. This is fixed now for a proper migration to t5memory.', '15'),
('2022-08-18', 'TRANSLATE-3024', 'bugfix', 'LanguageResources - Solve Problems with Empty Sources and TMs', 'FIX: Empty sources in segments lead to errors when saving them to Translation Memories', '15'),
('2022-08-18', 'TRANSLATE-2916', 'bugfix', 'VisualReview / VisualTranslation - Repetitions in the segment grid are not linked to the visual', 'NOTHING TO MENTION, issue resolved with TRANSLATE-2380', '15');