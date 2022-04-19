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
SET `value` = '{\"de\":[{\"unicode\":\"U+00AB\",\"visualized\":\"«\"},{\"unicode\":\"U+00BB\",\"visualized\":\"»\"},{\"unicode\":\"U+201E\",\"visualized\":\"„\"},{\"unicode\":\"U+201C\",\"visualized\":\"“\"},{\"unicode\":\"U+2013\",\"visualized\":\"–\"},{\"unicode\":\"U+00B2\",\"visualized\":\"²\"},{\"unicode\":\"U+2082\",\"visualized\":\"₂\"},{\"unicode\":\"U+2039\",\"visualized\":\"‹\"},{\"unicode\":\"U+203A\",\"visualized\":\"›\"},{\"unicode\":\"U+201A\",\"visualized\":\"‚\"},{\"unicode\":\"U+2018\",\"visualized\":\"‘\"},{\"unicode\":\"U+0160\",\"visualized\":\"Š\"},{\"unicode\":\"U+00A9\",\"visualized\":\"©\"},{\"unicode\":\"U+00AE\",\"visualized\":\"®\"},{\"unicode\":\"U+2122\",\"visualized\":\"™\"}],\"fr\":[{\"unicode\":\"U+00AB\",\"visualized\":\"«\"},{\"unicode\":\"U+00BB\",\"visualized\":\"»\"},{\"unicode\":\"U+2013\",\"visualized\":\"–\"},{\"unicode\":\"U+00B2\",\"visualized\":\"²\"},{\"unicode\":\"U+2082\",\"visualized\":\"₂\"},{\"unicode\":\"U+2019\",\"visualized\":\"’\"},{\"unicode\":\"U+0160\",\"visualized\":\"Š\"},{\"unicode\":\"U+00A9\",\"visualized\":\"©\"},{\"unicode\":\"U+00AE\",\"visualized\":\"®\"},{\"unicode\":\"U+2122\",\"visualized\":\"™\"},{\"unicode\":\"U+0153\",\"visualized\":\"œ\"}],\"it\":[{\"unicode\":\"U+00AB\",\"visualized\":\"«\"},{\"unicode\":\"U+00BB\",\"visualized\":\"»\"},{\"unicode\":\"U+00B2\",\"visualized\":\"²\"},{\"unicode\":\"U+2082\",\"visualized\":\"₂\"},{\"unicode\":\"U+2019\",\"visualized\":\"’\"},{\"unicode\":\"U+201C\",\"visualized\":\"“\"},{\"unicode\":\"U+201D\",\"visualized\":\"”\"},{\"unicode\":\"U+0160\",\"visualized\":\"Š\"},{\"unicode\":\"U+00A9\",\"visualized\":\"©\"},{\"unicode\":\"U+00AE\",\"visualized\":\"®\"},{\"unicode\":\"U+2122\",\"visualized\":\"™\"}],\"en\":[{\"unicode\":\"U+00B2\",\"visualized\":\"²\"},{\"unicode\":\"U+2082\",\"visualized\":\"₂\"},{\"unicode\":\"U+2019\",\"visualized\":\"’\"},{\"unicode\":\"U+2018\",\"visualized\":\"‘\"},{\"unicode\":\"U+201C\",\"visualized\":\"“\"},{\"unicode\":\"U+201D\",\"visualized\":\"”\"},{\"unicode\":\"U+0160\",\"visualized\":\"Š\"},{\"unicode\":\"U+00A9\",\"visualized\":\"©\"},{\"unicode\":\"U+00AE\",\"visualized\":\"®\"},{\"unicode\":\"U+2122\",\"visualized\":\"™\"}]}'
WHERE (`name` = 'runtimeOptions.editor.segments.editorSpecialCharacters');
