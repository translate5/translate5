
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-02-13', 'TRANSLATE-4454', 'change', 'MatchAnalysis & Pretranslation, t5memory - Add waiting for t5memory to be available before starting match analysis', 'Add waiting for t5memory to become available before match analysis and pretranslation', '15'),
('2025-02-13', 'TRANSLATE-4461', 'bugfix', 'job coordinator - Jobcoordinators don\'t see any tasks', 'Job Coordinator: fix project overview', '15'),
('2025-02-13', 'TRANSLATE-4460', 'bugfix', 'Workflows - PM Light permissions issue', 'PM Light: Fix permissions issue', '15'),
('2025-02-13', 'TRANSLATE-4457', 'bugfix', 'Editor general - Fatal error in TaskViewDataProvider', 'Fixed fatal error in TaskViewDataProvider', '15'),
('2025-02-13', 'TRANSLATE-4456', 'bugfix', 'Editor general - FIX: Unbookmarking watched segments creates invalid SQL', 'FIX: Unbookmarking watched segments creates invalid SQL', '15'),
('2025-02-13', 'TRANSLATE-4455', 'bugfix', 'Editor general, job coordinator - Position action returns different on same request', 'Fix: Fix blinking project overview ', '15'),
('2025-02-13', 'TRANSLATE-4453', 'bugfix', 'Editor general - Filters in language resource assoc panel are not working anymore', 'Fixed filtering in task to language resource association panel', '15'),
('2025-02-13', 'TRANSLATE-4451', 'bugfix', 't5memory - When memory is overflown during fuzzy its name is overwritten', 'Fixed bug which might cause t5memory language resource to get fuzzy name in case memory is overflown during match analysis', '15'),
('2025-02-13', 'TRANSLATE-4449', 'bugfix', 'API - Revert back incompatible changes', 'Rollback API entries type changes changes', '15'),
('2025-02-13', 'TRANSLATE-4434', 'bugfix', 'InstantTranslate - Instanttranslate: Waiting error on now action', 'Instanttranslate: Fix stalling instant translation tasks', '15'),
('2025-02-13', 'TRANSLATE-4433', 'bugfix', 'MatchAnalysis & Pretranslation - FIX: MatchAnalysis is queued with state "scheduled" for import', 'FIX: MatchAnalysis was queued with wrong state causing stuck imports in rare cases', '15'),
('2025-02-13', 'TRANSLATE-4023', 'bugfix', 'Auto-QA - AutoQA portlet should dissappear with no active checks', '7.20.2: UI Error caused by the original fix.
7.7.0: Editor\'s AutoQA leftside portlet is now hidden if no autoQA enabled for the task', '15'),
('2025-02-13', 'TRANSLATE-4422', 'bugfix', 'Content Protection: Change protection flow', 'Content Protection: Change flow of protection to allow whitespace handling with protection rules', '15');
