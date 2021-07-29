# DELETE FROM LEK_terms WHERE termEntryId IS NULL;
# No delete  all termEntries without termEntryId,-> create new termEntries in LEK_term_entry and associate to Lek_Term table???

ALTER TABLE LEK_term_entry ADD tmpTermId LONGTEXT;
ALTER TABLE LEK_term_attributes ADD tmpLangSetGuid LONGTEXT;
DELETE FROM LEK_terms WHERE term IS NULL OR term = '';

# UUID for new terms and attributes before migration
UPDATE LEK_term_attributes t1, (
    SELECT collectionId, termEntryId, attrLang, UUID() newLangSetUuid -- oder language, evaluieren welches.
    FROM LEK_term_attributes
    WHERE NOT attrLang IS NULL -- a langset entry must have a language
    AND not termEntryId IS NULL -- why are there attributes with termEntry = null, must not be, should be solved first by filling termEntryId via termId
    GROUP BY collectionId, termEntryId, attrLang) t2
SET t1.tmpLangSetGuid = t2.newLangSetUuid
WHERE t1.collectionId = t2.collectionId
  AND t1.termEntryId = t2.termEntryId
  AND t1.attrLang = t2.attrLang;

# END UUID migration


ALTER TABLE terms_term_entry ADD tmpOldId int(11) NOT NULL;
CREATE INDEX idx_tmpOldId_te ON terms_term_entry (tmpOldId);

ALTER TABLE terms_term ADD tmpOldId int(11) NOT NULL;
CREATE INDEX idx_tmpOldId_tt ON terms_term (tmpOldId);
ALTER TABLE terms_term ADD tmpOldTermEntryId int(11) NOT NULL;
CREATE INDEX idx_tmpOldTermEntryId_tt ON terms_term (tmpOldTermEntryId);

ALTER TABLE terms_attributes ADD tmpOldId int(11) NOT NULL;
CREATE INDEX idx_tmpOldId_ta ON terms_attributes (tmpOldId);
ALTER TABLE terms_attributes ADD tmpOldTermId int(11) NOT NULL;
CREATE INDEX idx_tmpOldTermId_ta ON terms_attributes (tmpOldTermId);
ALTER TABLE terms_attributes ADD tmpOldTermEntryId int(11) NOT NULL;
CREATE INDEX idx_tmpOldTermEntryId_ta ON terms_attributes (tmpOldTermEntryId);

ALTER TABLE terms_transacgrp ADD tmpOldId int(11) NOT NULL;
CREATE INDEX idx_tmpOldId_trg ON terms_transacgrp (tmpOldId);
ALTER TABLE terms_transacgrp ADD tmpOldTermId int(11) NOT NULL;
CREATE INDEX idx_tmpOldTermId_ta ON terms_transacgrp (tmpOldTermId);
ALTER TABLE terms_transacgrp ADD tmpOldTermEntryId int(11) NOT NULL;
CREATE INDEX idx_tmpOldTermEntryId_ta ON terms_transacgrp (tmpOldTermEntryId);


INSERT INTO terms_term_entry (
    collectionId,
    termEntryTbxId,
    isProposal,
    entryGuid,
    tmpOldId
)
SELECT old_term_entry.collectionId,
       old_term_entry.groupId AS termEntryTbxId,
       old_term_entry.isProposal AS isProposal,
       UUID() AS guid,
       old_term_entry.id
FROM LEK_term_entry old_term_entry;

# INSERT FOR TERMS
INSERT INTO terms_term (
    termTbxId,
    collectionId,
    termEntryId,
    termEntryTbxId,
    termEntryGuid,
    langSetGuid,
    guid,
    languageId,
    language,
    term,
    status,
    processStatus,
    definition,
    userGuid,
    userName,
    created,
    updated,
    tmpOldId,
    tmpOldTermEntryId
)
SELECT old_terms.mid AS termId,
       old_terms.collectionId,
       null,
       old_terms.groupId AS termEntryTbxId,
       null AS termEntryGuid,
       null AS langSetGuid,
       UUID() AS guid,
       old_terms.language AS languageId,
       null,
       old_terms.term,
       old_terms.status,
       old_terms.processStatus,
       old_terms.definition,
       old_terms.userGuid,
       old_terms.userName,
       old_terms.created,
       old_terms.updated,
       old_terms.id,
       old_terms.termEntryId
FROM LEK_terms old_terms;

# ToDo: it is ok to update termEntryTbxId (groupId) twice?
#  in corrupt LEK_term table it's possible that groupId is null,
#  but in terms_term_entry (LEK_term_entry) groupId exist, that reason why i check with this update.
UPDATE terms_term terms
    JOIN terms_term_entry tte on terms.tmpOldTermEntryId = tte.tmpOldId
