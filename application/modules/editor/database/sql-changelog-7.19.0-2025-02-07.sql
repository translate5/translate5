
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-02-07', 'TRANSLATE-4425', 'feature', 'Editor general - Dummy Pseudo translator: New plugin for testing to be translated files', 'Added new plugin which purpose is to visualize which text can be translated in complext projects (documents etc.) in translate5', '15'),
('2025-02-07', 'TRANSLATE-4437', 'change', 'TermTagger integration - Tweaks to use threaded termtagger', 'Improvement: Tweaks for the use of a threaded Termtagger in the cloud', '15'),
('2025-02-07', 'TRANSLATE-4419', 'change', 'textshuttle - Change TextShuttle API URL', 'TextShuttle api URL changed', '15'),
('2025-02-07', 'TRANSLATE-4417', 'change', 'TrackChanges - Add predefined languages to as target-languages deactivating TrackChanges', 'Enhancement: Add predefined target languages deactivating TrackChanges', '15'),
('2025-02-07', 'TRANSLATE-4414', 'change', 'Hotfolder Import - Hotfolder: Don\'t process project folders recursively', 'Hotfloder: Don\'t process project folders recursively', '15'),
('2025-02-07', 'TRANSLATE-4380', 'change', 'Editor general - Hide error task and project error columns by default', 'Events column in project and task overview is hidden by default.', '15'),
('2025-02-07', 'TRANSLATE-4202', 'change', 'VisualReview / VisualTranslation - Visual: Download of Websites/Webapps with Headless Browser Instead of WGET', 'Improvement: Websites / Webapps are now downloaded with a Headless Browser and an optional configuration', '15'),
('2025-02-07', 'TRANSLATE-4093', 'change', 'file format settings - OKAPI integration: Compatibility with 1.47, clean up Pipelines', 'File Format Settings: General compatibility with Okapi 1.47, improved Pipeline handling', '15'),
('2025-02-07', 'TRANSLATE-4436', 'bugfix', 'openai - OpenAI: Service-Check does not work anymore', 'FIX: Service-Check for OpenAI did not work anymore', '15'),
('2025-02-07', 'TRANSLATE-4427', 'bugfix', 'Import/Export - SDLXLIFF: Export segment draft state', 'SDLXLIFF: fix export of draft state', '15'),
('2025-02-07', 'TRANSLATE-4418', 'bugfix', 'Okapi integration - Windows-based srx path within pipeline step causes error on bconf upload', 'Added proper handling of Windows-based srx paths within uploaded bconfs', '15'),
('2025-02-07', 'TRANSLATE-4416', 'bugfix', 'Editor general, TrackChanges - Switch how differences in fuzzy matches are shown', 'FIXED: misleding styles for differences between segment and match', '15'),
('2025-02-07', 'TRANSLATE-4299', 'bugfix', 'User Management - Client-PM assignment for task as translator does NOT work for job, where he is NOT PM', 'Solved in the frame of job coordinator feature', '15');