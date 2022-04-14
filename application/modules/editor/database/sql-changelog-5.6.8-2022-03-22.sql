
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-03-22', 'TRANSLATE-2915', 'change', 'Okapi integration - Optimize okapi android xml and ios string settings', 'Settings for android xml and IOs string files were optimized to protect certain tag structures, cdata and special characters', '15'),
('2022-03-22', 'TRANSLATE-2907', 'change', 'InstantTranslate - Improve FileTranslation in InstantTranslate', 'InstantTranslate FileTranslation always starts direct after selecting (or Drag\'nDrop) the file no matter what is configed for runtimeOptions.InstantTranslate.instantTranslationIsActive', '15'),
('2022-03-22', 'TRANSLATE-2903', 'change', 'TermPortal - Batch edit for Process Status and Usage Status attrs', 'TermPortal: batch editing is now possible for Process Status and Usage Status attributes', '15'),
('2022-03-22', 'TRANSLATE-2920', 'bugfix', 'Editor general - REVERT:  TRANSLATE-2345-fix-jumping-cursor', 'ROLLBACK: Fix for jumping cursor reverted', '15'),
('2022-03-22', 'TRANSLATE-2912', 'bugfix', 'Import/Export - reviewHTML.txt import in zip file does not work anymore', 'Fixes a problem where reviewHTML.txt file in the zip import package is ignored.', '15'),
('2022-03-22', 'TRANSLATE-2911', 'bugfix', 'Editor general - Cursor jumps to start of segment', 'FIX: Cursor Jumps when SpellChecker runs and after navigating with arrow-keys ', '15'),
('2022-03-22', 'TRANSLATE-2905', 'bugfix', 'InstantTranslate - No usable error message on file upload error due php max file size reached', 'Custom error message when uploading larger files as allowed in instant-translate.', '15'),
('2022-03-22', 'TRANSLATE-2890', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Module redirect based on initial_page acl', 'Authentication acl improvements', '15'),
('2022-03-22', 'TRANSLATE-2848', 'bugfix', 'Import/Export - TermCollection not listed in import wizard', 'Language resources will be grouped by task in language-resources to task association panel in the import wizard.', '15');