SET terms.termEntryId = tte.id,
    terms.termEntryTbxId = tte.termEntryTbxId,
    terms.termEntryGuid = tte.entryGuid
WHERE terms.tmpOldTermEntryId = tte.tmpOldId;

UPDATE terms_term terms
    JOIN LEK_languages lng on lng.id = terms.languageId
SET terms.language = LOWER(lng.rfc5646)
WHERE lng.id = terms.languageId;

# INSERT FOR ATTRIBUTES
INSERT INTO terms_attributes (
    elementName,
    language,
    attrLang,
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
    tmpOldId,
    tmpOldTermId,
    tmpOldTermEntryId
)
SELECT old_term_attributes.name,
       LOWER(old_term_attributes.language),
       old_term_attributes.attrLang,
       old_term_attributes.value,
       old_term_attributes.attrType,
       old_term_attributes.attrTarget,
       old_term_attributes.attrDataType,
       old_term_attributes.collectionid,
       null,
       null AS termEntryGuid,
       null AS langSetGuid,
       null,
       old_term_attributes.labelId,
       UUID() AS guid,
       old_term_attributes.internalCount,
       old_term_attributes.userGuid,
       old_term_attributes.userName,
       old_term_attributes.created,
       old_term_attributes.updated,
       old_term_attributes.id,
       old_term_attributes.termId,
       old_term_attributes.termEntryId
FROM LEK_term_attributes old_term_attributes
WHERE attrType != 'modification'
  AND attrType != 'creation'
  AND attrType != 'date'
  AND attrType != 'responsiblePerson';

UPDATE terms_attributes termAtt
    JOIN terms_term_entry tte on termAtt.tmpOldTermEntryId = tte.tmpOldId
SET termAtt.termEntryId = tte.id,
    termAtt.termEntryGuid = tte.entryGuid
WHERE termAtt.tmpOldTermEntryId = tte.tmpOldId;

UPDATE terms_attributes termAtt
    JOIN terms_term lt on termAtt.tmpOldTermId = lt.tmpOldId
SET termAtt.termId = lt.id,
    termAtt.termGuid = lt.guid
WHERE termAtt.tmpOldTermId = lt.tmpOldId;

UPDATE terms_attributes termAtt
    JOIN LEK_term_attributes lta on termAtt.tmpOldId = lta.id
SET termAtt.langSetGuid = lta.tmpLangSetGuid
WHERE termAtt.tmpOldId = lta.id;

# how handle if term has no attributes in LEK_attributes
UPDATE terms_term terms
    JOIN LEK_term_attributes LekT on LekT.termId = terms.tmpOldId
SET terms.langSetGuid = LekT.tmpLangSetGuid
WHERE LekT.termId = terms.tmpOldId;

# INSERT FOR TRANSACGRP
INSERT INTO terms_transacgrp (
    elementName,
    transac,
    date,
    language,
    attrLang,
    transacNote,
    transacType,
    ifDescripgrp,
    collectionId,
    termEntryId,
    termId,
    termEntryGuid,
    langSetGuid,
    guid,
    tmpOldId,
    tmpOldTermId,
    tmpOldTermEntryId
)
SELECT t1.name AS elementName,
       attrType AS transac,
       '' AS date,
       language AS language,
       attrLang AS attrLang,
       '' AS transacNote,
       attrType AS transacType,
       if(t1.parentId = NULL, 1, 0) AS ifDescripgrp,
       t1.collectionId AS collectionId,
       null AS termEntryId,
       null as termIdOld,
       null AS termEntryGuid,
       null AS langSetGuid,
       UUID(),
       t1.id AS oldId,
       t1.termId AS termId,
       t1.termEntryId AS termEntryId
FROM LEK_term_attributes t1
WHERE attrType = 'modification' OR attrType = 'creation';

UPDATE terms_transacgrp termAtt
    JOIN terms_term_entry tte on termAtt.tmpOldTermEntryId = tte.tmpOldId
SET termAtt.termEntryId = tte.id,
    termAtt.termEntryGuid = tte.entryGuid
WHERE termAtt.tmpOldTermEntryId = tte.tmpOldId;

UPDATE terms_transacgrp termTrg
    JOIN LEK_terms lta on termTrg.tmpOldTermId = lta.id
SET termTrg.termId = lta.id
WHERE termTrg.tmpOldTermId = lta.id;

UPDATE terms_transacgrp termTrg
    JOIN LEK_term_attributes lta on termTrg.tmpOldId = lta.parentId
SET termTrg.date = lta.value
WHERE lta.name = 'date';

