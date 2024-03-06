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


UPDATE Zf_configuration t
SET t.value = 0,
    t.default = 0,
    t.description = 'If enabled "Source text" field is used as reference for checking and copying tags, otherwise "Target text at time of import" is used. This is only for review tasks, so tasks where directly the bilingual files have been imported to translate5 and even for those tasks only for segments, that had already content in the target of the imported bilingual segments. For sdlxliff files it is important, that this config is disabled. If you enable it, the sdlxliff files exported from translate5 may be corrupt in Trados. Please see for reasons why: https://jira.translate5.net/browse/TRANSLATE-3780'
WHERE t.name = 'runtimeOptions.editor.frontend.reviewTask.useSourceForReference';

UPDATE LEK_customer_config c
SET c.value = 0
WHERE c.name = 'runtimeOptions.editor.frontend.reviewTask.useSourceForReference';