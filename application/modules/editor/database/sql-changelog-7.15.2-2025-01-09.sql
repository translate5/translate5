
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-01-09', 'TRANSLATE-4362', 'change', 'TM Maintenance - Error message when deleting a segment should be changed', 'Error message when deleting a segment in TM Maintenace changed to be more accurate', '15'),
('2025-01-09', 'TRANSLATE-4357', 'change', 't5memory - Splitted TMs: Save segments in part with lowest number and still space', 'Add support of block overflow error from t5memory, now memory part stays writable in case block overflow error occurs.', '15'),
('2025-01-09', 'TRANSLATE-4349', 'change', 't5memory - Add new error code for memory overflow', 'Added new error code that is handled as memory overflow', '15'),
('2025-01-09', 'TRANSLATE-4347', 'change', 't5memory - Add flushing memory if overflow error occurres', 'Added saving memory to the disk in case overflown error occurs', '15'),
('2025-01-09', 'TRANSLATE-4328', 'change', 'SpellCheck (LanguageTool integration) - Introduce task level config for setting the LanguageTool config value level', 'Introduce a a way to pass additional configuration parameters to SpellCheck Languagetool and added the rule level parameter.', '15'),
('2025-01-09', 'TRANSLATE-4356', 'bugfix', 'Editor general - Find next prev segment in workflow calculation was done wrong on server', 'The prev / next segment in workflow calculation was producing wrong results.', '15'),
('2025-01-09', 'TRANSLATE-4354', 'bugfix', 'Export - Transit plugin: error on export because of not matching segment it', 'Fix export problem in transit plugin.', '15'),
('2025-01-09', 'TRANSLATE-4350', 'bugfix', 't5memory - Wrong data sent to t5memory and sometimes re-import of failed tasks did not work', 'Added migration script for fixing broken segments in t5memory', '15'),
('2025-01-09', 'TRANSLATE-4348', 'bugfix', 'Hotfolder Import - Wrong namespace in Hotfolder plugin.', 'Fix namespaces.', '15'),
('2025-01-09', 'TRANSLATE-4331', 'bugfix', 'Editor general - Wrong workflow rendered in taskGrid', 'translate5 - 7.15.0: Fix workflow column value rendered wrong.
translate5 - 7.15.2: Wrong data used in renderer.', '15'),
('2025-01-09', 'TRANSLATE-4294', 'bugfix', 'Task Management - Fix custom fields handling on metadata export', 'Fixed custom fields handling on metadata export', '15');