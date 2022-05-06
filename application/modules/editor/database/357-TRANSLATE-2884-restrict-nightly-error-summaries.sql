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

INSERT INTO `Zf_acl_rules` (`id`, `module`, `role`, `resource`, `right`)
VALUES
       (null, 'editor', 'systemadmin', 'auto_set_role', 'admin'),
       (null, 'editor', 'systemadmin', 'auto_set_role', 'editor'),
       (null, 'editor', 'systemadmin', 'auto_set_role', 'pm'),
       (null, 'editor', 'systemadmin', 'editor_log', 'all'),
       (null, 'editor', 'systemadmin', 'frontend', 'systemLog'),
       (null, 'editor', 'systemadmin', 'backend', 'systemLogSummary');

INSERT INTO `Zf_acl_rules` (`id`, `module`, `role`, `resource`, `right`)
SELECT null, 'editor', 'systemadmin', 'setaclrole', role FROM Zf_acl_rules group by role;

-- api role should only be settable for sys admins, since API has more rights as just the admin
DELETE FROM Zf_acl_rules WHERE module = 'editor' AND role = 'admin' AND resource = 'setaclrole' AND `right` = 'api';

UPDATE Zf_users set roles = concat('systemadmin,', roles) WHERE login in ('manager', 'mittagqi') AND (email like '%@translate5.net' or email like '%@mittagqi.com');