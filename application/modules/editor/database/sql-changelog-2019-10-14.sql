
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

INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2019-10-14', 'TRANSLATE-1378 Search & Replace', 'change', 'Search & Replace: Activate "Replace"-key right away', 'Bislang war der „Ersetzen“-Button erst aktiv, nachdem etwas bereits gesucht wurde. Nun ist er sofort aktiv und sucht etwas und markiert es zum Ersetzen.', '14'),
('2019-10-14', 'TRANSLATE-1615 Move whitespace buttons to segment meta-panel', 'change', 'Move whitespace buttons to segment meta-panel', 'The buttons to add extra whitespace have been moved from beneath the opened segment to the right column', '14'),
('2019-10-14', 'TRANSLATE-1815 Segment editor should automatically move down a bit', 'change', 'Segment editor should automatically move down a bit', 'The opened segment now tends to focus in below the upper third of the segments, if this is possible. This enables the user to always see preceeding and following content.', '14'),
('2019-10-14', 'TRANSLATE-1836', 'change', 'Get rid of message "Segment updated in TM!"', '', '14'),
('2019-10-14', 'TRANSLATE-1826', 'bugfix', 'Include east asian sub-languages and thai in string-based termTagging', 'For Asian sub-languages and Thai, string-based terminology highlighting was enabled, otherwise no term tagging would be possible there', '12');