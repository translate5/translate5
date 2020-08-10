
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-06-27', 'TRANSLATE-1676', 'feature', 'Disable file extension check if a custom bconf is provided', 'If a custom BCONF file for Okapi is provided in the import Package, the file type filter in the import is deactivated. So the it is possible to enable currently not supported file formats via Okapi.', '12'),
('2019-06-27', 'TRANSLATE-1665', 'change', 'Change font colour to black', 'The font colour in the segment grid is changed to pure black', '14'),
('2019-06-27', 'TRANSLATE-1701', 'bugfix', 'Searching in bookmarked segments leads to SQL error (missing column)', 'When setting the segment filter to show only bookmarked segments, performing a search with search and replace triggered that error', '14'),
('2019-06-27', 'TRANSLATE-1660', 'bugfix', 'Remove message for unsupported browser for MS Edge', 'The "unsupported browser" message is not shown any more for Edge users, IE 9 and 10 are not officially supported any more', '8'),
('2019-06-27', 'TRANSLATE-1620', 'bugfix', 'Relais (pivot) import does not work, if Trados alters mid', 'It can happen that the MIDs of the segments do not match between the different languages. If this is the case, the pivot language is matched by the segmentNrInTask field.', '12'),
('2019-06-27', 'TRANSLATE-1181', 'bugfix', 'Workflow Cron Daily actions are called multiple times', 'The delivery date remind e-mail were sent two times due this issue.', '12'),
('2019-06-27', 'TRANSLATE-1695', 'bugfix', 'VisualReview: segmentmap generation has a bad performance', 'On loading a VisualReview we had some performance issues. For tasks where the loading performance is bad, a DB table rebuild should be triggered by end and reopen that task.', '14'),
('2019-06-27', 'TRANSLATE-1694', 'bugfix', 'Allow SDLXLIFF tags with dashes in the ID', 'The task import do not stop anymore, if there are dashes in the tag IDs of the SDLXLIFF file.', '12'),
('2019-06-27', 'TRANSLATE-1691', 'bugfix', 'Search and Replace does not escape entities in the replaced text properly', 'On using "replace all" in conjunction with the & < > entities, this entities were saved wrong in the DB.', '12'),
('2019-06-27', 'TRANSLATE-1684', 'bugfix', 'Uneditable segments with tags only can lose content on export', 'Sometimes the content of non editable segments (containing tags only) is getting lost on export.', '12'),
('2019-06-27', 'TRANSLATE-1669', 'bugfix', 'repetition editor deletes wrong tags', 'It could happen, that on using the repetition editor some tags are removed by accident', '14'),
('2019-06-27', 'TRANSLATE-1693', 'bugfix', 'Search and Replace does not open segment on small tasks', 'The search and replace dialog did not open the segment if the segment grid was not scrolling on selecting a segment via the search.', '14'),
('2019-06-27', 'TRANSLATE-1666', 'bugfix', 'Improve error communication when uploading a import package without proofRead folder', 'Improve error communication when uploading a import package without proofRead folder, also re-enable the import button in the task add wizard', '12'),
('2019-06-27', 'TRANSLATE-1689', 'bugfix', 'Pressing "tab" in search and replace produces a JS error', 'Pressing "tab" in search and replace to jump between the input fields was producing a JS error', '14'),
('2019-06-27', 'TRANSLATE-1683', 'bugfix', 'Inserting white-space tags in the editor can overwrite other tags in the target', 'Inserting new white-space tags in the editor can overwrite other existing tags in the target content', '14'),
('2019-06-27', 'TRANSLATE-1659', 'bugfix', 'Change of description for auto-assignment area in user management', 'Change of textual description for auto-assignment area in user management', '12'),
('2019-06-27', 'TRANSLATE-1654', 'bugfix', 'TermTagger stops working on import of certain task - improved error management and logging', 'Since the reason for the crashes could not determined, the logging and error management in the term tagging process was improved. So if a termtagger is not reachable any more, it is not used any more until it is available again.', '8');