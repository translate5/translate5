
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-02-24', 'TRANSLATE-5305', 'change', 'InstantTranslate - Instant-Translate: Link filename to download', 'On finished Instant-Translate files the filename is now also clickable to download the file.', '15'),
('2026-02-24', 'TRANSLATE-5290', 'change', 'Translate5 CLI - Add hosting backup check as system requirement module', 'Add hosting backup check as system requirement module', '15'),
('2026-02-24', 'TRANSLATE-5210', 'change', 'Editor general - Make new line tags actually look like new line', '[🆕 Feature] New line tag now actually have a line break so content is actually looks like a new line', '15'),
('2026-02-24', 'TRANSLATE-5302', 'bugfix', 'LanguageResources - Google resource: invalid default option for format', 'Fix default options for google format api parameter.', '15'),
('2026-02-24', 'TRANSLATE-5286', 'bugfix', 'Task Management - live updateing of levenshtein and post-editing time statistics does not seem to work', 'Fix the calculation of the levenshtein distance of segments with internal tags.', '15'),
('2026-02-24', 'TRANSLATE-5282', 'bugfix', 'translate5 AI - TQE: quota exceeded error not handled', 'Detect and log error when LLM quota is exceeded.', '15'),
('2026-02-24', 'TRANSLATE-5269', 'bugfix', 'MatchAnalysis & Pretranslation - use internal fuzzies switch broken', 'Disabling internal fuzzy will pre-translate segments marked as internal fuzzy on analysis run.', '15'),
('2026-02-24', 'TRANSLATE-5256', 'bugfix', 't5memory - Reimport pushes all segment to the TM', '[🐞 Fix] Fixed ignorance of blocked segments in reimport to TM process', '15');