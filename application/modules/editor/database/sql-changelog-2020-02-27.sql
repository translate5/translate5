
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-02-27', 'TRANSLATE-1987', 'feature', 'Load custom page in the editors branding area', 'Custom content in the branding area can now be included via URL', '64'),
('2020-02-27', 'TRANSLATE-1927', 'feature', 'Pre-translate documents in InstantTranslate', 'InstantTranslate is now able to translate documents', '98'),
('2020-02-27', 'TRANSLATE-1989', 'bugfix', 'Erroneously locked segment on tasks with only one user and no simultaneous usage mode', 'Some segments were locked in the frontend although only one user was working on the task.', '98'),
('2020-02-27', 'TRANSLATE-1988', 'bugfix', 'Enhanced filters button provides drop-down with to much user names', 'Only the users associated to the tasks visible to the current user should be visible.', '96'),
('2020-02-27', 'TRANSLATE-1986', 'bugfix', 'Unable to import empty term with attributes', 'An error occurs when importing term with empty term value, valid term attributes and valid term id.', '64'),
('2020-02-27', 'TRANSLATE-1980', 'bugfix', 'Button "open task" is missing for unaccepted jobs', 'For jobs that are not accepted so far, the "open task" action icon is missing. It should be shown again.', '98'),
('2020-02-27', 'TRANSLATE-1978', 'bugfix', 'In InstantTranslate the Fuzzy-Match is not highlighted correctly', 'The source difference of fuzzy matches was not shown correctly.', '64'),
('2020-02-27', 'TRANSLATE-1911', 'bugfix', 'Error if spellcheck answer returns from server after task was left already', 'When the task was left before the spellcheck answer was returned from the server an error occured.', '98'),
('2020-02-27', 'TRANSLATE-1841', 'bugfix', 'pc elements in xliff 2.1 exports are not correctly nested in conjunction with TrackChanges Markup', 'The xliff 2.1 export produced invalid XML in some circumstances.', '96');