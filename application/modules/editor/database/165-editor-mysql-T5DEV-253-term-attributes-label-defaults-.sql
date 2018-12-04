-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
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

DROP PROCEDURE IF EXISTS updateAtributesLabel;

DELIMITER $$
CREATE PROCEDURE updateAtributesLabel(IN labelParam VARCHAR(255),IN typeParam VARCHAR(255),IN labelTextParam VARCHAR(255))
BEGIN
    SET @recCount = (SELECT COUNT(*) FROM `LEK_term_attributes_label` WHERE `label`=labelParam AND `type`<=>typeParam AND (`labelText`='' OR `labelText` IS NULL));
    If @recCount > 0 THEN
        UPDATE `LEK_term_attributes_label` SET `labelText` = labelTextParam WHERE `label`=labelParam AND `type` <=>typeParam;
	ELSE
		INSERT INTO `LEK_term_attributes_label` (label,type,labelText) VALUES(labelParam,typeParam,labelTextParam);
    END IF;
END $$
DELIMITER ;

CALL updateAtributesLabel( 'transac', 'origination', 'Erstellung');
CALL updateAtributesLabel( 'transacNote', 'responsibility', 'Verantwortlich');
CALL updateAtributesLabel( 'date', NULL, 'Datum');
CALL updateAtributesLabel( 'transac', 'modification', 'Letzte Änderung');
CALL updateAtributesLabel( 'termNote', 'termType', 'Term Typ');
CALL updateAtributesLabel( 'descrip', 'definition', 'Definition');
CALL updateAtributesLabel( 'termNote', 'abbreviatedFormFor', 'Abkürzung für');
CALL updateAtributesLabel( 'termNote', 'pronunciation', 'Aussprache');
CALL updateAtributesLabel( 'termNote', 'normativeAuthorization', 'Einstufung');
CALL updateAtributesLabel( 'descrip', 'subjectField', 'Fachgebiet');
CALL updateAtributesLabel( 'descrip', 'relatedConcept', 'Verwandtes Konzept');
CALL updateAtributesLabel( 'descrip', 'relatedConceptBroader', 'Erweitertes verwandtes Konzept');
CALL updateAtributesLabel( 'admin', 'productSubset', 'Produkt-Untermenge');
CALL updateAtributesLabel( 'admin', 'sourceIdentifier', 'Quellenidentifikator');
CALL updateAtributesLabel( 'termNote', 'partOfSpeech', 'Wortart');
CALL updateAtributesLabel( 'descrip', 'context', 'Kontext');
CALL updateAtributesLabel( 'admin', 'businessUnitSubset', 'Teilbereich der Geschäftseinheit');
CALL updateAtributesLabel( 'admin', 'projectSubset', 'Projektuntermenge');
CALL updateAtributesLabel( 'termNote', 'grammaticalGender', 'Grammatikalisches Geschlecht');
CALL updateAtributesLabel( 'note', NULL, 'Anmerkung');
CALL updateAtributesLabel( 'termNote', 'administrativeStatus', 'Administrativer Status');
CALL updateAtributesLabel( 'termNote', 'transferComment', 'Übertragungskommentar');
CALL updateAtributesLabel( 'admin', 'entrySource', 'Quelle des Eintrags');

DROP PROCEDURE IF EXISTS updateAtributesLabel;