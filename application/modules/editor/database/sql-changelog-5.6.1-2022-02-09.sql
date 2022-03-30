
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-02-09', 'TRANSLATE-2810', 'change', 'TermPortal - All roles should be able to see all terms with all process status.', 'unprocessed-terms are now searchable even if user has no termProposer-role', '15'),
('2022-02-09', 'TRANSLATE-2809', 'change', 'TermPortal - Reimport term should be only possible for tasks created by the Term-Translation workflow', 'Reimport of terms back to their TermCollections is possible only for task, created via TermPortal terms transfer function', '15'),
('2022-02-09', 'TRANSLATE-2825', 'bugfix', 'Task Management - Multiple files with multiple pivot files can not be uploaded', 'Multiple files with multiple pivot files can not be added in the task creation wizard. The pivot files are marked as invalid.', '15'),
('2022-02-09', 'TRANSLATE-2824', 'bugfix', 'Okapi integration - Enable aggressive tag clean-up in Okapi for MS Office files by default', 'Office often creates an incredible mess with inline tags, if users edit with character based markup.
Okapi has an option to partly clean this up when converting an office file.
This option is now switched on by default.', '15'),
('2022-02-09', 'TRANSLATE-2821', 'bugfix', 'Auto-QA - Empty segment check does not report completely empty segments', 'Segments with completely empty targets are now counted in AutoQA: Empty-check', '15'),
('2022-02-09', 'TRANSLATE-2817', 'bugfix', 'VisualReview / VisualTranslation - Solve Problems with CommentNavigation causing too much DB strain', 'FIX: Loading of Comment Navigation may was slow', '15'),
('2022-02-09', 'TRANSLATE-2816', 'bugfix', 'Comments - Comment Overview performance problem and multiple loading calls', '"AllComments" store: Prevent multiple requests by only making new ones when none are pending.', '15'),
('2022-02-09', 'TRANSLATE-2815', 'bugfix', 'Import/Export, Task Management - Upload time out for bigger files', 'The upload timeout in the import wizard is increased to prevent timeouts for slower connections.', '15'),
('2022-02-09', 'TRANSLATE-2814', 'bugfix', 'VisualReview / VisualTranslation - Solve Problems with Caching of plugin resources', 'FIX: Resources needed for the visual may become cached too long generating JS errors', '15'),
('2022-02-09', 'TRANSLATE-2808', 'bugfix', 'TermPortal - Mind sublanguages while terms transfer validation', 'Sublanguages are now respected while terms transfer validation', '15'),
('2022-02-09', 'TRANSLATE-2803', 'bugfix', 'Editor general - Displaying the logos causes issues', 'If the consortium logos were shown with a configured delay on the application startup, this may lead to problems when loading the application via an URL containing a task ID to open that task directly.', '15'),
('2022-02-09', 'TRANSLATE-2802', 'bugfix', 'Task Management - Add isReview and isTranslation methods to task entity', 'Internal functions renamed.', '15'),
('2022-02-09', 'TRANSLATE-2685', 'bugfix', 'Editor general - Error on pasting tags inside segment-editor', 'There was JS problem when editing a segment and pasting external content containing XML fragments.', '15');