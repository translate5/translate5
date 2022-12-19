
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

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-12-16', 'TRANSLATE-3108', 'feature', 'Main back-end mechanisms (Worker, Logging, etc.) - App tokens for API authentication', 'Via CLI tool appTokens can now be added to dedicated users. Such app tokens should be used then in the future for authentication via API.', '15'),
('2022-12-16', 'TRANSLATE-3069', 'feature', 'LanguageResources - TM pre-translation match rate set to 80 as default', 'Enables the minimum value form pre-translate TM match-rate to be configurable for client.', '15'),
('2022-12-16', 'TRANSLATE-2185', 'feature', 'Installation & Update - Prepare translate5 for usage with docker', 'Introducing the setup of translate5 and the used services as docker containers.', '15'),
('2022-12-16', 'TRANSLATE-3143', 'change', 'Editor Length Check - Change some default config values for pixel length check', 'The settings runtimeOptions.lengthRestriction.automaticNewLineAdding and 
runtimeOptions.lengthRestriction.newLineReplaceWhitespace are set now to off by default. ', '15'),
('2022-12-16', 'TRANSLATE-3134', 'change', 'OpenTM2 integration - Amend translate5 to send appropriate json terminator to t5memory', 'Request json sent to t5memory is now pretty printed', '15'),
('2022-12-16', 'TRANSLATE-2925', 'change', 'VisualReview / VisualTranslation - API tests for all types of visuals', 'Added API tests for all types of visuals', '15'),
('2022-12-16', 'TRANSLATE-764', 'change', 'Import/Export - Restructuring of export.zip', 'The content structure of the export zip changed. In the future it does NOT contain any more a folder with the task guid, but directly on the highest level of the zip all files of the task that were translated/reviewed.', '15'),
('2022-12-16', 'TRANSLATE-3137', 'bugfix', 'TermPortal - TermPortal: missing ACL for pure termportal-users', 'added missing ACL rules for pure TermPortal users', '15'),
('2022-12-16', 'TRANSLATE-3132', 'bugfix', 'TermPortal - TermPortal: duplicated users in \'Created by\' filter', 'only distinct user names are now shown in \'Created by\' and \'Updated by\' filters', '15'),
('2022-12-16', 'TRANSLATE-3131', 'bugfix', 'TermTagger integration - Termtagger not synchronized with Terminology', 'Task terminology is now refreshed prior Analyse/Re-check operations', '15'),
('2022-12-16', 'TRANSLATE-3129', 'bugfix', 'Task Management - PM light can not choose different PM for a project', 'PmLight user is now allowed to change PM of a project', '15'),
('2022-12-16', 'TRANSLATE-3128', 'bugfix', 'Task Management - PM of task can not be changed to PM light user', 'Task can be assigned to pmLight user now', '15');