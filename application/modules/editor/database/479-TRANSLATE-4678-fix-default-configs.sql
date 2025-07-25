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

UPDATE `Zf_configuration` SET `default` = '["internal:internal_tag_structure_faulty"]'
WHERE `name` = 'runtimeOptions.autoQA.mustBeZeroErrorsQualities';

UPDATE `Zf_configuration` SET `default` = '5034,5035,6044,5037,8009'
WHERE `name` = 'runtimeOptions.LanguageResources.t5memory.memoryOverflowErrorCodes';

UPDATE `Zf_configuration` SET `default` = '0'
WHERE `name` = 'runtimeOptions.openid.removeUsersAfterDays';

UPDATE `Zf_configuration` SET `default` = '{"admittedTerm": "permitted", "deprecatedTerm": "forbidden", "legalTerm": "permitted", "preferredTerm": "preferred", "regulatedTerm": "permitted", "standardizedTerm": "standardized", "supersededTerm": "forbidden"}'
WHERE `name` = 'runtimeOptions.tbx.termLabelMap';
