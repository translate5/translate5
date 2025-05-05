
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-05-02', 'TRANSLATE-3740', 'change', 'Editor general, usability editor - Right QA panel of editor too small', 'FIXED:  German keyboard layout problem with Ctrl+Alt+2 and Ctrl+Alt+3 combinations', '15'),
('2025-05-02', 'TRANSLATE-4633', 'bugfix', 'Content Protection, t5memory - Number protection tags are removed in fuzzy requests', 'Fixed bug which caused content protection tags to be removed when calling t5memory for matches', '15'),
('2025-05-02', 'TRANSLATE-4631', 'bugfix', 'Client management - delete client button missing', 'Fix delete client button', '15'),
('2025-05-02', 'TRANSLATE-4629', 'bugfix', 'Import/Export - XLF tags with ctype content replacement fixed for paired tags', 'Fix for native XLF dialect that the content of placeholder tags is either shown or not properly.', '15'),
('2025-05-02', 'TRANSLATE-4617', 'bugfix', 'InstantTranslate - Human Revision projects via Instanttranslate only visible for Sysadmin User', 'Fixed visibility of InstantTranslate projects for PM users', '15'),
('2025-05-02', 'TRANSLATE-4499', 'bugfix', 'TM Maintenance - Missing German in TM Maintenance', 'FIXED: grid columns menu items do now support German locale', '15'),
('2025-05-02', 'TRANSLATE-4445', 'bugfix', 'Editor general - Language resource did not respond in a reasonable time (termtagger?)', 'Increased timeout for matchpanel requests to 3 minutes', '15'),
('2025-05-02', 'TRANSLATE-4410', 'bugfix', 'LanguageResources - Add default assignment possibility for wrong sublanguage TMs (penalty feature)', 'translate5 - 7.23.0: If a TM has default read/write assignment for a client but task sublanguage mismatches - only use read assignment
translate5 - 7.23.1: Revoked matching logic', '15');