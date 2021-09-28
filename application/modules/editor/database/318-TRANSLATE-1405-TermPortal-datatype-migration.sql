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


-- DROP old tables
-- DROP TABLE IF EXISTS `LEK_term_attributes`;
-- DROP TABLE IF EXISTS `LEK_terms`;
-- DROP TABLE IF EXISTS `LEK_term_entry`;
DROP TABLE IF EXISTS `terms_migration_langset`;

DROP PROCEDURE IF EXISTS translate5Logger;

DELIMITER $$
CREATE PROCEDURE translate5Logger(
    IN message VARCHAR(255)
)
BEGIN
    INSERT INTO Zf_errorlog (`level`, `domain`, `message`, `file`)
    VALUES(8, 'termportal.migration.datatypes', concat('TermPortal Migration: ', message, ' (rows: ', ROW_COUNT(), ')'), 'application/modules/editor/database/318-TRANSLATE-1405-TermPortal-datatype-migration.sql');
END $$
DELIMITER ;

call translate5Logger('Start migration of the datatypes');

DROP PROCEDURE IF EXISTS updateTbxBasic;


# Update all TbxBasic attributes (TBX 2008 (v2))
DELIMITER $$
CREATE PROCEDURE updateTbxBasic(
    IN labelParam VARCHAR(255),
    IN typeParam VARCHAR(255),
    IN labelTextParam VARCHAR(255),
    IN levelParam VARCHAR(255),
    IN nameParam VARCHAR(255),
    IN dataTypeParam VARCHAR(255),
    IN picklistValuesParam VARCHAR(255),
    IN isTbxBasicParam tinyint(1)
)
BEGIN
	SET @recCount = 0;

    SET @typeFilter = if(typeParam is null,' `type` IS NULL;',concat(' `type` = "',typeParam,'";'));
  	SET @query := CONCAT('SELECT COUNT(*) INTO @recCount FROM `terms_attributes_datatype` WHERE `label`="',labelParam,'" AND ',@typeFilter);

PREPARE stmt1 FROM @query;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

If @recCount > 0 THEN

    SET @picklistValues = if(picklistValuesParam is null,'", `picklistValues` = NULL ',concat('", `picklistValues` = "',picklistValuesParam,'"'));
    SET @updateQuery := CONCAT('UPDATE `terms_attributes_datatype` SET `level` = "',levelParam,'",`l10nCustom` = "',nameParam,'",`dataType` = "',dataTypeParam,@picklistValues,',`isTbxBasic` = ',isTbxBasicParam,' WHERE `label`= "',labelParam,'" AND ', @typeFilter);

    PREPARE stmt2 FROM @updateQuery;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
ELSE
		INSERT INTO `terms_attributes_datatype` (label, type, l10nSystem,level, l10nCustom, dataType, picklistValues, isTbxBasic)
        VALUES(labelParam,typeParam,labelTextParam,levelParam,nameParam,dataTypeParam,picklistValuesParam,isTbxBasicParam);
END IF;
END $$
DELIMITER ;

