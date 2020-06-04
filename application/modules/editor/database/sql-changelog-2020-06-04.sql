
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-06-04', 'TRANSLATE-1610', 'feature', 'Bundle tasks to projects', 'Several tasks with same content and same source language can now be bundled to projects. A completely new project overview was created therefore.', '12'),
('2020-06-04', 'TRANSLATE-1901', 'feature', 'Support lines in pixel-based length check', 'If configured the width of each new-line in target content is calculated and checked separately.', '14'),

('2020-06-04', 'TRANSLATE-2086', 'feature', 'Integrate ModelFront (MT risk prediction)', 'ModelFront risk prediction for MT matches is integrated.', '12'),
('2020-06-04', 'TRANSLATE-2087', 'feature', 'VisualTranslation: Highlight pre-translated segments of bad quality / missing translations', 'Highlight pre-translated segments of bad quality / missing translations in visual translation ', '6'),
('2020-06-04', 'TRANSLATE-1929', 'feature', 'VisualTranslation: HTML files can import directly', 'HTML files can be used directly as import file in VisualTranslation', '4'),
('2020-06-04', 'TRANSLATE-2072', 'change', 'move character pixel definition from customer to file level', 'The definition of character pixel widths is move from customer to file level', '12'),
('2020-06-04', 'TRANSLATE-2084', 'change', 'Disable possiblity to delete tags by default', 'The possibility to save a segment with tag errors and ignore the warn message is disabled now. This can be re-enabled as described in https://jira.translate5.net/browse/TRANSLATE-2084. Whitespace tags can still be deleted. ', '6'),
('2020-06-04', 'TRANSLATE-2085', 'change', 'InstantTranslate: handling of single segments with dot', 'Translating one sentence with a trailing dot was recognized as multiple sentences instead only one.', '6');
