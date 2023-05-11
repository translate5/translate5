
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-05-11', 'TRANSLATE-3322', 'feature', 'Editor general - Integrate Tilde MT in translate5', 'Added new plugin which integrates Tilde Machine Translation into Translate5.', '15'),
('2023-05-11', 'TRANSLATE-3317', 'change', 'Editor general - Add port to be configurable', 'Custom database port can be set when installing new translate5 instance using the translate5 installer. This can be done with setting the new environment variable T5_INSTALL_DB_PORT while installing Translate5.', '15'),
('2023-05-11', 'TRANSLATE-3313', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - place php.log under data log and use log rotation', 'PHP error_log moved to translate5 installation root folder /data/logs/ directory, with logs rotation enabled, if needed elsewhere overwrite the location in installation.ini', '15'),
('2023-05-11', 'TRANSLATE-3299', 'change', 'Auto-QA - Enable Segment Batches in Spellchecker Request', 'Enabled batch-processing of segments in the Spellcheck during import.', '15'),
('2023-05-11', 'TRANSLATE-3267', 'change', 'LanguageResources - Improve automatic memory reorganization', 'translate5 - 6.0.0
    - Language resource while reorganizing TM is happening is now treated as importing to restrict any other operation on it.
    - Update now is also disabled while reorganizing TM is in progress.

translate5 - 6.2.0
    - Reformating of the error codes list', '15'),
('2023-05-11', 'TRANSLATE-3241', 'change', 'OpenTM2 integration - T5memory automatic reorganize and via CLI', 'translate - 5.9.4
Added two new commands: 
  - t5memory:reorganize for manually triggering translation memory reorganizing
  - t5memory:list - for listing all translation memories with their statuses
Add new config for setting up error codes from t5memory that should trigger automatic reorganizing
Added automatic translation memory reorganizing if appropriate error appears in response from t5memory engine

translate - 6.2.0
 -  Fix the status check for GroupShare language resources', '15'),
('2023-05-11', 'TRANSLATE-3323', 'bugfix', 'InstantTranslate, t5memory - t5memory translations not available in instant translate', 'Fix for a problem where t5memory results where not listed in instant-translate.', '15'),
('2023-05-11', 'TRANSLATE-3318', 'bugfix', 'MatchAnalysis & Pretranslation - Cloning of pricing template does not clone prices in no-matches column', 'FIXED: Price for \'No match\' column not cloned during pricing preset cloning', '15'),
('2023-05-11', 'TRANSLATE-3310', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Maintenance display text localization', 'Enable custom maintenance text and text localization.', '15'),
('2023-05-11', 'TRANSLATE-3307', 'bugfix', 'Translate5 CLI - Cron events are not triggered on CLI usage', 'translate5 command line tool fix for cron commands. In details: cron did not trigger the cron related events.', '15'),
('2023-05-11', 'TRANSLATE-3306', 'bugfix', 'Editor general - Error occurred when trying to assign language resource to task', 'The translate5 will no longer raise error in case for duplicate user assignment.', '15'),
('2023-05-11', 'TRANSLATE-3280', 'bugfix', 'Editor general - Fixing UI errors', 'translate - 6.0.2
- Fix for error when switching customer in add task window and quickly closing the window with esc key. (me.selectedCustomersConfigStore is null)
- Fix for error when "segment qualities" are still loading but the user already left/close the task. (this.getMetaFalPosPanel() is undefined)
- Right clicking on disabled segment with spelling error leads to an error. (c is null_in_quality_context.json)
- Applying delayed quality styles to segment can lead to an error in case the user left the task before the callback/response is evaluated.

translate - 6.2.0
 - Fix for UI error : setRootNode is undefined', '15'),
('2023-05-11', 'TRANSLATE-3262', 'bugfix', 'Import/Export - Protected non breaking spaces are not respected on reimport', 'On re-import, the protected tags (white spaces, line breaks etc)  from the incoming content will no longer be ignored.', '15'),
('2023-05-11', 'TRANSLATE-3061', 'bugfix', 'Test framework - FIX API Tests', 'translate5 - 5.7.13
 - Code refactoring for the testing environment. Improvements and fixes for API test cases.
translate5 - 6.0.2
 - Fixed config loading level in testing environment 
translate5 - 6.2.0
 - general improvement in API test cases', '15');