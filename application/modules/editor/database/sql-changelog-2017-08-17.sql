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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2017-08-17', 'TRANSLATE-957', 'change', 'XLF Import: Different tag numbering in review tasks on tags swapped position from source to target', 'Internal tags were numbered different in short tag view mode between source and target, if the tag position swapped between source and target', '14'),
('2017-08-17', 'TRANSLATE-955', 'change', 'XLF Import: Whitespace import in XLF documents', 'The application reacts differently on existing whitespace in XLF documents. The default behaviour of the application can be configured to ignore or preserve whitespace. In any case the xml:space attribute is respected.', '14'),
('2017-08-17', 'TRANSLATE-925', 'bugfix', 'support xliff 1.2 as import format - several smaller fixes', 'Several smaller issues were fixed for the XLF import.', '12'),
('2017-08-17', 'TRANSLATE-971', 'bugfix', 'Importing an XLF with comments produces an error', 'XLF Import could not deal with XML comments', '12'),
('2017-08-17', 'TRANSLATE-937', 'bugfix', 'translate untranslated GUI elements', 'Added two english translations', '14'),
('2017-08-17', 'TRANSLATE-968', 'bugfix', 'Ignore CDATA blocks in the Import XMLParser', 'XLF Import could not deal with CDATA blocks', '12'),
('2017-08-17', 'TRANSLATE-967', 'bugfix', 'SDLXLIFF segment attributes could not be parsed', 'In special cases some SDLXLIFF attributes could not be parsed', '12'),
('2017-08-17', 'MITTAGQI-42', 'bugfix', 'Changes.xliff filename was invalid under windows; ErrorLogging was complaining about a missing HTTP_HOST variable', 'The generated changes.xliff filename was changed for windows installations, error logging can deal now with missing HTTP_HOST variable.', '12'),
('2017-08-17', 'TRANSLATE-960', 'bugfix', 'Trying to delete a task user assoc entry produces an exception with enabled JS Logger', 'Only installations with activated JS Logger were affected.', '12');