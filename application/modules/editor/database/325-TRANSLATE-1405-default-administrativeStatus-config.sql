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
 ** create a default administrative status attribute for all terms not having it
 */
UPDATE Zf_configuration t
LEFT JOIN (
    SELECT termNoteValue, mappedStatus
    FROM terms_term_status_map
    WHERE termNoteType = 'administrativeStatus'
    GROUP BY mappedStatus
    ) m ON m.mappedStatus = t.value
SET t.name        = 'runtimeOptions.tbx.defaultAdministrativeStatus',
    t.value       = ifnull(m.termNoteValue, 'admitted'),
    t.`default`   = 'admitted',
    t.defaults    = 'admitted',
    t.description = 'Default value for the usage status, if in the imported file no usage status is defined for a term.',
    t.guiName     = 'Default usage status for terminology'
WHERE name = 'runtimeOptions.tbx.defaultTermStatus';

