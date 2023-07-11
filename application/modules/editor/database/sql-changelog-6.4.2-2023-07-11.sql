
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-07-11', 'TRANSLATE-3417', 'change', 'VisualReview / VisualTranslation - Skip optimization step in pdfconverter', 'PDF converter now doesn\'t optimize pdf files before conversion by default, but does that as a fallback if conversion failed. Behavior can be changed by enabling runtimeOptions.plugins.VisualReview.optimizeBeforeConversion config option.', '15'),
('2023-07-11', 'TRANSLATE-3423', 'bugfix', 'VisualReview / VisualTranslation - Error focusing segment alias in split-frame sidbar', 'Fix for a front-end problem when trying to focus segment in visual split-frame.', '15'),
('2023-07-11', 'TRANSLATE-3416', 'bugfix', 'LanguageResources - DeepL languages request missing languages', 'Fix for a problem where regional languages where not listed as available target option for DeepL language resource', '15'),
('2023-07-11', 'TRANSLATE-3412', 'bugfix', 'LanguageResources - Missing class include in Glossary events', 'Fix for a problem where term collection was not able to be assigned as glossary source', '15'),
('2023-07-11', 'TRANSLATE-3399', 'bugfix', 'InstantTranslate, LanguageResources - Unescaped special chars returned by DeepL', 'It seems like the DeepL API has changed its default behaviour regarding the tag handling. Now we force HTML usage if not explicitly XML is given to fix some encoding problems', '15');