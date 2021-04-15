# INSERT FOR TERMS
INSERT INTO terms_term (
    term,
    termid,
    status,
    processstatus,
    definition,
    termentrytbxid,
    languageId,
    collectionid,
    termentryid,
    created,
    updated,
    userguid,
    username,
    guid,
    descrip,
    language,
    termEntryGuid,
    langsetguid,
    descriptype,
    descriptarget
)
SELECT old_terms.term,
       old_terms.mid,
       old_terms.status,
       old_terms.processStatus,
       old_terms.definition,
       old_terms.groupId,
       old_terms.language,
       old_terms.collectionId,
       old_terms.termEntryId,
       old_terms.created,
       old_terms.updated,
       old_terms.userGuid,
       old_terms.userName,
       UUID(),
       old_terms.definition,
       '',
       '',
       '',
       '',
       ''
FROM LEK_terms old_terms;



# INSERT FOR TERM ENTRIES /after inserting attributes we must update 'descrip'
INSERT INTO terms_term_entry (
    collectionId,
    termEntryTbxId,
    isProposal,
    descrip,
    entryGuid
)
SELECT old_term_entry.collectionId,
       old_term_entry.groupId,
       old_term_entry.isProposal,
       '',
       UUID()
FROM LEK_term_entry old_term_entry;

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
      updated
      )
SELECT old_term_attributes.name,
       old_term_attributes.language,
       old_term_attributes.value,
       old_term_attributes.attrType,
       old_term_attributes.attrTarget,
       old_term_attributes.attrDataType,
       old_term_attributes.collectionid,
       old_term_attributes.termEntryId,
       '',
       '',
       old_term_attributes.termId,
       old_term_attributes.labelId,
       UUID(),
       old_term_attributes.internalCount,
       old_term_attributes.userGuid,
       old_term_attributes.userName,
       old_term_attributes.created,
       old_term_attributes.updated
FROM LEK_term_attributes old_term_attributes
WHERE attrType != 'modification' and attrType != 'creation';



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
    guid
)
SELECT  if(t1.attrType = 'creation' || t1.attrType = 'modification', t1.value, '') AS transac,
        if(t1.parentId = NULL, 1, 0) AS ifDescripgrp,
        t1.attrType AS transacType,
        t1.collectionId AS collectionId,
        t1.termEntryId AS termEntryId,
        t1.termId AS termId,
        t1.parentId,
       '',
       '',
       '',
       '',
       '',
       '',
        UUID()
FROM LEK_term_attributes t1
WHERE attrType = 'modification' OR attrType = 'creation';

