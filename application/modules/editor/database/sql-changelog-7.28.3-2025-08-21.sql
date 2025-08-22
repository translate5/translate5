
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-08-21', 'TRANSLATE-4900', 'bugfix', 'Editor general, TermPortal - RootCause: [PromiseRejectionEvent] undefined', 'FIXED: this error seems to happen somewhere internally in ExtJS and it don\'t have stack trace to be investigated, so it\'s now made to be ignored', '15'),
('2025-08-21', 'TRANSLATE-4897', 'bugfix', 'Editor general - Editor breaks long words', 'Fixed breaking long words in trackchanges nodes', '15'),
('2025-08-21', 'TRANSLATE-4895', 'bugfix', 'Editor general - RootCause: undefined is not an object (evaluating \'string.replace\')', 'Added logging for debug purposes', '15'),
('2025-08-21', 'TRANSLATE-4894', 'bugfix', 'Editor general - Strange request to GET /Editor.model.admin.TaskUserAssoc leading to RootCause error', 'DEBUG: added debug code for further investigation', '15'),
('2025-08-21', 'TRANSLATE-4893', 'bugfix', 'Editor general - ALT+F3 hotkey doesn\'t work', 'Fixed ALT+F3 hotkey', '15'),
('2025-08-21', 'TRANSLATE-4871', 'bugfix', 'Import/Export - Enhance error message on missing files on re-import of translator package', 'Improve error message text when zip structure is not correct.', '15'),
('2025-08-21', 'TRANSLATE-4866', 'bugfix', 'MatchAnalysis & Pretranslation - Repeated 100%-Matches should be pre-translated and potentially locked', 'Fix tag handling in repetition processing', '15');