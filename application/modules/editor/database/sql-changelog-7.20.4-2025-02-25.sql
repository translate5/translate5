
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-02-25', 'TRANSLATE-4486', 'change', 'TermTagger integration - Further Improvements for TermTagger being overloaded', 'Enhancement: Improve behaviour a TermTagger-Worker/Looper reacts on overloaded Termtaggers', '15'),
('2025-02-25', 'TRANSLATE-4468', 'change', 'MatchAnalysis & Pretranslation - Use TM matches prior to repetitions', 'XLF with given resname information: same segments are only repetitions if the given resname information is the same. If resname is different TM matches are used then.', '15'),
('2025-02-25', 'TRANSLATE-4463', 'change', 'Workflows - set job type "editor" as default in two places', 'Use job type Editor as default in job assignment form', '15'),
('2025-02-25', 'TRANSLATE-3963', 'change', 'Task Management - Implement CTRL-G shortcut for project / task overview', 'The CTRL-g shortcut is now also implemented in the task and projectoverview.', '15'),
('2025-02-25', 'TRANSLATE-4495', 'bugfix', 'Hotfolder Import - Hotfolder: Proceed process folders on error in one of them', 'Hotfolder: If error happens on import of one of projects remaining will proceed', '15'),
('2025-02-25', 'TRANSLATE-4493', 'bugfix', 'Authentication - Wrong check for password existence', 'Fix check for password in payload', '15'),
('2025-02-25', 'TRANSLATE-4480', 'bugfix', 'Import/Export - Match analysis worker not queued', 'Fix: Queue Match analysis worker on import', '15'),
('2025-02-25', 'TRANSLATE-4471', 'bugfix', 'Import/Export - Worker-queue may stuck on import due to MatchAnalysis', 'translate5 - 7.20.3: FIX: Import may stuck due to MatchAnalysis being queued too late
translate5 - 7.20.4: FIX: additional improvements', '15'),
('2025-02-25', 'TRANSLATE-4467', 'bugfix', 'InstantTranslate, t5memory - Task TM is created for instant translate task', 'Skip creating a task TM for instant translate tasks even if there are language resources with checked "Write access by default"', '15'),
('2025-02-25', 'TRANSLATE-4466', 'bugfix', 'TermPortal - Wrong browser tab title in TermPortal', 'FIXED: incorrect browser tab title in TermPortal\'s attributes management screen', '15'),
('2025-02-25', 'TRANSLATE-4448', 'bugfix', 'VisualReview / VisualTranslation - Headless download does not respect proxy', 'FIX: Visual Download of URLs did not use configurable outboud Proxy anymore', '15'),
('2025-02-25', 'TRANSLATE-4423', 'bugfix', 'InstantTranslate - Copy button in InstantTranslate produces entities', 'Decode html entities on copy clipboard', '15'),
('2025-02-25', 'TRANSLATE-4420', 'bugfix', 'InstantTranslate - InstantTranslate: Default File-Format is not applied for File-translation bound to a certain customer', 'FIX: InstantTranslate file-translations did not use the default File-Format-Settings of the assigned Customer with added file-extensions', '15'),
('2025-02-25', 'TRANSLATE-4413', 'bugfix', 'Okapi integration - Files with file extensions partly or fully in uppercase are not accepted', 'File extensions in uppercase are accepted by InstantTranslate', '15'),
('2025-02-25', 'TRANSLATE-4072', 'bugfix', 'Import/Export, Workflows - notify users not working if previously unchecked in import wizard', '"Notify users" button is working now if "Notify users after import" was unchecked in import wizard', '15');