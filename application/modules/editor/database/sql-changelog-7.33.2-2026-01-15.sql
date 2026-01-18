
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-01-15', 'TRANSLATE-5191', 'change', 't5memory - Rename from OpenTM2 to t5memory in UI', '7.33.2: convert the serviceType OpenTM2 automatically to T5Memory and create deprecation warning into the log when creating language resources via API and use old serviceType name
7.33.0: Rename OpenTM2 to T5Memory', '15'),
('2026-01-15', 'TRANSLATE-5204', 'bugfix', 'Comments, Import/Export - Comments produced by XLF resname field are not imported properly', 'The resname information of segments in XLF are imported as comments but they were not shown as comment in the segment grid.', '15'),
('2026-01-15', 'TRANSLATE-5203', 'bugfix', 'Editor general - RootCause: Cannot read properties of null (reading \'editableNext\')', 'FIXED: handled case when where is no next segment', '15'),
('2026-01-15', 'TRANSLATE-5201', 'bugfix', 'Editor general - RootCause: can\'t access property "unmask" of null', 'FIXED: added handling for cases when prompt details window is closed before request callback received', '15'),
('2026-01-15', 'TRANSLATE-5130', 'bugfix', 'VisualReview / VisualTranslation - Visual exchange: window shrinks', 'Fixed window shrinking when adding or exchanging a file for visual', '15'),
('2026-01-15', 'TRANSLATE-5035', 'bugfix', 'Configuration - changed company name not respected for email sender', 'The variable {companyName} is now replaced in the configured from name of the sender e-mail', '15');