
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-08-04', 'TRANSLATE-2580', 'feature', 'Add segment length check to AutoQA', 'AutoQA now incorporates a check of the(pixel based)  segment-length', '15'),
('2021-08-04', 'TRANSLATE-2416', 'feature', 'Create PM-light system role', 'A new role PM-light is created, which may only administrate its own projects and tasks and has no access to user management or language resources management.', '15'),
('2021-08-04', 'TRANSLATE-2586', 'change', 'Check the URLs in the reviewHtml.txt file for the visual', 'ENHANCEMENT: Warn and clean visual source URLs that can not be imported because they have a fragment "#"
ENHANCEMENT: Skip duplicates and clean URLs in the reviewHtml.txt file', '15'),
('2021-08-04', 'TRANSLATE-2583', 'change', 'Save config record instead of model sync', 'Code improvements in the configuration overview grid.', '15'),
('2021-08-04', 'TRANSLATE-2589', 'bugfix', 'Exclude meta data of images for word files by default', 'By default translate5 will now not extract any more meta data of images, that are embedded in MS Word files.', '15'),
('2021-08-04', 'TRANSLATE-2587', 'bugfix', 'Improve error logging', 'Improves error messages in instant-translate.', '15'),
('2021-08-04', 'TRANSLATE-2585', 'bugfix', 'Evaluate auto_set_role acl for OpenID authentications', 'All missing mandatory translate roles for users authentication via SSO will be automatically added.', '15'),
('2021-08-04', 'TRANSLATE-2584', 'bugfix', 'Across XLF with translate no may contain invalid segmented content', 'Across XLF may contain invalid segmented content for not translatable (not editable) segments. This is fixed by using the not segment content in that case.', '15'),
('2021-08-04', 'TRANSLATE-2570', 'bugfix', 'AutoQA checks blocked segments / finds unedited fuzzy errors in unedited bilingual segments', 'ENHANCEMENT: blocked segments will no longer be evaluated in the quality-management, only if they have structural internal tag-errors they will appear in a new category for this
FIX: Missing internal tags may have been detected in untranslated empty segments
FIX: Added task-name & guid to error-logs regarding structural internal tag errors
FIX: Quality-Management is now bound to a proper ACL
FIX: Re-establish proper layout of action icons in Task-Grid

', '15'),
('2021-08-04', 'TRANSLATE-2564', 'bugfix', 'Do not render MQM-Tags parted by overlappings', 'FIX: MQM-Tags now are visualized with overlappings unresolved (not cut into pieves)
', '15');