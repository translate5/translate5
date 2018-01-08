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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2017-05-29', 'TRANSLATE-871', 'feature', 'New Tool-tip should show segment meta data over “segmentNrInTask” column', 'A new tool-tip over the segments “segmentNrInTask” column should show all segment meta data expect the data which is already shown (autostates, matchrate and locked (by css))', '14'),
('2017-05-29', 'TRANSLATE-878', 'feature', 'Enable GUI JS logger TheRootCause', 'By default the front-end error logger and feedback tool is enabled. The user always has the choice to send feedback to MittagQI. On JS errors the user gets also the choice to send technical information related to the error directly to MittagQI.', '14'),
('2017-05-29', 'TRANSLATE-877', 'feature', 'Make Worker URL separately configurable', 'For special system setups (for example using translate5 behind a SSL proxy) it may be necessary to configure the worker base URL separately compared to the public base URL.', '8'),
('2017-05-29', 'TRANSLATE-823', 'change', 'Internal tags are ignored for relays import segment comparison ', 'When relays data is imported, the source columns of relays and normal data are compared to ensure that the alignment is correct. In this comparison internal tags are ignored now completely. Also HTML entities are getting normalized on both sides of the comparison.', '12'),
('2017-05-29', 'TRANSLATE-870', 'change', 'Enable MatchRate and Relays column per default in ergonomic mode', 'Enable MatchRate and Relais column per default in ergonomic mode', '14'),
('2017-05-29', 'TRANSLATE-857', 'change', 'change target column names in the segment grid', 'The target column names in the segment grid are changed from just “target” to “target (version on import time)”', '14'),
('2017-05-29', 'TRANSLATE-880', 'change', 'XLF import: Copy source to target, if target is empty or does not exist', 'On translation tasks (no translation exists at all) the target fields were empty on import time. For XLF files this is changed: The source content is copied to the target column.', '12'),
('2017-05-29', 'TRANSLATE-897', 'change', 'changes.xliff generation: alt-trans shorttext for target columns must be changed', 'In the lat-trans fields of the generated changes.xliff files, the shorttext attribute is changed for target columns. It contains now target instead of Zieltext.', '12'),
('2017-05-29', 'TRANSLATE-875', 'bugfix', 'The width of the relays column was calculated wrong', 'Since using ergonomic mode as default mode, the width of the relays column was calculated too small', '14'),
('2017-05-29', 'TRANSLATE-891', 'bugfix', 'OpenTM2 responses containing Unicode characters and internal tags produces invalid HTML in the editor', 'If OpenTM2 returns a response containing Unicode characters with multiple bytes, and this response contains also internal tags, this was leading to invalid HTML on showing such a segment in the front-end.', '14'),
('2017-05-29', 'TRANSLATE-888', 'bugfix', 'Mask tab character in source files with internal tag (similar to multiple spaces)', 'Tabulator characters contained in the imported data are now converted to internal tags. A similar converting is already done for multiple white-space characters.', '14'),
('2017-05-29', 'TRANSLATE-879', 'bugfix', 'sdlxliff and XLF import does not work with missing target tags', 'On translation tasks the target tags in XML based import formats (SDLXLIFF and XLF) are missing. This leads to errors while importing such files. This is fixed for XLF files right now.', '12');