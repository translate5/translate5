
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-06-09', 'TRANSLATE-3365', 'change', 'Test framework - Improvement on testing framework', 'Improvement in translate5 testing framework.', '15'),
('2023-06-09', 'TRANSLATE-3349', 'change', 'LanguageResources - HOTFIX: DeepL API changes regarding formality', 'HOTFIX: API-changes with DeepL (formality) lead to pretranslation/analysis fails for certain target languages', '15'),
('2023-06-09', 'TRANSLATE-3329', 'change', 'Test framework - Testing certain branch in the cloud accessible for developers', 'Cloud based testing Implementation.', '15'),
('2023-06-09', 'TRANSLATE-3367', 'bugfix', 'VisualReview / VisualTranslation - Visual can not be created due to failing CSS processing', 'FIX: In very rare cases the CSS processing of the Visual Markup failed preventing the Visual to be created', '15'),
('2023-06-09', 'TRANSLATE-3358', 'bugfix', 'LanguageResources - TildeMT update translation does not work', 'Updating translations was not possible because of wrong API parameter name.', '15'),
('2023-06-09', 'TRANSLATE-3356', 'bugfix', 'file format settings - OKAPI import: Available extensions of used bconf not used for processing files', 'FIX: Added extensions in custom file-format-settings may have been rejected nevertheless when trying to import files with this extension
FIX: In the Client Panels freshly added file-filter-settings created an error when deleted immediately after creation', '15'),
('2023-06-09', 'TRANSLATE-3353', 'bugfix', 'Translate5 CLI - HOTFIX: qautodiscovery-command does not work properly in self-hosted dockerized instances', 'FIX: improved service:autodiscovery command when used in self-hosted instances', '15'),
('2023-06-09', 'TRANSLATE-3352', 'bugfix', 'VisualReview / VisualTranslation - Increase timeout for communication with visualconverter and pdfconverter', 'Communication timeouts between T5 and visualconverter/pdfconverter were increased to 30 seconds', '15'),
('2023-06-09', 'TRANSLATE-3350', 'bugfix', 'Export - Error when task is exported multiple times', 'Exporting task multiple times lead to an error. Now the users will no longer be able to export a task if there is already running export for the same task.', '15'),
('2023-06-09', 'TRANSLATE-3347', 'bugfix', 'Import/Export - Race condition in creating task meta data on import', 'When there is a longer time gap between steps in the import it may happen that the import crashes due race-conditions in saving the task meta table.', '15'),
('2023-06-09', 'TRANSLATE-3341', 'bugfix', 'TBX-Import - TBX files are kept on disk on updating term-collections', 'On updating TermCollections all TBX files are kept on disk: this is reduced to 3 months in the past for debugging purposes.', '15'),
('2023-06-09', 'TRANSLATE-3335', 'bugfix', 't5memory - Reimport stops, if one segment can not be saved because of segment length', 'Fixed an error that might cause t5memory reorganizing when it was not actually needed', '15'),
('2023-06-09', 'TRANSLATE-3320', 'bugfix', 'LanguageResources - FIX Tag check and tag handling for LanguageResource matches', 'FIX: Solve Problems with additional whitespace tags from accepted TM matches not being saved / stripped on saving', '15'),
('2023-06-09', 'TRANSLATE-3319', 'bugfix', 'Import/Export - FIX tag-handling in Transit Plugin', 'Fixed bug with tag parsing in Transit plugin', '15');