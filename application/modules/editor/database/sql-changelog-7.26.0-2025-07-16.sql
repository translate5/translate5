
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-07-16', 'TRANSLATE-4680', 'feature', 'LanguageResources - Re-Segment TMX on TMX import', 'Add new Re-segment TMX on import feature', '15'),
('2025-07-16', 'TRANSLATE-4751', 'change', 'TM Maintenance - TM maintenance: column selection not persistent', 'All columns in TM segments grid are now visible by default', '15'),
('2025-07-16', 'TRANSLATE-4747', 'change', 'TM Maintenance - TM Maintenance: text from columns cannot be marked (and copied)', 'Text values in segments grid columns are now selectable', '15'),
('2025-07-16', 'TRANSLATE-4734', 'change', 'Export, LanguageResources, TermPortal - API test for TermCollection excel export', 'added missing API tests', '15'),
('2025-07-16', 'TRANSLATE-4714', 'change', 'Auto-QA, InstantTranslate, LanguageResources - Assign all language resources on sending to human revision', 'All language resource used for file translation are now assigned to a task after sending to human revision.', '15'),
('2025-07-16', 'TRANSLATE-4699', 'change', 'ConnectWorldserver - Plugin ConnectWorldserver: Attribut “translate5_translation_tmx” optional', 'Plugin ConnectWorldserver:
providing a TM in Worldserver projects is now optional and not mandatory any more.', '15'),
('2025-07-16', 'TRANSLATE-4302', 'change', 'Okapi integration - Add new grids/windows missing in the Okapi Plugin (idml, openxml, xliff)', 'Added filter options missing in the Okapi Plugin (idml, openxml, xliff)', '15'),
('2025-07-16', 'TRANSLATE-4795', 'bugfix', 'Export - Implement clean up of the data/Export folder to save disk space', 'Sometimes data generated for export is not properly cleaned up, what might lead to unneeded hard disk usage. An automatic clean up for such data is generated.', '15'),
('2025-07-16', 'TRANSLATE-4738', 'bugfix', 'Auto-QA - RootCause: Cannot read properties of null (reading \'style\')', 'FIXED: problem with repeated sources/targets backgound color set up', '15'),
('2025-07-16', 'TRANSLATE-4728', 'bugfix', 'Workflows - Avoid duplicate workflow step labels in job\'s workflow steps list', 'Avoid duplicate optional workflow step labels in job\'s workflow steps list', '15'),
('2025-07-16', 'TRANSLATE-4727', 'bugfix', 'InstantTranslate, t5memory - Use client\'s segmentation rules in InstantTranslate text field and for further segmentation of tmx on tmx import', 'Use client\'s segmentation rules for InstantTranslate text field instead of hard-coded srx-rules', '15'),
('2025-07-16', 'TRANSLATE-4726', 'bugfix', 'InstantTranslate - DeepL problem with & character in Chinese', '7.26.0: Additional languages fixed
7.25.1: FIXED: DeepL problem with \'&\' character in Chinese', '15'),
('2025-07-16', 'TRANSLATE-4134', 'bugfix', 'LanguageResources, t5memory - Clean-up CLI command for find and cleaning orphaned TMs and LanguageResources', 'It may happen that Language Resources are deleted in translate5 but the files remain in t5memory and vice versa. There is now a CLI tool to find that fragments and clean them up.', '15');