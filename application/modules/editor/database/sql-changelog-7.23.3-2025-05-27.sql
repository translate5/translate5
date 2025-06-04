
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-05-27', 'TRANSLATE-4677', 'change', 't5memory - Add new code to reorganize', 'Added new code to reorganize memory codes', '15'),
('2025-05-27', 'TRANSLATE-4664', 'change', 'TBX-Import - TBX import: unexpected elements changed to expected - should be logged', 'distinct unexpected element names (if any) are now logged during tbx import', '15'),
('2025-05-27', 'TRANSLATE-4654', 'change', 't5memory, TM Maintenance - Change batch deletion behavior in TM Maintenance', 'Improved batch deletion in TM Maintenace for small batches', '15'),
('2025-05-27', 'TRANSLATE-4649', 'change', 'GroupShare integration - GroupShare: The request produced an error in the queried service', 'Fixed problem with concordance search in plugin.', '15'),
('2025-05-27', 'TRANSLATE-4513', 'change', 'TermTagger integration - Send TBX ID as part of the URL to the termTagger', '7:23.3: Fix log entries produced when termtagger is not properly answering
7.23.2: Fix implementation as specified, TBX loading problems in termtagger lead now to delay instead termtagger disabling
7.22.0: TBX ID is sent as part of the request/header to the termTagger', '15'),
('2025-05-27', 'TRANSLATE-4674', 'bugfix', 'VisualReview / VisualTranslation - Visual: mappingType is initialized wrongly when not given', 'Visual mappingType was initialized wrongly for Hotfolder & ConnectWorldserver Tasks', '15'),
('2025-05-27', 'TRANSLATE-4670', 'bugfix', 'Workflows - Optimize excessive data load in kpiAction', '7.23.3: Reduced KPI window loading time - further improvements will follow', '15'),
('2025-05-27', 'TRANSLATE-4665', 'bugfix', 'Export - Export tries to get worker that no longer in DB', 'Fix export worker in seldom case where worker was deleted before other depending calls were done
', '15'),
('2025-05-27', 'TRANSLATE-4662', 'bugfix', 'AI - RootCause: rec.getMajor is not a function', 'FIXED: server-side error behind a crash on attempt to add a pre-configured prompt to a training', '15'),
('2025-05-27', 'TRANSLATE-4650', 'bugfix', 'InstantTranslate - Newlines are sent to DeepL as individual segments', 'FIXED: newlines are now not sent to DeepL as indivudual segments', '15'),
('2025-05-27', 'TRANSLATE-4618', 'bugfix', 'Workflows - Restrict possible backup filename length', '7.23.3: Remove all non ascii characters from file name
7.23.2: Added max possible backup filename length limit to make sure backup files can be created and uploaded', '15'),
('2025-05-27', 'TRANSLATE-4252', 'bugfix', 'Import/Export, MatchAnalysis & Pretranslation - Task: word total in task counts "blocked" segments', 'FIXED: blocked segments are now excluded from word count calculation; to re-calculate the wordcount check/uncheck the edit 100% match checkbox', '15');