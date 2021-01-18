
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2020-11-17', 'TRANSLATE-2225', 'feature', 'Import filter for special Excel file format containing texts with special length restriction needs', 'A client specific import filter for a data in a client specific excel file format.', '12'),
('2020-11-17', 'TRANSLATE-2296', 'change', 'Improve Globalese integration to work with project feature', 'Fix Globalese integration with latest translate5.', '12'),
('2020-11-17', 'TRANSLATE-2313', 'bugfix', 'InstantTranslate: new users sometimes can not use InstantTranslate', 'New users are sometimes not able to use instanttranslate. That depends on the showSubLanguages config and the available languages.', '8'),
('2020-11-17', 'TRANSLATE-2312', 'bugfix', 'Can\'t use "de" anymore to select a target language', 'In project creation target language field type "(de)" and you get no results. Instead typing "Ger" works. The first one is working now again.', '12'),
('2020-11-17', 'TRANSLATE-2311', 'bugfix', 'Cookie Security', 'Set the authentication cookie according to the latest security recommendations.', '8'),
('2020-11-17', 'TRANSLATE-2308', 'bugfix', 'Disable webserver directory listing', 'The apache directory listing is disabled for security reasons in the .htaccess file.', '8'),
('2020-11-17', 'TRANSLATE-2307', 'bugfix', 'Instanttranslate documents were accessable for other users', 'Instanttranslate documents could be accessed from other users by guessing the task id in the URL.', '8'),
('2020-11-17', 'TRANSLATE-2306', 'bugfix', 'Rename "Continue task later" button', 'The button in the editor to leave a task (formerly "Leave task"), which is currently labeled "Continue task later" is renamed to "Back to task list" as agreed in monthly meeting.', '15'),
('2020-11-17', 'TRANSLATE-2293', 'bugfix', 'Custom panel is not state full', 'The by default disabled custom panel is now also stateful.', '8'),
('2020-11-17', 'TRANSLATE-2288', 'bugfix', 'Reduce translate5.zip size to decrease installation time', 'The time needed for an update of translate5 depends also on the package size. The package was blown up in the last time, now the size is reduced again.', '8'),
('2020-11-17', 'TRANSLATE-2287', 'bugfix', 'Styles coming from plugins are added multiple times to the HtmlEditor', 'Sometimes the content styles of the HTML Editor are added multiple times, this is fixed.', '8'),
('2020-11-17', 'TRANSLATE-2265', 'bugfix', 'Microsoft translator directory lookup change', 'Solves the problem that microsoft translator does not provide results when searching text in instant translate with more then 5 characters.', '15'),
('2020-11-17', 'TRANSLATE-2224', 'bugfix', 'Deleted tags in TrackChanges do not really look deleted', 'FIX: Deleted tags in TrackChanges in the HTML-Editor now look deleted as well (decorated with a strike-through)', '15'),
('2020-11-17', 'TRANSLATE-2172', 'bugfix', 'maxNumberOfLines currently only works for pixel-length and not char-length checks', 'Enabling line based length check also for length unit character.', '12'),
('2020-11-17', 'TRANSLATE-2151', 'bugfix', 'Visual Editing: If page grows to large (gets blue footer) and had  been zoomed, some visual effects do not work, as they should', 'Fixed inconsistencies with the Text-Reflow and especially the page-growth colorization when zooming the visual review. Pages now keep their grown size  when scrolling them out of view & back.', '15'),
('2020-11-17', 'TRANSLATE-1034', 'bugfix', 'uploading file bigger as post_max_size or upload_max_filesize gives no error message, just a empty window', 'If uploading a file bigger as post_max_size or upload_max_filesize gives an error message is given now.', '8');
