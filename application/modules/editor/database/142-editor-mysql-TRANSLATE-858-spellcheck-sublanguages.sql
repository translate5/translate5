/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2018 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/* add comments */
ALTER TABLE `LEK_languages` CHANGE `rfc5646` `rfc5646` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'RFC5646 language shortcut according to the specification';

/* fix spelling */
UPDATE `LEK_languages` SET `rfc5646` = 'az-Cyrl' WHERE `LEK_languages`.`rfc5646` = 'Az-Cyrl';

/* add new column: sublanguage */
ALTER TABLE `LEK_languages` ADD `sublanguage` VARCHAR(30) NULL DEFAULT NULL COMMENT 'RFC5646 language shortcut (or similar) of the sublanguage that is most important e.g. for SpellChecks' AFTER `iso3166Part1alpha2`;

UPDATE `LEK_languages` SET `sublanguage` = 'af-ZA' WHERE `LEK_languages`.`rfc5646` = 'af';
UPDATE `LEK_languages` SET `sublanguage` = 'af-ZA' WHERE `LEK_languages`.`rfc5646` = 'af-ZA';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-AE' WHERE `LEK_languages`.`rfc5646` = 'ar';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-AE' WHERE `LEK_languages`.`rfc5646` = 'ar-AE';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-BH' WHERE `LEK_languages`.`rfc5646` = 'ar-BH';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-DZ' WHERE `LEK_languages`.`rfc5646` = 'ar-DZ';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-EG' WHERE `LEK_languages`.`rfc5646` = 'ar-EG';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-IQ' WHERE `LEK_languages`.`rfc5646` = 'ar-IQ';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-JO' WHERE `LEK_languages`.`rfc5646` = 'ar-JO';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-KW' WHERE `LEK_languages`.`rfc5646` = 'ar-KW';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-LB' WHERE `LEK_languages`.`rfc5646` = 'ar-LB';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-LY' WHERE `LEK_languages`.`rfc5646` = 'ar-LY';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-MA' WHERE `LEK_languages`.`rfc5646` = 'ar-MA';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-OM' WHERE `LEK_languages`.`rfc5646` = 'ar-OM';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-QA' WHERE `LEK_languages`.`rfc5646` = 'ar-QA';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-SA' WHERE `LEK_languages`.`rfc5646` = 'ar-SA';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-SY' WHERE `LEK_languages`.`rfc5646` = 'ar-SY';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-TN' WHERE `LEK_languages`.`rfc5646` = 'ar-TN';
UPDATE `LEK_languages` SET `sublanguage` = 'ar-YE' WHERE `LEK_languages`.`rfc5646` = 'ar-YE';
UPDATE `LEK_languages` SET `sublanguage` = 'az-AZ' WHERE `LEK_languages`.`rfc5646` = 'az';
UPDATE `LEK_languages` SET `sublanguage` = 'az-AZ' WHERE `LEK_languages`.`rfc5646` = 'az-AZ';
UPDATE `LEK_languages` SET `sublanguage` = 'az-Cyrl' WHERE `LEK_languages`.`rfc5646` = 'az-Cyrl';
UPDATE `LEK_languages` SET `sublanguage` = 'az-Cyrl-AZ' WHERE `LEK_languages`.`rfc5646` = 'az-Cyrl-AZ';
UPDATE `LEK_languages` SET `sublanguage` = 'be-BY' WHERE `LEK_languages`.`rfc5646` = 'be';
UPDATE `LEK_languages` SET `sublanguage` = 'be-BY' WHERE `LEK_languages`.`rfc5646` = 'be-BY';
UPDATE `LEK_languages` SET `sublanguage` = 'bg-BG' WHERE `LEK_languages`.`rfc5646` = 'bg';
UPDATE `LEK_languages` SET `sublanguage` = 'bg-BG' WHERE `LEK_languages`.`rfc5646` = 'bg-BG';
UPDATE `LEK_languages` SET `sublanguage` = 'bn-IN' WHERE `LEK_languages`.`rfc5646` = 'bn';
UPDATE `LEK_languages` SET `sublanguage` = 'bo-TI' WHERE `LEK_languages`.`rfc5646` = 'bo';
UPDATE `LEK_languages` SET `sublanguage` = 'bs-BA' WHERE `LEK_languages`.`rfc5646` = 'bs';
UPDATE `LEK_languages` SET `sublanguage` = 'bs-BA' WHERE `LEK_languages`.`rfc5646` = 'bs-BA';
UPDATE `LEK_languages` SET `sublanguage` = 'ca-ES' WHERE `LEK_languages`.`rfc5646` = 'ca';
UPDATE `LEK_languages` SET `sublanguage` = 'ca-ES' WHERE `LEK_languages`.`rfc5646` = 'ca-ES';
UPDATE `LEK_languages` SET `sublanguage` = 'cs-CZ' WHERE `LEK_languages`.`rfc5646` = 'cs';
UPDATE `LEK_languages` SET `sublanguage` = 'cs-CZ' WHERE `LEK_languages`.`rfc5646` = 'cs-CZ';
UPDATE `LEK_languages` SET `sublanguage` = 'cy-GB' WHERE `LEK_languages`.`rfc5646` = 'cy';
UPDATE `LEK_languages` SET `sublanguage` = 'cy-GB' WHERE `LEK_languages`.`rfc5646` = 'cy-GB';
UPDATE `LEK_languages` SET `sublanguage` = 'da-DK' WHERE `LEK_languages`.`rfc5646` = 'da';
UPDATE `LEK_languages` SET `sublanguage` = 'da-DK' WHERE `LEK_languages`.`rfc5646` = 'da-DK';
UPDATE `LEK_languages` SET `sublanguage` = 'de-DE' WHERE `LEK_languages`.`rfc5646` = 'de';
UPDATE `LEK_languages` SET `sublanguage` = 'de-AT' WHERE `LEK_languages`.`rfc5646` = 'de-AT';
UPDATE `LEK_languages` SET `sublanguage` = 'de-CH' WHERE `LEK_languages`.`rfc5646` = 'de-CH';
UPDATE `LEK_languages` SET `sublanguage` = 'de-DE' WHERE `LEK_languages`.`rfc5646` = 'de-DE';
UPDATE `LEK_languages` SET `sublanguage` = 'de-LI' WHERE `LEK_languages`.`rfc5646` = 'de-LI';
UPDATE `LEK_languages` SET `sublanguage` = 'de-LU' WHERE `LEK_languages`.`rfc5646` = 'de-LU';
UPDATE `LEK_languages` SET `sublanguage` = 'dv-MV' WHERE `LEK_languages`.`rfc5646` = 'dv';
UPDATE `LEK_languages` SET `sublanguage` = 'dv-MV' WHERE `LEK_languages`.`rfc5646` = 'dv-MV';
UPDATE `LEK_languages` SET `sublanguage` = 'ee-EE' WHERE `LEK_languages`.`rfc5646` = 'ee';
UPDATE `LEK_languages` SET `sublanguage` = 'el-GR' WHERE `LEK_languages`.`rfc5646` = 'el';
UPDATE `LEK_languages` SET `sublanguage` = 'el-GR' WHERE `LEK_languages`.`rfc5646` = 'el-GR';
UPDATE `LEK_languages` SET `sublanguage` = 'en-GB' WHERE `LEK_languages`.`rfc5646` = 'en';
UPDATE `LEK_languages` SET `sublanguage` = 'en-AU' WHERE `LEK_languages`.`rfc5646` = 'en-AU';
UPDATE `LEK_languages` SET `sublanguage` = 'en-BZ' WHERE `LEK_languages`.`rfc5646` = 'en-BZ';
UPDATE `LEK_languages` SET `sublanguage` = 'en-CA' WHERE `LEK_languages`.`rfc5646` = 'en-CA';
UPDATE `LEK_languages` SET `sublanguage` = 'en-CB' WHERE `LEK_languages`.`rfc5646` = 'en-CB';
UPDATE `LEK_languages` SET `sublanguage` = 'en-GB' WHERE `LEK_languages`.`rfc5646` = 'en-GB';
UPDATE `LEK_languages` SET `sublanguage` = 'en-IE' WHERE `LEK_languages`.`rfc5646` = 'en-IE';
UPDATE `LEK_languages` SET `sublanguage` = 'en-JM' WHERE `LEK_languages`.`rfc5646` = 'en-JM';
UPDATE `LEK_languages` SET `sublanguage` = 'en-NZ' WHERE `LEK_languages`.`rfc5646` = 'en-NZ';
UPDATE `LEK_languages` SET `sublanguage` = 'en-PH' WHERE `LEK_languages`.`rfc5646` = 'en-PH';
UPDATE `LEK_languages` SET `sublanguage` = 'en-TT' WHERE `LEK_languages`.`rfc5646` = 'en-TT';
UPDATE `LEK_languages` SET `sublanguage` = 'en-US' WHERE `LEK_languages`.`rfc5646` = 'en-US';
UPDATE `LEK_languages` SET `sublanguage` = 'en-ZA' WHERE `LEK_languages`.`rfc5646` = 'en-ZA';
UPDATE `LEK_languages` SET `sublanguage` = 'en-ZW' WHERE `LEK_languages`.`rfc5646` = 'en-ZW';
UPDATE `LEK_languages` SET `sublanguage` = 'eo' WHERE `LEK_languages`.`rfc5646` = 'eo';                     /* ??? */
UPDATE `LEK_languages` SET `sublanguage` = 'es-ES' WHERE `LEK_languages`.`rfc5646` = 'es';
UPDATE `LEK_languages` SET `sublanguage` = 'es-AR' WHERE `LEK_languages`.`rfc5646` = 'es-AR';
UPDATE `LEK_languages` SET `sublanguage` = 'es-BO' WHERE `LEK_languages`.`rfc5646` = 'es-BO';
UPDATE `LEK_languages` SET `sublanguage` = 'es-CL' WHERE `LEK_languages`.`rfc5646` = 'es-CL';
UPDATE `LEK_languages` SET `sublanguage` = 'es-CO' WHERE `LEK_languages`.`rfc5646` = 'es-CO';
UPDATE `LEK_languages` SET `sublanguage` = 'es-CR' WHERE `LEK_languages`.`rfc5646` = 'es-CR';
UPDATE `LEK_languages` SET `sublanguage` = 'es-DO' WHERE `LEK_languages`.`rfc5646` = 'es-DO';
UPDATE `LEK_languages` SET `sublanguage` = 'es-EC' WHERE `LEK_languages`.`rfc5646` = 'es-EC';
UPDATE `LEK_languages` SET `sublanguage` = 'es-ES' WHERE `LEK_languages`.`rfc5646` = 'es-ES';
UPDATE `LEK_languages` SET `sublanguage` = 'es-GT' WHERE `LEK_languages`.`rfc5646` = 'es-GT';
UPDATE `LEK_languages` SET `sublanguage` = 'es-HN' WHERE `LEK_languages`.`rfc5646` = 'es-HN';
UPDATE `LEK_languages` SET `sublanguage` = 'es-MX' WHERE `LEK_languages`.`rfc5646` = 'es-MX';
UPDATE `LEK_languages` SET `sublanguage` = 'es-NI' WHERE `LEK_languages`.`rfc5646` = 'es-NI';
UPDATE `LEK_languages` SET `sublanguage` = 'es-PA' WHERE `LEK_languages`.`rfc5646` = 'es-PA';
UPDATE `LEK_languages` SET `sublanguage` = 'es-PE' WHERE `LEK_languages`.`rfc5646` = 'es-PE';
UPDATE `LEK_languages` SET `sublanguage` = 'es-PR' WHERE `LEK_languages`.`rfc5646` = 'es-PR';
UPDATE `LEK_languages` SET `sublanguage` = 'es-PY' WHERE `LEK_languages`.`rfc5646` = 'es-PY';
UPDATE `LEK_languages` SET `sublanguage` = 'es-SV' WHERE `LEK_languages`.`rfc5646` = 'es-SV';
UPDATE `LEK_languages` SET `sublanguage` = 'es-UY' WHERE `LEK_languages`.`rfc5646` = 'es-UY';
UPDATE `LEK_languages` SET `sublanguage` = 'es-VE' WHERE `LEK_languages`.`rfc5646` = 'es-VE';
UPDATE `LEK_languages` SET `sublanguage` = 'et-EE' WHERE `LEK_languages`.`rfc5646` = 'et-EE';
UPDATE `LEK_languages` SET `sublanguage` = 'eu-ES' WHERE `LEK_languages`.`rfc5646` = 'eu';
UPDATE `LEK_languages` SET `sublanguage` = 'eu-ES' WHERE `LEK_languages`.`rfc5646` = 'eu-ES';
UPDATE `LEK_languages` SET `sublanguage` = 'fa-IR' WHERE `LEK_languages`.`rfc5646` = 'fa';
UPDATE `LEK_languages` SET `sublanguage` = 'fa-IR' WHERE `LEK_languages`.`rfc5646` = 'fa-IR';
UPDATE `LEK_languages` SET `sublanguage` = 'fi-FI' WHERE `LEK_languages`.`rfc5646` = 'fi';
UPDATE `LEK_languages` SET `sublanguage` = 'fi-FI' WHERE `LEK_languages`.`rfc5646` = 'fi-FI';
UPDATE `LEK_languages` SET `sublanguage` = 'fo-FO' WHERE `LEK_languages`.`rfc5646` = 'fo';
UPDATE `LEK_languages` SET `sublanguage` = 'fo-FO' WHERE `LEK_languages`.`rfc5646` = 'fo-FO';
UPDATE `LEK_languages` SET `sublanguage` = 'fr-FR' WHERE `LEK_languages`.`rfc5646` = 'fr';
UPDATE `LEK_languages` SET `sublanguage` = 'fr-BE' WHERE `LEK_languages`.`rfc5646` = 'fr-BE';
UPDATE `LEK_languages` SET `sublanguage` = 'fr-CA' WHERE `LEK_languages`.`rfc5646` = 'fr-CA';
UPDATE `LEK_languages` SET `sublanguage` = 'fr-CH' WHERE `LEK_languages`.`rfc5646` = 'fr-CH';
UPDATE `LEK_languages` SET `sublanguage` = 'fr-FR' WHERE `LEK_languages`.`rfc5646` = 'fr-FR';
UPDATE `LEK_languages` SET `sublanguage` = 'fr-LU' WHERE `LEK_languages`.`rfc5646` = 'fr-LU';
UPDATE `LEK_languages` SET `sublanguage` = 'fr-MC' WHERE `LEK_languages`.`rfc5646` = 'fr-MC';
UPDATE `LEK_languages` SET `sublanguage` = 'ga-IE' WHERE `LEK_languages`.`rfc5646` = 'ga-IE';
UPDATE `LEK_languages` SET `sublanguage` = 'gl-ES' WHERE `LEK_languages`.`rfc5646` = 'gl';
UPDATE `LEK_languages` SET `sublanguage` = 'gl-ES' WHERE `LEK_languages`.`rfc5646` = 'gl-ES';
UPDATE `LEK_languages` SET `sublanguage` = 'gu-IN' WHERE `LEK_languages`.`rfc5646` = 'gu';
UPDATE `LEK_languages` SET `sublanguage` = 'gu-IN' WHERE `LEK_languages`.`rfc5646` = 'gu-IN';
UPDATE `LEK_languages` SET `sublanguage` = 'he-IL' WHERE `LEK_languages`.`rfc5646` = 'he';
UPDATE `LEK_languages` SET `sublanguage` = 'he-IL' WHERE `LEK_languages`.`rfc5646` = 'he-IL';
UPDATE `LEK_languages` SET `sublanguage` = 'hi-IN' WHERE `LEK_languages`.`rfc5646` = 'hi';
UPDATE `LEK_languages` SET `sublanguage` = 'hi-IN' WHERE `LEK_languages`.`rfc5646` = 'hi-IN';
UPDATE `LEK_languages` SET `sublanguage` = 'hr-HR' WHERE `LEK_languages`.`rfc5646` = 'hr';
UPDATE `LEK_languages` SET `sublanguage` = 'hr-BA' WHERE `LEK_languages`.`rfc5646` = 'hr-BA';
UPDATE `LEK_languages` SET `sublanguage` = 'hr-HR' WHERE `LEK_languages`.`rfc5646` = 'hr-HR';
UPDATE `LEK_languages` SET `sublanguage` = 'hu-HU' WHERE `LEK_languages`.`rfc5646` = 'hu';
UPDATE `LEK_languages` SET `sublanguage` = 'hu-HU' WHERE `LEK_languages`.`rfc5646` = 'hu-HU';
UPDATE `LEK_languages` SET `sublanguage` = 'hy-AM' WHERE `LEK_languages`.`rfc5646` = 'hy';
UPDATE `LEK_languages` SET `sublanguage` = 'hy-AM' WHERE `LEK_languages`.`rfc5646` = 'hy-AM';
UPDATE `LEK_languages` SET `sublanguage` = 'id-ID' WHERE `LEK_languages`.`rfc5646` = 'id';
UPDATE `LEK_languages` SET `sublanguage` = 'id-ID' WHERE `LEK_languages`.`rfc5646` = 'id-ID';
UPDATE `LEK_languages` SET `sublanguage` = 'ig-NG' WHERE `LEK_languages`.`rfc5646` = 'ig';
UPDATE `LEK_languages` SET `sublanguage` = 'is-IS' WHERE `LEK_languages`.`rfc5646` = 'is';
UPDATE `LEK_languages` SET `sublanguage` = 'is-IS' WHERE `LEK_languages`.`rfc5646` = 'is-IS';
UPDATE `LEK_languages` SET `sublanguage` = 'it-IT' WHERE `LEK_languages`.`rfc5646` = 'it';
UPDATE `LEK_languages` SET `sublanguage` = 'it-CH' WHERE `LEK_languages`.`rfc5646` = 'it-CH';
UPDATE `LEK_languages` SET `sublanguage` = 'it-IT' WHERE `LEK_languages`.`rfc5646` = 'it-IT';
UPDATE `LEK_languages` SET `sublanguage` = 'ja-JP' WHERE `LEK_languages`.`rfc5646` = 'ja';
UPDATE `LEK_languages` SET `sublanguage` = 'ja-JP' WHERE `LEK_languages`.`rfc5646` = 'ja-JP';
UPDATE `LEK_languages` SET `sublanguage` = 'ka-GE' WHERE `LEK_languages`.`rfc5646` = 'ka';
UPDATE `LEK_languages` SET `sublanguage` = 'ka-GE' WHERE `LEK_languages`.`rfc5646` = 'ka-GE';
UPDATE `LEK_languages` SET `sublanguage` = 'kk-KZ' WHERE `LEK_languages`.`rfc5646` = 'kk';
UPDATE `LEK_languages` SET `sublanguage` = 'kk-KZ' WHERE `LEK_languages`.`rfc5646` = 'kk-KZ';
UPDATE `LEK_languages` SET `sublanguage` = 'km-KH' WHERE `LEK_languages`.`rfc5646` = 'km';
UPDATE `LEK_languages` SET `sublanguage` = 'kn-IN' WHERE `LEK_languages`.`rfc5646` = 'kn';
UPDATE `LEK_languages` SET `sublanguage` = 'kn-IN' WHERE `LEK_languages`.`rfc5646` = 'kn-IN';
UPDATE `LEK_languages` SET `sublanguage` = 'ko-KR' WHERE `LEK_languages`.`rfc5646` = 'ko';
UPDATE `LEK_languages` SET `sublanguage` = 'ko-KR' WHERE `LEK_languages`.`rfc5646` = 'ko-KR';
UPDATE `LEK_languages` SET `sublanguage` = 'kok-IN' WHERE `LEK_languages`.`rfc5646` = 'kok';
UPDATE `LEK_languages` SET `sublanguage` = 'kok-IN' WHERE `LEK_languages`.`rfc5646` = 'kok-IN';
UPDATE `LEK_languages` SET `sublanguage` = 'ku-KU' WHERE `LEK_languages`.`rfc5646` = 'ku';
UPDATE `LEK_languages` SET `sublanguage` = 'ky-KG' WHERE `LEK_languages`.`rfc5646` = 'ky';
UPDATE `LEK_languages` SET `sublanguage` = 'ky-KG' WHERE `LEK_languages`.`rfc5646` = 'ky-KG';
UPDATE `LEK_languages` SET `sublanguage` = 'lt-LT' WHERE `LEK_languages`.`rfc5646` = 'lt';
UPDATE `LEK_languages` SET `sublanguage` = 'lt-LT' WHERE `LEK_languages`.`rfc5646` = 'lt-LT';
UPDATE `LEK_languages` SET `sublanguage` = 'lv-LV' WHERE `LEK_languages`.`rfc5646` = 'lv';
UPDATE `LEK_languages` SET `sublanguage` = 'lv-LV' WHERE `LEK_languages`.`rfc5646` = 'lv-LV';
UPDATE `LEK_languages` SET `sublanguage` = 'mi-NZ' WHERE `LEK_languages`.`rfc5646` = 'mi';
UPDATE `LEK_languages` SET `sublanguage` = 'mi-NZ' WHERE `LEK_languages`.`rfc5646` = 'mi-NZ';
UPDATE `LEK_languages` SET `sublanguage` = 'mk-MK' WHERE `LEK_languages`.`rfc5646` = 'mk';
UPDATE `LEK_languages` SET `sublanguage` = 'mk-MK' WHERE `LEK_languages`.`rfc5646` = 'mk-MK';
UPDATE `LEK_languages` SET `sublanguage` = 'ml-IN' WHERE `LEK_languages`.`rfc5646` = 'ml';
UPDATE `LEK_languages` SET `sublanguage` = 'mn-MN' WHERE `LEK_languages`.`rfc5646` = 'mn';
UPDATE `LEK_languages` SET `sublanguage` = 'mn-MN' WHERE `LEK_languages`.`rfc5646` = 'mn-MN';
UPDATE `LEK_languages` SET `sublanguage` = 'mo-MD' WHERE `LEK_languages`.`rfc5646` = 'mo';
UPDATE `LEK_languages` SET `sublanguage` = 'mr-IN' WHERE `LEK_languages`.`rfc5646` = 'mr';
UPDATE `LEK_languages` SET `sublanguage` = 'mr-IN' WHERE `LEK_languages`.`rfc5646` = 'mr-IN';
UPDATE `LEK_languages` SET `sublanguage` = 'ms-MY' WHERE `LEK_languages`.`rfc5646` = 'ms';
UPDATE `LEK_languages` SET `sublanguage` = 'ms-BN' WHERE `LEK_languages`.`rfc5646` = 'ms-BN';
UPDATE `LEK_languages` SET `sublanguage` = 'ms-MY' WHERE `LEK_languages`.`rfc5646` = 'ms-MY';
UPDATE `LEK_languages` SET `sublanguage` = 'mt-MT' WHERE `LEK_languages`.`rfc5646` = 'mt';
UPDATE `LEK_languages` SET `sublanguage` = 'mt-MT' WHERE `LEK_languages`.`rfc5646` = 'mt-MT';
UPDATE `LEK_languages` SET `sublanguage` = 'nb-NO' WHERE `LEK_languages`.`rfc5646` = 'nb';
UPDATE `LEK_languages` SET `sublanguage` = 'nb-NO' WHERE `LEK_languages`.`rfc5646` = 'nb-NO';
UPDATE `LEK_languages` SET `sublanguage` = 'nl-NL' WHERE `LEK_languages`.`rfc5646` = 'nl';
UPDATE `LEK_languages` SET `sublanguage` = 'nl-BE' WHERE `LEK_languages`.`rfc5646` = 'nl-BE';
UPDATE `LEK_languages` SET `sublanguage` = 'nl-NL' WHERE `LEK_languages`.`rfc5646` = 'nl-NL';
UPDATE `LEK_languages` SET `sublanguage` = 'nn-NO' WHERE `LEK_languages`.`rfc5646` = 'nn';
UPDATE `LEK_languages` SET `sublanguage` = 'nn-NO' WHERE `LEK_languages`.`rfc5646` = 'nn-NO';
UPDATE `LEK_languages` SET `sublanguage` = 'ns-ZA' WHERE `LEK_languages`.`rfc5646` = 'ns';
UPDATE `LEK_languages` SET `sublanguage` = 'ns-ZA' WHERE `LEK_languages`.`rfc5646` = 'ns-ZA';
UPDATE `LEK_languages` SET `sublanguage` = 'pa-IN' WHERE `LEK_languages`.`rfc5646` = 'pa';
UPDATE `LEK_languages` SET `sublanguage` = 'pa-IN' WHERE `LEK_languages`.`rfc5646` = 'pa-IN';
UPDATE `LEK_languages` SET `sublanguage` = 'pl-PL' WHERE `LEK_languages`.`rfc5646` = 'pl';
UPDATE `LEK_languages` SET `sublanguage` = 'pl-PL' WHERE `LEK_languages`.`rfc5646` = 'pl-PL';
UPDATE `LEK_languages` SET `sublanguage` = 'ps-AR' WHERE `LEK_languages`.`rfc5646` = 'ps';
UPDATE `LEK_languages` SET `sublanguage` = 'ps-AR' WHERE `LEK_languages`.`rfc5646` = 'ps-AR';
UPDATE `LEK_languages` SET `sublanguage` = 'pt-PT' WHERE `LEK_languages`.`rfc5646` = 'pt';
UPDATE `LEK_languages` SET `sublanguage` = 'pt-BR' WHERE `LEK_languages`.`rfc5646` = 'pt-BR';
UPDATE `LEK_languages` SET `sublanguage` = 'pt-PT' WHERE `LEK_languages`.`rfc5646` = 'pt-PT';
UPDATE `LEK_languages` SET `sublanguage` = 'qu-PE' WHERE `LEK_languages`.`rfc5646` = 'qu';
UPDATE `LEK_languages` SET `sublanguage` = 'qu-BO' WHERE `LEK_languages`.`rfc5646` = 'qu-BO';
UPDATE `LEK_languages` SET `sublanguage` = 'qu-EC' WHERE `LEK_languages`.`rfc5646` = 'qu-EC';
UPDATE `LEK_languages` SET `sublanguage` = 'qu-PE' WHERE `LEK_languages`.`rfc5646` = 'qu-PE';
UPDATE `LEK_languages` SET `sublanguage` = 'ro-RO' WHERE `LEK_languages`.`rfc5646` = 'ro';
UPDATE `LEK_languages` SET `sublanguage` = 'ro-RO' WHERE `LEK_languages`.`rfc5646` = 'ro-RO';
UPDATE `LEK_languages` SET `sublanguage` = 'ru-RU' WHERE `LEK_languages`.`rfc5646` = 'ru';
UPDATE `LEK_languages` SET `sublanguage` = 'ru-RU' WHERE `LEK_languages`.`rfc5646` = 'ru-RU';
UPDATE `LEK_languages` SET `sublanguage` = 'sa-IN' WHERE `LEK_languages`.`rfc5646` = 'sa';
UPDATE `LEK_languages` SET `sublanguage` = 'sa-IN' WHERE `LEK_languages`.`rfc5646` = 'sa-IN';
UPDATE `LEK_languages` SET `sublanguage` = 'se-FI' WHERE `LEK_languages`.`rfc5646` = 'se';                  /* ??? */
UPDATE `LEK_languages` SET `sublanguage` = 'se-FI' WHERE `LEK_languages`.`rfc5646` = 'se-FI';
UPDATE `LEK_languages` SET `sublanguage` = 'se-NO' WHERE `LEK_languages`.`rfc5646` = 'se-NO';
UPDATE `LEK_languages` SET `sublanguage` = 'se-SE' WHERE `LEK_languages`.`rfc5646` = 'se-SE';
UPDATE `LEK_languages` SET `sublanguage` = 'sk-SK' WHERE `LEK_languages`.`rfc5646` = 'sk';
UPDATE `LEK_languages` SET `sublanguage` = 'sk-SK' WHERE `LEK_languages`.`rfc5646` = 'sk-SK';
UPDATE `LEK_languages` SET `sublanguage` = 'sl-SI' WHERE `LEK_languages`.`rfc5646` = 'sl';
UPDATE `LEK_languages` SET `sublanguage` = 'sl-SI' WHERE `LEK_languages`.`rfc5646` = 'sl-SI';
UPDATE `LEK_languages` SET `sublanguage` = 'so-SO' WHERE `LEK_languages`.`rfc5646` = 'so';
UPDATE `LEK_languages` SET `sublanguage` = 'sq-AL' WHERE `LEK_languages`.`rfc5646` = 'sq';
UPDATE `LEK_languages` SET `sublanguage` = 'sq-AL' WHERE `LEK_languages`.`rfc5646` = 'sq-AL';
UPDATE `LEK_languages` SET `sublanguage` = 'sr-SP' WHERE `LEK_languages`.`rfc5646` = 'sr';
UPDATE `LEK_languages` SET `sublanguage` = 'sr-BA' WHERE `LEK_languages`.`rfc5646` = 'sr-BA';
UPDATE `LEK_languages` SET `sublanguage` = 'sr-Cyrl' WHERE `LEK_languages`.`rfc5646` = 'sr-Cyrl';
UPDATE `LEK_languages` SET `sublanguage` = 'sr-Cyrl-BA' WHERE `LEK_languages`.`rfc5646` = 'sr-Cyrl-BA';
UPDATE `LEK_languages` SET `sublanguage` = 'sr-Cyrl-SP' WHERE `LEK_languages`.`rfc5646` = 'sr-Cyrl-SP';
UPDATE `LEK_languages` SET `sublanguage` = 'sr-SP' WHERE `LEK_languages`.`rfc5646` = 'sr-SP';
UPDATE `LEK_languages` SET `sublanguage` = 'st-LS' WHERE `LEK_languages`.`rfc5646` = 'st';
UPDATE `LEK_languages` SET `sublanguage` = 'sv-SE' WHERE `LEK_languages`.`rfc5646` = 'sv';
UPDATE `LEK_languages` SET `sublanguage` = 'sv-FI' WHERE `LEK_languages`.`rfc5646` = 'sv-FI';
UPDATE `LEK_languages` SET `sublanguage` = 'sv-SE' WHERE `LEK_languages`.`rfc5646` = 'sv-SE';
UPDATE `LEK_languages` SET `sublanguage` = 'sw-KE' WHERE `LEK_languages`.`rfc5646` = 'sw';
UPDATE `LEK_languages` SET `sublanguage` = 'sw-KE' WHERE `LEK_languages`.`rfc5646` = 'sw-KE';
UPDATE `LEK_languages` SET `sublanguage` = 'syr-SY' WHERE `LEK_languages`.`rfc5646` = 'syr';
UPDATE `LEK_languages` SET `sublanguage` = 'syr-SY' WHERE `LEK_languages`.`rfc5646` = 'syr-SY';
UPDATE `LEK_languages` SET `sublanguage` = 'ta-IN' WHERE `LEK_languages`.`rfc5646` = 'ta';
UPDATE `LEK_languages` SET `sublanguage` = 'ta-IN' WHERE `LEK_languages`.`rfc5646` = 'ta-IN';
UPDATE `LEK_languages` SET `sublanguage` = 'te-IN' WHERE `LEK_languages`.`rfc5646` = 'te';
UPDATE `LEK_languages` SET `sublanguage` = 'te-IN' WHERE `LEK_languages`.`rfc5646` = 'te-IN';
UPDATE `LEK_languages` SET `sublanguage` = 'tg-TJ' WHERE `LEK_languages`.`rfc5646` = 'tg';
UPDATE `LEK_languages` SET `sublanguage` = 'th-TH' WHERE `LEK_languages`.`rfc5646` = 'th';
UPDATE `LEK_languages` SET `sublanguage` = 'th-TH' WHERE `LEK_languages`.`rfc5646` = 'th-TH';
UPDATE `LEK_languages` SET `sublanguage` = 'tk-TM' WHERE `LEK_languages`.`rfc5646` = 'tk';
UPDATE `LEK_languages` SET `sublanguage` = 'tl-PH' WHERE `LEK_languages`.`rfc5646` = 'tl';
UPDATE `LEK_languages` SET `sublanguage` = 'tl-PH' WHERE `LEK_languages`.`rfc5646` = 'tl-PH';
UPDATE `LEK_languages` SET `sublanguage` = 'tn-ZA' WHERE `LEK_languages`.`rfc5646` = 'tn';
UPDATE `LEK_languages` SET `sublanguage` = 'tn-ZA' WHERE `LEK_languages`.`rfc5646` = 'tn-ZA';
UPDATE `LEK_languages` SET `sublanguage` = 'tr-TR' WHERE `LEK_languages`.`rfc5646` = 'tr';
UPDATE `LEK_languages` SET `sublanguage` = 'tr-TR' WHERE `LEK_languages`.`rfc5646` = 'tr-TR';
UPDATE `LEK_languages` SET `sublanguage` = 'ts' WHERE `LEK_languages`.`rfc5646` = 'ts';                      /* ??? */
UPDATE `LEK_languages` SET `sublanguage` = 'tt-RU' WHERE `LEK_languages`.`rfc5646` = 'tt';
UPDATE `LEK_languages` SET `sublanguage` = 'tt-RU' WHERE `LEK_languages`.`rfc5646` = 'tt-RU';
UPDATE `LEK_languages` SET `sublanguage` = 'uk-UA' WHERE `LEK_languages`.`rfc5646` = 'uk';
UPDATE `LEK_languages` SET `sublanguage` = 'uk-UA' WHERE `LEK_languages`.`rfc5646` = 'uk-UA';
UPDATE `LEK_languages` SET `sublanguage` = 'ur-PK' WHERE `LEK_languages`.`rfc5646` = 'ur';
UPDATE `LEK_languages` SET `sublanguage` = 'ur-PK' WHERE `LEK_languages`.`rfc5646` = 'ur-PK';
UPDATE `LEK_languages` SET `sublanguage` = 'uz-UZ' WHERE `LEK_languages`.`rfc5646` = 'uz';
UPDATE `LEK_languages` SET `sublanguage` = 'uz-Cyrl' WHERE `LEK_languages`.`rfc5646` = 'uz-Cyrl';
UPDATE `LEK_languages` SET `sublanguage` = 'uz-Cyrl-UZ' WHERE `LEK_languages`.`rfc5646` = 'uz-Cyrl-UZ';
UPDATE `LEK_languages` SET `sublanguage` = 'uz-UZ' WHERE `LEK_languages`.`rfc5646` = 'uz-UZ';
UPDATE `LEK_languages` SET `sublanguage` = 'vi-VN' WHERE `LEK_languages`.`rfc5646` = 'vi';
UPDATE `LEK_languages` SET `sublanguage` = 'vi-VN' WHERE `LEK_languages`.`rfc5646` = 'vi-VN';
UPDATE `LEK_languages` SET `sublanguage` = 'xh-ZA' WHERE `LEK_languages`.`rfc5646` = 'xh';
UPDATE `LEK_languages` SET `sublanguage` = 'xh-ZA' WHERE `LEK_languages`.`rfc5646` = 'xh-ZA';
UPDATE `LEK_languages` SET `sublanguage` = 'yo' WHERE `LEK_languages`.`rfc5646` = 'yo';                     /* ??? */
UPDATE `LEK_languages` SET `sublanguage` = 'zh-CN' WHERE `LEK_languages`.`rfc5646` = 'zh';
UPDATE `LEK_languages` SET `sublanguage` = 'zh-CN' WHERE `LEK_languages`.`rfc5646` = 'zh-CN';
UPDATE `LEK_languages` SET `sublanguage` = 'zh-HK' WHERE `LEK_languages`.`rfc5646` = 'zh-HK';
UPDATE `LEK_languages` SET `sublanguage` = 'zh-MO' WHERE `LEK_languages`.`rfc5646` = 'zh-MO';
UPDATE `LEK_languages` SET `sublanguage` = 'zh-SG' WHERE `LEK_languages`.`rfc5646` = 'zh-SG';
UPDATE `LEK_languages` SET `sublanguage` = 'zh-TW' WHERE `LEK_languages`.`rfc5646` = 'zh-TW';
UPDATE `LEK_languages` SET `sublanguage` = 'zu-ZA' WHERE `LEK_languages`.`rfc5646` = 'zu';
UPDATE `LEK_languages` SET `sublanguage` = 'zu-ZA' WHERE `LEK_languages`.`rfc5646` = 'zu-ZA';
