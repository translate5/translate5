
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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-10-28', 'TRANSLATE-5016', 'change', 't5memory - Redo reorganise call', '7.31.1: Fix reorganize worker
7.30.0: T5Memory: replaced reorganize with export/import to avoid potential data losses', '15'),
('2025-10-28', 'TRANSLATE-4877', 'change', 't5memory - Make re-try calls on update of segments in t5memory', '7.31.1: Fix reimport segments timestamp
7.30.0: Updates of segments in t5memory will re-try calls now in case of service down time', '15'),
('2025-10-28', 'TRANSLATE-5086', 'bugfix', 'LanguageResources - Change default config value for deepl taghandling to xml', 'Default value for runtimeOptions.plugins.DeepL.api.parametars.tagHandling changes from none to XML since this gives better results.', '15'),
('2025-10-28', 'TRANSLATE-5071', 'bugfix', 'Client management - Cannot save the default user assignment without a deadline', 'User association can be saved now when deadline is empty in Clients/User assignment defaults', '15'),
('2025-10-28', 'TRANSLATE-5059', 'bugfix', 'Export - Prevent formulas to be used in different excel exports', 'Updated used spreadsheet generation libraries, ensured that now malicious formulas could be generated', '15'),
('2025-10-28', 'TRANSLATE-5051', 'bugfix', 'Editor general - Repetition filtering and exclude first repetition not working as expected', 'The segment repetition filters were misleading the user about what is in the current filter what not.', '15'),
('2025-10-28', 'TRANSLATE-5039', 'bugfix', 'TermTagger integration - TermTagging TBX export problem', 'FIXED: problem with exporting terms under wrong term entries', '15'),
('2025-10-28', 'TRANSLATE-5017', 'bugfix', 'Auto-QA - must be zero quality and allow tag errors faulty', 'Auto-QA: user is allowed to finish the task if tag errors are absent if they are configured to be', '15'),
('2025-10-28', 'TRANSLATE-4993', 'bugfix', 'VisualReview / VisualTranslation - Libreoffice conversion process timeouts on complex documents in officeconverter container', 'Increased Libreoffice conversion process\' timeout to handle complex documents by officeconverter', '15'),
('2025-10-28', 'TRANSLATE-4290', 'bugfix', 'Editor general - Make concordance search in editor and TM Maintenance NOT scroll down', 'FIXED: concordance and TMMaintenance search results are not auto-scrolled down anymore to prevent user distraction', '15');