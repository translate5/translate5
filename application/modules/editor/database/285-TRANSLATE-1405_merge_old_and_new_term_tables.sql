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

DELETE FROM LEK_terms WHERE termEntryId IS NULL;
DELETE FROM LEK_term_attributes WHERE termEntryId IS NULL;

ALTER TABLE terms_term_entry ADD tmpOldId int(11) NOT NULL;
CREATE INDEX idx_tmpOldId_te ON terms_term_entry (tmpOldId);

ALTER TABLE terms_term ADD tmpOldId int(11) NOT NULL;
CREATE INDEX idx_tmpOldId_tt ON terms_term (tmpOldId);

ALTER TABLE terms_attributes ADD tmpOldId int(11) NOT NULL;
CREATE INDEX idx_tmpOldId_ta ON terms_attributes (tmpOldId);

ALTER TABLE terms_transacgrp ADD tmpOldId int(11) NOT NULL;
CREATE INDEX idx_tmpOldId_trg ON terms_transacgrp (tmpOldId);

INSERT INTO terms_term_entry (
    collectionId,
    termEntryTbxId,
    isProposal,
    descrip,
    entryGuid,
    tmpOldId
)
SELECT old_term_entry.collectionId,
       old_term_entry.groupId AS termEntryTbxId,
       old_term_entry.isProposal AS isProposal,
       (SELECT value FROM LEK_term_attributes
        WHERE termEntryId = old_term_entry.id
          AND name = 'descrip'
          AND attrType = 'definition'
          AND collectionId = old_term_entry.collectionId
          AND parentId is NULL LIMIT 1) AS definition,
       UUID() AS guid,
       old_term_entry.id
FROM LEK_term_entry old_term_entry;

# INSERT FOR TERMS
INSERT INTO terms_term (
    termId,
    collectionId,
    termEntryId,
    termEntryTbxId,
    termEntryGuid,
    langSetGuid,
    guid,
    languageId,
    language,
    term,
    descrip,
    descripType,
    descripTarget,
    status,
    processStatus,
    definition,
    userGuid,
    userName,
    created,
    updated,
    tmpOldId
)
SELECT old_terms.mid AS termId,
       old_terms.collectionId,
       old_terms.termEntryId,
       'tmp' AS termEntryTbxId,
       'tmp' AS termEntryGuid,
       'old table no UUID()' AS langSetGuid,
       UUID() AS guid,
       old_terms.language AS languageId,
       'none',
       old_terms.term,
       old_terms.definition,
       '' AS descripType,
       '' AS descripTarget,
       old_terms.status,
       old_terms.processStatus,
       old_terms.definition,
       old_terms.userGuid,
       old_terms.userName,
       old_terms.created,
       old_terms.updated,
       old_terms.id
FROM LEK_terms old_terms;

UPDATE terms_term terms
    JOIN terms_term_entry tte on terms.termEntryId = tte.tmpOldId
SET terms.termEntryId = tte.id,
    terms.termEntryTbxId = tte.termEntryTbxId,
    terms.termEntryGuid = tte.entryGuid
WHERE terms.termEntryId = tte.tmpOldId;

UPDATE terms_term terms
    JOIN LEK_languages lng on lng.id = terms.languageId
SET terms.language = LOWER(lng.rfc5646)
WHERE lng.id = terms.languageId;

# INSERT FOR ATTRIBUTES
INSERT INTO terms_attributes (
    elementName,
    language,
    value,
    type,
    target,
    dataType,
    collectionid,
    termEntryId,
    termEntryGuid,
    langSetGuid,
    termId,
    labelId,
    guid,
    internalCount,
    userGuid,
    userName,
    created,
    updated,
    tmpOldId
)
SELECT old_term_attributes.name,
       old_term_attributes.language,
       old_term_attributes.value,
       old_term_attributes.attrType,
       old_term_attributes.attrTarget,
       old_term_attributes.attrDataType,
       old_term_attributes.collectionid,
       old_term_attributes.termEntryId,
       '' AS termEntryGuid,
       'old table no UUID()' AS langSetGuid,
       (SELECT terms_term.termId FROM terms_term
        WHERE terms_term.tmpOldId = old_term_attributes.termId) AS termId,
       old_term_attributes.labelId,
       UUID() AS guid,
       old_term_attributes.internalCount,
       old_term_attributes.userGuid,
       old_term_attributes.userName,
       old_term_attributes.created,
       old_term_attributes.updated,
       old_term_attributes.id
FROM LEK_term_attributes old_term_attributes
WHERE attrType != 'modification'
  AND attrType != 'creation'
  AND attrType != 'date'
  AND attrType != 'responsiblePerson';

UPDATE terms_attributes termAtt
    JOIN terms_term_entry tte on termAtt.termEntryId = tte.tmpOldId
SET termAtt.termEntryId = tte.id,
    termAtt.termEntryGuid = tte.entryGuid
WHERE termAtt.termEntryId = tte.tmpOldId;


# INSERT FOR TRANSACGRP
INSERT INTO terms_transacgrp (
    elementName,
    transac,
    date,
    adminType,
    adminValue,
    transacNote,
    transacType,
    ifDescripgrp,
    collectionId,
    termEntryId,
    termId,
    termEntryGuid,
    langSetGuid,
    guid,
    tmpOldId
)
SELECT t1.name AS elementName,
       attrType AS transac,
       '' AS date,
       t1.attrType AS adminType,
       t1.attrDataType AS adminValue,
       '' AS transacNote,
       'responsiblePerson' AS transacType,
       if(t1.parentId = NULL, 1, 0) AS ifDescripgrp,
       t1.collectionId AS collectionId,
       t1.termEntryId,
       t1.termId AS termId,
       '' AS termEntryGuid,
       'lagSetGuid',
       UUID(),
       t1.id
FROM LEK_term_attributes t1
WHERE attrType = 'modification' OR attrType = 'creation';

UPDATE terms_transacgrp termTrg
    JOIN terms_term_entry tte on termTrg.termEntryId = tte.tmpOldId
SET termTrg.termEntryId = tte.id,
    termTrg.termEntryGuid = tte.entryGuid
WHERE termTrg.termEntryId = tte.tmpOldId;

UPDATE terms_transacgrp termTrg
    JOIN LEK_term_attributes lta on termTrg.tmpOldId = lta.parentId
SET termTrg.date = lta.value
WHERE lta.name = 'date';

UPDATE terms_transacgrp termTrg
    JOIN LEK_term_attributes lta on termTrg.tmpOldId = lta.parentId
SET termTrg.transacNote = lta.value
WHERE lta.attrType = 'responsiblePerson';

ALTER TABLE terms_term_entry DROP COLUMN tmpOldId;
ALTER TABLE terms_term DROP COLUMN tmpOldId;
ALTER TABLE terms_attributes DROP COLUMN tmpOldId;
ALTER TABLE terms_transacgrp DROP COLUMN tmpOldId;
