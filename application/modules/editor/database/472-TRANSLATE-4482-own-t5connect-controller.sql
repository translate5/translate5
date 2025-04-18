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

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUE
    ('editor', 'api', 'editor_t5connect', 'index'),
    ('editor', 'api', 'editor_t5connect', 'failed'),
    ('editor', 'api', 'editor_t5connect', 'imported'),
    ('editor', 'api', 'editor_t5connect', 'confirmed'),
    ('editor', 'api', 'editor_t5connect', 'finished'),
    ('editor', 'api', 'editor_t5connect', 'setforeignstate'),
    ('editor', 'api', 'editor_t5connect', 'byforeignid');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES ('runtimeOptions.t5connect.foreignName',
        '1',
        'editor',
        't5connect',
        't5Connect',
        't5Connect',
        '',
        'string',
        't5connect: the used foreign-name to identify t5connect tasks',
        1,
        't5connect foreignName',
        'System setup: General',
        ''
       );

ALTER TABLE `LEK_task` ADD COLUMN `foreignState` VARCHAR(38) AFTER `foreignName`;
