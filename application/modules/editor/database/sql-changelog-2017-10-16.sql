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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2017-10-16', 'TRANSLATE-869', 'feature', 'Okapi integration for source file format conversion', 'Okapi is integrated to convert different source file formats into importable XLF.', '12'),
('2017-10-16', 'TRANSLATE-995', 'feature', 'Import files with generic XML suffix with auto type detection', 'Import files with generic XML suffix if they are recognized as XLF files.', '12'),
('2017-10-16', 'TRANSLATE-994', 'feature', 'Support RTL languages in the editor', 'translate5 supports now also RTL languages. This can be set per language in the languages DB table.', '14'),
('2017-10-16', 'TRANSLATE-1012', 'change', 'Improve REST API on task creation', 'The following new data fields were added to a task: workflowStepName, foreignId and foreignName. On task creation for the language fields not only the DB internal language IDs can be used, but also the rfc5646 value and the lcid (prefixed with lcid-). See the REST API document in confluence.', '8'),
('2017-10-16', 'TRANSLATE-1004', 'change', 'Enhance text description for task grid column to show task type', 'The column label of the translation job boolean column the task overview was changed.', '8'),
('2017-10-16', 'TRANSLATE-1011', 'bugfix', 'XLIFF Import can not deal with internal unicodePrivateUseArea tags', 'Some special UTF8 characters could not be imported with the XLIFF import, this fixed now.', '12'),
('2017-10-16', 'TRANSLATE-1015', 'bugfix', 'Reference Files are not attached to tasks', 'Reference files were not imported anymore, this is fixed now.', '14'),
('2017-10-16', 'TRANSLATE-983', 'bugfix', 'More tags in OpenTM2 answer than in translate5 segment lead to error', 'If an OpenTM2 answer contains more tags as expected, this leads to an error in translate5. This is fixed now.', '14'),
('2017-10-16', 'TRANSLATE-972', 'bugfix', 'translate5 does not check, if there are relevant files in the import zip', 'Translate5 crashed on the import if there were no usable files in the import ZIP. Now translate5 checks the existence of files and logs an error message instead of crashing.', '12');