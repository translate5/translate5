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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

UPDATE `Zf_configuration`
SET `description` = 'If enabled framing tags (tag pairs only [setting: paired]or all tags [setting: all] that surround the complete segment) are ignored on task import. Does work for native file formats and standard xliff. Does not work for sdlxliff. See http://confluence.translate5.net/display/TFD/Xliff. If you change this setting, in many cases it might make sense to clean existing TMs. For this, the content protection feature, that supports TM conversion, can be used as a workaround. Please consult the translate5 support, if you need to clean existing TMs for help.
This setting correlates with the TMX import setting "Strip framing tags at import", that should be set to the same value.'
WHERE `name` = 'runtimeOptions.import.xlf.ignoreFramingTags';

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES ('runtimeOptions.LanguageResources.t5memory.stripFramingTagsEnabled', '1', 'app', 'system', '0', '0', '', 'boolean', 'If enabled new option appears in UI', 1, 'Strip framing tags config', 'Language resources', '');
