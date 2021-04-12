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




INSERT INTO terms_term_entry (
    collectionid,
    termentrytbxid,
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
SELECT old_teransacGrp.name,
       old_teransacGrp.attrType as transac,
       old_teransacGrp.value as date,
       '',
       '',
       '',
       '',
       1 or 0,
       old_teransacGrp.collectionId,
       old_teransacGrp.termEntryId,
       old_teransacGrp.termId,
       '',
       '',
       UUID()
FROM LEK_term_attributes old_teransacGrp;




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
FROM LEK_term_attributes old_term_attributes;


SELECT old_teransacGrp.name,
       old_teransacGrp.attrType,
       old_teransacGrp.value,
       old_teransacGrp.collectionId,
       old_teransacGrp.termEntryId,
       old_teransacGrp.termId,
       old_teransacGrp.parentId,
       UUID()
FROM LEK_term_attributes old_teransacGrp
WHERE old_teransacGrp.name = 'date'
OR old_teransacGrp.name = 'transac'
OR old_teransacGrp.name = 'transacNote';
