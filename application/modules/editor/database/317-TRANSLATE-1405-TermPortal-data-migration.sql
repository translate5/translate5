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

-- implement a logger to log the progress to the system log
DROP PROCEDURE IF EXISTS translate5Logger;

DELIMITER $$
CREATE PROCEDURE translate5Logger(
    IN message VARCHAR(255)
)
BEGIN
    INSERT INTO Zf_errorlog (`level`, `domain`, `message`, `file`)
    VALUES(8, 'termportal.migration', concat('TermPortal Migration: ', message, ' (rows: ', ROW_COUNT(), ')'), 'application/modules/editor/database/317-TRANSLATE-1405-TermPortal-data-migration.sql');
END $$
DELIMITER ;

call translate5Logger('Start migration from LEK_term tables to new term_ tables');

-- fix missing term entries for existing term entries
UPDATE `LEK_terms` t, `LEK_term_entry` te
SET t.termEntryId = te.id
WHERE t.collectionId = te.collectionId AND t.groupId = te.groupId AND t.termEntryId is null;

call translate5Logger('fixed missing term entries for existing term entries');

-- create missing term entries
INSERT INTO `LEK_term_entry`
(`collectionId`, `groupId`, `isProposal`)
SELECT `collectionId`, `groupId`, 0 AS `isProposal`
FROM `LEK_terms`
WHERE termEntryId is null
GROUP BY `collectionId`, `groupId`;

call translate5Logger('create missing term entries');

-- rerun update to set now the created term entry IDs:
UPDATE `LEK_terms` t, `LEK_term_entry` te
SET t.termEntryId = te.id
WHERE t.collectionId = te.collectionId AND t.groupId = te.groupId AND t.termEntryId is null;

-- term entries without terms are removed
DELETE FROM LEK_term_entry WHERE id IN (
    SELECT id FROM (
        SELECT  LEK_term_entry.id
        FROM    LEK_term_entry
        LEFT JOIN LEK_terms
        ON LEK_terms.termEntryId = LEK_term_entry.id
        WHERE   LEK_terms.termEntryId IS NULL
    ) as subselect
);

call translate5Logger('deleted term entries without terms');

-- migrate the term entries from LEK_term_entry to terms_term_entry
INSERT IGNORE INTO `terms_term_entry`
(`id`, `collectionId`, `termEntryTbxId`, `isCreatedLocally`, `entryGuid`)
SELECT id, collectionId, groupId AS `termEntryTbxId`, isProposal AS isCreatedLocally, UUID() AS `entryGuid`
FROM LEK_term_entry te;

call translate5Logger('copied term entries');

-- add missing user data for migration
UPDATE LEK_terms t, Zf_users u
SET t.userId = u.id
WHERE u.userGuid = t.userGuid;

UPDATE LEK_term_proposal t, Zf_users u
SET t.userId = u.id
WHERE u.userGuid = t.userGuid;

UPDATE LEK_term_history t, Zf_users u
SET t.userId = u.id
WHERE u.userGuid = t.userGuid;

UPDATE LEK_term_history h, Zf_users u
SET h.userId = u.id
WHERE u.userGuid = h.userGuid;

call translate5Logger('added missing user IDs');

-- copy old terms to new table
INSERT IGNORE INTO terms_term
(id, updatedBy, updatedAt, collectionId, termEntryId, languageId, `language`, term, proposal, status, processStatus, definition, termEntryTbxId, termTbxId, termEntryGuid, langSetGuid, guid)
SELECT id, userId as updatedBy, updated as updatedAt, collectionId, termEntryId, `language` as languageId, null as `language`, term, null as proposal, status, processStatus, definition,
       groupId as termEntryTbxId, mid as termTbxId, null as termEntryGuid, null as langSetGuid, UUID() as guid
FROM LEK_terms;

call translate5Logger('copied terms');

-- fix missing language rfc5646 values
UPDATE terms_term t, LEK_languages lng
SET t.language = LOWER(lng.rfc5646)
WHERE lng.id = t.languageId;

-- fill termEntryGuid from terms_term_entry
UPDATE terms_term t, terms_term_entry te
SET t.termEntryGuid = te.entryGuid
WHERE t.termEntryId = te.id;

call translate5Logger('fill termEntryGuid from terms_term_entry');

-- fill migration langset table
INSERT IGNORE INTO terms_migration_langset
(collectionId, termEntryId, languageId, `language`, langSetGuid)
SELECT collectionId, termEntryId, languageId, `language`, UUID()
FROM terms_term
GROUP BY collectionId, termEntryId, languageId;

