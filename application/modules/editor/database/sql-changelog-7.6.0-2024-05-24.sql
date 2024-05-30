
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-05-24', 'TRANSLATE-3852', 'feature', 'Workflows - Workflow step "Revision language with editable 100% matches"', 'Complex workflow step 2nd revision renamed to "2nd revision language with editable 100% matches" and action added to unlock 100% matches for editing and trigger auto QA in this step.', '15'),
('2024-05-24', 'TRANSLATE-3850', 'feature', 'OpenId Connect - SSO via OpenID: Define if IDP should be able to remove rights', 'New client flag added for OpenId configured IDP. It can enable or disable updating of user roles, gender and locale from the IDP user claims.', '15'),
('2024-05-24', 'TRANSLATE-3964', 'change', 'Import/Export - Prevent PXSS in filenames', 'Fixed XSS issues in filenames', '15'),
('2024-05-24', 'TRANSLATE-3960', 'change', 'Editor general - Test PXSS in all input fields of the application', 'Security: fixed some remaining PXSS issues.', '15'),
('2024-05-24', 'TRANSLATE-3965', 'bugfix', 'Package Ex and Re-Import - Missing dot in TMX file names in translator package', 'In translator packages the TMX files are generated with the dot before the TMX file extension. This is fixed now.', '15'),
('2024-05-24', 'TRANSLATE-3959', 'bugfix', 'Editor general - Languages filters: search with no value leads to an error', 'Fixes problem when filtering languages with no value in language resources overview ', '15'),
('2024-05-24', 'TRANSLATE-3956', 'bugfix', 'TermTagger integration - Backend error on recalculation of the transFound transNotFound and transNotDefined', 'FIXED: problem popping when only translations are found but that\'s homonym ones', '15'),
('2024-05-24', 'TRANSLATE-3955', 'bugfix', 'Content Protection - Task creation fails on sublanguage if main languages was deleted', 'Fix: Task creation fails on sublanguage if main languages was deleted', '15'),
('2024-05-24', 'TRANSLATE-3954', 'bugfix', 'Editor general - Missing instant translate auto set role for admins', 'Added missing auto-set roles for admin users.', '15'),
('2024-05-24', 'TRANSLATE-3951', 'bugfix', 'Editor general - Missing userGuid in user tracking table', 'Fix for error when empty user entire exist in user tracking table.', '15'),
('2024-05-24', 'TRANSLATE-3950', 'bugfix', 'ConnectWorldserver, Import/Export - Add missing tests to TRANSLATE-3931', 'Add missing tests to TRANSLATE-3931', '15'),
('2024-05-24', 'TRANSLATE-3683', 'bugfix', 'VisualReview / VisualTranslation - when source segment is edited it will be shown in target visual', 'FIX: The WYSYWIG Visual now only reflects changes on the (first) target and not e.g. an editable source', '15'),
('2024-05-24', 'TRANSLATE-3621', 'bugfix', 'VisualReview / VisualTranslation - If first repetition is selected, all repetitions are highlightes green', 'Improvement: When the Visual has more repetitions of a certain segment then the grid, these additional repetitions (on the end of the visual) are nowassociated with the last repetition in the grid instead of the first', '15');