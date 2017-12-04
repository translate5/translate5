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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2017-10-19', 'TRANSLATE-944', 'feature', 'Import and Export comments from Across Xliff', 'Across comments to trans-units are imported to and exported from translate5 segments.', '14'),
('2017-10-19', 'TRANSLATE-1013', 'feature', 'Improve embedded translate5 usage by a static link', 'For usage details see confluence.', '12'),
('2017-10-19', 'T5DEV-161', 'feature', 'Non public VisualReview Plug-In', 'This plug-in turns translate5 into a visual review editor. The Plug-In is not publically available!', '14'),
('2017-10-19', 'TRANSLATE-1028', 'change', 'Correct wrong or misleading language shortcuts', 'Some wrong language tags are corrected: "jp" will be replaced by "ja" (refers to Japanese), "no" will be replaced by "nb" (refers to Norwegian Bokmal); Serbian (cyrillic) "sr-Cyrl" was added; Uszek (cyrillic) "uz-Cyrl" was added; Norwegian (Nynorsk) "nn" was added', '12');