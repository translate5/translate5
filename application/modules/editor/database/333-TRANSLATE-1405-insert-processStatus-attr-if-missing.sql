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
 ** create a processStatus-attribute for all terms not having it
 */
SELECT @dataTypeId := id FROM terms_attributes_datatype WHERE label = 'termNote' and type = 'processStatus';

SET autocommit=0;
SET unique_checks=0;
SET foreign_key_checks=0;
INSERT INTO terms_attributes (
  collectionId, termEntryId, language, termId, termTbxId, dataTypeId, type,
  value, target, isCreatedLocally, createdBy, createdAt, updatedBy, updatedAt,
  termEntryGuid, langSetGuid, termGuid, guid, elementName, attrLang, isDescripGrp
)
SELECT
   t.collectionId, t.termEntryId, t.language, t.id as termId, t.termTbxId, @dataTypeId, 'processStatus' as type,
   t.processStatus as value, null as target, 0 as isCreatedLocally, null as createdBy, now() as createdAt, null as updatedBy, now() as updatedAt,
   t.termEntryGuid, t.langSetGuid, t.guid as termGuid, uuid() as guid, 'termNote' as elementName, null as attrLang, 0 as isDescripGrp
FROM terms_term t
LEFT JOIN terms_attributes ta ON ta.termId = t.id AND ta.elementName = 'termNote' AND ta.type = 'processStatus'
WHERE ta.id is null;
COMMIT;
SET unique_checks=1;
SET foreign_key_checks=1;
SET autocommit=1;

INSERT IGNORE INTO `terms_collection_attribute_datatype` (collectionId,dataTypeId) (SELECT collectionId,dataTypeId FROM `terms_attributes` group by collectionId,dataTypeId);

-- migration of the existing other values (across picklists, normativeAuthorization)
-- is not needed since they was setting the term status on import and term status could not be changed.