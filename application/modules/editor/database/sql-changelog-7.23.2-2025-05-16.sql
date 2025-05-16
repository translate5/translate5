
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-05-16', 'TRANSLATE-4638', 'change', 'Import/Export - Support MRK tags x-generic in XLF import', 'The XLF import can now import MRK tags of mtype x-generic - mostly introduced by XLF2 files converted to XLF by Okapi', '15'),
('2025-05-16', 'TRANSLATE-4636', 'change', 'Content Protection - Improve "default simple" regex', 'Improve default rules for content protection', '15'),
('2025-05-16', 'TRANSLATE-4632', 'change', 't5memory, TM Maintenance - Disable delete all button in case language filter is set', 'Added disabling "Delete all" button if language filter is selected in TM Maintenance', '15'),
('2025-05-16', 'TRANSLATE-4605', 'change', 'TM Maintenance - Case-Sensitive Search in TM-Maintenance', 'Added capability to make search case-sensitive in TM Maintenance', '15'),
('2025-05-16', 'TRANSLATE-4594', 'change', 'PlunetConnector - PlunetConnector should handle character-based reports', 'PlunetConnector should handle character-based reports
- analysis report is based on pricing-unit which is defined in task (not fixed to "word")
- bugfix: pricing presets are not set properly if customer is not submitted in meta on task-creation
- bugfix: Import of Plunt-Items which do not have a language information (e.g. file-preparation, meeting, ...)
', '15'),
('2025-05-16', 'TRANSLATE-4513', 'change', 'TermTagger integration - Send TBX ID as part of the URL to the termTagger', '7.23.2: Fix implementation as specified, TBX loading problems in termtagger lead now to delay instead termtagger disabling
7.22.0: TBX ID is sent as part of the request/header to the termTagger', '15'),
('2025-05-16', 'TRANSLATE-4511', 'change', 'Workflows - Add custom dialogs on task finishing', '7.23.2: Fix a problem preventing opening the waiting for PDF step again
7.22.0: For customized workflows the possibility to add custom actions on workflow finish was added', '15'),
('2025-05-16', 'TRANSLATE-4658', 'bugfix', 'LanguageResources, MatchAnalysis & Pretranslation - Tag repair tag handler uses wrong segment reference when repair is applied', 'Fix for tag repair problem in xliff_paired_tags tag handler where wrong segment reference was used to recreate broken tags in batch pre-translation.', '15'),
('2025-05-16', 'TRANSLATE-4648', 'bugfix', 'Content Protection - Content Protection: string converted in task but not TM', 'Fix protection of float numbers', '15'),
('2025-05-16', 'TRANSLATE-4647', 'bugfix', 'Import/Export - Internal Tag Numeration is wrong in sdlxliff', 'Fix internal tag number assignment for sdlfliff files', '15'),
('2025-05-16', 'TRANSLATE-4642', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Change logging table db engine to innodb', 'The logging table DB type was changed to remove performance problems in log table usage.', '15'),
('2025-05-16', 'TRANSLATE-4618', 'bugfix', 'Workflows - Restrict possible backup filename length', 'Added max possible backup filename length limit to make sure backup files can be created and uploaded', '15'),
('2025-05-16', 'TRANSLATE-4611', 'bugfix', 't5memory, TM Maintenance - T5Memory segment ids are 0 for some reason', 'Added automatic memory reorganizing if not all segments have generated IDs in t5memory', '15');