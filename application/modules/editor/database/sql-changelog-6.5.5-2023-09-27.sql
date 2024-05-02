
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-09-27', 'TRANSLATE-3499', 'bugfix', 'VisualReview / VisualTranslation - Set --correct-text-visibility for pdfconverter via GUI', 'Added new config option runtimeOptions.plugins.VisualReview.pdfcorrectTextVisibility which changes text visibility in PDF which contains for example image overlays / watersigns like "draft" or similar things hiding the real text.', '15'),
('2023-09-27', 'TRANSLATE-3487', 'bugfix', 'Editor general - Taking over fuzzy matches in the UI may lead to corrupted internal tags', '6.5.4: FIX: Segments with more then 9 tags were producing errors in the UI
6.5.3: Taking over fuzzy matches in the UI was producing corrupted internal tags. In the Editor the tags were looking correctly, but on database level they did contain the wrong content. 
6.5.5: Fix problem with locked segment tag', '15'),
('2023-09-27', 'TRANSLATE-3289', 'bugfix', 'TermPortal - Login deletes hash of TermPortal URL', 'Addressbar location hash is now preserved on login, if applicable', '15');