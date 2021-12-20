
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-12-17', 'TRANSLATE-2761', 'change', 'Test for tbx specialchars import', 'Added test for import tbx containing specialchars', '15'),
('2021-12-17', 'TRANSLATE-2760', 'change', 'AutoQA also processed when performing an Analysis  & add AutoQA Reanalysis', '* The AnalysisOperation in a task\'s MatchAnalysis panel now covers a re-evaluation of the QA
* This makes the seperate Button to tag the Terms obsolete, so it is removed
* added Button to Re-check the QA in the task\'s QA panel', '15'),
('2021-12-17', 'TRANSLATE-2488', 'change', 'Excel export of TermCollection', 'Added ability to export TermCollections into xlsx-format', '15'),
('2021-12-17', 'TRANSLATE-2763', 'bugfix', 'Term term entries older than current import deletes also unchanged terms', 'TBX Import: The setting "Term term entries older than current import" did also delete the terms which are contained unchanged in the TBX.', '15'),
('2021-12-17', 'TRANSLATE-2759', 'bugfix', 'Deleted newlines were still counting as newline in length calculation', 'When using the line counting feature in segment content deleted newlines were still counted since they still exist as trackchanges.', '15'),
('2021-12-17', 'TRANSLATE-2758', 'bugfix', 'scrollToAnnotation: Annotation references, sorting and size', 'Scrolling, size and sorting of annotations has been fixed', '15'),
('2021-12-17', 'TRANSLATE-2756', 'bugfix', 'Segments were locked after pre-translation but no translation content was set', 'It could happen that repeated segments were blocked with a matchrate >= 100% but no content was pre-translated in the segment. Also the target original field was filled wrong on using repetitions. And the match-rate for repetitions is now 102% as defined and not original the percentage from the repeated segment. This is now the same behaviour as in the analysis.', '15'),
('2021-12-17', 'TRANSLATE-2755', 'bugfix', 'Workers getting PHP fatal errors remain running', 'Import workers getting PHP fatal errors were remain running, instead of being properly marked crashed. ', '15'),
('2021-12-17', 'TRANSLATE-2751', 'bugfix', 'Mouse over segment with add-annotation active', 'The cursor will be of type cross when the user is in annotation creation mode and the mouse is over the segment.', '15'),
('2021-12-17', 'TRANSLATE-2750', 'bugfix', 'Make project tasks overview and task properties resizable and stateful', 'The height of the project tasks overview and the property panel of a single task are now resizeable.', '15'),
('2021-12-17', 'TRANSLATE-2749', 'bugfix', 'Blocked segments in workflow progress', 'The blocked segments now will be included in the workflow step progress calculation.', '15'),
('2021-12-17', 'TRANSLATE-2747', 'bugfix', 'Proposals are not listed in search results in some cases', 'TermPortal: it\'s now possible to find proposals for existing terms using \'Unprocessed\' as a value of \'Process status\' filter', '15'),
('2021-12-17', 'TRANSLATE-2746', 'bugfix', 'Add a Value for "InstantTranslate: TM minimum match rate"', 'Set the default value to 70 for minimum matchrate allowed to be displayed in InstantTranslate result list for TM language resources.', '15'),
('2021-12-17', 'TRANSLATE-2745', 'bugfix', '500 Internal Server Error on creating comments', 'Creating a segment comment was leading to an error due the new comment overview feature.', '15'),
('2021-12-17', 'TRANSLATE-2744', 'bugfix', 'XLIFF2 Export with more than one translator does not work', 'The XLIFF2 export was not working with more than one translator associated to the task.', '15'),
('2021-12-17', 'TRANSLATE-2719', 'bugfix', 'TermPortal result column is empty, despite matches are shown', 'TermPortal: fixed \'left column is empty, despite matches are shown\' bug', '15');