
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
            
INSERT INTO `LEK_change_log` (`dateOfChange`, `jiraNumber`, `type`, `title`, `description`, `userGroup`) VALUES ('2023-11-08', 'TRANSLATE-3563', 'bugfix', 'Editor general - Fix plugin localized strings', 'Fix a problem in the plugin localizations preventing translate5 to be loaded after login.', '15'),
('2023-11-08', 'TRANSLATE-3558', 'bugfix', 'InstantTranslate - InstantTranslate missing white space between 2 sentences in target', 'FIXED: Multiple sentences are now concatenated with whitespaces in-between.', '15'),
('2023-11-08', 'TRANSLATE-3546', 'bugfix', 'Editor general, VisualReview / VisualTranslation - Editor user preferences not persistent, when task left in simple mode', 'FIXED: user preferences persistence on view mode change', '15'),
('2023-11-08', 'TRANSLATE-1068', 'bugfix', 'API - Improve REST API on wrong usage', '6.7.1: fix for special use case when authenticating against API session endpoint
6.7.0: API requests (expect file uploading requests) can now understand JSON in raw body, additionally to the encapsulated JSON in a data form field. Also a proper HTTP error code is sent when providing invalid JSON.', '15');