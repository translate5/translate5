
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-06-12', 'TRANSLATE-4713', 'bugfix', 'Content Protection - Conversion of TM result in deletion of TM on t5memory side', 'Fix TM conversion', '15'),
('2025-06-12', 'TRANSLATE-4712', 'bugfix', 't5memory - TMX splitting in import process produces error', 'Fix TMX splitting on import', '15'),
('2025-06-12', 'TRANSLATE-4706', 'bugfix', 'openai - Trained model can not be trained again if using Azure cloud', 'Fixed bug, which may prevent OpenAI model to be trained more than 1 time.', '15'),
('2025-06-12', 'TRANSLATE-4698', 'bugfix', 'Editor general - hide fuzzy match panel in new "review visual view"', 'Fuzzy match panel is hidden in the new "Review visual view" mode', '15');