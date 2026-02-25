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
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `level`, `description`, `guiName`, `guiGroup`)
VALUES
    ('runtimeOptions.LanguageResources.t5memory.skipAuthor', 1, 'app', 'system', '0', '0', '', 'boolean', 2, 'If set to "true" on TMX import and TM update operations author will not be evaluated for segment uniqueness', 'Skip author evaluation on TM repetition check', 't5memory maintenance'),
    ('runtimeOptions.LanguageResources.t5memory.skipDocument', 1, 'app', 'system', '0', '0', '', 'boolean', 2, 'If set to "true" on TMX import and TM update operations document name will not be evaluated for segment uniqueness', 'Skip document name evaluation on TM repetition check', 't5memory maintenance'),
    ('runtimeOptions.LanguageResources.t5memory.skipContext', 1, 'app', 'system', '0', '0', '', 'boolean', 2, 'If set to "true" on TMX import and TM update operations context will not be evaluated for segment uniqueness', 'Skip context evaluation on TM repetition check', 't5memory maintenance'),

    ('runtimeOptions.LanguageResources.t5memory.useTmxUtilsConcat', 1, 'app', 'system', '1', '1', '', 'boolean', 1, 'If set to "true" t5memory will use the tmx-utils concat feature on import to merge segments that were split in the source file', 'Use tmx-utils concat on t5memory import', 'TMX processing integration'),
    ('runtimeOptions.LanguageResources.t5memory.useTmxUtilsTrim', 1, 'app', 'system', '1', '1', '', 'boolean', 1, 'If set to "true" t5memory will use the tmx-utils trim part of TMX file', 'Use tmx-utils trim on t5memory import', 'TMX processing integration'),
    ('runtimeOptions.LanguageResources.t5memory.useTmxUtilsFilter', 1, 'app', 'system', '1', '1', '', 'boolean', 1, 'If set to "true" t5memory will use the tmx-utils filter feature on import to filter out unwanted segments based on configurable rules', 'Use tmx-utils filter on t5memory import', 'TMX processing integration')
;
