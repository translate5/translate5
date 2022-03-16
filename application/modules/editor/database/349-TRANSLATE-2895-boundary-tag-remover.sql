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
SET value = 'all'
-- old default was 1 so, set them to 'all'
WHERE name = 'runtimeOptions.import.xlf.ignoreFramingTags' and value = '1';

UPDATE `Zf_configuration`
SET value = 'none'
-- the ones had it disabled, should keep it disabled
WHERE name = 'runtimeOptions.import.xlf.ignoreFramingTags' and value = '0';

UPDATE `Zf_configuration`
SET `default` = 'all', `type` = 'string', defaults = 'none,paired,all',
    description = 'If enabled framing tags (tag pairs only or all tags that surround the complete segment) are ignored on import. Does work for native file formats and standard xliff. Does not work for sdlxliff. See http://confluence.translate5.net/display/TFD/Xliff.'
WHERE name = 'runtimeOptions.import.xlf.ignoreFramingTags';