
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-10-23', 'TRANSLATE-4239', 'change', 'TM Maintenance - Increase request timeouts for TMMaintenance', 'Timeout for requests in TM Maintenance increased to 15 minutes', '15'),
('2024-10-23', 'TRANSLATE-4228', 'change', 'TM Maintenance - Change limit for the first request in TM Maintenance', 'First 20 segments are now loaded one by one via separate requests instead of via single request', '15'),
('2024-10-23', 'TRANSLATE-4215', 'change', 't5memory - Implement new t5memory statuses processing', 'Add support for new t5memory statuses and support of requests timeout with retry', '15'),
('2024-10-23', 'TRANSLATE-4136', 'change', 'Hotfolder Import - Hotfolder: PM client override doesn\'t work', 'Get PM from customer default if customer provided and specific PM not provided in instructions.xml', '15'),
('2024-10-23', 'TRANSLATE-4008', 'change', 'Configuration - Use noreply address as default sender', 'Now a noreply address is used as sender in default installations instead the support address.', '15'),
('2024-10-23', 'TRANSLATE-4005', 'change', 'Test framework - Create enum containing all test user logins as constants', 'Code clean-up: replace hard coded usernames in the tests.', '15'),
('2024-10-23', 'TRANSLATE-3941', 'change', 't5memory - Handle t5memory TM splitting in connection with size', 'Added new code to handle t5memory overflow error', '15'),
('2024-10-23', 'TRANSLATE-4237', 'bugfix', 'InstantTranslate - Text translations stay visible after switch to Translate file', 'FIXED: text translations do not stay visible anymore when user switched to \'Translate file\' mode', '15'),
('2024-10-23', 'TRANSLATE-4235', 'bugfix', 'Hotfolder Import, TBX-Import - SFTP TermImport: tbx import via zip with images inside is not working', 'FIXED: tbx import via zip with images inside is now working via SFTP TermImport', '15'),
('2024-10-23', 'TRANSLATE-4234', 'bugfix', 'MatchAnalysis & Pretranslation - MatchAnalysis may lead to hanging delayed workers', 'Using the match analysis after the import may lead to hanging workers in status delayed.', '15'),
('2024-10-23', 'TRANSLATE-4188', 'bugfix', 'Content Protection - Content Protection for InstantTranslate', 'Fix content unprotect logic.', '15'),
('2024-10-23', 'TRANSLATE-4187', 'bugfix', 'Editor general, Workflows - CTRL+ENTER does not correctly work in complex workflow\'s multiple review steps', 'Fix CTRL+ENTER behavior for segments editing within complex workflows: use current workflow step instead of autostates map', '15'),
('2024-10-23', 'TRANSLATE-4127', 'bugfix', 'Auto-QA - RootCause: this.getView() is null', 'translate - 7.8.0 : added logging for further investigation of a problem with AutoQA filters
translate - 7.12.0: bug fixed', '15'),
('2024-10-23', 'TRANSLATE-4112', 'bugfix', 'InstantTranslate - InstantTranslate target text should not be bold', 'FIXED: styles are stripped from translation result when copied to clipboard', '15');