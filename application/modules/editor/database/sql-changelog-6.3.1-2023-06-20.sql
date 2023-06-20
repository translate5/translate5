
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-06-20', 'TRANSLATE-3384', 'change', 'ConnectWorldserver - Plugin ConnectWorldserver unusable due merge-conflicts', 'Repair ConnectWorldserver plug-in which was unusable since the code base was on the development state and not on a releasable state.', '15'),
('2023-06-20', 'TRANSLATE-3382', 'change', 'Editor general - Fix TextShuttle plugin', 'TextShuttle plugin structure fixed
', '15'),
('2023-06-20', 'TRANSLATE-3357', 'change', 'LanguageResources - Make Tilde config data overwriteable on client level', 'TildeMT API configuration parameters can now be overwritten on client level', '15'),
('2023-06-20', 'TRANSLATE-3354', 'change', 'LanguageResources - API Keys for Textshuttle via GUI, overwritable on client level', 'Some API configurations for TextShuttle plugin can now be overwritten on client level', '15'),
('2023-06-20', 'TRANSLATE-3388', 'bugfix', 'Editor general - Fix and improve architecture to evaluate the supported file formats', 'Improve evaluation of supported file types/formats, fix wrong filetype-evalution in frontend when task specific file filters were set.', '15'),
('2023-06-20', 'TRANSLATE-3387', 'bugfix', 'Editor general - Unable to change UI langauge', 'Fix problem where the UI language was unable to be changed', '15'),
('2023-06-20', 'TRANSLATE-3383', 'bugfix', 'Editor general - Newline visualization in internal-tags / segments', 'FIX: Newlines in tags may appear as newlines in translate5 internal tags leading to defect tags in the frontend. Now they are converted to visual newlines instead.', '15'),
('2023-06-20', 'TRANSLATE-3379', 'bugfix', 'Import/Export - Missing workflow user preferences leads to errors in the UI', 'Error in the UI when the task has no workflow preferences entries which can happen if the task can not be imported.', '15'),
('2023-06-20', 'TRANSLATE-3343', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Stop PdfToHtmlWorker if pdfconverter failed to create a job', 'PdfToHtmlWorker now finishes immediately if the conversion job failed to create or there is an error occurred while retrieving the conversion job result. So now the error on task import appears faster without waiting for the maximum pdfconverter timeout to exceed.', '15'),
('2023-06-20', 'TRANSLATE-3186', 'bugfix', 'Import/Export - Import is interrupted because of files with no segments', 'If an import contains some files containing no translatable content will no longer set the whole task to status error.', '15');