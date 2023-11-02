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

INSERT INTO `LEK_workflow_action` (`workflow`,`trigger`,`inStep`,`byRole`,`userState`,`actionClass`,`action`,`parameters`,`position`)
VALUES 
('default', 'doCronDaily', null, null, null, 'MittagQI\\Translate5\\Workflow\\DeleteOpenidUsersAction', 'deleteOpenidUsers', null, 0);

INSERT INTO `Zf_configuration`
(`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `typeClass`, `level`, `description`, `guiName`, `guiGroup`)
VALUES
    (
        'runtimeOptions.openid.fallbackPm',
        1,
        'default',
        'openid',
        '',
        '',
        '',
        'string',
        '\\MittagQI\\Translate5\\DbConfig\\Type\\CoreTypes\\DefaultPmType',
        2,
        'PM that will be assigned to a task owned by auto-deleted SSO PM. Fallback PM cannot be auto-deleted. If none provided - no PM will be auto-deleted',
        'SSO fallback PM',
        'System setup: Authentication'
    ),
    (
        'runtimeOptions.openid.removeUsersAfterDays',
        1,
        'default',
        'openid',
        '0',
        '',
        '',
        'integer',
        '',
        2,
        'Set number of days that have passed since the last login of SSO user before automatic deletion. 0 days means: do not delete inactive users. Attention: This period of days needs to be relevantly longer then your session timeout. Otherwise it may happen, that a user is deleted, that still has an active session. Notice: user task associations will be deleted on user deletion',
        'Remove SSO (OpenId) users after X days',
        'System setup: Authentication'
    );

UPDATE `Zf_configuration` SET `description` = 'Session lifetime in seconds. Attention: The session timeout needs to be relevantly shorter then the period of time, after which SSO users are deleted automatically after their last login. Otherwise it may happen, that a user is deleted, that still has an active session.' WHERE `name` = 'resources.ZfExtended_Resource_Session.lifetime';
