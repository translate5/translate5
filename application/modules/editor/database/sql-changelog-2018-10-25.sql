
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-10-25', 'TRANSLATE-1339', 'feature', 'InstantTranslate-Portal: integration of SDL Language Cloud, Terminology, TM and MT resources and similar', 'With the InstantTranslate-Portal several language resource can be integrated in translate5. This are SDL Language Cloud, Terminology, TM and MT resources and similar. With this feature the Plugin MatchResource was renamed to LanguageResource and moved into the core code. Also Terminology Collections are now available and maintainable via the LanguageResource Panel.', '14'),
('2018-10-25', 'TRANSLATE-1362', 'feature', 'Integrate Google Translate as language resource', 'Integrate Google Translate as language resource', '12'),
('2018-10-25', 'TRANSLATE-1162', 'feature', 'GroupShare Plugin: Use SDL Trados GroupShare as Language-Resource', 'GroupShare Plugin: Use SDL Trados GroupShare as Language-Resource', '12'),
('2018-10-25', 'VISUAL-56', 'change', 'VisualReview: Change text that is shown, when segment is not connected', 'VisualReview: Change text that is shown, when segment is not connected', '12'),
('2018-10-25', 'TRANSLATE-1447', 'bugfix', 'Escaping XML Entities in XLIFF 2.1 export (like attribute its:person)', 'Escaping XML Entities in XLIFF 2.1 export (attributes like its:person were unescaped)', '12'),
('2018-10-25', 'TRANSLATE-1448', 'bugfix', 'translate5 stops loading with Internet Explorer 11', 'translate5 stops loading with Internet Explorer 11', '14');