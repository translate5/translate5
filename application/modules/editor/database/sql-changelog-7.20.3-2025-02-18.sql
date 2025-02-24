
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-02-18', 'TRANSLATE-4464', 'change', 'Editor general - Make Dummy pseudo translator plugin to be activated by default', 'Dummy pseudo translator plugin is now activated by default', '15'),
('2025-02-18', 'TRANSLATE-4472', 'bugfix', 'Editor general - UI error: loading jobs rarely reads to an error', 'Fix for UI error where loading of a jobs in project overview can crash the UI.', '15'),
('2025-02-18', 'TRANSLATE-4471', 'bugfix', 'Import/Export - Worker-queue may stuck on import due to MatchAnalysis', 'FIX: Import may stuck due to MatchAnalysis being queued too late', '15'),
('2025-02-18', 'TRANSLATE-4470', 'bugfix', 'Auto-QA - Exception when loading task-tbx leads to blocked task-operation', 'FIX: Exception in TBX-loading for a task may leads to a stuck AutoQA-operation', '15'),
('2025-02-18', 'TRANSLATE-4465', 'bugfix', 'TM Maintenance - Delete all button in TM Maintenance', 'TM Maintenance "Delete all" UI wording changed to be more clear. Fixed behavior of Yes - No buttons in German localization. ', '15'),
('2025-02-18', 'TRANSLATE-4421', 'bugfix', 'Import/Export - Quick insert sdlxliff tags do not get incremented short tag number', 'SDLXLIFF Import: Fix tag duplicating issue for segments with Quick insert tags', '15');