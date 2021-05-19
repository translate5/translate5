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

# Rename the new labels table to datatype
ALTER TABLE `LEK_term_attributes_label` 
RENAME TO  `terms_attributes_datatype` ;


# Rename tha labelId field in attributes table
ALTER TABLE `terms_attributes` 
CHANGE COLUMN `labelId` `dataTypeId` INT(11) NULL DEFAULT NULL ;


# Add new columns to the datatype table
ALTER TABLE `terms_attributes_datatype` 
ADD COLUMN `level` SET('entry', 'language', 'term') NULL DEFAULT 'entry,language,term' COMMENT 'Level represented as comma separated values where the label(attribute) can appear. entry,language,term' AFTER `labelText`,
ADD COLUMN `name` VARCHAR(255) NULL COMMENT 'Human readable name of the lable(attribute)' AFTER `level`,
ADD COLUMN `dataType` ENUM('plainText', 'noteText', 'basicText', 'picklist', 'Language code','date') NULL DEFAULT 'plainText' AFTER `name`,
ADD COLUMN `picklistValues` VARCHAR(255) NULL COMMENT 'Available comma separated values for selecting for the attribute when the attributa dataType is picklist.' AFTER `dataType`,
ADD COLUMN `isTbxBasic` TINYINT(1) NULL DEFAULT 0 AFTER `picklistValues`;

DROP PROCEDURE IF EXISTS updateTbxBasic;


CREATE TABLE `terms_collection_attribute_datatype` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `collectionId` INT NULL,
  `dataTypeId` INT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_terms_collection_attribute_datatype_1_idx` (`collectionId` ASC),
  INDEX `fk_terms_collection_attribute_datatype_2_idx` (`dataTypeId` ASC),
  INDEX `indexCollectionIdAndDataTypeId` (`collectionId` ASC, `dataTypeId` ASC),
  CONSTRAINT `fk_terms_collection_attribute_datatype_1`
    FOREIGN KEY (`collectionId`)
    REFERENCES `LEK_languageresources` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_terms_collection_attribute_datatype_2`
    FOREIGN KEY (`dataTypeId`)
    REFERENCES `terms_attributes_datatype` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE);


# Insert all available attributes for collection in the terms_collection_attribute_datatype table
INSERT INTO `terms_collection_attribute_datatype` (collectionId,dataTypeId) (SELECT collectionId,dataTypeId FROM `terms_attributes` group by collectionId,dataTypeId);

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

    SET @typeFilter = if(typeParam is null," `type` IS NULL;",concat(" `type` = '",typeParam,"';"));
  	SET @query := CONCAT("SELECT COUNT(*) INTO @recCount FROM `terms_attributes_datatype` WHERE `label`='",labelParam,"' AND ",@typeFilter);

PREPARE stmt1 FROM @query;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

If @recCount > 0 THEN

		SET @picklistValues = if(picklistValuesParam is null,"', `picklistValues` = NULL ",concat("', `picklistValues` = '",picklistValuesParam,"'"));
        SET @updateQuery := CONCAT("UPDATE `terms_attributes_datatype` SET `level` = '",levelParam,"',`name` = '",nameParam,"',`dataType` = '",dataTypeParam,@picklistValues,",`isTbxBasic` = ",isTbxBasicParam," WHERE `label`= '",labelParam,"' AND ", @typeFilter);

PREPARE stmt2 FROM @updateQuery;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;
ELSE
		INSERT INTO `terms_attributes_datatype` (label, type, labelText,level, name, dataType, picklistValues, isTbxBasic)
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