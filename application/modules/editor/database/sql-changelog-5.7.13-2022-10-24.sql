
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-10-24', 'TRANSLATE-2994', 'feature', 'LanguageResources, OpenTM2 integration - t5memory roll-out', 'Added new cli command for migrating OpenTM2 to t5memory.
Check the usage of 
./translate5.sh help otm2:migrate', '15'),
('2022-10-24', 'TRANSLATE-3080', 'change', 'LanguageResources - Language resources Help video', 'Integrate language resources video in Language-resources help page.', '15'),
('2022-10-24', 'TRANSLATE-3082', 'bugfix', 'VisualReview / VisualTranslation - FIX alias-segments may not be translated in the live-editing when they are "far away" from each other', 'FIX: Repeated segments in the right visual layout, that were several pages "away" from each other may remained untranslated when scrolling to the rear occurances
FIX: Right WYSIWYG frame is partly untranslated in PDF-based visuals with lots of pages or when scrolling fast', '15'),
('2022-10-24', 'TRANSLATE-3081', 'bugfix', 'TermPortal - Fix show sub languages config level in TermPortal', 'Correct the accessibility level for show sub-languages config in term portal.', '15'),
('2022-10-24', 'TRANSLATE-3077', 'bugfix', 'LanguageResources - Auto-start pivot translation', 'Introduce a configuration to auto-start pivot pre-translation on API based task imports.', '15'),
('2022-10-24', 'TRANSLATE-3062', 'bugfix', 'Installation & Update, Test framework - Test DB reset and removement of mysql CLI dependency', 'Removed the mysql CLI tool as dependency from translate5 PHP code.', '15'),
('2022-10-24', 'TRANSLATE-3061', 'bugfix', 'Test framework - FIX API Tests', 'Fixed API tests, generalized test API', '15'),
('2022-10-24', 'TRANSLATE-3053', 'bugfix', 'Editor general - Refactor direct role usages into usages via ACL rights', 'Direct role usages refactored with rights usages instead', '15');