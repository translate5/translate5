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

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"Anmerkung","en":"Comment"}'
WHERE label = "note" AND type = NULL;

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"Sachgebiet","en":"Subject field"}'
WHERE label = "descrip" AND type = "subjectField";

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"Abbildung/Multimedia","en":"Illustration / Multimedia"}'
WHERE label = "xref" AND type = "xGraphic";

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"externer Verweis","en":"External reference"}'
WHERE label = "xref" AND type = "externalCrossReference";

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"Querverweis","en":"Cross reference"}'
WHERE label = "ref" AND type = "crossReference";

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"Quelle","en":"Source"}'
WHERE label = "admin" AND type = "source";

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"Benennungstyp","en":"Term type"}'
WHERE label = "termNote" AND type = "termType";

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"Genus","en":"Gender"}'
WHERE label = "termNote" AND type = "grammaticalGender";

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"Gebrauch","en":"Usage status"}'
WHERE label = "termNote" AND type = "administrativeStatus";

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"regionale Verwendung","en":"Regional use"}'
WHERE label = "termNote" AND type = "geographicalUsage";

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"typischer Verwendungsfall","en":"Typical use case"}'
WHERE label = "termNote" AND type = "termLocation";

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"Kunde","en":"TCustomer"}'
WHERE label = "admin" AND type = "customerSubset";

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"Projekt","en":"Project"}'
WHERE label = "admin" AND type = "projectSubset";

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"angelegt von","en":"Created by"}'
WHERE label = "transac" AND type = "origination";

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"angelegt von","en":"Created by"}'
WHERE label = "transac" AND type = "creation";

UPDATE terms_attributes_datatype set l10nSystem = '{"de":"geändert von","en":"Modified by"}'
WHERE label = "transac" AND type = "modification";