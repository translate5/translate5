
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-07-13', '[TRANSLATE-2137] - Translate files with InstantTranslate', 'feature', 'Enable the possibility to switch of the file translation in InstantTranslate in the system configuration', '', '8'),
('2020-07-13', '[TRANSLATE-2035] - Add extra column to languageresource log table', 'bugfix', 'The possibilities to show log messages in the GUI for language resources have been enhanced', '', '8'),
('2020-07-13', '[TRANSLATE-2047] - Errormessages on DB Update V 3.4.1', 'bugfix', 'Update possibilites for very old instances has been enhanced', '', '8'),
('2020-07-13', '[TRANSLATE-2120] - Add missing DB constraint to Zf_configuration table', 'bugfix', 'The DB structure has been enhanced', '', '8'),
('2020-07-13', '[TRANSLATE-2129] - Look for and solve open Javascript bugs (theRootCause)', 'bugfix', 'Some Javascript errors reported by users have been fixed', '', '14'),
('2020-07-13', '[TRANSLATE-2131] - APPLICATON_PATH under Windows contains slash', 'bugfix', 'Solve a path issue when loading plug-ins for Windows installations', '', '8'),
('2020-07-13', '[TRANSLATE-2132] - Kpi buttons are visible for editor only users', 'bugfix', 'The button for showing KPIs is now not visible for translators andn reviewers any more, because he can not make use of it in a sense-making way anyway', '', '12'),
('2020-07-13', '[TRANSLATE-2134] - Remove document properties for MS Office and LibreOffice formats of default okapi bconf', 'bugfix', 'By default the document properties are not extracted any more for MS Offfice and Libre Office files', '', '12');