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

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES ('runtimeOptions.import.callbackUrl', '1', 'editor', 'import', '', '', '', 'string', 'A URL which is called via POST after import. The imported task is send as raw JSON in that request.', 8, 'Import: callback URL', 'System setup: Import', ''),
       ('runtimeOptions.import.timeout', '1', 'editor', 'import', '48', '48', '', 'integer', 'The timeout in hours after which a task in status import is set to status error.', 2, 'Import: timeout', 'System setup: Import', '');

-- fix wrong ACL rule
UPDATE `Zf_acl_rules` set `resource` = 'editor_fakelangres' WHERE `resource` = 'editor_fakelang';

-- add a created timestamp for tasks
ALTER TABLE LEK_task
ADD COLUMN `created` timestamp NOT NULL DEFAULT current_timestamp;

-- initially we take over the orderdate, it is not perfect but better then nothing
UPDATE LEK_task
SET created = orderdate
WHERE id > 0;