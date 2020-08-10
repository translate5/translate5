
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-04-16', 'TRANSLATE-1218', 'change', 'XLIFF Import: preserveWhitespace per default to true', 'The new default value for the preserveWhitespace config for XLIFF Imports should be set to true. Currently its false, so that all whitespace is ignored in the XLIFF Import. This should be changed.', '12'),
('2018-04-16', 'Renamed all editor modes', 'change', 'Renamed all editor modes', 'Renamed all editor modes', '14'),
('2018-04-16', 'TRANSLATE-1154', 'bugfix', 'XLIFF import does not set match rate', 'XLIFF Import is now importing the matchrate (match-quality) of the alt-trans tags containing the used TM references.', '12'),
('2018-04-16', 'TRANSLATE-1215', 'bugfix', 'TrackChanges: JS Exception on CTRL+. usage', 'Fixed some error when using CTRL + . with TrackChanges.', '14'),
('2018-04-16', 'TRANSLATE-1140', 'bugfix', 'Search and Replace: Row editor is not displayed after the first match in certain situations', 'Search and Replace: Row editor is not displayed after the first match in certain situations', '14'),
('2018-04-16', 'TRANSLATE-1219', 'bugfix', 'MatchResource Plug-in: Editor iframe body is reset and therefore not usable due missing content', 'With activated MatchResource Plug-in the editor iframe body was reset and therefore the segment editor was not usable.', '12'),
('2018-04-16', 'VISUAL-28', 'bugfix', 'Opening of visual task in IE 11 throws JS error', 'Opening of visual task in IE 11 throws JS error', '14');