
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-06-19', 'TRANSLATE-1900', 'feature', 'Pixel length check: Handle characters with unkown pixel length', 'Pixel length check: Handle characters with unkown pixel length', '8'),
('2020-06-19', 'TRANSLATE-2054', 'feature', 'Integrate PangeaMT with translate5', 'Integrate PangeaMT as new machine translation language resource.', '12'),
('2020-06-19', 'TRANSLATE-2092', 'feature', 'Import specific DisplayText XML', 'Import specific DisplayText XML', '8'),
('2020-06-19', 'TRANSLATE-2071', 'feature', 'VisualTranslation: When a XSL Stylesheet is linked in an imported XML, a HTML as source for the VisualReview will be generated from it', 'An imported XML may contains a link to an XSL stylesheet. If this link exists (as a file or valid URL) the Source for the VisualTranslation is generated from the XSL processing of the XML', '12'),
('2020-06-19', 'TRANSLATE-2070', 'change', 'In XLF Import: Move also bx,ex and it tags out of the segment (sponsored by Supertext)', 'Move paired tags out of the segment, where the corresponding tag belongs to another segment', '14'),
('2020-06-19', 'TRANSLATE-2091', 'bugfix', 'Prevent hanging imports when starting maintenance mode', 'Starting an improt while a maintenance is scheduled could lead to hanging import workers. Now workers don\'t start when a maintenance is scheduled.', '8');