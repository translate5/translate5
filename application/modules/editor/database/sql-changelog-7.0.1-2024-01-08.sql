
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-01-08', 'TRANSLATE-3632', 'change', 't5memory - Log if segment is not saved  to TM', 'Add check if a segment was updated properly in t5memory and if not - log that for debug purposes', '15'),
('2024-01-08', 'TRANSLATE-3629', 'change', 'Package Ex and Re-Import - Translator package import: Move checkbox for "save to TM" from upload window to sysconfig', 'Write segments to TM on package re-import is now configurable on customer and task level and is not available any more as separate checkbox on re-import dialogue.', '15'),
('2024-01-08', 'TRANSLATE-3639', 'bugfix', 'Auto-QA, MatchAnalysis & Pretranslation - Inserted fuzzy should not write into "target at import time" field', 'Target text (at time of import / pretranslation) is now not updated anymore when applying match from translation memory match (was erroneously introduced in 7.0.0)', '15'),
('2024-01-08', 'TRANSLATE-3638', 'bugfix', 'Auto-QA, TrackChanges - Tags checker doesn\'t ignore deleted tags', 'Fix bug when deleted tags weren\'t ignored during tags validation', '15'),
('2024-01-08', 'TRANSLATE-3614', 'bugfix', 'InstantTranslate - TM match in instant translate ignored', 'Fix for translating segmented text in instant-translate so that more results come from TMs if assigned.', '15');
