
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-04-08', 'TRANSLATE-1997', 'feature', 'Show help window automatically and remember "seen" click', 'If configured the window pops up automatically and saves the "have seen" info', '14'),
('2020-04-08', 'TRANSLATE-2001', 'feature', 'Support MemoQ comments for im- and export', 'Added comment support to the MemoQ im- and export', '12'),
('2020-04-08', 'TRANSLATE-2007', 'change', 'LanguageResources that cannot be used: Improve error handling', 'Improved the error handling if a chosen language-resource is not available.', '12'),
('2020-04-08', 'TRANSLATE-2022', 'bugfix', 'Prevent huge segments to be send to the termTagger', 'Huge Segments (configurable, default more then 150 words) are not send to the TermTagger anymore due performance reasons.', '12'),
('2020-04-08', 'TRANSLATE-1753', 'bugfix', 'Import Archive for single uploads misses files and can not be reimported', 'In the import archive for single uploads some files were missing, so that the task could not be reimported with the clone button.', '8'),
('2020-04-08', 'TRANSLATE-2018', 'bugfix', 'mysql error when date field as default value has CURRENT_TIMESTAMP', 'The problem is solved in translate5 by adding the current timestamp there', '8'),
('2020-04-08', 'TRANSLATE-2008', 'bugfix', 'Improve TermTagger usage when TermTagger is not reachable', 'The TermTagger is not reachable in the time when it is tagging terms. So if the segments are bigger this leads to timeout messages when trying to connect to the termtagger.', '8'),
('2020-04-08', 'TRANSLATE-2004', 'bugfix', 'send import summary mail to pm on import errors', 'Sends a summary of import errors and warnings to the PM, by default only if the PM did not start the import but via API. Can be overriden by setting always to true in the workflow notification configuration.', '12'),
('2020-04-08', 'TRANSLATE-1977', 'bugfix', 'User can not be assigned to 2 different workflow roles of the same task', 'A user can not added multiple times in different roles to a task. For example: first as translator and additionaly as second reviewer.', '12'),
('2020-04-08', 'TRANSLATE-1998', 'bugfix', 'Not able to edit segment in editor, segment locked', 'This was an error in the multi user backend', '14'),
('2020-04-08', 'TRANSLATE-2013', 'bugfix', 'Not replaced relaisLanguageTranslated in task association e-mail', 'A text fragment was missing in the task association e-mail', '12'),
('2020-04-08', 'TRANSLATE-2012', 'bugfix', 'MessageBus is not reacting to requests', 'The MessageBus-server was hanging in an endless loop in some circumstances.', '8'),
('2020-04-08', 'TRANSLATE-2003', 'bugfix', 'Remove criticical data from error mails', 'Some critical data is removed automatically from log e-mails.', '8'),
('2020-04-08', 'TRANSLATE-2005', 'bugfix', '"Display tracked changes" only when TrackChanges are active for a task', 'The button to toggle TrackChanges is disabled if TrackChanges are not available due workflow reasons', '14');