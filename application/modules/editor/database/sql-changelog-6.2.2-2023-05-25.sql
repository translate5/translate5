
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-05-25', 'TRANSLATE-3172', 'feature', 'file format settings - XML File Filter Settings for Figma', 'Assigned "*.figma" file -extension for the figma file-filter setting', '15'),
('2023-05-25', 'TRANSLATE-3344', 'bugfix', 'VisualReview / VisualTranslation - FIX symlink creation in visual for invalid symlinks', 'Symlink creation in visual might not refresh outdated symlinks', '15'),
('2023-05-25', 'TRANSLATE-3339', 'bugfix', 'Editor general - HOTFIX: several smaller fixes', 'ENHANCEMENT: improved event-msg of the "too many segments per trans-unit" exception
FIX: increased max segments per transunit to 250
ENHANCEMENT: Add all OKAPI versions when using the autodiscovery for development', '15'),
('2023-05-25', 'TRANSLATE-3338', 'bugfix', 'SNC - Clean SNC numbers check debug output', 'Clean the debug output in SNC numbers check library.', '15'),
('2023-05-25', 'TRANSLATE-3337', 'bugfix', 'API - Plugin ConnectWorldserver: wrong attribut for Visual', 'Plugin ConnectWordserver: changed attribute for visual from "layout_source_translate5" to new name "translate5_layout_source"', '15');