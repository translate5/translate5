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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2017-09-14', 'TRANSLATE-994', 'feature', 'Support RTL languages in the editor', 'The editor now supports RTL languages. Which language should be be rendered as rtl language can be defined per language in the LEK_languages table.', '14'),
('2017-09-14', 'TRANSLATE-974', 'feature', 'Save all segments of a task to a TM', 'All segments of chosen tasks can be saved in a bulk manner into a TM of OpenTM2', '12'),
('2017-09-14', 'TRANSLATE-925', 'change', 'support xliff 1.2 as import format - improve fileparser to file extension mapping', 'Improves internal handling of file suffixes. ', '12'),
('2017-09-14', 'TRANSLATE-926', 'change', 'ExtJS 6.2 update', 'The graphical user interface ExtJS is updated to version ExtJS 6.2', '8'),
('2017-09-14', 'TRANSLATE-972', 'change', 'translate5 does not check, if there are relevant files in the import zip', 'It could happen that a import ZIP could not contain any file to be imported. This produced unexpected errors.', '12'),
('2017-09-14', 'TRANSLATE-981', 'change', 'User inserts content copied from rich text wordprocessing tool', 'Pasting translation content from another website or word processing tool inserted also invisible formatting characters into the editor. This led to errors on saving the segment.', '14'),
('2017-09-14', 'TRANSLATE-984', 'bugfix', 'The editor converts single quotes to the corresponding HTML entity', 'The XLF Import introduced a bug on typing single quotes in the editor, they were stored as HTML entity instead as character. A migration script is provided with this fix.', '14'),
('2017-09-14', 'TRANSLATE-997', 'bugfix', 'Reset password works only once without reloading the user data', 'Very seldom issue in the user administration interface, a user password could only be resetted once in the session.', '12'),
('2017-09-14', 'TRANSLATE-915', 'bugfix', 'JS Error: response is undefined', 'Some seldom errors in the workflow handling on the server triggers an error which was not properly handled in the GUI, this is fixed now.', '12');