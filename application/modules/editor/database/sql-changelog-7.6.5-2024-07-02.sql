
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2024-07-02', 'TRANSLATE-3593', 'feature', 'Auto-QA, TermTagger integration - Split \'Terminology > Not found in target\' AutoQA-category into 4 categories', 'translate5 - 7.4.0: \'Terminology > Not found in target\' quality is now split into 4 sub-categories
translate5 - 7.6.5:  Additional improvements ', '15'),
('2024-07-02', 'TRANSLATE-4037', 'change', 'LanguageResources, t5memory - On resaving segments to TM: Log segmentnumber in task', 'On reimporting segments to the t5memory failed segment ids are now added to the log record.', '15'),
('2024-07-02', 'TRANSLATE-4035', 'change', 'ConnectWorldserver - Plugin ConnectWorldserver: Cache for expensive function getAllUsers()', 'To speed things up, the result of a once loaded list is cached inside Translate5. Default timeout for this cache is 1 hour (60 minutes) and is be configurable by a Translate5 config.

!!! a once given timeout can not be shortend. This means: if cache is stored with timeout 60 it will be used for 60 minutes. !!!', '15'),
('2024-07-02', 'TRANSLATE-4033', 'change', 'InstantTranslate - InstantTranslate:TM minimum match rate overwritable on client level', 'Enables clients overwrite for match.rate border config in instant translate.', '15'),
('2024-07-02', 'TRANSLATE-4027', 'change', 'Auto-QA, TermTagger integration - Restore client-side termtagging for old tasks', 'TermTagging ability on client-side is now restored in order to be able to work with old tasks and to provide some transition period of time needed for the end users', '15'),
('2024-07-02', 'TRANSLATE-3995', 'change', 'file format settings - Remove not so useful rule from t5 default SRX', 'Enhancement: Remove Rule from t5 default file-format settings that tried to segment malformed sentences "A sentence.The next sentence." but did more harm than good', '15'),
('2024-07-02', 'TRANSLATE-4049', 'bugfix', 'Export - Html entities not escaped on sdlxliff export', 'Fix: Escape html entities on sdlxliff export', '15'),
('2024-07-02', 'TRANSLATE-4038', 'bugfix', 'InstantTranslate - HOTFIX: InstantTranslate GUI may leads to request-buildup on the backend', 'FIX: InstantTranslate may cause request-buildups in the backend degrading performance significantly. The fix changes the way InstantTranslate works:
* An instant translation request is only sent after the request before returned.
* If the system is too slow for "instant" translation (or too many Languageresources are assigned to the current customer) Instant translate will switch back to manual mode with a "translate" button', '15'),
('2024-07-02', 'TRANSLATE-4021', 'bugfix', 'VisualReview / VisualTranslation - Visual does not reflect changes in the WYSIWYG with freshly imported translation tasks', 'FIX: Visual does not reflect changes in the WYSIWYG with freshly imported translation tasks', '15'),
('2024-07-02', 'TRANSLATE-3971', 'bugfix', 'Import/Export - SDLXLIFF internal tags with "textual" IDs', 'SDLXLIFF: Fixed processing of format tags from QuickInsertsList', '15'),
('2024-07-02', 'TRANSLATE-3714', 'bugfix', 'Editor general, usability editor - Summarize diffs in fuzzy match results', 'FIXED: problem with diff appearance in fuzzy match panel', '15');