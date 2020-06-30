
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-06-30', 'TRANSLATE-1774', 'feature', 'Integrated NEC-TM with translate5 as Language-Resource', 'Integrated NEC-TM with translate5 as Language-Resource', '12'),
('2020-06-30', 'TRANSLATE-2052', 'feature', 'Added capabilities to assign different segments of the same task to different users', 'Added capabilities to assign different segments of the same task to different users', '12'),
('2020-06-30', 'TRANSLATE-2094', 'bugfix', 'Removed workflow action „setReviewersFinishDate“', 'Removed workflow action „setReviewersFinishDate“', '12'),
('2020-06-30', 'TRANSLATE-2096', 'bugfix', 'Use FontAwesome5 for all icons in translate5', 'Use FontAwesome5 for all icons in translate5', '8'),
('2020-06-30', 'TRANSLATE-2097', 'bugfix', 'Minimum characters requirement for client name in clients form is now 1', 'Minimum characters requirement for client name in clients form is now 1', '12'),
('2020-06-30', 'TRANSLATE-2101', 'bugfix', 'Disable automated translation xliff creation from notFoundTranslation xliff in production instances', 'Disable automated creation of a xliff-file from notFoundTranslation xliff in production instances', '8'),
('2020-06-30', 'TRANSLATE-2102', 'bugfix', 'VisualTranslation: Commas in the PDF filenames (formerly leading to failing imports) are now automatically corrected', 'VisualTranslation: Commas in PDF filenames (formerly leading to failing imports) are now automatically corrected', '14'),
('2020-06-30', 'TRANSLATE-2104', 'bugfix', 'The KPI Button works as expected now', 'The KPI Button works as expected now', '14'),
('2020-06-30', 'TRANSLATE-2105', 'bugfix', 'The serverside check for the pixel-based length check works as expected with multiple lines now', 'The serverside check for the pixel-based length check works as expected with multiple lines now', '14'),
('2020-06-30', 'TRANSLATE-2106', 'bugfix', 'Whitespace and blanks from user login and password in the login form are automatically removed', 'Whitespace and blanks from user login and password in the login form are automatically removed', '14'),
('2020-06-30', 'TRANSLATE-2109', 'bugfix', 'Remove string length restriction flag', 'Remove string length restriction configuration option', '8'),
('2020-06-30', 'TRANSLATE-2121', 'bugfix', 'Fixed issues with filenames on NEC-TM tmx export and import', 'Fixed issues with filenames on NEC-TM tmx export and import', '12');