CALL updateTbxBasic( 'descrip', 'subjectField', 'Fachgebiet','entry','Subject field','plainText',NULL,1);
CALL updateTbxBasic( 'xref', 'xGraphic', 'Bild','entry','Subject field','plainText',NULL,1);
CALL updateTbxBasic( 'langSet', NULL, 'Sprache','language','Language','Language code',NULL,1);
CALL updateTbxBasic( 'note', NULL, 'Anmerkung','entry,language,term','Note','noteText',NULL,1);
CALL updateTbxBasic( 'term', NULL, 'Term','term','Term','basicText',NULL,1);
CALL updateTbxBasic( 'admin', 'source', '#UT#Source of term','term','Term','noteText',NULL,1);
CALL updateTbxBasic( 'xref', 'externalCrossReference', '#UT#External cross-reference','entry,term','External cross-reference','plainText',NULL,1);
CALL updateTbxBasic( 'transac', 'origination', 'Erstellung','entry,language,term','Origination','plainText',NULL,1);
CALL updateTbxBasic( 'transac', 'modification', 'Letzte Änderung','entry,language,term','Modification','plainText',NULL,1);
CALL updateTbxBasic( 'transacNote', 'responsibility', 'Verantwortlich','entry,language,term','Responsibility','plainText',NULL,1);
CALL updateTbxBasic( 'date', NULL, 'Datum','entry,language,term','Date','date',NULL,1);
CALL updateTbxBasic( 'termNote', 'termType', 'Term Typ','term','Term type','picklist','fullForm,acronym,abbreviation,shortForm,variant,phrase',1);
CALL updateTbxBasic( 'termNote', 'partOfSpeech', 'Wortart','term','Part of speech','picklist','noun,verb,adjective,adverb,properNoun,other',1);
CALL updateTbxBasic( 'termNote', 'grammaticalGender', 'Grammatikalisches Geschlecht','term','Gender','picklist','masculine,feminine,neuter,other',1);
CALL updateTbxBasic( 'descrip', 'definition', 'Definition','entry,language','Definition','noteText',NULL,1);
CALL updateTbxBasic( 'admin', 'source', 'Definition','entry,language','Source of definition','noteText',NULL,1);
CALL updateTbxBasic( 'descrip', 'context', 'Kontext','term','Context','noteText',NULL,1);
CALL updateTbxBasic( 'admin', 'source', 'Definition','term','Source of context','noteText',NULL,1);
CALL updateTbxBasic( 'termNote', 'administrativeStatus', 'Administrativer Status','term','Usage status','picklist','preferred,admitted,notRecommended,obsolete',1);
CALL updateTbxBasic( 'termNote', 'geographicalUsage', '#UT#Geographical usage','term','Geographical usage','plainText',NULL,1);
CALL updateTbxBasic( 'termNote', 'termLocation', '#UT#Term location','term','Term location','plainText',NULL,1);
CALL updateTbxBasic( 'ref', 'crossReference', '#UT#Cross reference','entry,term','Cross reference','plainText',NULL,1);
CALL updateTbxBasic( 'admin', 'customerSubset', 'Kunde','term','Customer','plainText',NULL,1);
CALL updateTbxBasic( 'admin', 'projectSubset', 'Projektuntermenge','term','Project','plainText',NULL,1);

DROP PROCEDURE IF EXISTS updateTbxBasic;

-- fix the termNote level in attributes datatype
UPDATE `terms_attributes_datatype` SET `level`='term' WHERE `label`='termNote';

call translate5Logger('Updated TBX basic values.');

-- removing duplications by set ranking in the duplicates according to the rules which duplicate should be kept

-- we add some helper fields
alter table terms_attributes_datatype
    add column duplicateRank int(11) default 0 not null,
    add column toBeUsedDatatypeId int(11) default 0 not null;

-- reset in case of rerun
UPDATE terms_attributes_datatype SET toBeUsedDatatypeId = 0, duplicateRank = 0 WHERE id > 0;

-- we set the null type fields to empty strings for easier comparsion (since null = null is false)
UPDATE terms_attributes_datatype
SET type = ''
WHERE type is null;

-- by default we rank duplicates with 1
UPDATE terms_attributes_datatype dt, (
    select label, type, count(*) as groupCount
    from terms_attributes_datatype
    group by label, type
    having groupCount > 1
) dupl
set dt.duplicateRank = 1
WHERE dt.label = dupl.label AND (dt.type = dupl.type OR dt.type is null and dupl.type is null);

call translate5Logger('Mark duplicated datatypes.');

-- in original code first was searched for the datatypes with isTbxBasic = 1, if multiple found or nothing found, then the first duplicate is used.
-- so in other words, isTbxBasic is higher righted as the pure insertion order, so we weight it with 2
UPDATE terms_attributes_datatype dt
SET dt.duplicateRank = dt.duplicateRank + 2
WHERE duplicateRank > 0 AND dt.isTbxBasic = 1;

-- after that we add 1 to the first of each group (additionally grouped per isTbxBasic). After that the ones with the highest rank is the desired one
UPDATE terms_attributes_datatype dt, (
    SELECT min(id) as firstFound, label, type, isTbxBasic
    FROM terms_attributes_datatype
    WHERE terms_attributes_datatype.duplicateRank > 0
    GROUP BY label, type, isTbxBasic
) ff
SET dt.duplicateRank = dt.duplicateRank + 1
WHERE firstFound = dt.id;

