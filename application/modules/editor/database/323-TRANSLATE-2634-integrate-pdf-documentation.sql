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


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`)
VALUES ('runtimeOptions.frontend.helpWindow.editor.documentationUrl', '1', 'editor', 'system', '/help/editordocumentation/?lang={0}', '/help/editordocumentation/?lang={0}', '', 'string', 'The content of the defined URL will be loaded in this help page section. If empty and if the URL for the help video is also empty, nothing is loaded and the help button will not be available. If a video URL is also defined there will be a tab navigation in the help window.', '2', 'Help window URL PDF documentation: Editor', 'System setup: Help');

UPDATE `Zf_configuration` SET `description` = 'The content of the defined URL will be loaded in this help page section. If empty and if the URL for the PDF documentation is also empty, nothing is loaded and the help button will not be available. If a PDF documentation URL is also defined there will be a tab navigation in the help window.', `guiName` = 'Help window URL video: Editor' WHERE `name` = 'runtimeOptions.frontend.helpWindow.editor.loaderUrl';

