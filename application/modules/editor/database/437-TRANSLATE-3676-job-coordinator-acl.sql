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

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`)
VALUES
    ('editor', 'pm', 'setaclrole', 'jobCoordinator'),
    ('editor', 'admin', 'setaclrole', 'jobCoordinator'),
    ('editor', 'systemadmin', 'setaclrole', 'jobCoordinator'),

    ('editor', 'jobCoordinator', 'setaclrole', 'jobCoordinator'),
    ('editor', 'jobCoordinator', 'setaclrole', 'editor'),
    ('editor', 'jobCoordinator', 'setaclrole', 'instantTranslate'),

    ('editor', 'jobCoordinator', 'editor_userassocdefault', 'index'),
    ('default', 'jobCoordinator', 'auto_set_role', 'editor'),
    ('editor', 'jobCoordinator', 'editor_customer', 'index'),
    ('editor', 'jobCoordinator', 'editor_taskuserassoc', 'all'),
    ('editor', 'jobCoordinator', 'editor_task', 'index'),
    ('editor', 'jobCoordinator', 'editor_task', 'get'),
    ('editor', 'jobCoordinator', 'editor_task', 'editor_taskuserassoc'),
    ('editor', 'jobCoordinator', 'editor_task', 'userlist'),
    ('editor', 'jobCoordinator', 'editor_task', 'position'),
    ('editor', 'jobCoordinator', 'frontend', 'editAllTasks'),
    ('editor', 'jobCoordinator', 'frontend', 'editorAddUser'),
    ('editor', 'jobCoordinator', 'frontend', 'editorChangeUserAssocTask'),
    ('editor', 'jobCoordinator', 'frontend', 'editorDeleteUser'),
    ('editor', 'jobCoordinator', 'frontend', 'editorEditAllTasks'),
    ('editor', 'jobCoordinator', 'frontend', 'editorEditUser'),
    ('editor', 'jobCoordinator', 'frontend', 'editorMenuProject'),
    ('editor', 'jobCoordinator', 'frontend', 'editorProjectTask'),
    ('editor', 'jobCoordinator', 'frontend', 'loadAllTasks'),
    ('editor', 'jobCoordinator', 'frontend', 'userAdministration'),
    ('editor', 'jobCoordinator', 'editor_user', 'index'),
    ('editor', 'jobCoordinator', 'editor_user', 'post'),
    ('editor', 'jobCoordinator', 'editor_user', 'put'),
    ('editor', 'jobCoordinator', 'editor_user', 'delete'),
    ('editor', 'jobCoordinator', 'editor_user', 'get')
;

