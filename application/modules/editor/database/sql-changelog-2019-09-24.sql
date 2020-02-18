
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-09-24', 'TRANSLATE-1045', 'bugfix', 'Jvascript error: rendered block refreshed at (this is the fix for the doRefreshView override function in the BufferedRenderer)', 'An error occured, when the user sorted segments, while they where loaded', '14'),
('2019-09-24', 'TRANSLATE-1219', 'bugfix', 'Editor iframe body is reset and therefore not usable due missing content', 'translate5 segment editor blocked the whole application, when a segment was opened. This occured in rare situations, but with a new Chrome release this suddenly occured in Chrome for every segment', '14'),
('2019-09-24', 'TRANSLATE-1756', 'bugfix', 'Excel export error with segments containing an equal sign at the beginning', 'Segments starting with an equal sign (=) led to an error in Excel export of segments', '12'),
('2019-09-24', 'TRANSLATE-1796', 'bugfix', 'Error on match analysis tab panel open', 'Opening the match analysis tab led to an error', '12'),
('2019-09-24', 'TRANSLATE-1797', 'bugfix', 'Deleting of terms on import does not work', 'When importing a TBX file into a TermCollection the deletion of terms, that are not present in the TBX did only work, if the complete TermEntry was not present (the corresponding import flag has to be active to delete terms on import)', '12'),
('2019-09-24', 'TRANSLATE-1798', 'bugfix', 'showSubLanguages in TermPortal does not work as it should', 'If the selection of sublanguages is disabled for the search field of the TermPortal, the main language was not present, if only terms with a sublanguage had been part of the TermCollection', '14'),
('2019-09-24', 'TRANSLATE-1799', 'bugfix', 'TermEntry Proposals get deleted, when they should not', 'When importing a TBX file into a TermCollection, TermEntries containing only suggestions were deleted, even though suggestions should not be deleted (the corresponding import flag has to be active to delete terms on import)', '12'),
('2019-09-24', 'TRANSLATE-1800', 'bugfix', 'Uncaught Error: rendered block refreshed at 0 rows', 'When certain rights have been disabled, the language resource panel was blocked, when it should not', '12');