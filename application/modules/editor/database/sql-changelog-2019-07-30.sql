
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-07-30', 'TRANSLATE-1720', 'feature', 'Add segment editing history (snapshots) to JS debugging (rootcause)', 'Now each segment editing steps are logged in case of an error in the front-end.', '8'),
('2019-07-30', 'TRANSLATE-1273', 'feature', 'Propose new terminology and terminology changes', 'The TermPortal was extended with features to propose new terminology and terminology changes', '8'),
('2019-07-30', 'TRANSLATE-717', 'bugfix', 'Blocked column in segment grid shows no values and filter is inverted', 'In the segment grid the blocked column was empty and the filter values yes and no were flipped.', '14'),
('2019-07-30', 'TRANSLATE-1305', 'bugfix', 'Exclude framing internal tags from xliff import also for translation projects', 'This behaviour can be disabled by setting runtimeOptions.import.xlf.ignoreFramingTags to 0 in the configuration.', '12'),
('2019-07-30', 'TRANSLATE-1724', 'bugfix', 'TrackChanges: JavaSript error: WrongDocumentError (IE11 only)', 'Fixed JavaSript error WrongDocumentError (IE11 only).', '14'),
('2019-07-30', 'TRANSLATE-1721', 'bugfix', 'JavaScript error: me.allMatches is null', 'Fixed JavaScript error me.allMatches is null.', '14'),
('2019-07-30', 'TRANSLATE-1045', 'bugfix', 'JavaScript error: rendered block refreshed at 16 rows while BufferedRenderer view size is 48', 'Fixed JavaScript error rendered block refreshed at 16 rows while BufferedRenderer view size is 48', '14'),
('2019-07-30', 'TRANSLATE-1717', 'bugfix', 'Segments containing one whitespace character can crash Okapi on export', 'If in a XLF created from Okapi a segment with only a white-space character in the source is contained, this character is removed in the target. This led to errors in Okapi export then.', '12'),
('2019-07-30', 'TRANSLATE-1718', 'bugfix', 'Flexibilize LanguageResource creation via API by allow also language lcid', 'Flexibilize LanguageResource creation via API by allow also language lcid and RFC 5646 values', '8'),
('2019-07-30', 'TRANSLATE-1716', 'bugfix', 'Pretranslation does not replace tags in repetitions correctly', 'The correct tag content was not used, instead always the tags of the first segment were used.', '12'),
('2019-07-30', 'TRANSLATE-1634', 'bugfix', 'TrackChanges: CTRL+Z: undo works, but looses the TrackChange-INS', 'Using undo is working, but some TrackChanges tags were lost.', '14'),
('2019-07-30', 'TRANSLATE-1711', 'bugfix', 'TrackChanges are not added on segment reset to import state', 'Now the resetted content is placed in change marks too.', '14'),
('2019-07-30', 'TRANSLATE-1710', 'bugfix', 'TrackChanges are not correct on taking over TM match', 'On taking over a TM match the changes marks were not placed at the correct place.', '14'),
('2019-07-30', 'TRANSLATE-1627', 'bugfix', 'SpellCheck impedes TrackChanges for CTRL+V and CTRL+. into empty segments', 'No change marks were created on using CTRL+V and CTRL+. into empty segments with enabled spell checker.', '14');