-- now the highest rank of one group is the one to be kept. for easier selection we search them and set the rank to a fixed value, higher as the max possible rank
-- highest rank is isDuplicate 1 + isTbxBasic 2 + first of group 1 = 4 so we set the fixed value to 9
UPDATE terms_attributes_datatype dt, (
    SELECT max(duplicateRank) as best, label, type
    FROM terms_attributes_datatype
    WHERE terms_attributes_datatype.duplicateRank > 0
    GROUP BY label, type
) bf
SET dt.duplicateRank = 9
WHERE bf.best = dt.duplicateRank AND bf.label = dt.label  AND bf.type = dt.type;

-- FIXME in discussion: select here the highest ranked with non empty labeltext, and update the entry with duplicateRank = 9 with that labelText if it should be used

-- we set the toBeUsedDatatypeId to the id of the datatype finally to be used
UPDATE terms_attributes_datatype dt, (
    SELECT id, label, type
    FROM terms_attributes_datatype
    WHERE duplicateRank = 9
) bf
SET dt.toBeUsedDatatypeId = bf.id
WHERE bf.label = dt.label  AND bf.type = dt.type AND dt.duplicateRank < 9;

-- the same on the labels to be used itself
UPDATE terms_attributes_datatype dt
SET dt.toBeUsedDatatypeId = dt.id
WHERE dt.duplicateRank = 9;

call translate5Logger('Define which duplicated datatypes should be used.');

-- now we update the duplicated attributes labels to the new IDs:
UPDATE terms_attributes a, terms_attributes_datatype dt
SET a.dataTypeId = dt.toBeUsedDatatypeId
WHERE a.dataTypeId = dt.id AND dt.duplicateRank > 0;

call translate5Logger('Update attributes to be used the new datatypes.');

-- check at least attribute id 1154 must be datatypeid 64 not 75!

-- now delete all unwanted duplicates
DELETE FROM terms_attributes_datatype WHERE duplicateRank > 0 AND duplicateRank < 9;
call translate5Logger('delete duplicated datatypes.');

-- reset to null type fields
UPDATE terms_attributes_datatype
SET type = null
WHERE type = '';

alter table terms_attributes_datatype
    drop column duplicateRank,
    drop column toBeUsedDatatypeId;

-- after removing the duplicates the unique key can be set
ALTER TABLE `terms_attributes_datatype`
    ADD UNIQUE INDEX `idx_term_attributes_label_type_level` (`label` ASC, `type` ASC, `level` ASC);

call translate5Logger('recreate unique key.');

-- update
UPDATE `terms_attributes_datatype` 
SET `dataType` = 'picklist', `picklistValues` = 'unprocessed,provisionallyProcessed,finalized,rejected'
WHERE `type` = 'processStatus';

UPDATE `terms_attributes_datatype` SET `l10nSystem` = 'Kommentar', `l10nCustom` = 'Comment' WHERE `label` = 'note';

UPDATE `terms_attributes_datatype` SET `l10nCustom` = 'Image' WHERE `type` = 'figure';
UPDATE `terms_attributes_datatype` SET `l10nSystem` = 'Prozessstatus' WHERE `type` = 'processStatus';
UPDATE `terms_attributes_datatype` SET `l10nSystem` = 'Geographische Verwendung' WHERE `type` = 'geographicalUsage';
UPDATE `terms_attributes_datatype` SET `l10nSystem` = CONCAT('{"de":"', IFNULL(`l10nSystem`, ''), '","en":"', IFNULL(`l10nCustom`, ''), '"}');
UPDATE `terms_attributes_datatype` SET `l10nCustom` = '{"de":"","en":""}' WHERE id > 0;
UPDATE `terms_attributes_datatype` SET `isTbxBasic` = '1' WHERE `type` = 'processStatus' LIMIT 1;

# fill the collection to data type assoc table
INSERT INTO `terms_collection_attribute_datatype` (collectionId,dataTypeId) (SELECT collectionId,dataTypeId FROM `terms_attributes` group by collectionId,dataTypeId);

call translate5Logger('fix some values and update the datatype collection mapping.');
call translate5Logger('datatype migration is done - FINISHED OVERALL MIGRATION');

DROP PROCEDURE IF EXISTS translate5Logger;
