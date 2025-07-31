
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-07-31', 'TRANSLATE-4823', 'change', 'TBX-Import - TermCollection-Update: (At least) one term is lost, although in tbx', 'FIXED: \'same\' terms check is now case sensitive', '15'),
('2025-07-31', 'TRANSLATE-4793', 'change', 'Configuration - Rename config runtimeOptions.worker.server', 'The config name runtimeOptions.worker.server is misleading since its used in general for internal connections not only for workers. So its renamed to 
runtimeOptions.server.internalURL.', '15'),
('2025-07-31', 'TRANSLATE-4840', 'bugfix', 'TermPortal - batch set for "process status" in TermCollection not working', 'UI: added loading mask until request completes - to prevent concurring requests', '15'),
('2025-07-31', 'TRANSLATE-4838', 'bugfix', 't5memory - Type error on reorganise call', 'Fix call of reorganise in fuzzy search logic', '15'),
('2025-07-31', 'TRANSLATE-4826', 'bugfix', 'file format settings - RootCause: controller.createInfoSpan is not a function', 'FIXED: problem with incorrect handling of invalid uploaded filter/fprm file', '15'),
('2025-07-31', 'TRANSLATE-4825', 'bugfix', 'MatchAnalysis & Pretranslation - In pre-translation repetitions get tags in mixed oreder', 'Fix tags order for repetitions in pre-translation', '15'),
('2025-07-31', 'TRANSLATE-4824', 'bugfix', 'Editor general - RootCause: Cannot read properties of undefined (reading \'isEmptyStore\')', 'FIXED: \'Language resources\' combobox won\'t now be shown in \'Tasks\'-tab\'s \'Advanced filters\' window if current user have no rights for \'Language resources\' tab', '15'),
('2025-07-31', 'TRANSLATE-4820', 'bugfix', 't5memory - t5memory clean-up command deletes memories from other instances', 'Fix t5memory:clean-up command. Provide new reimport commands', '15'),
('2025-07-31', 'TRANSLATE-4817', 'bugfix', 'Hotfolder Import - Integrity constraint error on failing hotfolder import', 'On specific import failures of the hotfolder import the error is not handled properly and will lead to follow up errors. ', '15'),
('2025-07-31', 'TRANSLATE-4669', 'bugfix', 'AI - translate5 AI: Show already trained prompts separated in training window', 'Prompts that have been sent to training remain now separated in training window after training', '15');