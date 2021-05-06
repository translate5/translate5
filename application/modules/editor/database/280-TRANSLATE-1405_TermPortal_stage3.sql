create table if not exists terms_attributes
(
    id int auto_increment
        primary key,
    elementName varchar(100) not null,
    language varchar(16) null,
    value text null,
    type varchar(100) null,
    target varchar(100) null,
    dataType varchar(100) null,
    collectionId int not null,
    termEntryId int not null,
    termEntryGuid varchar(38) not null,
    langSetGuid varchar(38) not null,
    termId varchar(100) null,
    labelId int null,
    guid varchar(38) not null,
    internalCount int null,
    userGuid varchar(38) null,
    userName varchar(255) null,
    created timestamp default '0000-00-00 00:00:00' not null,
    updated timestamp default '0000-00-00 00:00:00' not null on update CURRENT_TIMESTAMP,
    processStatus varchar(128) default 'finalized' null comment 'old term processStatus',
    constraint terms_attributes_guid_uindex
        unique (guid)
);

create index collectionId_idx
    on terms_attributes (collectionId);

create index termId_idx
    on terms_attributes (termId);

create index termEntryId_idx
    on terms_attributes (termEntryId);

create table if not exists terms_images
(
    id int auto_increment
        primary key,
    targetId varchar(255) not null,
    name varchar(100) null,
    encoding varchar(100) null,
    format varchar(255) null,
    xbase blob null,
    collectionId int not null
);

create table if not exists terms_term
(
    id int auto_increment
        primary key,
    termTbxId varchar(100) null,
    collectionId int not null,
    termEntryId int not null,
    termEntryTbxId varchar(100) null,
    termEntryGuid varchar(38) not null,
    langSetGuid varchar(38) not null,
    guid varchar(38) not null,
    languageId int not null,
    language varchar(32) not null,
    term text not null,
    descrip mediumtext null,
    descripType varchar(100) null,
    descripTarget varchar(100) null,
    status varchar(128) not null,
    processStatus varchar(128) default 'finalized' null,
    definition mediumtext null,
    userGuid varchar(38) not null,
    userName varchar(128) default 'finalized' null,
    created timestamp default '0000-00-00 00:00:00' not null,
    updated timestamp default '0000-00-00 00:00:00' not null on update CURRENT_TIMESTAMP,
    constraint terms_term_guid_uindex
        unique (guid)
);

create index collectionId_idx
    on terms_term (collectionId);

create index termEntryId_idx
    on terms_term (termEntryId);

create table if not exists terms_term_entry
(
    id int auto_increment
        primary key,
    collectionId int null,
    termEntryTbxId varchar(100) not null,
    isProposal tinyint(1) default 0 null,
    descrip text null,
    entryGuid varchar(38) not null,
    constraint terms_term_entry_entryGuid_uindex
        unique (entryGuid)
);

create index collectionId_idx
    on terms_term_entry (collectionId);

create index termEntryTbxId_idx
    on terms_term_entry (termEntryTbxId);

create index termEntryGuid_idx
    on terms_term_entry (entryGuid);

create table if not exists terms_transacgrp
(
    id int auto_increment
        primary key,
    elementName varchar(20) null,
    transac text null,
    date varchar(100) null,
    adminType varchar(100) null,
    adminValue varchar(100) null,
    transacNote text null,
    transacType varchar(255) null,
    ifDescripgrp tinyint(1) default 0 null,
    collectionId int not null,
    termEntryId int not null,
    termId varchar(100) null,
    termEntryGuid varchar(38) not null,
    langSetGuid varchar(38) not null,
    guid varchar(38) not null,
    constraint terms_transacgrp_guid_uindex
        unique (guid)
);

create index collectionId_idx
    on terms_transacgrp (collectionId);

create index termEntryId_idx
    on terms_transacgrp (termEntryId);
