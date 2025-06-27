
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-06-27', 'TRANSLATE-3567', 'feature', 'Editor general - Open a task in translate5\'s editor from InstantTranslate', '7.25.1: Terminology fixes; Add missing new default role taskOverview to allowed roles in OpenID config of clients
7.25.0: Added possibility to open task for editing from instant translate', '15'),
('2025-06-27', 'TRANSLATE-4744', 'change', 'Editor general - workflowStep filter leads to invalid filter SQL', 'FIX: numeric or date filters in filtered grids lead to SQL errors when values are empty', '15'),
('2025-06-27', 'TRANSLATE-4736', 'change', 'Editor general - Add new special characters', 'New special characters registered in the editor.', '15'),
('2025-06-27', 'TRANSLATE-3827', 'change', 'Authentication - IP-based Authentication should still allow to log in as a user', 'Introduced a new config to control for which applets (editor,instanttranslate,termportal etc) the ip based authentication should be applied or the normal login page should be shown.', '15'),
('2025-06-27', 'TRANSLATE-4742', 'bugfix', 't5memory - Reorganise of memory in internal fuzzy leads to memory deletion', 'Fix reorganise logic in internal fuzzy analysis scope', '15'),
('2025-06-27', 'TRANSLATE-4741', 'bugfix', 'Editor general - RootCause: You\'re trying to decode an invalid JSON String', 'FIXED: error on languageresource checkbox doubleclick in 3rd step of task import wizard ', '15'),
('2025-06-27', 'TRANSLATE-4737', 'bugfix', 'OpenId Connect - New role taskOverview is not set in allowed roles of OpenID Connect', 'New role taskOverview is not added to allowed roles of OpenID Configuration', '15'),
('2025-06-27', 'TRANSLATE-4735', 'bugfix', 'Import/Export - HOTFIX: Import of comments leads to deadlock', 'HOTFIX: Import of resname-comments had bugs leading to extremely slow imports and may resulting in Deadlocks', '15'),
('2025-06-27', 'TRANSLATE-4733', 'bugfix', 'MatchAnalysis & Pretranslation - No analysis entries saved for term collection repetitions', 'Analysis entries are added for term collection repetitions.', '15'),
('2025-06-27', 'TRANSLATE-4732', 'bugfix', 'MatchAnalysis & Pretranslation - RootCause: Cannot read properties of undefined (reading \'dataSource\')', 'FIXED: Row-editor problem in \'MatchRanges and Pricing\' tab', '15'),
('2025-06-27', 'TRANSLATE-4731', 'bugfix', 'Authentication - Allow session delete API endpoint', 'According to the documentation the sessions are deletable via API, this is currently not possible.
For the deletion via internal Unique ID the prefix internal- must be added to the given ID in the DELETE URL.
Since the feature was anyway broken we consider this not as an incompatible API change.', '15'),
('2025-06-27', 'TRANSLATE-4730', 'bugfix', 'Content Protection - Fix CP rules', 'Fix some rules', '15'),
('2025-06-27', 'TRANSLATE-4726', 'bugfix', 'InstantTranslate - DeepL problem with & character in Chinese', 'FIXED: DeepL problem with \'&\' character in Chinese', '15'),
('2025-06-27', 'TRANSLATE-2373', 'bugfix', 'TermTagger integration - Prevent termtagger usage if source and target language are equal', '7.25.1: introduce a config to disable this behaviour
5.1.0: Prevent termtagger hanging when source and target language of a task are identical. Now in these cases the terms are not tagged anymore', '15');