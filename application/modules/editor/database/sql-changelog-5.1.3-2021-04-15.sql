
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2021-04-15', 'TRANSLATE-2363', 'feature', 'Development tool session:impersonate accessible via api', 'Enables an API user to authenticate in a name of different user. This feature is only available via translate5 API and for users with api role. More info you can find here : https://confluence.translate5.net/display/TAD/Session', '15'),
('2021-04-15', 'TRANSLATE-2471', 'bugfix', 'Auto-assigned users and deadline-date', 'Fixes missing deadline date for auto assigned users.', '15'),
('2021-04-15', 'TRANSLATE-2470', 'bugfix', 'Errors on log mail delivery stops whole PHP process', 'Errors on log e-mail delivery stops whole application process and leads to additional errors. The originating error is not logged in the translate5 log, only in the PHP log.', '15'),
('2021-04-15', 'TRANSLATE-2468', 'bugfix', 'Instant-translate custom title', 'Enables instant-translate custom title definition in client-specific locales.', '15'),
('2021-04-15', 'TRANSLATE-2467', 'bugfix', 'RootCause Error "Cannot read property \'nodeName\' of null"', 'Fixed Bug in TrackChanges when editing already edited segments', '15'),
('2021-04-15', 'TRANSLATE-2465', 'bugfix', 'Add version parameter to instanttranslate and termportal assets', 'The web assets (CSS and JS files) were not probably updated in termportal and instanttranslate after an update.', '15'),
('2021-04-15', 'TRANSLATE-2464', 'bugfix', 'Tag protection feature does not work if content contains XML comments or CDATA blocks', 'The tag protection feature was not working properly if the content contains XML comments or CDATA blocks.', '15'),
('2021-04-15', 'TRANSLATE-2463', 'bugfix', 'Match analysis and batch worker fix', 'Fixes that machine translation engines were queried to often with enabled batch quries and projects with multiple target languages and some other minor problems with match analysis and batch query workers.', '15'),
('2021-04-15', 'TRANSLATE-2461', 'bugfix', 'Non Public Plugin Classes referenced in public code', 'Pure public translate5 installations were not usable due a code reference to non public code.', '15'),
('2021-04-15', 'TRANSLATE-2459', 'bugfix', 'Segments grid scroll-to uses private function', 'Segments grid scroll to segment function improvement.', '15'),
('2021-04-15', 'TRANSLATE-2458', 'bugfix', 'Reenable logout on window close also for open id users', 'Currently the logout on window close feature is not working for users logging in via OpenID connect.', '15'),
('2021-04-15', 'TRANSLATE-2457', 'bugfix', 'Globalese engines string IDs crash translate5 task import wizard', 'Globalese may return also string based engine IDs, translate5 was only supporting integer ids so far.', '15'),
('2021-04-15', 'TRANSLATE-2431', 'bugfix', 'Errors on update with not configured mail server', 'If there is no e-mail server configured, the update shows an error due missing SMTP config.', '15');