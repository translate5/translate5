
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-09-01', 'TRANSLATE-3019', 'feature', 'Configuration - Support Subnets in IP-based authentication', 'Change IpAuthentication plugin to support subnet masks, e.g. 192.168.0.1/24', '15'),
('2022-09-01', 'TRANSLATE-3016', 'feature', 'Configuration, Editor general, TermTagger integration - Show and use only terms of a certain process level in the editor', 'What kind of process status the terms has, used for term tagging and listed in the editor term-portlet  can be configured as system, client and task level.', '15'),
('2022-09-01', 'TRANSLATE-3015', 'feature', 'TBX-Import - Merge multiple attributes of the same type in TBX import', 'Two attributes will be merged into one if they are from the same type and appear on same level.', '15'),
('2022-09-01', 'TRANSLATE-3014', 'feature', 'Editor general - Show color of TermCollection behind term in editors termportlet', 'Term collection color will be listed in the term portlet for each term.', '15'),
('2022-09-01', 'TRANSLATE-3003', 'feature', 'Editor general - Show term attributes in term-portlet of translate5s editor', 'Tooltip with the term entry, language and term attributes will be show with mouse over the terms in the term portlet in editor.', '15'),
('2022-09-01', 'TRANSLATE-3045', 'bugfix', 'TermTagger integration - Optimize terms_term indexes', 'Improve the DB indizes for the terms_term table.', '15'),
('2022-09-01', 'TRANSLATE-3043', 'bugfix', 'SpellCheck (LanguageTool integration) - spellcheck markup is destroying internal tags', 'SpellCheck: Multi-whitespaces are now respected while applying spellcheck styles', '15'),
('2022-09-01', 'TRANSLATE-3041', 'bugfix', 'Auto-QA, Editor general - Wrong whitespace tag numbering leads to non working whitespace added QA check', 'The internal numbering of whitespace tags (newline, tab etc) was not consistent anymore between source and target, therefore the whitespace added auto QA is producing a lot of false positives.', '15'),
('2022-09-01', 'TRANSLATE-3030', 'bugfix', 'Auto-QA - Fixes Spellcheck-QA-Worker: Index for state-field, proper solution for logging / "last worker"', 'FIX: Spellcheck AutoQA-worker was lacking an database-Index, with the index spellchecking should be faster on import', '15'),
('2022-09-01', 'TRANSLATE-3029', 'bugfix', 'file format settings - IDML FPRM Editor too heigh', 'FIX: Height of IDML FPRM Editor too big on smaller screens so that buttons are not visible', '15'),
('2022-09-01', 'TRANSLATE-3028', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Reset password error', 'Fix for a problem where the user was not able to reset the password.', '15');