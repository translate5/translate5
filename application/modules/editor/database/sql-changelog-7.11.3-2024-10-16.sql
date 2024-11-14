
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-10-16', 'TRANSLATE-4229', 'change', 'TM Maintenance - Make TM Maintenance plugin to be enabled by default', 'TM Maintenance plugin is now enabled by default', '15'),
('2024-10-16', 'TRANSLATE-4236', 'bugfix', 'MatchAnalysis & Pretranslation - Deadlocks in segment processing leads to follow up error', 'Fixed error about already active transactions in auto QA segment processing.', '15'),
('2024-10-16', 'TRANSLATE-4225', 'bugfix', 'GroupShare integration - GroupShare TMs should not be deletable via translate5', 'Since the list of GroupShare TMs is synchronized from GroupShare itself, there should be no way to delete such TMs in translate5.', '15'),
('2024-10-16', 'TRANSLATE-4223', 'bugfix', 'Import/Export - Fix mxliff (Phrase) internal Tags (very strange, non xliff-standard format)', 'Converts the mxliff custom markup({b>, {i> etc...) to ph (placeholder) tags and restore the markup on export.', '15'),
('2024-10-16', 'TRANSLATE-4222', 'bugfix', 'Editor general - CTRL+f in editor should not remember last search', 'FIXED: search field\'s text is now empty on first search/replace window open, and pre-selected on further opens for easy overwriting with Ctrl+V ', '15');