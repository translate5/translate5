
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2022-08-05', 'TRANSLATE-3010', 'feature', 'LanguageResources - Set default pivot language in systemconfiguration', 'Default task pivot languages can be configured for each customer.', '15'),
('2022-08-05', 'TRANSLATE-2812', 'feature', 'Editor general, LanguageResources - Send highlighted word in segment to concordance search or synonym search', 'Enables selected text in editor to be send as synonym or concordance search.', '15'),
('2022-08-05', 'TRANSLATE-2538', 'feature', 'Auto-QA - AutoQA: Include Spell-, Grammar- and Style-Check', 'All spelling, grammar and style errors found by languagetool for all segments of a task are now listed in AutoQA and it is possible to filter the segments by error type.
In addition errors are now not only marked in the segment open for editing, but also in all other segments.
In addition there are now many more subtypes for errors (before we had only spelling, grammar and style).', '15'),
('2022-08-05', 'TRANSLATE-3008', 'change', 'LanguageResources - Change tooltip for checkbox "Pre-translate (MT)"', 'Improves tooltip texts in match analysis.', '15'),
('2022-08-05', 'TRANSLATE-2932', 'change', 'Okapi integration, Task Management - BCONF Management Milestone 2', 'BCONF Management Milestone 2
* adds capabilities to upload/update the SRX files embedded in a BCONF
* adds the frontend to manage the embedded filters/FPRM\'s of a bconf together with the related extension-mapping
* New filters can be created by cloning existing (customized or default) ones
* adds capabilities to generally edit and validate filters/FPRM\'s
* adds frontend editors for the following filters: okf_html, okf_icml, okf_idml, okf_itshtml5, okf_openxml, okf_xml, okf_xmlstream', '15'),
('2022-08-05', 'TRANSLATE-3022', 'bugfix', 'Editor general - RXSS with help page editordocumentation possible', 'Security related fix.', '15'),
('2022-08-05', 'TRANSLATE-3020', 'bugfix', 'Editor general - PXSS on showing reference files', 'Security related fix.', '15'),
('2022-08-05', 'TRANSLATE-3011', 'bugfix', 'Import/Export - Extend error handling in xlf parser', 'Error handling code improvement for xlf parser.', '15'),
('2022-08-05', 'TRANSLATE-3009', 'bugfix', 'Editor general - Base tooltip class problem', 'Fix for a general problem when tooltips are shown in some places in the application.', '15'),
('2022-08-05', 'TRANSLATE-2935', 'bugfix', 'Auto-QA, TermTagger integration - Avoid term-check false positive in case of homonyms and display homonyms in source and target', 'TermTagger: Fixed term-check false positives in case of homonyms', '15'),
('2022-08-05', 'TRANSLATE-2063', 'bugfix', 'Import/Export - Enable parallele use of multiple okapi versions to fix Okapi bugs', 'Multiple okapi instances can be configured and used for task imports.', '15');