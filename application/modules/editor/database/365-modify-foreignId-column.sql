ALTER TABLE translate5.LEK_task
    MODIFY foreignId VARCHAR (1024) DEFAULT '' NOT NULL COMMENT 'Used as optional reference field for Tasks create vi API';
