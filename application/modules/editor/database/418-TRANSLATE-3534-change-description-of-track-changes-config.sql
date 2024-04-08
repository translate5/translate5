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

UPDATE Zf_configuration set
    description = 'Defines if SDLXLIFF change marks should be applied to and removed from the content, or if they should produce an error on import. If translate5 trackChanges plug-in is active, this config is disregarded and sdlxliff trackChanges mark-up is converted to translate5 trackChanges mark-up on import and reconverted to sdlxliff markup on export. See https://confluence.translate5.net/display/BUS/SDLXLIFF.',
    guiName = 'SDLXLIFF track changes: Apply on import (config has no effect, if translate5 trackChanges plug-in is active)'
where name = 'runtimeOptions.import.sdlxliff.applyChangeMarks';
