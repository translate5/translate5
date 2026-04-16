
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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

-- userGroup calculation: basic: 1; editor: 2; pm: 4; admin: 8
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2026-04-16', 'TRANSLATE-5383', 'change', 'Okapi integration - Import Across abbreviations or language segmentation rules', 'OKAPI: Add CLI command to import Across Segmentation Settings regarding Abbrevation ("terms") to an SRX as used in translate5', '15'),
('2026-04-16', 'TRANSLATE-5164', 'change', 'TBX-Import - Plugin TermImport: leave SFTP instruction.ini where it is', 'Added new config to instruction.ini file to keep that file in Import-directory on sftp host, that allows to reuse that file for further imports, so only tbx-files are needed to be uploaded into that directory for any further imports', '15'),
('2026-04-16', 'TRANSLATE-5424', 'bugfix', 'translate5 AI - Translate5 AI: make reasoning effort configurable', 'Make reasoning effort configurable in model properties for reasoning models.', '15'),
('2026-04-16', 'TRANSLATE-5399', 'bugfix', 'translate5 AI - Rag promt: UI error on loading multiple prompts', '7.36.1: prevent additional UI error
7.36.0: Fix for a problem where UI error was triggered if the user loads a lot of prompts for RAG.', '15'),
('2026-04-16', 'TRANSLATE-5360', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Send valid xml with paired tag handler', 'Send valid xml tags with the xml paired tags tag handler.', '15'),
('2026-04-16', 'TRANSLATE-5097', 'bugfix', 'Editor general - RootCause: Strange request to GET /Editor.model.admin.TaskUserAssoc leading to RootCause error. 4rd attempt', 'FIXED: missing URL for loading associated users for a task that is in status Import', '15'),
('2026-04-16', 'TRANSLATE-4795', 'bugfix', 'Export - Implement clean up of the data/Export folder to save disk space', '7.36.1: Enabled for all instances
7.26.0: Enabled for some instances - sometimes data generated for export is not properly cleaned up, what might lead to unneeded hard disk usage. An automatic clean up for such data is generated.', '15');