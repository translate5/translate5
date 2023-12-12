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

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`,
                                `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES ('runtimeOptions.import.xliff.importComments', '1', 'editor', 'import', '1', '1', '', 'boolean',
        'If set to active, the segment comments will be imported from the imported bilingual file (if this is supported by the implementation for that specific xliff type).',
        8, 'XLIFF comments: Import them', 'File formats', '');

INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`,
                                `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES ('runtimeOptions.export.xliff.commentAddTranslate5Namespace', '1', 'editor', 'export', '1', '1', '', 'boolean',
        'If set to active, additional information like author and creation date are added as translate5 specific attributes to the exported note tag - for default xlf comment export only.',
        8, 'XLIFF comments: Export note tag attributes', 'File formats', '');