call translate5Logger('filled migration langset table');

-- disable the derived merge optimization since this was slowing down the following update on mysql 5.7 machines
SET SESSION optimizer_switch='derived_merge=off';

-- set langSetGuid
UPDATE terms_term t
LEFT JOIN terms_migration_langset l ON t.collectionId = l.collectionId AND t.termEntryId = l.termEntryId AND t.languageId = l.languageId
SET t.langSetGuid = l.langSetGuid, t.updatedAt = t.updatedAt; -- prevent autoupdate here!

call translate5Logger('set langSetGuid in term table');

-- reenable derived merge, since for other queries it does not seem to be a problem
SET SESSION optimizer_switch='derived_merge=on';

-- proposals from proposal table
UPDATE terms_term t, LEK_term_proposal p
SET t.proposal = p.term, t.updatedAt = p.created, t.updatedBy = p.userId,
    t.updatedAt = t.updatedAt -- prevent autoupdate here!
WHERE t.id = p.termId;

call translate5Logger('move term proposals to term table field');

-- NOT NEEDED but keep for reference: reset updatedAt timestamp
-- UPDATE terms_term t, LEK_terms told
-- SET t.updatedAt = told.updated
-- WHERE t.id = told.id and t.proposal is null;

-- copy old term history to new table:
INSERT IGNORE INTO terms_term_history
(termId, collectionId, termEntryId, languageId, `language`, term, proposal, status, processStatus, updatedBy, updatedAt, definition, termEntryTbxId, termTbxId, termEntryGuid, langSetGuid, guid)
SELECT h.termId, h.collectionId, t.termEntryId, t.languageId, t.`language`, h.term, null as proposal, h.status, h.processStatus, h.userId as updatedBy, h.updated as updatedAt, h.definition, t.termEntryTbxId, t.termTbxId, t.termEntryGuid, t.langSetGuid, t.guid
FROM LEK_term_history h JOIN terms_term t ON t.id = h.termId;

call translate5Logger('copy term history');

-- copy LEK_term_attributes_label > terms_attributes_datatype - still unconverted!
INSERT IGNORE INTO terms_attributes_datatype
(id, label, `type`, l10nSystem)
SELECT id, label, `type`, labelText
FROM LEK_term_attributes_label;

call translate5Logger('copy term attribute datatypes');

-- fix missing termEntryIds in attribute table:
UPDATE `LEK_term_attributes` ta, `LEK_terms` t
SET ta.termEntryId = t.termEntryId
WHERE ta.collectionId = t.collectionId AND ta.termId = t.id AND ta.termEntryId is null;

call translate5Logger('fix missing termEntryIds in attribute table');

-- set empty language strings to null
UPDATE `LEK_term_attributes` ta
SET ta.`language` = NULL
WHERE ta.`language` = '';

-- since there is no index on parentId, we just create one
ALTER TABLE LEK_term_attributes ADD INDEX (parentId);

-- LEK_term_attributes > terms_transacgrp
INSERT IGNORE INTO terms_transacgrp
(elementName, transac, `date`, transacNote, transacType, target, `language`, isDescripGrp, collectionId, termEntryId, termId, termTbxId, termGuid, termEntryGuid, langSetGuid, guid)
SELECT (
    CASE
        WHEN aout.`language` is not null and aout.termId is null THEN 'langSet'
        WHEN aout.`language` is not null and aout.termId is not null THEN 'tig'
        ELSE 'termEntry'
        END) as elementName,
       aout.attrType as transac,
       IF(adate.value REGEXP '^-?[0-9]+$', FROM_UNIXTIME(adate.value), adate.value) as `date`,
       ain.value as transacNote,
       ain.attrType as transacType,
       ain.attrTarget as target,
       aout.`language`,
       if(aout.parentId is null, 0, 1) as isDescripGrp,
       aout.collectionId,
       aout.termEntryId,
       aout.termId,
       null as termTbxId,
       null as termGuid,
       null as termEntryGuid,
       null as langSetGuid,
       UUID() as guid
FROM LEK_term_attributes aout
         LEFT JOIN LEK_term_attributes ain on aout.id = ain.parentId AND ain.name = 'transacNote'
         LEFT JOIN LEK_term_attributes adate on aout.id = adate.parentId AND adate.name = 'date'
WHERE aout.name = 'transac';

call translate5Logger('copied transac entries');

-- update the guid and tbx id fields, since they do not exist in the old attribute table
UPDATE terms_transacgrp tr, terms_term te
SET tr.termEntryGuid = te.termEntryGuid, tr.termTbxId = te.termTbxId, tr.termGuid = te.guid, tr.langSetGuid = te.langSetGuid
WHERE tr.termId = te.id;

