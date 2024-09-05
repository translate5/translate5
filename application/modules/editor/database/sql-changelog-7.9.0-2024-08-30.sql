
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-08-30', 'TRANSLATE-3938', 'feature', 'Import/Export - Export segments as html', 'Introduce HTML Task export feature', '15'),
('2024-08-30', 'TRANSLATE-4109', 'change', 'VisualReview / VisualTranslation - Add PDF Version check to pdfconverter container', 'IMPROVEMENT Visual: warn, when imported PDF is of X-4 subtype (which frequently create problems when converting)', '15'),
('2024-08-30', 'TRANSLATE-3960', 'change', 'Editor general - Test PXSS in all input fields of the application', 'Security: fixed remaining PXSS issues by adding frontend-sanitization', '15'),
('2024-08-30', 'TRANSLATE-3518', 'change', 'LanguageResources - Infrastructure for using "translate5 language resources" as training resources for MT', 'Cross Language Resource synchronisation mechanism and abstraction layer introduced into application.
From now on we have mechanic to connect different Language Resource types (like t5memory, Term Collection, etc) for data synchronisation if it is possible', '15'),
('2024-08-30', 'TRANSLATE-4161', 'bugfix', 'TM Maintenance - Segments batch deletion no longer works', 'Fix segments batch deletion in TM Maintenance', '15'),
('2024-08-30', 'TRANSLATE-4159', 'bugfix', 't5memory - Html entities are not escaped in TMX export', 'Escape tag like enties for t5memory', '15'),
('2024-08-30', 'TRANSLATE-4157', 'bugfix', 'TermPortal, TM Maintenance - Uncaught TypeError: Ext.scrollbar._size is null', 'FIXED: problem with production builds of TermPortal and TMMaintenance', '15'),
('2024-08-30', 'TRANSLATE-4152', 'bugfix', 'LanguageResources - reenable usage of sub-languages in as source of lang synch with major languages as target of lang synch', 'Changed language resource synchronisation makes it possible to connect source language resource with a sub-language to a target language resource with a major language', '15'),
('2024-08-30', 'TRANSLATE-3452', 'bugfix', 'Auto-QA - Automatic tag correction completes to many tags on Excel re-import', 'Excel Re-import: taglike placeholders are now escaped to prevent errors in the UI', '15'),
('2024-08-30', 'TRANSLATE-3079', 'bugfix', 'Editor general, Security Related - Self-XSS is still possible', 'Security: fixed PXSS issuesin grids in the frontend', '15');