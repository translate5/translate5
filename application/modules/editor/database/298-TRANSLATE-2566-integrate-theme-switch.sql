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

UPDATE `Zf_configuration`
SET `defaults`='aria,classic,crisp,crisp-touch,gray,neptune,neptune-touch,triton',
    `level`='32' ,
    `default`='triton'
WHERE `name`='runtimeOptions.extJs.cssFile';

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`)
VALUES ('runtimeOptions.frontend.changeUserThemeVisible', '1', 'editor', 'system', '1', '1', '', 'boolean', 'Can the user change the translate5 GUI layout theme from within the translate5 front-end ?', '2', 'User can change GUI layout theme', 'Editor: UI layout & more');
INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`)
VALUES ('runtimeOptions.cleanUpUserTheme', '1', 'editor', 'system', '0', '0', '', 'boolean', 'If set to active, a changed GUI layout theme is only temporary for the current user session.', '2', 'Reset GUI layout theme to default with new login.', 'Editor: UI layout & more');