
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-08-06', 'TRANSLATE-4858', 'change', 'TermPortal - Make sure TermPortal is compatible with PHP 8.3', 'FIXED TermPortal compatibility problems with PHP 8.3', '15'),
('2025-08-06', 'TRANSLATE-4684', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - Upgrade translate5 to PHP 8.3', 'Upcoming switch to PHP 8.3: 
7.26.4: fix things producing deprecated messages
7.26.3: compatibility related fixes', '15'),
('2025-08-06', 'TRANSLATE-4860', 'bugfix', 't5memory - TMX import: tag content is imported', 'Add new TMX processor and config with regex to filter problematic TUs from TMX on import', '15'),
('2025-08-06', 'TRANSLATE-4856', 'bugfix', 'Auto-QA - AutoQA: Unchanged Fuzzy Match check also lists not-changed MT pre-translations', 'FIXED: MT-translations are not counted as Unedited fuzzy matches anymore', '15'),
('2025-08-06', 'TRANSLATE-4855', 'bugfix', 'Editor general - RootCause: Invalid JSON - answer seems not to be from translate5 - x-translate5-version header is missing.', 'DEBUG: added debug code for further investigation', '15'),
('2025-08-06', 'TRANSLATE-4853', 'bugfix', 'VisualReview / VisualTranslation - Cannot read properties of null (reading \'segmentHasComments\')', 'FIX: JS-error in visual when delivery of data is very slow', '15'),
('2025-08-06', 'TRANSLATE-4852', 'bugfix', 'Workflows - list changed segments in review finished notification to PM again', 'The changed segments in the review finished notification to the PM are listed again', '15'),
('2025-08-06', 'TRANSLATE-4850', 'bugfix', 'TermPortal - RootCause: can\'t access property "get", a.getRecord() is null', 'FIXED: fixed problem popping on switching between TermCollections in Attributes management screen', '15'),
('2025-08-06', 'TRANSLATE-4843', 'bugfix', 'VisualReview / VisualTranslation - RootCause: Cannot read properties of undefined (reading \'length\')', 'FIX: Visual scroller might causes RootCause-error due to unitialized variable', '15'),
('2025-08-06', 'TRANSLATE-4842', 'bugfix', 'Workflows - Always skip the second confirmation window for finishing print approval', 'The second confirmation window for finishing print approval is skipped now', '15'),
('2025-08-06', 'TRANSLATE-4840', 'bugfix', 'TermPortal - batch set for "process status" in TermCollection not working', '7.26.4: added more loading masks
7.26.2: added loading mask until request completes - to prevent concurring requests', '15'),
('2025-08-06', 'TRANSLATE-4839', 'bugfix', 't5memory - Check and potentially solve some output of logging of potentially problematic answers of t5memory', 'Fix logging for some t5memory errors ', '15');