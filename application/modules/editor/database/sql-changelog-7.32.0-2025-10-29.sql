
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-10-29', 'TRANSLATE-4444', 'feature', 'InstantTranslate - InstantTranslate: file translation into multiple languages at once', 'New feature where files in instant-translate can be translated to multiple languages. To enable it, activate: runtimeOptions.plugins.InstantTranslate.enableMultiLanguageFileTranslation ', '15'),
('2025-10-29', 'TRANSLATE-3710', 'change', 'usability editor - Introduce new processing status "draft" for segments', 'Introduced new processing status "draft" for segments', '15'),
('2025-10-29', 'TRANSLATE-5095', 'bugfix', 'Search & Replace (editor) - Replace button click produces Js error', '[🐞 Fix] Fixed error that might have popped up in the dev tools when replacing a text with search/replace tool', '15'),
('2025-10-29', 'TRANSLATE-5094', 'bugfix', 'Import/Export - Replace Task-Meta for Okapi to resolve Import Event interdependencies', 'Refactor Task-Meta handling in OKAPI to plugin-specific data to resolve Import ', '15'),
('2025-10-29', 'TRANSLATE-5036', 'bugfix', 'LanguageResources - Pretranslation crashes on specific language combinations', 'Resolved match analysis / pre-translation crashes for specific language combinations.', '15'),
('2025-10-29', 'TRANSLATE-5007', 'bugfix', 'InstantTranslate - Improve filetranslation speed for small tasks', 'Instanttranslate file translation speed for small translations improved', '15');