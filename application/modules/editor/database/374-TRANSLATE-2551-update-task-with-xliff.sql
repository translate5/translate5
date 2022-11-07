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

DELETE FROM `Zf_acl_rules` WHERE `resource` = 'editor_file';

INSERT INTO `Zf_acl_rules` (`id`, `module`, `role`, `resource`, `right`)
VALUES (null, 'editor', 'pm', 'editor_file', 'all');

INSERT INTO `Zf_acl_rules` (`id`, `module`, `role`, `resource`, `right`)
VALUES (null, 'editor', 'editor', 'editor_filetree', 'all');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `typeClass`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES('runtimeOptions.worker.MittagQI\\Translate5\\Task\\Reimport\\Worker.maxParallelWorkers','1','editor','worker','3','3','','integer',NULL,'Max parallel running workers of task reimport.','1','','','');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`)
VALUES ('editor', 'pm', 'frontend', 'taskReimport');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`)
VALUES ('editor', 'admin', 'frontend', 'taskReimport');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`)
VALUES ('editor', 'pmlight', 'frontend', 'taskReimport');