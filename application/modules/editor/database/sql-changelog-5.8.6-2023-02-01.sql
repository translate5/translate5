
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-02-01', 'TRANSLATE-3039', 'change', 'Editor general - Improve password rules (4.7)', 'The current password rule (just 8 characters) was to lax. The new user password roles requirements can be found in this link: https://confluence.translate5.net/x/AYBVG (released in 5.8.5, fixes in 5.8.6)
', '15'),
('2023-02-01', 'TRANSLATE-3180', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Changing user association in import wizard does not take effect', 'Fix for a problem when user association is modified in the import wizard.', '15'),
('2023-02-01', 'TRANSLATE-3178', 'bugfix', 'VisualReview / VisualTranslation - Visual Video import: Blank Segments cause subtitle-number to sip into next segment and empty segment to be skipped', 'FIX: Video SRT Import: Subtitles with Timestamp but without Content caused Quirks in the Segmentation', '15'),
('2023-02-01', 'TRANSLATE-3176', 'bugfix', 'Export, Main back-end mechanisms (Worker, Logging, etc.) - Filenames with quotes are truncated upon download', 'Quotes in the task name led to cut of filenames on export. Fixed in 5.8.6.', '15'),
('2023-02-01', 'TRANSLATE-3175', 'bugfix', 'LanguageResources - Need to allow importing new file only after importing is finished', 'Language resource import and export buttons are disabled while importing is in progress', '15'),
('2023-02-01', 'TRANSLATE-3123', 'bugfix', 'Import/Export - Tbx import: handling duplicated attributes', 'TBX import: removed term-level attributes duplicates', '15');