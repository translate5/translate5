
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-01-17', 'TRANSLATE-950', 'feature', 'Implement a user hierarchy for user listing and editing', 'If the user has not the right to see all users, he sees just the users he has created (his child users), and the recursively their child users too.', '12'),
('2018-01-17', 'TRANSLATE-1089', 'feature', 'Create segment history entry when set autostatus untouched, auto-set and reset username on unfinish', 'If reverting in the workflow from step translator check back to proofreading, all segments with autostate untouched, auto-set are reset to their original autostate. This is tracked in the segment history right now. Also the previous editor is set in the segment (on setting it to untouched the responsible proofreader was entered).', '14'),
('2018-01-17', 'TRANSLATE-1099', 'feature', 'Exclude framing internal tags from xliff import', 'On importing XLIFF proofreading tasks, internal tags just encapsulating the segment content are not imported anymore. This makes editing easier.', '14'),
('2018-01-17', 'TRANSLATE-941', 'feature', 'New front-end rights enable more fine grained access to features in the GUI', 'New front-end rights enable more fine grained access to features in the GUI', '12'),
('2018-01-17', 'TRANSLATE-942', 'feature', 'New task attributes tab in task properties window', 'Some task attributes (like task name) can be changed via the new task attributes panel.', '12'),
('2018-01-17', 'TRANSLATE-1090', 'feature', 'Implement an ACL right to allow the usage of roles in the user administration panel.', 'Through that new ACL right it can be controlled which ACL roles the current user can set to other users.', '12'),
('2018-01-17', 'Integrate segmentation rules for EN in Okapi default bconf-file', 'change', 'Integrate segmentation rules for EN in Okapi default bconf-file', 'Integrate segmentation rules for EN in Okapi default bconf-file', '12'),
('2018-01-17', 'TRANSLATE-1091', 'change', 'Rename "language" field/column in user grid / user add window', 'Trivial text change in the user administration.', '12'),
('2018-01-17', 'TRANSLATE-1101', 'bugfix', 'Using Translate5 in internet explorer leads sometimes to logouts while application load', 'It can happen that users of internet explorer were logged out automatically directly after the application was loaded.', '14'),
('2018-01-17', 'TRANSLATE-1086', 'bugfix', 'Leaving a visual review task in translate5 leads to an error in IE 11', 'Leaving a visual review task in translate5 leads to an error in IE 11', '14'),
('2018-01-17', 'T5DEV-219', 'bugfix', 'Error Subsegment img found on saving some segments with tags and enabled track changes', 'Under some circumstances the saving of a segment fails if track changes is active, and the segment contains internal tags.', '14');
