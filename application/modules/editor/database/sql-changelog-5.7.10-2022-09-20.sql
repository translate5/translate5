
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-09-20', 'TRANSLATE-3038', 'feature', 'Editor general - Integrate anti virus software (4.6)', 'SECURITY ENHANCEMENT: Added blacklist to limit uploadable reference file types', '15'),
('2022-09-20', 'TRANSLATE-3016', 'feature', 'Configuration, Editor general, TermTagger integration - Show and use only terms of a certain process level in the editor', 'Only the terms with a defined process status are used for term tagging and listed in the editor term-portlet. The configuration is runtimeOptions.termTagger.usedTermProcessStatus. ', '15'),
('2022-09-20', 'TRANSLATE-3057', 'change', 'TermPortal - Extend term status map', 'Extend the term status mapping with additional types.', '15'),
('2022-09-20', 'TRANSLATE-3040', 'change', 'User Management - On password change the old one must be given (4.8)', 'If a user is changing his password, the old password must be given and validated too, to prevent taking over stolen user accounts.', '15'),
('2022-09-20', 'TRANSLATE-3056', 'bugfix', 'Auto-QA - MQM Controller does not activate when changing task after deactivation', 'FIX: After deactivating MQM, it was not activated anymore when opening the next task', '15'),
('2022-09-20', 'TRANSLATE-3051', 'bugfix', 'User Management - Add SALT to MD5 user password (4.4)', 'The user passwords are now stored in a more secure way.', '15'),
('2022-09-20', 'TRANSLATE-3050', 'bugfix', 'Import/Export - Whitespace tag handling did encode internal tag placeholders on display text import filter', 'Fix for a proprietary import filter.', '15'),
('2022-09-20', 'TRANSLATE-3041', 'bugfix', 'Auto-QA, Editor general - Wrong whitespace tag numbering leads to non working whitespace added QA check', 'The internal numbering of whitespace tags (newline, tab etc) was not consistent anymore between source and target, therefore the whitespace added auto QA is producing a lot of false positives.', '15'),
('2022-09-20', 'TRANSLATE-3036', 'bugfix', 'VisualReview / VisualTranslation - Visual: Do not update blocked empty segments, fix multiple-variables segments in variable segmentation', 'FIX: Visual: Segments with several singular internal tags seen as variables were not detected
FIX: Visual: A hidden left iframe may prevented a proper update with the current segments in the right iframes layout
ENHANCEMENT: Visual: Empty blocked segments are not updated (=deleted) in the layout anymore', '15'),
('2022-09-20', 'TRANSLATE-3035', 'bugfix', 'SpellCheck (LanguageTool integration) - UI spellcheck is not working after a task with disabled spellcheck was opened', 'Spellcheck remained disabled for other tasks after opening one task where spellcheck was explicitly disabled with liveCheckOnEditing config.', '15'),
('2022-09-20', 'TRANSLATE-3026', 'bugfix', 'Editor general - Jump to task from task overview to project overview', 'Fix for the problem when clicking on jump to task action button in task overview, the project grid is stuck in endless reload loop.', '15');