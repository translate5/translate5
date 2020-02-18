
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-02-17', 'TRANSLATE-1960', 'feature', 'Define if source or target is connected with visualReview on import', 'The user can choose now if the uploaded PDF corresponds to the source or target content.', '96'),
('2020-02-17', 'TRANSLATE-1831', 'feature', 'Integrate DeepL in translate5 (Only for users with support- and development)', 'The DeepL Integration is only available for users with a support- and development contract. The Plug-In must be activated and the DeepL key configured in the config for usage. See https://confluence.translate5.net/display/TPLO/DeepL', '98'),
('2020-02-17', 'TRANSLATE-1455', 'feature', 'Deadlines and assignment dates for every role of a task', 'This was only possible for the whole task, now per each associated user a dedicated deadline can be defined.', '96'),
('2020-02-17', 'TRANSLATE-1959', 'change', 'InstantTranslate: handle tags in the source as part of the source-text', 'InstantTranslate is now supposed to handle tags in the source as part of the source-text.', '98'),
('2020-02-17', 'TRANSLATE-1918', 'change', 'VisualReview: log segmentation results', 'The results of the segmentation is logged into the task log and is sent via email.', '96'),
('2020-02-17', 'TRANSLATE-1916', 'change', 'Change supported browser message', 'The message about the supported browsers was changed, also IE11 is no not supported anymore.', '98'),
('2020-02-17', 'TRANSLATE-905', 'change', 'Improve maintenance mode', 'The maintenance mode has now a free-text field to display data to the users, also the maintenance can be announced to all admin users. See https://confluence.translate5.net/display/TIU/install-and-update.sh+functionality', '64'),
('2020-02-17', 'TRANSLATE-1981', 'bugfix', 'Sorting the bookmark column produces errors', 'Sorting the by default hidden bookmark column in the segment table produced an error.', '98'),
('2020-02-17', 'TRANSLATE-1975', 'bugfix', 'Reenable Copy & Paste from term window', 'Copy and paste was not working any more for the terms listed in the segment meta panel on the right.', '98'),
('2020-02-17', 'TRANSLATE-1973', 'bugfix', 'TrackChanges should not added by default on translation tasks without a workflow with CTRL+INS', 'When using CTRL+INS to copy the source to the target content, TrackChanges should be only added for review tasks in any case.', '96'),
('2020-02-17', 'TRANSLATE-1972', 'bugfix', 'Default role in translation tasks should be translator not reviewer', 'This affects the front-end default role in the task user association window.', '96'),
('2020-02-17', 'TRANSLATE-1971', 'bugfix', 'segments excluded with excluded framing ept and bpt tags could not be exported', 'Very seldom error in combination with segments containing ept and bpt tags.', '96'),
('2020-02-17', 'TRANSLATE-1970', 'bugfix', 'Unable to open Instant-translate/Term-portal from translate5 buttons', 'This bug was applicable only if the config runtimeOptions.logoutOnWindowClose is enabled.', '96'),
('2020-02-17', 'TRANSLATE-1968', 'bugfix', 'Correct spelling mistake', 'Fixed a german typo in the user notification on association pop-up.', '96'),
('2020-02-17', 'TRANSLATE-1969', 'bugfix', 'Adding hunspell directories for spell checking does not work for majority of languages', 'Using external hunspell directories via LanguageTool is working now. Usage is described in https://confluence.translate5.net/display/TIU/Activate+additional+languages+for+spell+checking', '96'),
('2020-02-17', 'TRANSLATE-1966', 'bugfix', 'File-system TBX import error on term-collection create', 'The file-system based TBX import is now working again.', '64'),
('2020-02-17', 'TRANSLATE-1964', 'bugfix', 'OpenID: Check for provider roles before the default roles check', 'OpenID was throwing an exception if the default roles are not set for the client domain even if the openid provider provide the roles in the claims response.', '64'),
('2020-02-17', 'TRANSLATE-1963', 'bugfix', 'Tbx import fails when importing a file', 'On TBX import the TBX parser throws an exception and the import process is stopped only when the file is uploaded from the users itself.', '64'),
('2020-02-17', 'TRANSLATE-1962', 'bugfix', 'SDLLanguageCloud: status always returns unavailable', 'Checking the status was always returning unavailable, although the LanguageResource is available and working.', '96'),
('2020-02-17', 'TRANSLATE-1919', 'bugfix', 'taskGuid column is missing in LEK_comment_meta', 'A database column was missing.', '64'),
('2020-02-17', 'TRANSLATE-1913', 'bugfix', 'Missing translation if no language resource is available for the language combination', 'Just added the missing English translation.', '96');