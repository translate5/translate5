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

-- Typos --
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "auto-propgate", "auto-propagate");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "invokation", "invocation");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "punctiation", "punctuation");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "e.g ", "e.g. ");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "If one line of a segment is to long", "If one line of a segment is too long");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "superseeded", "superseded");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "For moreinfo see the branding", "For more info see the branding");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "Pattern for a XML reference file", "Pattern for an XML reference file");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "Okapi bconf is not used for CSV iimport", "Okapi bconf is not used for CSV import");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "CSV import: ecnclosure", "CSV import: enclosure");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "this is determined by another configuraiton parameter", "this is determined by another configuration parameter");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "If set to active, informations are added", "If set to active, information is added");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "and if this checkbox is checcked", "and if this checkbox is checked");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "If checcked, whitespace is preserved,", "If checked, whitespace is preserved,");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "ressources", "resources");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "azureaccount", "azure account");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "accessable", "accessible");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "date is shown.If set to", "date is shown. If set to");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "immidiately", "immediately");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "keeped", "kept");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "prefered", "preferred");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "from the users browser", "from the user's browser");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "Errors will be send to translate5s", "Errors will be sent to translate5s");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "occurence", "occurrence");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "Okapi server used for the a task", "Okapi server used for a task");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "If emtpy, nothing is loaded", "If empty, nothing is loaded");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "The imported task is send as raw JSON in that request", "The imported task is sent as raw JSON in that request");
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, "successfull", "successful");

-- Inconsistencies --
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'api', 'API') WHERE `description` REGEXP '(^| )api';
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'Api', 'API') WHERE `description` REGEXP '(^| )api';
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'url', 'URL') WHERE `description` REGEXP '(^| )url';
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'MicroSoft', 'Microsoft');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'microsoft', 'Microsoft');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'pretranslated', 'pre-translation');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'pretranslation', 'pre-translation');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'matchrate', 'match-rate');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'match rate', 'match-rate');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'languagetool', 'LanguageTool');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'LanguagaTool', 'LanguageTool');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'languageTool', 'LanguageTool');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'languagecloud', 'LanguageCloud');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'termtagger', 'TermTagger');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'Websocket', 'WebSocket');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'Extjs', 'ExtJS');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, 'termportal', 'TermPortal');
UPDATE `Zf_configuration` SET `description` = REPLACE(`description`, ' he ', ' the user ');