UPDATE terms_transacgrp termTrg
    JOIN LEK_term_attributes lta on termTrg.tmpOldId = lta.parentId
SET termTrg.elementName = lta.name
WHERE termTrg.tmpOldId = lta.parentId;

UPDATE terms_transacgrp termTrg
    JOIN LEK_term_attributes lta on termTrg.tmpOldId = lta.parentId
SET termTrg.transacNote = lta.value
WHERE lta.attrType = 'responsiblePerson';

UPDATE terms_transacgrp termTrg
    JOIN LEK_term_attributes lta on termTrg.collectionId = lta.collectionId
SET termTrg.langSetGuid = lta.tmpLangSetGuid
WHERE termTrg.tmpOldId = lta.id;

UPDATE terms_transacgrp termTrg
    JOIN terms_term lta on termTrg.collectionId = lta.collectionId
SET termTrg.termId = lta.id
WHERE termTrg.tmpOldTermId = lta.tmpOldId;

# before we can update new termId we must drop foreign key, after update we add new FK
alter table LEK_term_proposal drop foreign key LEK_term_proposal_ibfk_1;
UPDATE LEK_term_proposal termProposal
    JOIN terms_term lt on termProposal.termId = lt.tmpOldId
SET termProposal.termId = lt.id
WHERE termProposal.termId = lt.tmpOldId;

alter table LEK_term_proposal
    add constraint LEK_term_proposal_ibfk_1
        foreign key (termId) references terms_term (id)
            on update cascade on delete cascade;

alter table LEK_term_attribute_proposal drop foreign key LEK_term_attribute_proposal_ibfk_1;
alter table LEK_term_attribute_proposal
    add constraint LEK_term_attribute_proposal_attributeId_ibfk_1
        foreign key (attributeId) references terms_attributes (id)
            on update cascade on delete cascade;

alter table LEK_term_attribute_history drop foreign key LEK_term_attribute_history_ibfk_1;
alter table LEK_term_attribute_history
    add constraint LEK_term_attribute_history_ibfk_1
        foreign key (attributeId) references terms_attributes (id)
            on update cascade on delete cascade;

alter table LEK_term_history drop foreign key LEK_term_history_ibfk_1;
alter table LEK_term_history
    add constraint LEK_term_history_ibfk_1
        foreign key (termId) references terms_term (id)
            on update cascade on delete cascade;

alter table terms_term
    add constraint terms_termentry_ibfk_1
        foreign key (termEntryId) references terms_term_entry (id)
            on update cascade on delete cascade;

alter table terms_term
    add constraint terms_term_entry_ibfk_1
        foreign key (termEntryId) references terms_term_entry (id)
            on update cascade on delete cascade;

alter table terms_term
    add constraint terms_term_collection_ibfk_1
        foreign key (collectionId) references LEK_languageresources (id)
            on update cascade on delete cascade;

alter table terms_attributes
    add constraint terms_term_ibfk_1
        foreign key (termId) references terms_term (id)
            on update cascade on delete cascade;

alter table terms_attributes
    add constraint terms_entry_ibfk_1
        foreign key (termEntryId) references terms_term_entry (id)
            on update cascade on delete cascade;

alter table terms_attributes
    add constraint terms_collection_ibfk_1
        foreign key (collectionId) references LEK_languageresources (id)
            on update cascade on delete cascade;

alter table terms_transacgrp
    add constraint terms_tgrp_term_ibfk_1
        foreign key (termId) references terms_term (id)
            on update cascade on delete cascade;

alter table terms_transacgrp
    add constraint terms_tgrp_entry_ibfk_1
        foreign key (termEntryId) references terms_term_entry (id)
            on update cascade on delete cascade;

alter table terms_transacgrp
    add constraint terms_tgrp_collection_ibfk_1
        foreign key (collectionId) references LEK_languageresources (id)
            on update cascade on delete cascade;

# ALTER TABLE terms_term DROP COLUMN tmpOldId;
# ALTER TABLE terms_term DROP COLUMN tmpOldTermEntryId;
# ALTER TABLE terms_term_entry DROP COLUMN tmpOldId;
# ALTER TABLE terms_attributes DROP COLUMN tmpOldId;
# ALTER TABLE terms_attributes DROP COLUMN tmpOldTermId;
# ALTER TABLE terms_attributes DROP COLUMN tmpOldTermEntryId;
# ALTER TABLE terms_transacgrp DROP COLUMN tmpOldId;
# ALTER TABLE terms_transacgrp DROP COLUMN tmpOldTermId;
# ALTER TABLE terms_transacgrp DROP COLUMN tmpOldTermEntryId;
