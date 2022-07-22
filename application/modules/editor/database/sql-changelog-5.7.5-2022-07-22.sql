
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-07-22', 'TRANSLATE-3002', 'feature', 'Workflows - Ask for task finish on task close too', 'Added dialog shown on leaving the application in embedded mode, with finish task and just leave as possible choices. Added config option to control whether such dialog should be shown.', '15'),
('2022-07-22', 'TRANSLATE-2999', 'change', 'TermPortal - Create missing term attributes datatype foreign key', 'Fixed problem with missing data types for term attributes in term portal.', '15'),
('2022-07-22', 'TRANSLATE-3007', 'bugfix', 'InstantTranslate - Instant translate search content with tags', 'FIXED Bug in Instanttranslate when segmented results are processed due to a missing API', '15'),
('2022-07-22', 'TRANSLATE-3006', 'bugfix', 'LanguageResources - Problem with DeepL target language', 'Fixes problem where the DeepL language resource target language was saved as lowercase value.', '15'),
('2022-07-22', 'TRANSLATE-3004', 'bugfix', 'Editor general - Error on deleting project', 'Solves problem where error pop-up was shown when deleting project.', '15'),
('2022-07-22', 'TRANSLATE-3000', 'bugfix', 'Editor general - Use project task store for task reference in import wizard', 'Solves problem in import wizard when assigning task users.', '15'),
('2022-07-22', 'TRANSLATE-2996', 'bugfix', 'MatchAnalysis & Pretranslation - Analysis grid reconfigure leads to an error', 'Solves problem with front-end error in match analysis overview.', '15'),
('2022-07-22', 'TRANSLATE-2995', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Event logger error', 'Fixed back-end error with workflow actions info logging.', '15'),
('2022-07-22', 'TRANSLATE-2987', 'bugfix', 'Task Management - Routing problems when jumping from and to project overview', 'Fixed a problem where the selected task was not focused after switching between the overviews.', '15'),
('2022-07-22', 'TRANSLATE-2963', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.), MatchAnalysis & Pretranslation - Queuing matchanalysis multiple times leads to locked tasks', 'FIX: Prevent running multiple operations for the same task', '15'),
('2022-07-22', 'TRANSLATE-2813', 'bugfix', 'Client management, LanguageResources, Task Management, User Management - Copy&paste content of PM grids', 'Now you can copy text from all grids cells in translate5.', '15'),
('2022-07-22', 'TRANSLATE-2786', 'bugfix', 'Import/Export - xliff 1.2 import fails if a g tag contains a mrk segment tag', 'The XLF import fails if there are g tags surrounding the mrk segmentation tags.', '15');