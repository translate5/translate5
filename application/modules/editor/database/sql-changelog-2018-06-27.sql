
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2018-06-27', 'TRANSLATE-1269', 'feature', 'TermPortal: Enable deletion of older terms', 'Enable deletion of older terms in the TermPortal via config', '8'),
('2018-06-27', 'TRANSLATE-858', 'feature', 'SpellCheck: Integrate languagetool grammer, style and spell checker as micro service', 'Integrate languagetool grammer, style and spell checker as micro service. Languagetool must be setup as separate server.', '14'),
('2018-06-27', 'VISUAL-44', 'feature', 'VisualReview: Make "switch editor mode"-button configureable in visualReview', 'The VisualReview "switch editor mode"-button can be disabled via configuration', '12'),
('2018-06-27', 'TRANSLATE-1310', 'change', 'Improve import performance by SQL optimizing in metacache update', 'Improve import performance by SQL optimizing in metacache update', '12'),
('2018-06-27', 'TRANSLATE-1317', 'change', 'A check for application:/data/tbx-import folder was missing', 'A check for application:/data/tbx-import folder was missing', '8'),
('2018-06-27', 'TRANSLATE-1304', 'change', 'remove own js log call for one specific segment editing error in favour of rootcause', 'remove own js log call for one specific segment editing error in favour of rootcause', '8'),
('2018-06-27', 'TRANSLATE-1287', 'change', 'TermPortal: Introduce scrollbar in left result column of termPortal', 'TermPortal: Introduce scrollbar in left result column of termPortal', '12'),
('2018-06-27', 'TRANSLATE-1296', 'change', 'Simplify error message on missing tags on saving a segment', 'Simplify error message on missing tags on saving a segment', '14'),
('2018-06-27', 'TRANSLATE-1295', 'change', 'Remove sorting by click on column header in editor', 'Remove sorting by click on column header in editor', '14'),
('2018-06-27', 'TRANSLATE-1311', 'bugfix', 'segmentMeta transunitId was set to null or was calculated wrong for string ids', 'For non XLIFF imports and XLIFF Imports where the trans-unit id was not an integer the final transunitId stored in the DB was calculated wrong. This leads to a wrong meta cache for sibling data which then results in corrupt data crashing the frontend.', '8'),
('2018-06-27', 'TRANSLATE-1313', 'bugfix', 'No error handling if tasks languages are not present in TBX', 'No error handling if tasks languages are not present in TBX', '8'),
('2018-06-27', 'TRANSLATE-1315', 'bugfix', 'SpellCheck & TrackChanges: corrected errors still marked', 'SpellCheck & TrackChanges: corrected errors still marked', '14'),
('2018-06-27', 'T5DEV-245', 'bugfix', 'VisualReview: Error on opening a segment', 'VisualReview: Error on opening a segment', '14'),
('2018-06-27', 'TRANSLATE-1283', 'bugfix', 'TermPortal: Use internal translation system for term attribute labels', 'TermPortal: Use internal translation system for term attribute labels', '8'),
('2018-06-27', 'TRANSLATE-1318', 'bugfix', 'TermPortal: Pre-select search language with matching GUI language group', 'TermPortal: Pre-select search language with matching GUI language group', '12'),
('2018-06-27', 'TRANSLATE-1294', 'bugfix', 'TermPortal: Undefined variable: translate in termportal', 'TermPortal: Undefined variable: translate in termportal', '8'),
('2018-06-27', 'TRANSLATE-1292', 'bugfix', 'TermPortal: Undefined variable: file in okapi worker', 'TermPortal: Undefined variable: file in okapi worker', '8'),
('2018-06-27', 'TRANSLATE-1286', 'bugfix', 'TermPortal: Number shows up, when selecting term from the live search', 'TermPortal: Number shows up, when selecting term from the live search', '8');