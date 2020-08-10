
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-05-07', 'TRANSLATE-1136', 'feature', 'Check for content outside of mrk-tags (xliff)', 'Import fails if there is other content as whitespace or tags outside of mrk mtype seg texts.', '12'),
('2018-05-07', 'TRANSLATE-1192', 'feature', 'Length restriction: Add length of several segments', 'Length calculation is now done over multiple segments (mrks) of one trans-unit', '14'),
('2018-05-07', 'TRANSLATE-1130', 'feature', 'Show specific whitespace-tag instead just internal tag symbol', 'Internal tags masking whitespace are now displaying which and how many characters are masked', '14'),
('2018-05-07', 'TRANSLATE-1190', 'feature', 'Plugin: Automatic import of TBX files from Across', 'Plugin: Automatic import of TBX files from Across', '8'),
('2018-05-07', 'TRANSLATE-1189', 'feature', 'Flexible Term and TermEntry Attributes', 'Term Attributes are now also imported and stored in the internal term DB', '12'),
('2018-05-07', 'TRANSLATE-1187', 'feature', 'Introduce TermCollections to share terminology between different tasks', 'Introduce TermCollections to share terminology between different tasks', '12'),
('2018-05-07', 'TRANSLATE-1188', 'feature', 'Extending the TBX-import', 'Extending the TBX-import so that terms could be added / updated in existing term collections.', '12'),
('2018-05-07', 'TRANSLATE-1186', 'feature', 'new system role "termCustomerSearch"', 'new system role termCustomerSearch for new term search portal', '12'),
('2018-05-07', 'TRANSLATE-1184', 'feature', 'Client management', 'Client management, deactivated by default', '12'),
('2018-05-07', 'TRANSLATE-1185', 'feature', 'Add field "end client" to user management', 'Add field "end client" to user management', '12'),
('2018-05-07', 'VISUAL-30', 'change', 'The connection algorithm connects segments only partially', 'The connection algorithm connects segments only partially', '12'),
('2018-05-07', 'TRANSLATE-1229', 'bugfix', 'xliff 1.2 export deletes tags', 'On xliff 1.2 export some tags are lost on export, if the tag was the only content in a mrk tag.', '12'),
('2018-05-07', 'TRANSLATE-1236', 'bugfix', 'User creation via API should accept a given userGuid', 'On creating users via API always a UserGuid was generated, and no guid could be given.', '8'),
('2018-05-07', 'TRANSLATE-1235', 'bugfix', 'User creation via API produces errors on POST/PUT with invalid content', 'User creation via API produces errors on POST/PUT with given invalid content', '8'),
('2018-05-07', 'TRANSLATE-1128', 'bugfix', 'Selecting segment and scrolling leads to jumping of grid ', 'Selecting segment and scrolling leads to jumping of grid, this is fixed now.', '14'),
('2018-05-07', 'TRANSLATE-1233', 'bugfix', 'Keyboard Navigation through grid looses focus', 'Keyboard Navigation through grid looses focus', '14');