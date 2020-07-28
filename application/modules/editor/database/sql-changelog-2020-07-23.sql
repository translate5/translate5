
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-07-23', 'TRANSLATE-2139', 'change', 'Pre-translation exceptions', 'The error handling for integrated language resources has been improved', '12'),
('2020-07-23', 'TRANSLATE-2117', 'bugfix', 'LanguageResources: update & query segments with tags', 'For PangeaMT and NEC-TM the usage of internal tags was provided / fixed and a general mechanism for language resources for this issue introduced', '12'),
('2020-07-23', 'TRANSLATE-2127', 'bugfix', 'Xliff files with file extension xml are passed to okapi instead of translate5s xliff parser', 'XML files that acutally contain XLIFF had been passed to Okapi instead of the translate5 xliff parser, if they startet with a BOM (Byte order mark)', '12'),
('2020-07-23', 'TRANSLATE-2138', 'bugfix', 'Visual via URL does not work in certain cases', 'In some cases passing the layout via URL did not work', '12'),
('2020-07-23', 'TRANSLATE-2142', 'bugfix', 'Missing property definition', 'A small fix', '8'),
('2020-07-23', 'TRANSLATE-2143', 'bugfix', 'Problems Live-Editing: Shortened segments, insufficient whitespace', 'Major enhancements in the „What you see is what you get“ feature regarding whitespace handling and layout issues', '14'),
('2020-07-23', 'TRANSLATE-2144', 'bugfix', 'Several problems with copy and paste content into an edited segment', '', '14'),
('2020-07-23', 'TRANSLATE-2146', 'bugfix', 'Exclude materialized view check in segments total count', 'A small fix', '8');