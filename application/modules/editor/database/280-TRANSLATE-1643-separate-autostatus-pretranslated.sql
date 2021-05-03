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

-- new uuid field for language resources
ALTER TABLE `LEK_languageresources`
ADD `langResUuid` varchar(36) NOT NULL COMMENT 'language resource guid for interoperability of language resources over different instances' AFTER `entityVersion`;

-- fill already existing language resources with an uuid
UPDATE `LEK_languageresources` SET `langResUuid` = uuid();

-- provide field for each segment, to reference the used language resource uuid on pre-translation. The lang res name is stored in the LEK_segments table directly.
ALTER TABLE `LEK_segments_meta`
ADD `preTransLangResUuid` varchar(36) DEFAULT NULL COMMENT 'if segment was field with pre-translation the language resource uuid is stored here for reference';
