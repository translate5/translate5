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
 ** create a new table for configuration of term status map
 */
DROP TABLE IF EXISTS `terms_term_status_map`;
CREATE TABLE `terms_term_status_map` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `termNoteType` varchar(128) NOT NULL,
    `termNoteValue` varchar(64) NOT NULL,
    `mappedStatus` enum('supersededTerm', 'preferredTerm', 'admittedTerm', 'deprecatedTerm'),
    PRIMARY KEY (`id`),
    UNIQUE (`termNoteType`, `termNoteValue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- add the previous existing custom values
INSERT INTO `terms_term_status_map` (termNoteType, termNoteValue, mappedStatus)
select SUBSTRING_INDEX(replace(name, 'runtimeOptions.tbx.termImportMap.', ''),'.',1) as termNoteType, SUBSTRING_INDEX(name,'.',-1) as termNoteValue, value as mappedStatus from Zf_configuration where name like 'runtimeOptions.tbx.termImportMap.%' and value in ('supersededTerm', 'preferredTerm', 'admittedTerm', 'deprecatedTerm');

-- add the default values
INSERT INTO `terms_term_status_map` (termNoteType, termNoteValue, mappedStatus)
VALUES
('normativeAuthorization', 'preferredTerm', 'preferredTerm'),
('normativeAuthorization', 'standardizedTerm', 'admittedTerm'),
('normativeAuthorization', 'regulatedTerm', 'admittedTerm'),
('normativeAuthorization', 'legalTerm', 'admittedTerm'),
('normativeAuthorization', 'deprecatedTerm', 'deprecatedTerm'),
('normativeAuthorization', 'supersededTerm', 'deprecatedTerm'),
('normativeAuthorization', 'admittedTerm', 'admittedTerm'),
('normativeAuthorization', 'proposed', 'preferredTerm'),
('normativeAuthorization', 'admitted', 'admittedTerm'),
('normativeAuthorization', 'deprecated', 'deprecatedTerm'),
('administrativeStatus', 'preferredTerm-admn-sts', 'preferredTerm'),
('administrativeStatus', 'standardizedTerm-admn-sts', 'admittedTerm'),
('administrativeStatus', 'regulatedTerm-admn-sts', 'admittedTerm'),
('administrativeStatus', 'legalTerm-admn-sts', 'admittedTerm'),
('administrativeStatus', 'deprecatedTerm-admn-sts', 'deprecatedTerm'),
('administrativeStatus', 'supersededTerm-admn-sts', 'deprecatedTerm'),
('administrativeStatus', 'admittedTerm-admn-sts', 'admittedTerm'),
('administrativeStatus', 'preferred', 'preferredTerm'),
('administrativeStatus', 'admitted', 'admittedTerm'),
('administrativeStatus', 'notRecommended', 'deprecatedTerm'),
('administrativeStatus', 'obsolete', 'deprecatedTerm');

DELETE FROM Zf_configuration WHERE name = 'runtimeOptions.tbx.termImportMap';
DELETE FROM Zf_configuration WHERE name like 'runtimeOptions.tbx.termImportMap.%';

UPDATE terms_attributes_datatype
SET dataType = 'picklist'
WHERE level = 'term' AND label = 'termNote' AND type = 'normativeAuthorization';

-- initial sync of the picklists to the configured values
UPDATE terms_attributes_datatype dt, (
        SELECT termNoteType as type, GROUP_CONCAT(termNoteValue) AS picklistValues
        FROM terms_term_status_map
        GROUP BY termNoteType
    ) as s
SET dt.picklistValues = s.picklistValues, dt.dataType = 'picklist'
WHERE dt.label = 'termNote' AND dt.level = 'term' AND dt.type = s.type;