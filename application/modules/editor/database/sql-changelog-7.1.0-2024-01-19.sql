
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-01-19', 'TRANSLATE-3650', 'feature', 'Editor general - Special characters listed for all languages', 'Can be defined special characters in the editor to be available for all languages.', '15'),
('2024-01-19', 'TRANSLATE-3533', 'feature', 'Import/Export, VisualReview / VisualTranslation - Placeables in translate5', 'Added capabilities to identify Placeables in xliff-tags. Placeables are single internal tags that will be shown with their text-content instead as tag. For identification XPaths have to be defined in the configuration.', '15'),
('2024-01-19', 'TRANSLATE-3483', 'feature', 'Task Management - Custom project/task meta data fields', 'New feature where custom fields can be defined for a task.', '15'),
('2024-01-19', 'TRANSLATE-2276', 'feature', 'Client management, LanguageResources, Task Management, User Management - Save customization of project, task, language resource, user and client management', 'Columns in main grids do now remember their order, visibility, sorting and filtering', '15'),
('2024-01-19', 'TRANSLATE-3636', 'change', 'Auto-QA - FIX Quality Decorations in Segment Grid', 'FIX: Spellcheck decorations may have wrong positions and/or wrong Segment-Text in right-click layer in the segment-grid', '15'),
('2024-01-19', 'TRANSLATE-3622', 'change', 'Main back-end mechanisms (Worker, Logging, etc.) - Zip and upload data-directory to Indi Engine logger after pipeline completion', 'Translate5 instance logger improvements.', '15'),
('2024-01-19', 'TRANSLATE-3669', 'bugfix', 'TBX-Import - Cross API connector was not working on php 8.1 due class loading problems', 'The Across TBX Import was not working anymore with php 8.1', '15'),
('2024-01-19', 'TRANSLATE-3664', 'bugfix', 'sso - Missing header in proxy config', 'For https request the http host was set with wrong value leading SSO customers to be not detected based on the domain.', '15'),
('2024-01-19', 'TRANSLATE-3662', 'bugfix', 'LanguageResources - Dictionary search language support', 'Check for dictionary supported languages before searching for result.', '15'),
('2024-01-19', 'TRANSLATE-3661', 'bugfix', 'Okapi integration - Okapi config allows deletion of okapi instances even if in use', 'Okapi servers being in use by several tasks could be deleted over the UI, this is prevented now.', '15'),
('2024-01-19', 'TRANSLATE-3658', 'bugfix', 'file format settings - File formats: Make format-check in the import-wizard dynamic', 'FIX: Check of added workfiles did not respect the extension-mapping of the selected bconf', '15'),
('2024-01-19', 'TRANSLATE-3653', 'bugfix', 't5memory - t5memory TMX Upload does not work anymore', 'The TMX upload was not working anymore in hosted environments', '15'),
('2024-01-19', 'TRANSLATE-3652', 'bugfix', 'Import/Export - Remove wrong SRX rule from all languages', 'Remove erroneus SRX-rule from translate5 default File-format settings (BCONF)', '15'),
('2024-01-19', 'TRANSLATE-3640', 'bugfix', 'Client management - email link to task not working for clients with own domain', 'Fix for translate5 url in email templates.', '15'),
('2024-01-19', 'TRANSLATE-3635', 'bugfix', 'Auto-QA, Editor general - Usage of "target at import time" as source for tags: Only for bilingual tasks', 'Target at import time is considered to be a reference field for checking tags only for files where we did directly get the bilingual files in the import', '15'),
('2024-01-19', 'TRANSLATE-3633', 'bugfix', 'VisualReview / VisualTranslation - Visual: Order of merged PDFs random', 'FIX: When merging PDFs for a Visual, the order of Files is now sorted by name', '15'),
('2024-01-19', 'TRANSLATE-3617', 'bugfix', 'Editor general - Help button is not visible in editor', 'Fix for help button not visible in editor overview.', '15');