call translate5Logger('update the guid and tbx id fields, since they do not exist in the old attribute table');

-- update the guid and tbx id fields for transacs on entry level
UPDATE terms_transacgrp tr, terms_term_entry te
SET tr.termEntryGuid = te.entryGuid
WHERE tr.termEntryId = te.id AND tr.termId is null;

call translate5Logger('update the guid and tbx id fields for transacs on entry level');

-- commit LEK_term_attribute_proposal to LEK_term_attributes table
UPDATE LEK_term_attributes ta, LEK_term_attribute_proposal tp
SET ta.value = tp.value, ta.updated = tp.created, ta.userGuid = tp.userGuid, ta.processStatus = 'MIG_FROM_PROP'
WHERE ta.id = tp.attributeId;

call translate5Logger('commit LEK_term_attribute_proposal to LEK_term_attributes table');

-- clean internal count field for reusage as userId container
UPDATE LEK_term_attributes
SET internalCount = null;

-- since internalCount is not used anymore, we just put the userId inside
UPDATE LEK_term_attributes t, Zf_users u
SET t.internalCount = u.id
WHERE u.userGuid = t.userGuid;

-- isDescripGrp calculation on attributes is not as easy as for transacs
-- the only way to recognize if a descrip is inside a descripGrp, is if the descrip itself is parent if other elements.
-- In that case it is inside a descripGrp, and we set the id as parentId for descrips inside a descripGrp
UPDATE LEK_term_attributes ta, (SELECT main.id
                                FROM LEK_term_attributes main
                                         JOIN LEK_term_attributes child ON main.id = child.parentId
                                WHERE main.name = 'descrip' GROUP BY main.id) te
SET ta.parentId = ta.id
WHERE ta.id = te.id;

call translate5Logger('fix descrip parentIds');

-- LEK_term_attributes > terms_attributes
-- internalCount contains the userIds!
INSERT IGNORE INTO terms_attributes
(id, collectionId, termEntryId, `language`, termId, termTbxId, dataTypeId, `type`,
 value, target, isCreatedLocally, createdBy, createdAt, updatedBy, updatedAt,
 termEntryGuid, langSetGuid, termGuid, guid,
 elementName, attrLang, isDescripGrp)
SELECT id, collectionId, termEntryId, `language`, termId, null as termTbxId, labelId as dataTypeId, attrType as `type`,
       value, attrTarget as target, IF(processStatus = 'MIG_FROM_PROP', 1, 0) as isCreatedLocally, internalCount as createdBy, created as createdAt, internalCount as updatedBy, updated as updatedAt,
       null as termEntryGuid, null as langSetGuid, null as termGuid, UUID() as guid,
       name as elementName, attrLang, if(parentId is null, 0, 1) as isDescripGrp
FROM LEK_term_attributes
WHERE name not in ('date', 'transac', 'transacNote');

call translate5Logger('copy term attributes');

-- update the guid fields, since they do not exist in the old attribute table
UPDATE terms_attributes ta, terms_term te
SET ta.termEntryGuid = te.termEntryGuid, ta.termGuid = te.guid, ta.langSetGuid = te.langSetGuid,
    ta.updatedAt = ta.updatedAt -- prevent autoupdate here!
WHERE ta.termId = te.id;

-- update the guid fields for attributes on entry level
UPDATE terms_attributes ta, terms_term_entry te
SET ta.termEntryGuid = te.entryGuid,
    ta.updatedAt = ta.updatedAt -- prevent autoupdate here!
WHERE ta.termEntryId = te.id AND ta.termId is null;

call translate5Logger('update missing attribute uuids');

-- LEK_term_attributes_history > terms_attributes_history
INSERT INTO terms_attributes_history
(attrId, collectionId, termEntryId, `language`, termId, dataTypeId, `type`, value,
 target, updatedBy, updatedAt, termEntryGuid, langSetGuid,
 termGuid, guid, elementName, attrLang)
SELECT h.attributeId, h.collectionId, ta.termEntryId, ta.`language`, ta.termId, ta.dataTypeId, ta.`type`, h.value,
       ta.target, h.userId as updatedBy, h.updated as updatedAt, ta.termEntryGuid, ta.langSetGuid,
       ta.termGuid, ta.guid, ta.elementName, ta.attrLang
FROM LEK_term_attribute_history h JOIN terms_attributes ta ON h.attributeId = ta.id;

call translate5Logger('copied term attributes history');
call translate5Logger('basic data migration is done');



