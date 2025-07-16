-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - '.(date('Y')).' Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`)
VALUES
    ('default', 'admin', 'auto_set_role', 'taskOverview'),
    ('default', 'api', 'auto_set_role', 'taskOverview'),
    ('default', 'clientpm', 'auto_set_role', 'taskOverview'),
    ('default', 'jobCoordinator', 'auto_set_role', 'taskOverview'),
    ('default', 'pm', 'auto_set_role', 'taskOverview'),
    ('editor', 'admin', 'auto_set_role', 'taskOverview'),
    ('editor', 'api', 'auto_set_role', 'taskOverview'),
    ('editor', 'clientpm', 'auto_set_role', 'taskOverview'),
    ('editor', 'pm', 'auto_set_role', 'taskOverview'),
    ('editor', 'pmlight', 'auto_set_role', 'taskOverview'),
    ('editor', 'systemadmin', 'auto_set_role', 'taskOverview')
;
