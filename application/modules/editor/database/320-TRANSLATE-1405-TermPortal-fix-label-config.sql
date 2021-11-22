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

 /*
 ** convert multiple termLabelMap configs into one config
 */
INSERT INTO Zf_configuration
(name, confirmed, module, category, value, `default`, `defaults`, `type`, description, `level`, guiName, guiGroup, comment)
VALUES('runtimeOptions.tbx.termLabelMap', 1, 'editor', 'termtagger', '{}', '{"admittedTerm": "permitted", "deprecatedTerm": "forbidden", "legalTerm": "permitted", "preferredTerm": "preferred", "regulatedTerm": "permitted", "standardizedTerm": "permitted", "supersededTerm": "forbidden"}', '', 'map', 'Defines how the Term Status should be visualized in the Frontend, valid values are preferred,permitted,forbidden', 1, '', '', '');

SET @res = JSON_OBJECT();

SELECT @res := JSON_INSERT(@res, CONCAT('$.', SUBSTRING_INDEX(replace(name, 'runtimeOptions.tbx.termLabelMap.', ''),'.',1)), value)
 FROM Zf_configuration
 where name like 'runtimeOptions.tbx.termLabelMap.%';

UPDATE Zf_configuration c
SET c.value = @res
where c.name = 'runtimeOptions.tbx.termLabelMap';

DELETE FROM Zf_configuration WHERE name like 'runtimeOptions.tbx.termLabelMap.%';
