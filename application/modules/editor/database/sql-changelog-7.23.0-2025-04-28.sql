
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-04-28', 'TRANSLATE-4616', 'change', 'VisualReview / VisualTranslation - Add Test for Visual Exchange', 'Added API-test for Visual Exchange', '15'),
('2025-04-28', 'TRANSLATE-4614', 'change', 'TM Maintenance - Load by 2000 segments until scroll up', 'Segments are loaded one by one until 2000 are loaded or until scroll up', '15'),
('2025-04-28', 'TRANSLATE-4577', 'change', 'Editor general - New database query command', 'New cli tool database query', '15'),
('2025-04-28', 'TRANSLATE-4490', 'change', 'openai - Check GPT tag handling', 'Chat GPT resource now uses xliff paired tag handler with option to repair broken tags.', '15'),
('2025-04-28', 'TRANSLATE-4620', 'bugfix', 'job coordinator - PM Light have empty user select in job assignment', 'Fix job assignment creation for PM light user role', '15'),
('2025-04-28', 'TRANSLATE-4615', 'bugfix', 'LanguageResources - Inconsistency of t5memory language resources', 'Fix of status rendering. Improve logging of t5memory resource', '15'),
('2025-04-28', 'TRANSLATE-4612', 'bugfix', 'Content Protection - Content Protection: Rule intended to protect whole segment applied for chunk of it', 'Fix usage of whole segment rules', '15'),
('2025-04-28', 'TRANSLATE-4591', 'bugfix', 'LanguageResources, t5memory - Some segments are fail to reimport', 'Added reimporting segments that fail to reimport in previous reimport attempt.', '15'),
('2025-04-28', 'TRANSLATE-4410', 'bugfix', 'LanguageResources - Add default assignment possibility for wrong sublanguage TMs (penalty feature)', 'If a TM has default read/write assignment for a client but task sublanguage mismatches - only use read assignment', '15'),
('2025-04-28', 'TRANSLATE-4295', 'bugfix', 'LanguageResources - Resource tag handling', 'Enable tag handling configuration for each resource. Introducing new xml tag handler with tag repair functionality.', '15');