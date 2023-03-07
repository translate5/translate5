
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-03-07', 'TRANSLATE-3223', 'feature', 'Editor general - Create a new user role to force the editor only mode', 'In editor-only-mode (leave application button instead back to tasklist) admins are now allowed to switch back to task list. 
For other users an optional role (editor-only-override) is added. This enables a hybrid setup of editor only mode and default mode with task overview.', '15'),
('2023-03-07', 'TRANSLATE-3205', 'feature', 'API - Make T5 API ready for use via Browser (full CORS support)', 'IMPROVEMENT: Full CORS support to enable API-usage via JS when authenticating with an App-Token ', '15'),
('2023-03-07', 'TRANSLATE-3188', 'feature', 'LanguageResources, MatchAnalysis & Pretranslation - Speed up internal fuzzy analysis by copying binary files', 'Now during match analyzing translation memory is cloned using the new t5memory API endpoint instead of export/import, which significantly increases the speed of cloning.', '15'),
('2023-03-07', 'TRANSLATE-3117', 'feature', 'Import/Export - translator package', 'Editor users are now able to download a zip package including everything needed to translate a job outside of translate5 and afterwards update the task with it.', '15'),
('2023-03-07', 'TRANSLATE-3097', 'feature', 'Editor general - Enhance editor menu usability', 'Enhanced editor menu usability. For details please see "important release notes".', '15'),
('2023-03-07', 'TRANSLATE-2994', 'feature', 'LanguageResources, OpenTM2 integration - t5memory roll-out', '5.9.0: FIX: increase timeout
5.7.13: Added new cli command for migrating OpenTM2 to t5memory.
Check the usage of 
./translate5.sh help otm2:migrate', '15'),
('2023-03-07', 'TRANSLATE-2185', 'feature', 'Installation & Update - Prepare translate5 for usage with docker', '5.9.0: Introduce service checks if the configured services are working
5.8.1: Introducing the setup of translate5 and the used services as docker containers.', '15'),
('2023-03-07', 'TRANSLATE-3216', 'change', 'VisualReview / VisualTranslation - Add Version Endpoint to PDF-Converter', 'Added new endpoint to pdfconverter API which returns list of libraries versions.', '15'),
('2023-03-07', 'TRANSLATE-3204', 'change', 'Export - PMs and PMlights should also be able to download the import archive', 'PMs should also be allowed to download the import archive of the imported task. Previously only admins were allowed to do that.', '15'),
('2023-03-07', 'TRANSLATE-3192', 'change', 'Task Management - Set default for multi usage mode to "Simultaneous"', 'Change default value for task initial usage mode from "Cooperative" to "Simultaneous".', '15'),
('2023-03-07', 'TRANSLATE-3183', 'change', 'API - Enable API-Usage via JS when using an App-Token', 'IMPROVEMENT: Sending Access-Control header to allow API-usage via JS when authenticating with an App-Token', '15'),
('2023-03-07', 'TRANSLATE-3072', 'change', 'file format settings - Usability enhancements for file format settings', 'ENHANCEMENT: Improved usability of File format and segmentation settings UI: better localization, more tooltips, some bugfixes', '15'),
('2023-03-07', 'TRANSLATE-3228', 'bugfix', 'Export - Doubled language code in filename of translate5 export zip', 'Removed doubled language code in filename of translate5 export zip', '15'),
('2023-03-07', 'TRANSLATE-3224', 'bugfix', 'Editor general, InstantTranslate - Column not found error when creating a project on fresh Docker install', 'Add missing column to LEK_languageresources table if installing without InstantTranslate.', '15'),
('2023-03-07', 'TRANSLATE-3219', 'bugfix', 'Workflows - Workflow notification json decode problems', 'When using JSON based workflow notification parameters it might come to strange JSON syntax errors.', '15'),
('2023-03-07', 'TRANSLATE-3217', 'bugfix', 'Editor general - RootCause: Invalid JSON - answer seems not to be from translate5 - x-translate5-version header is missing', 'In 5.9.0: added some debug code.', '15'),
('2023-03-07', 'TRANSLATE-3215', 'bugfix', 'TermPortal - RootCause: filter window error', 'In 5.9.0: added some debug code.', '15'),
('2023-03-07', 'TRANSLATE-3214', 'bugfix', 'TermPortal - RootCause: locale change in attributes management', 'FIXED: bug popping after GUI locale change in attributes management', '15'),
('2023-03-07', 'TRANSLATE-3209', 'bugfix', 'Editor general - RootCause error: vm is null', 'Fix for UI error when task progress is refreshed but the user opens task for editing.', '15'),
('2023-03-07', 'TRANSLATE-3208', 'bugfix', 'Editor general - RootCause error: Cannot read properties of undefined (reading \'removeAll\')', 'Fix for UI error when removing project.', '15'),
('2023-03-07', 'TRANSLATE-3203', 'bugfix', 'SpellCheck (LanguageTool integration) - RootCause error: Cannot read properties of undefined (reading \'message\')', 'Fix for UI error when accepting or changing spell check recommendations', '15'),
('2023-03-07', 'TRANSLATE-3199', 'bugfix', 'Task Management - RootCause-error: rendered block refreshed at 0 rows', 'FIXED: error unregularly/randomly popping on task import and/or initial projects grid load', '15'),
('2023-03-07', 'TRANSLATE-3195', 'bugfix', 'Editor general - RootCause-error: PageMap asked for range which it does not have', 'Fixed segments grid error popping on attempt to scroll to some position while (re)loading is in process', '15'),
('2023-03-07', 'TRANSLATE-3194', 'bugfix', 'Editor general, OpenTM2 integration - Front-end error on empty translate5 memory status response', 'Fix for front-end error on translate5 memory status check.', '15'),
('2023-03-07', 'TRANSLATE-3189', 'bugfix', 'MatchAnalysis & Pretranslation, Test framework - Reduce Match analysis test complexity', 'Improve the Analysis and pre-translation tests.', '15'),
('2023-03-07', 'TRANSLATE-3185', 'bugfix', 'User Management - Error message for duplicate user login', 'Improve failure error messages when creation or editing a user.', '15'),
('2023-03-07', 'TRANSLATE-3181', 'bugfix', 'Editor general - Pasted content inside concordance search is not used for searching', 'Fix for a problem where concordance search was not triggered when pasting content in one of the search fields and then clicking on the search button.', '15'),
('2023-03-07', 'TRANSLATE-3062', 'bugfix', 'Installation & Update, Test framework - Test DB reset and removement of mysql CLI dependency', '5.9.0: database dump and cron invocation via CLI possible
5.7.13: Removed the mysql CLI tool as dependency from translate5 PHP code.', '15'),
('2023-03-07', 'TRANSLATE-3052', 'bugfix', 'LanguageResources - Clean resource assignments after customer is removed', '5.9.0: Bugfix
5.8.5: Removing customer from resource will be prevented in case this resource is used/assigned to a task.', '15'),
('2023-03-07', 'TRANSLATE-2063', 'bugfix', 'Import/Export - Enable parallele use of multiple okapi versions to fix Okapi bugs', '5.9.0: Added dedicated CLI commands to maintain Okapi config.
5.7.6: Multiple okapi instances can be configured and used for task imports.', '15');