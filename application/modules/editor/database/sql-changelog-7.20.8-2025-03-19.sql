
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2025-03-19', 'TRANSLATE-4536', 'change', 'Import/Export - Plugin PlunetConnector JS Injection after Update to Plunet 10', 'TRANSLATE-4536: Plugin PlunetConnector JS Injection after Update to Plunet 10
- added header "Cross-Origin-Resource-Policy" and "Referrer-Policy" for JS injection;
- new JS selector for T5-icon clone-original;
- improved logging;
- update T5 import-worker trigger;', '15'),
('2025-03-19', 'TRANSLATE-4093', 'change', 'file format settings - OKAPI integration: Compatibility with 1.47, clean up Pipelines', '7.20.8: Fix loosing of Json codefinder rules on Fprm upgrade
7.19.0: File Format Settings: General compatibility with Okapi 1.47, improved Pipeline handling', '15'),
('2025-03-19', 'TRANSLATE-4555', 'bugfix', 'MatchAnalysis & Pretranslation - Segment can not be saved due TM usage log errors', 'The usage of TMs with penalties led to non savable segments in some circumstances.', '15'),
('2025-03-19', 'TRANSLATE-4551', 'bugfix', 't5memory - Segments are saved to not writable task tms', 'Now segments are saved only to task TMs associated to a task with writable checkbox enabled', '15'),
('2025-03-19', 'TRANSLATE-4546', 'bugfix', 'GroupShare integration - GroupShare TMs can not by synchronized', 'GroupShare synchronisation was not working any more if some TMs have to be deleted.', '15'),
('2025-03-19', 'TRANSLATE-4537', 'bugfix', 'InstantTranslate - Misleading error message when opening file translation', 'Choosing detect language in InstantTranslate text translation tab, then changing to file translation shows an unnecessary and misleading error message.', '15');