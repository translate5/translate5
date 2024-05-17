
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-05-17', 'TRANSLATE-3534', 'feature', 'Import/Export, TrackChanges - TrackChanges sdlxliff round-trip', 'Accept track changes sdlxliff markup on import and transform it to translate5 syntax.
Propagate translate5 track changes to sdlxliff file on export', '15'),
('2024-05-17', 'TRANSLATE-3931', 'change', 'ConnectWorldserver, Import/Export - Optionally remove content from sdlxliff target segments, that contain only tags and/or whitespace', '7.5.0: Add possibility to optionally remove content from sdlxliff target segments, that contain only tags and/or whitespace ', '15'),
('2024-05-17', 'TRANSLATE-3923', 'change', 'Auto-QA - "Not found in target" category according to target term', 'Quality errors in \'Not found in target\' category group now count cases when best possible translations of source terms are not found in segment target', '15'),
('2024-05-17', 'TRANSLATE-3914', 'change', 'VisualReview / VisualTranslation - Change visual wget test data location', 'Change internas of the wget test.', '15'),
('2024-05-17', 'TRANSLATE-3905', 'change', 'InstantTranslate - Improve API usage to provide file content as normal POST parameter', 'Improve the instanttranslate API to enable filepretranslations also via plain POST requests.', '15'),
('2024-05-17', 'TRANSLATE-3896', 'change', 'Import/Export - Use Okapi for CSV files by default', 'OKAPI now is the default Parser for CSV files and the translate5 internal parser has to be enabled in the config if it shall be used instead', '15'),
('2024-05-17', 'TRANSLATE-3537', 'change', 'Import/Export - Process comments from xliff 1.2 files', '7.5.0: Change export config label and description
6.8.0: XLF comments placed in note tags are now also imported and exported as task comments. The behavior is configurable.', '15'),
('2024-05-17', 'TRANSLATE-3949', 'bugfix', 't5memory - Reimport segments does not work as expected', 'Fix reimport task into t5memory', '15'),
('2024-05-17', 'TRANSLATE-3948', 'bugfix', 'Import/Export - FIX: pmlight cannot import tasks', 'FIX: pm-light role could not import tasks due to insufficient rights', '15'),
('2024-05-17', 'TRANSLATE-3937', 'bugfix', 'Import/Export - Matchrate calculated wrong on import', 'Fixed match rate calculation on importing xlf files containing alt-trans nodes', '15'),
('2024-05-17', 'TRANSLATE-3935', 'bugfix', 'Import/Export - SQL query runs into timeout with large file with many repetitions', 'Fix for deadlock problem when syncing repetitions.', '15'),
('2024-05-17', 'TRANSLATE-3934', 'bugfix', 'Import/Export - hotfolder project export: warning for empty segments', 'The warning E1150 if okapi export had empty targets is now logged only if there was an error on exporting via Okapi.', '15'),
('2024-05-17', 'TRANSLATE-3930', 'bugfix', 't5memory - Fix stripFramingTags parameter in request to t5memory', 'Fixed passing "strip framing tags" value to t5memory', '15'),
('2024-05-17', 'TRANSLATE-3926', 'bugfix', 'GroupShare integration - Fix GroupShare connector in order to work with translate5 7.4.0 and 7.4.1', 'GroupShare plug-in was not compatible to latest version 7.4.0 and 7.4.1', '15'),
('2024-05-17', 'TRANSLATE-3921', 'bugfix', 't5memory - Disable direct t5memory TM download due data disclosure', 'Disabled t5memory download TM functionality due a data disclosure - the TM file did contain the filenames of other opened TM files at the same time.', '15'),
('2024-05-17', 'TRANSLATE-3920', 'bugfix', 'User Management - Hotfolder projects make Client PM selectable as default', 'Hotfolder plugin: Add clientPm role to PM list in settings', '15'),
('2024-05-17', 'TRANSLATE-3911', 'bugfix', 'Configuration - Hotfolder settings passwort and DeepL API key readable when write protected', 'Configs visibility can be restricted based on a user roles.', '15'),
('2024-05-17', 'TRANSLATE-3907', 'bugfix', 'Hotfolder Import - Hotfolder Bug fixes', 'Hotfolder plugin: use PM over-written on client level', '15'),
('2024-05-17', 'TRANSLATE-3882', 'bugfix', 'Export - Export of Project Fails due to XML Parser Problems', 'FIX: BUG with XML-Parser during Export', '15'),
('2024-05-17', 'TRANSLATE-3869', 'bugfix', 'Import/Export - trackChanges for sdlxliff should only be contained in Changes-Export', 'Fix: Export sdlxliff without track changes no longer produce revision tags', '15'),
('2024-05-17', 'TRANSLATE-3749', 'bugfix', 'Auto-QA - QA consistency wrong results', 'FIX: Evaluation of QA errors/problems did not respect locked segments. Now locked segments will not count for QA problems', '15'),
('2024-05-17', 'TRANSLATE-3713', 'bugfix', 'TermTagger integration, usability editor - Wrong target term high-lighted in right column of the editor', 'Improved target terms usage highlighting in right-side Termportlet', '15'),
('2024-05-17', 'TRANSLATE-2500', 'bugfix', 'Main back-end mechanisms (Worker, Logging, etc.) - Worker Architecture: Solving Problems with Deadlocks and related Locking/Mutex Quirks', '7.5.0 Improved the setRunning condition to reduce duplicated worker runs
5.2.2 Improved the internal worker handling regarding DB dead locks and a small opportunity that workers run twice.', '15');