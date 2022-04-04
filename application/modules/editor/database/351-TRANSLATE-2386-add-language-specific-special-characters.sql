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

# Because of the missing map config editor, the config of this level is set to 1. When the map config editor will be implemented, the level should be changed to 16.
INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`, `level`, `guiName`, `guiGroup`, `comment`) VALUES ('runtimeOptions.editor.segments.editorSpecialCharacters', '1', 'editor', 'editor', '', '{"de":[{"unicode":"U+00AB","vizulized":"«"},{"unicode":"U+00BB","vizulized":"»"},{"unicode":"U+201E","vizulized":"„"},{"unicode":"U+201C","vizulized":"“"},{"unicode":"U+2013","vizulized":"–"},{"unicode":"U+00B2","vizulized":"²"},{"unicode":"U+2082","vizulized":"₂"},{"unicode":"U+2039","vizulized":"‹"},{"unicode":"U+203A","vizulized":"›"},{"unicode":"U+201A","vizulized":"‚"},{"unicode":"U+2018","vizulized":"‘"},{"unicode":"U+0160","vizulized":"Š"},{"unicode":"U+00A9","vizulized":"©"},{"unicode":"U+00AE","vizulized":"®"},{"unicode":"U+2122","vizulized":"™"}],"fr":[{"unicode":"U+00AB","vizulized":"«"},{"unicode":"U+00BB","vizulized":"»"},{"unicode":"U+2013","vizulized":"–"},{"unicode":"U+00B2","vizulized":"²"},{"unicode":"U+2082","vizulized":"₂"},{"unicode":"U+2019","vizulized":"’"},{"unicode":"U+0160","vizulized":"Š"},{"unicode":"U+00A9","vizulized":"©"},{"unicode":"U+00AE","vizulized":"®"},{"unicode":"U+2122","vizulized":"™"},{"unicode":"U+0153","vizulized":"œ"}],"it":[{"unicode":"U+00AB","vizulized":"«"},{"unicode":"U+00BB","vizulized":"»"},{"unicode":"U+00B2","vizulized":"²"},{"unicode":"U+2082","vizulized":"₂"},{"unicode":"U+2019","vizulized":"’"},{"unicode":"U+201C","vizulized":"“"},{"unicode":"U+201D","vizulized":"”"},{"unicode":"U+0160","vizulized":"Š"},{"unicode":"U+00A9","vizulized":"©"},{"unicode":"U+00AE","vizulized":"®"},{"unicode":"U+2122","vizulized":"™"}],"en":[{"unicode":"U+00B2","vizulized":"²"},{"unicode":"U+2082","vizulized":"₂"},{"unicode":"U+2019","vizulized":"’"},{"unicode":"U+2018","vizulized":"‘"},{"unicode":"U+201C","vizulized":"“"},{"unicode":"U+201D","vizulized":"”"},{"unicode":"U+0160","vizulized":"Š"},{"unicode":"U+00A9","vizulized":"©"},{"unicode":"U+00AE","vizulized":"®"},{"unicode":"U+2122","vizulized":"™"}]}', '', 'string', 'List of characters which will be shown as buttons in the editor for matching target language of the task and can be added in the caret location by clicking on them.', '1', 'Special characters', 'Editor: Segments', '');
