
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-02-28', 'TRANSLATE-1589', 'feature', 'Separate button to sync the GroupShare TMs in LanguageResources panel', 'Groupshare TMs are now synchronized manually instead automatically', '12'),
('2019-02-28', 'TRANSLATE-1586', 'feature', 'Close session on browser window close', 'The new default behaviour on closing the application window (browser) is to log out the user. This can be disabled via configuration (runtimeOptions.logoutOnWindowClose).', '14'),
('2019-02-28', 'TRANSLATE-1581', 'feature', 'Click on PM Name in task overview opens e-mail program to send an e-mail to the PM', 'This feature must be enabled via config: runtimeOptions.frontend.tasklist.pmMailTo', '14'),
('2019-02-28', 'TRANSLATE-1457', 'feature', 'Use OpenID Connect optionally for authentication and is now able to run under different domains', 'OpenID Connect can be optionally used for authentication. Therefore each customer cam be configured with a separate entry URL. If this URL is used to access the application the OpenID Configuration for that Customer is used.', '8'),
('2019-02-28', 'TRANSLATE-1583', 'change', 'VisualReview: Change the button layout in "leave visual review" messagebox', 'The button layout in "leave visual review" messagebox was changed.', '14'),
('2019-02-28', 'TRANSLATE-1584', 'change', 'Rename "Autostatus" to "Bearbeitungsstatus" in translate5 editor (german GUI)', 'Just a wording change in german from "Autostatus" to "Bearbeitungsstatus".', '14'),
('2019-02-28', 'TRANSLATE-1542', 'change', 'InstantTranslate: Improve language selection in InstantTranslate', 'Improved the language selection in InstantTranslate', '14'),
('2019-02-28', 'TRANSLATE-1587', 'change', 'Enable session delete to delete via internalSessionUniqId', 'For API usage it makes sense to delete sessions via the internalSessionUniqId. This is possible right now.', '8'),
('2019-02-28', 'TRANSLATE-1579', 'bugfix', 'TermTagger is not tagging terminology automatically on task import wizard', 'Since terminology is also a language resource, the termtagger did not start automatically on task import.', '12'),
('2019-02-28', 'TRANSLATE-1588', 'bugfix', 'Pre-translation is running although it was disabled', 'In the match-analysis panel the pre-translation was not checked, altough a pre-translation was started.', '12'),
('2019-02-28', 'TRANSLATE-1572', 'bugfix', 'Import language resources in background', 'The import of language resources is running now asynchronously in the background. So processing huge TMs or TermCollections will not produce an error in the front-end anymore.', '12'),
('2019-02-28', 'TRANSLATE-1575', 'bugfix', 'Unable to take over match from language resources match grid in editor', 'It could happen that the content in the match grid could not be used via double click or keyboard short-cut.', '14'),
('2019-02-28', 'TRANSLATE-1567', 'bugfix', 'Globalese integration: Error occurred during file upload or translation', 'translate5 ignores now that specific error, since it has no influence to the pre-translation.', '12'),
('2019-02-28', 'TRANSLATE-1560', 'bugfix', 'Introduce a config switch to disable match resource panel', 'In tasks having only terminology as language resource (for term tagging) the match panel can be deactivated via config right now.', '8'),
('2019-02-28', 'TRANSLATE-1580', 'bugfix', 'Remove word count field from the task import wizard', 'In the task creation wizard the input field for the word count was removed, the wordcount is generated via match analysis right now. Via API the setting the word count is still possible.', '12'),
('2019-02-28', 'TRANSLATE-1571', 'bugfix', 'Copy and paste segment content does not work when selecting whole source segment', 'Copy and paste of segment content was not working when selecting the whole source segment content via triple click.', '14');