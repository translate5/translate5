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

-- get the current system default
SELECT @defaultTheme := value
FROM `Zf_configuration`
WHERE name = 'runtimeOptions.extJs.cssFile';

-- add the new system default theme
INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES ('runtimeOptions.extJs.defaultTheme', 1, 'editor', 'layout', @defaultTheme, 'triton', 'aria,classic,crisp,crisp-touch,gray,neptune,neptune-touch,triton', 'string', 'The system default layout theme to be used. Not all layouts are thoroughly tested, so layout fixes may be needed.', 1, '', '', '');

-- reset the user/task/client configs where triton is set to the system default theme
UPDATE `LEK_user_config`
SET value = 'default'
WHERE name = 'runtimeOptions.extJs.cssFile' and value = 'triton';
UPDATE `LEK_task_config`
SET value = 'default'
WHERE name = 'runtimeOptions.extJs.cssFile' and value = 'triton';
UPDATE `LEK_customer_config`
SET value = 'default'
WHERE name = 'runtimeOptions.extJs.cssFile' and value = 'triton';

-- fix the current config
UPDATE `Zf_configuration`
    SET description = 'Default theme the user should get in the UI, default can be defined separately',
        guiName = 'The users default theme',
        guiGroup = 'Editor: UI layout & more',
        defaults = 'default,aria,triton',
        value = IF(value='triton', 'triton', IF(value='aria', 'aria', 'default')),
        name = 'runtimeOptions.extJs.theme'
    WHERE name = 'runtimeOptions.extJs.cssFile';

-- fix specific config names
UPDATE `LEK_user_config`
SET name = 'runtimeOptions.extJs.theme'
WHERE name = 'runtimeOptions.extJs.cssFile';
UPDATE `LEK_task_config`
SET name = 'runtimeOptions.extJs.theme'
WHERE name = 'runtimeOptions.extJs.cssFile';
UPDATE `LEK_customer_config`
SET name = 'runtimeOptions.extJs.theme'
WHERE name = 'runtimeOptions.extJs.cssFile';