
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-11-28', 'TRANSLATE-3562', 'change', 'LanguageResources - Make name of TildeMT configurable in system configuration', 'TildeMT service name now can be configured', '15'),
('2023-11-28', 'TRANSLATE-3547', 'change', 'LanguageResources, t5memory - Change direct saving to tm to queue', 'If enabled segments in TMs will be updated asynchronously via queued worker (runtimeOptions.LanguageResources.tmQueuedUpdate)', '15'),
('2023-11-28', 'TRANSLATE-3542', 'change', 'Editor general - Enhance translate5 with more tooltips for better usability', 'Enhanced Translate5 and TermPortal tooltips', '15'),
('2023-11-28', 'TRANSLATE-3421', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - Organize test output and php errors from live instances based on Indi Engine', 'Internal improvements for automatic testing in development cycle', '15'),
('2023-11-28', 'TRANSLATE-3583', 'bugfix', 'VisualReview / VisualTranslation - FIX Visual Image Test', 'Update Google libraries to solve API-test problems', '15'),
('2023-11-28', 'TRANSLATE-3577', 'bugfix', 'Auto-QA - Missing DB indizes are leading to long running analysis', 'Due a missing DB index the analysis and pre-translation was taking to much time.', '15'),
('2023-11-28', 'TRANSLATE-3573', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Fix start of task operations in case of exceptions', 'FIX: QA operation workers stay in database if start of task operation failed', '15'),
('2023-11-28', 'TRANSLATE-3571', 'bugfix', 'API - Add missing content-type header in task import callback', 'The import callback was not sending a content-type, some callback implementations were not able to handle that.', '15'),
('2023-11-28', 'TRANSLATE-3559', 'bugfix', 'Configuration, Test framework - Remove method \ZfExtended_Models_Config::loadListByNamePart', 'Remove a internal function using the system configuration in an incomplete way.', '15'),
('2023-11-28', 'TRANSLATE-3496', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - session code cleanup and performance improvement', 'translate5 - 6.7.0 
 * Loading performance of session data improved. (Step 1)
translate5 - 6.7.2
 * Storing session improved. (Step 2)
', '15');