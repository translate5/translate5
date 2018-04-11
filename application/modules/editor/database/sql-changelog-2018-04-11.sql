
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-04-11', 'TRANSLATE-1130', 'feature', 'Show specific whitespace tag for tags protecting whitespace', 'Internal tags protecting whitespace are showing directly on the tag which type of content is protected by the tag', '14'),
('2018-04-11', 'TRANSLATE-1132', 'feature', 'Tags which are protecting whitespace can be deleted in the editor', 'Tags which are protecting whitespace can be deleted in the editor - if enabled via config', '14'),
('2018-04-11', 'TRANSLATE-1127', 'feature', 'XLIFF Import: Whitespace outside mrk tags is preserved', 'XLIFF Import: Whitespace outside mrk tags is preserved during import for export', '12'),
('2018-04-11', 'TRANSLATE-1137', 'feature', 'Show bookmark and comment icons in autostatus column', 'Show bookmark and comment icons in autostatus column', '14'),
('2018-04-11', 'TRANSLATE-1058', 'feature', 'Send changelog via email to admin users when updating with install-and-update script', 'Send changelog via email to admin users when updating with install-and-update script', '8'),
('2018-04-11', 'T5DEV-217', 'change', 'remaining search and replace todos', 'remaining todos in the search and replace plugin', '14'),
('2018-04-11', 'TRANSLATE-1200', 'change', 'Refactor images of internal tags to SVG content instead PNG', 'Refactor images of internal tags to SVG content instead PNG', '12'),
('2018-04-11', 'TRANSLATE-1209', 'bugfix', 'TrackChanges: content tags in DEL INS tags are not displayed correctly in full tag mode', 'TrackChanges: content tags in DEL INS tags are not displayed correctly in full tag mode', '14'),
('2018-04-11', 'TRANSLATE-1212', 'bugfix', 'TrackChanges: deleted content tags in a DEL tag can not readded via CTRL+, + Number', 'The keyboard shortcut CTRL+, + number was not working in conjunction with TrackChanges.', '14'),
('2018-04-11', 'TRANSLATE-1210', 'bugfix', 'TrackChanges: Using repetition editor on segments where a content tag is in a DEL and INS tag throws an exception', 'TrackChanges: Using repetition editor on segments where a content tag is in a DEL and INS tag throws an exception', '14'),
('2018-04-11', 'TRANSLATE-1194', 'bugfix', 'TrackChanges: remove unnecessary whitespace remaining at the place of deleted content', 'When the export removes deleted words, no double spaces must be left. - replace deleted content that has a (= one) whitespace at both ends with one single whitespace first', '12'),
('2018-04-11', 'TRANSLATE-1124', 'bugfix', 'store whitespace tag metrics into internal tag', 'The whitespace tag metrics are stored into the internal tag, so that the min max length calculation is working correctly.', '14'),
('2018-04-11', 'VISUAL-24', 'bugfix', 'visualReview: After adding a comment, a strange white window appears', 'After adding a comment, a strange white window appears, this is fixed right now.', '14');