-- /*
-- START LICENSE AND COPYRIGHT
--
--  This file is part of translate5
--
--  Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `typeClass`,
                                `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES (
        'runtimeOptions.tmxImportProcessor.transformTusMapping',
        '1',
        'editor',
        'system',
        '',
        '',
        '',
        'map',
        'ZfExtended_DbConfig_Type_SimpleMap',
        concat(
            'Mapping that allows to define which TMX fields should be transformed to which fields in resulting file during TMX import.\n',
            'The mapping is defined as a map with one of: "author", "creationDate", "document" as key and the XPATH string as value.\n',
            'Example: "creationDate" => "//tuv[@xml:lang="{targetLang}"]/@creationdate" will map attribute of target language translation unit variant (<tuv xml:lang="en-gb" creationdate="20180821T131628Z">)) to creationDate field of resulting file.\n',
            '{sourceLang} and {targetLang} placeholders can be used in the XPATH string and will be replaced by source and target language of the Language resource.'
        ),
        4,
        'TMX Import: Mapping for TMX fields',
        'System setup: Language resources',
        ''
       );
