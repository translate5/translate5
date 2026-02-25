
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-02-25', 'TRANSLATE-5192', 'change', 't5memory - Repair wrong Tags for 100% Matches', '[🆕 Feature] added tags guessing when in response from t5memory we have excess tags. ', '15'),
('2026-02-25', 'TRANSLATE-5140', 'change', 't5memory - Use dash as context instead of segment number in task', 'Use dash as context instead of segment number in task', '15'),
('2026-02-25', 'TRANSLATE-5103', 'change', 't5memory - Improve deletion of Segments in Maintenance', 'Improve deletion of Segments in Maintenance:
Improve deletion of singular segments
Add new button "Delete all with same source"
Add new button "Delete all with same source + target"', '15'),
('2026-02-25', 'TRANSLATE-5034', 'change', 't5memory - Filter TMX on import', 'Apply filters on TMX on import time from translate5 side
All Translate5 TMs in instance will be marked as "not converted" and matches may have lower matches.
Language resources affected by this should be converted manually.', '15'),
('2026-02-25', 'TRANSLATE-3543', 'change', 'Editor general, usability editor - Move "toast" messages and warnings to an extra tabular view', 'Added log for toast messages and warnings within segments editor', '15'),
('2026-02-25', 'TRANSLATE-5303', 'bugfix', 'LanguageResources - Microsoft translator: textType parameter', 'Add textType parameter when query microsoft api for translations.', '15'),
('2026-02-25', 'TRANSLATE-5198', 'bugfix', 'LanguageResources - add warning on tmx import when languages not matching TM', 'Add warning on tmx import when languages not matching TM', '15'),
('2026-02-25', 'TRANSLATE-5038', 'bugfix', 'Editor general - selecting CP tag and text will swallow space when pasting in editor', 'FIXED: copy-pasting text with tags will respect white spaces', '15'),
('2026-02-25', 'TRANSLATE-4978', 'bugfix', 'Auto-QA - inconsistency check faulty', 'FIXED: missing background colors for inconsistent sources/targets in cases when there are more than 200 segments in the task', '15');