-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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
-- 	            http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT

ALTER TABLE LEK_taskUserAssoc CHANGE lspJobId coordinatorGroupJobId INT (11) DEFAULT NULL;
ALTER TABLE LEK_lsp_user CHANGE lspId groupId INT (11) DEFAULT NULL;
ALTER TABLE LEK_lsp_customer CHANGE lspId groupId INT (11) DEFAULT NULL;
ALTER TABLE LEK_lsp_job CHANGE lspId groupId INT (11) DEFAULT NULL;
ALTER TABLE LEK_default_lsp_job CHANGE lspId groupId INT (11) DEFAULT NULL;

ALTER TABLE LEK_lsp RENAME LEK_coordinator_group;
ALTER TABLE LEK_lsp_user RENAME LEK_coordinator_group_user;
ALTER TABLE LEK_lsp_customer RENAME LEK_coordinator_group_customer;
ALTER TABLE LEK_lsp_job RENAME LEK_coordinator_group_job;
ALTER TABLE LEK_default_lsp_job RENAME LEK_default_coordinator_group_job;

UPDATE Zf_acl_rules
SET resource = REPLACE(resource, 'editor_lsp', 'editor_coordinatorgroup')
WHERE resource LIKE 'editor_lsp%';

UPDATE Zf_acl_rules
SET resource = 'editor_defaultcoordinatorgroupjob'
WHERE resource = 'editor_defaultlspjob';

UPDATE Zf_acl_rules
SET `right` = 'coordinatorGroupAdministration'
WHERE `right` = 'lspAdministration';

