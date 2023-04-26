
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-04-26', 'TRANSLATE-3182', 'feature', 'Editor general - Show optionally character count for current open segment', 'Added optional character counter for the segment-editor. By default it is invisible - unless the runtimeOptions.editor.toolbar.showHideCharCounter config is set to active. The counter can be activated by the user  in the segment grid settings menu', '15'),
('2023-04-26', 'TRANSLATE-2991', 'feature', 'Configuration, MatchAnalysis & Pretranslation - Pricing & match rate presets', 'Sophisticated config options for calculating prices and defining custom match ranges are introduced.', '15'),
('2023-04-26', 'TRANSLATE-3246', 'change', 'file format settings - Fixed naming scheme for OKAPI config entries', 'Fixed naming scheme for Okapi Service Configuration Entries. In the frontend, no name must be defined anymore', '15'),
('2023-04-26', 'TRANSLATE-3225', 'change', 'InstantTranslate - DeepL at times simply not answers requests leading to errors in T5 that suggest the app is malfunctioning', 'Instant translate UI now shows errors happening with language resources during translation', '15'),
('2023-04-26', 'TRANSLATE-3302', 'bugfix', 'Translate5 CLI - Notification mails are not translated when starting cron via CLI', 'Internal translations were missing when calling cron via CLI.', '15'),
('2023-04-26', 'TRANSLATE-3298', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Version conflict when using multiple tabs', 'Improve logging when translate5 is opened in multiple tabs and reduce log entries when a version conflict pops up.', '15'),
('2023-04-26', 'TRANSLATE-3297', 'bugfix', 'Import/Export - Corrupt skeleton file on reimport', 'Fix for corrupted skeleton files after translator package was re-imported into a task.', '15'),
('2023-04-26', 'TRANSLATE-3296', 'bugfix', 'SpellCheck (LanguageTool integration) - Add config to prevent spellchecking non-editable / locked segments on import', 'Enhancement: Add configuration to skip spellchecking for read-only segments on import', '15'),
('2023-04-26', 'TRANSLATE-3295', 'bugfix', 'TermTagger integration - Terms in Source are not identified in target and therefore falsly are flagged "not found"', 'FIX: Terms in the segment source may have been falsely flagged as "not found in target"', '15'),
('2023-04-26', 'TRANSLATE-3294', 'bugfix', 'Editor general - Increase systemstatus timeout', 'The system-check under preferences may run into a timeout if some services do not respond in a reasonable amount of time, therefore the timeout in the UI is increased and a proper error message is shown.
', '15'),
('2023-04-26', 'TRANSLATE-3293', 'bugfix', 'Editor general - Stop trimming leading/trailing whitespaces on segment save', 'Leading/trailing whitespaces will no longer be trimmed from the segment on save.', '15'),
('2023-04-26', 'TRANSLATE-3287', 'bugfix', 'Editor general - Protected spaces are being removed automatically, when saving', 'FIX: protected spaces may be removed when saving a translated segment in the review', '15'),
('2023-04-26', 'TRANSLATE-3286', 'bugfix', 'Editor general - Error on trying to insert duplicate entry to DB', 'Fixed throwing duplicate entry exception in ZfExtended/Models/Entity/Abstract', '15'),
('2023-04-26', 'TRANSLATE-3283', 'bugfix', 'TermPortal - Set for rejected term automatically the term attribute normativeAuthorization "deprecatedTerm"', 'Fix for the problem where for a rejected term automatically the term attribute "normativeAuthorization" was not set to "deprecatedTerm".', '15'),
('2023-04-26', 'TRANSLATE-3278', 'bugfix', 'VisualReview / VisualTranslation - VisualReview sym link clean up', 'Visual (code quality improvement): 
- symbolic links are created as relative paths to simplify moving the data or application directory
- improve cleanup of symbolic link', '15'),
('2023-04-26', 'TRANSLATE-3250', 'bugfix', 'TermPortal - Term translation project creation fails silently', 'TermTranslation-project creation was just failing silently, if the PM user which was set as default PM for TermTranslation-projects was deleted before. ', '15'),
('2023-04-26', 'TRANSLATE-3058', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.), SpellCheck (LanguageTool integration), TermTagger integration - Simplify termtagger and spellcheck workers', 'translate5 - 6.0.0: 
Improvement: TermTagger Worker & SpellCheck Worker are not queued dynamically anymore but according to the configured slots & looping through segments. This reduces deadlocks & limits processes 
translate5 - 6.1.0:
Improve behavior of Processing-State queries regarding deadlocks', '15'),
('2023-04-26', 'TRANSLATE-2063', 'bugfix', 'Import/Export - Enable parallele use of multiple okapi versions to fix Okapi bugs', 'NEXT: Fixed docker autodiscovery not to overwrite existing config.
5.9.0: Added dedicated CLI commands to maintain Okapi config.
5.7.6: Multiple okapi instances can be configured and used for task imports.
6.1.0: Enhancement: Fixed naming scheme for the keys of the Okapi Server Configuration entries', '15');