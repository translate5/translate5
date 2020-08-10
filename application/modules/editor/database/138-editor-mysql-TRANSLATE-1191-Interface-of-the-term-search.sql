/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

ALTER TABLE `LEK_languages` ADD `ISO_3166-1_alpha-2` CHAR(2) NULL DEFAULT NULL AFTER `rfc5646`;

UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'de' WHERE `LEK_languages`.`rfc5646` = 'de';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'gb' WHERE `LEK_languages`.`rfc5646` = 'en';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'es' WHERE `LEK_languages`.`rfc5646` = 'es';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'gb' WHERE `LEK_languages`.`rfc5646` = 'en-GB';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'us' WHERE `LEK_languages`.`rfc5646` = 'en-US';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'fr' WHERE `LEK_languages`.`rfc5646` = 'fr';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'it' WHERE `LEK_languages`.`rfc5646` = 'it';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'bg' WHERE `LEK_languages`.`rfc5646` = 'bg';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'dk' WHERE `LEK_languages`.`rfc5646` = 'da';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ee' WHERE `LEK_languages`.`rfc5646` = 'ee';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'fi' WHERE `LEK_languages`.`rfc5646` = 'fi';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'gr' WHERE `LEK_languages`.`rfc5646` = 'el';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'hr' WHERE `LEK_languages`.`rfc5646` = 'hr';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'nl' WHERE `LEK_languages`.`rfc5646` = 'nl';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'no' WHERE `LEK_languages`.`rfc5646` = 'nb';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'pl' WHERE `LEK_languages`.`rfc5646` = 'pl';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'pt' WHERE `LEK_languages`.`rfc5646` = 'pt';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'br' WHERE `LEK_languages`.`rfc5646` = 'pt-BR';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ro' WHERE `LEK_languages`.`rfc5646` = 'ro';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ru' WHERE `LEK_languages`.`rfc5646` = 'ru';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'se' WHERE `LEK_languages`.`rfc5646` = 'sv';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'sk' WHERE `LEK_languages`.`rfc5646` = 'sk';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'si' WHERE `LEK_languages`.`rfc5646` = 'sl';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'tw' WHERE `LEK_languages`.`rfc5646` = 'zh-TW';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'cn' WHERE `LEK_languages`.`rfc5646` = 'zh-CN';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'za' WHERE `LEK_languages`.`rfc5646` = 'af';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'al' WHERE `LEK_languages`.`rfc5646` = 'sq';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'am' WHERE `LEK_languages`.`rfc5646` = 'hy';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'az' WHERE `LEK_languages`.`rfc5646` = 'az';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'bn';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ba' WHERE `LEK_languages`.`rfc5646` = 'bs';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'be' WHERE `LEK_languages`.`rfc5646` = 'nl-BE';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'be' WHERE `LEK_languages`.`rfc5646` = 'fr-BE';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ie' WHERE `LEK_languages`.`rfc5646` = 'ga-IE';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ge' WHERE `LEK_languages`.`rfc5646` = 'ka';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'gu';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'hi';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ng' WHERE `LEK_languages`.`rfc5646` = 'ig';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'id' WHERE `LEK_languages`.`rfc5646` = 'id';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'is' WHERE `LEK_languages`.`rfc5646` = 'is';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'jp' WHERE `LEK_languages`.`rfc5646` = 'ja';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'kh' WHERE `LEK_languages`.`rfc5646` = 'km';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'kn';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'kz' WHERE `LEK_languages`.`rfc5646` = 'kk';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'es' WHERE `LEK_languages`.`rfc5646` = 'ca';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'kg' WHERE `LEK_languages`.`rfc5646` = 'ky';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'kr' WHERE `LEK_languages`.`rfc5646` = 'ko';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'lv' WHERE `LEK_languages`.`rfc5646` = 'lv';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'lt' WHERE `LEK_languages`.`rfc5646` = 'lt';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'ml';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'my' WHERE `LEK_languages`.`rfc5646` = 'ms';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'mt' WHERE `LEK_languages`.`rfc5646` = 'mt';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'mr';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'mk' WHERE `LEK_languages`.`rfc5646` = 'mk';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'md' WHERE `LEK_languages`.`rfc5646` = 'mo';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'pa';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'af' WHERE `LEK_languages`.`rfc5646` = 'ps';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ir' WHERE `LEK_languages`.`rfc5646` = 'fa';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'rs' WHERE `LEK_languages`.`rfc5646` = 'sr';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ls' WHERE `LEK_languages`.`rfc5646` = 'st';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'so' WHERE `LEK_languages`.`rfc5646` = 'so';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'co' WHERE `LEK_languages`.`rfc5646` = 'es-CO';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'mx' WHERE `LEK_languages`.`rfc5646` = 'es-MX';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'tj' WHERE `LEK_languages`.`rfc5646` = 'tg';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ph' WHERE `LEK_languages`.`rfc5646` = 'tl';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'lk' WHERE `LEK_languages`.`rfc5646` = 'ta';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'te';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'th' WHERE `LEK_languages`.`rfc5646` = 'th';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ti' WHERE `LEK_languages`.`rfc5646` = 'bo';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'cz' WHERE `LEK_languages`.`rfc5646` = 'cs';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'za' WHERE `LEK_languages`.`rfc5646` = 'tn';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'tr' WHERE `LEK_languages`.`rfc5646` = 'tr';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'tm' WHERE `LEK_languages`.`rfc5646` = 'tk';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ua' WHERE `LEK_languages`.`rfc5646` = 'uk';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'hu' WHERE `LEK_languages`.`rfc5646` = 'hu';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'uz' WHERE `LEK_languages`.`rfc5646` = 'uz';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'vn' WHERE `LEK_languages`.`rfc5646` = 'vi';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'by' WHERE `LEK_languages`.`rfc5646` = 'be';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'za' WHERE `LEK_languages`.`rfc5646` = 'xh';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'za' WHERE `LEK_languages`.`rfc5646` = 'zu';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'at' WHERE `LEK_languages`.`rfc5646` = 'de-AT';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'me' WHERE `LEK_languages`.`rfc5646` = 'sr-Latn-ME';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'sa' WHERE `LEK_languages`.`rfc5646` = 'ar';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'no' WHERE `LEK_languages`.`rfc5646` = 'nn';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'rs' WHERE `LEK_languages`.`rfc5646` = 'sr-Cyrl';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'uz' WHERE `LEK_languages`.`rfc5646` = 'uz-Cyrl';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'za' WHERE `LEK_languages`.`rfc5646` = 'af-ZA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ae' WHERE `LEK_languages`.`rfc5646` = 'ar-AE';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'bh' WHERE `LEK_languages`.`rfc5646` = 'ar-BH';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'dz' WHERE `LEK_languages`.`rfc5646` = 'ar-DZ';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'eg' WHERE `LEK_languages`.`rfc5646` = 'ar-EG';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'iq' WHERE `LEK_languages`.`rfc5646` = 'ar-IQ';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'jo' WHERE `LEK_languages`.`rfc5646` = 'ar-JO';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'kw' WHERE `LEK_languages`.`rfc5646` = 'ar-KW';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'lb' WHERE `LEK_languages`.`rfc5646` = 'ar-LB';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ly' WHERE `LEK_languages`.`rfc5646` = 'ar-LY';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ma' WHERE `LEK_languages`.`rfc5646` = 'ar-MA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'om' WHERE `LEK_languages`.`rfc5646` = 'ar-OM';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'qa' WHERE `LEK_languages`.`rfc5646` = 'ar-QA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'sa' WHERE `LEK_languages`.`rfc5646` = 'ar-SA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'sy' WHERE `LEK_languages`.`rfc5646` = 'ar-SY';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'tn' WHERE `LEK_languages`.`rfc5646` = 'ar-TN';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ye' WHERE `LEK_languages`.`rfc5646` = 'ar-YE';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'az' WHERE `LEK_languages`.`rfc5646` = 'Az-Cyrl';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'az' WHERE `LEK_languages`.`rfc5646` = 'az-AZ';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'az' WHERE `LEK_languages`.`rfc5646` = 'az-Cyrl-AZ';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'by' WHERE `LEK_languages`.`rfc5646` = 'be-BY';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'bg' WHERE `LEK_languages`.`rfc5646` = 'bg-BG';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ba' WHERE `LEK_languages`.`rfc5646` = 'bs-BA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'es' WHERE `LEK_languages`.`rfc5646` = 'ca-ES';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'cz' WHERE `LEK_languages`.`rfc5646` = 'cs-CZ';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'gb' WHERE `LEK_languages`.`rfc5646` = 'cy';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'xs' WHERE `LEK_languages`.`rfc5646` = 'cy-GB';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'dk' WHERE `LEK_languages`.`rfc5646` = 'da-DK';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ch' WHERE `LEK_languages`.`rfc5646` = 'de-CH';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'de' WHERE `LEK_languages`.`rfc5646` = 'de-DE';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'li' WHERE `LEK_languages`.`rfc5646` = 'de-LI';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'lu' WHERE `LEK_languages`.`rfc5646` = 'de-LU';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'dv';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'mv' WHERE `LEK_languages`.`rfc5646` = 'dv-MV';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'gr' WHERE `LEK_languages`.`rfc5646` = 'el-GR';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'au' WHERE `LEK_languages`.`rfc5646` = 'en-AU';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'bz' WHERE `LEK_languages`.`rfc5646` = 'en-BZ';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ca' WHERE `LEK_languages`.`rfc5646` = 'en-CA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ie' WHERE `LEK_languages`.`rfc5646` = 'en-IE';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'jm' WHERE `LEK_languages`.`rfc5646` = 'en-JM';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'nz' WHERE `LEK_languages`.`rfc5646` = 'en-NZ';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ph' WHERE `LEK_languages`.`rfc5646` = 'en-PH';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'tt' WHERE `LEK_languages`.`rfc5646` = 'en-TT';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'za' WHERE `LEK_languages`.`rfc5646` = 'en-ZA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'zw' WHERE `LEK_languages`.`rfc5646` = 'en-ZW';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ar' WHERE `LEK_languages`.`rfc5646` = 'es-AR';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'bo' WHERE `LEK_languages`.`rfc5646` = 'es-BO';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'cl' WHERE `LEK_languages`.`rfc5646` = 'es-CL';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'cr' WHERE `LEK_languages`.`rfc5646` = 'es-CR';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'do' WHERE `LEK_languages`.`rfc5646` = 'es-DO';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ec' WHERE `LEK_languages`.`rfc5646` = 'es-EC';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'es' WHERE `LEK_languages`.`rfc5646` = 'es-ES';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'gt' WHERE `LEK_languages`.`rfc5646` = 'es-GT';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'hn' WHERE `LEK_languages`.`rfc5646` = 'es-HN';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ni' WHERE `LEK_languages`.`rfc5646` = 'es-NI';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'pa' WHERE `LEK_languages`.`rfc5646` = 'es-PA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'pe' WHERE `LEK_languages`.`rfc5646` = 'es-PE';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'pr' WHERE `LEK_languages`.`rfc5646` = 'es-PR';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'py' WHERE `LEK_languages`.`rfc5646` = 'es-PY';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'sv' WHERE `LEK_languages`.`rfc5646` = 'es-SV';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'uy' WHERE `LEK_languages`.`rfc5646` = 'es-UY';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 've' WHERE `LEK_languages`.`rfc5646` = 'es-VE';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ee' WHERE `LEK_languages`.`rfc5646` = 'et-EE';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'es' WHERE `LEK_languages`.`rfc5646` = 'eu';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'es' WHERE `LEK_languages`.`rfc5646` = 'eu-ES';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ir' WHERE `LEK_languages`.`rfc5646` = 'fa-IR';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'fi' WHERE `LEK_languages`.`rfc5646` = 'fi-FI';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'fo' WHERE `LEK_languages`.`rfc5646` = 'fo';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'fo' WHERE `LEK_languages`.`rfc5646` = 'fo-FO';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ca' WHERE `LEK_languages`.`rfc5646` = 'fr-CA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ch' WHERE `LEK_languages`.`rfc5646` = 'fr-CH';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'fr' WHERE `LEK_languages`.`rfc5646` = 'fr-FR';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'lu' WHERE `LEK_languages`.`rfc5646` = 'fr-LU';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'mc' WHERE `LEK_languages`.`rfc5646` = 'fr-MC';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'es' WHERE `LEK_languages`.`rfc5646` = 'gl';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'es' WHERE `LEK_languages`.`rfc5646` = 'gl-ES';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'gu-IN';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'il' WHERE `LEK_languages`.`rfc5646` = 'he';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'il' WHERE `LEK_languages`.`rfc5646` = 'he-IL';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'hi-IN';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ba' WHERE `LEK_languages`.`rfc5646` = 'hr-BA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'hr' WHERE `LEK_languages`.`rfc5646` = 'hr-HR';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'hu' WHERE `LEK_languages`.`rfc5646` = 'hu-HU';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'am' WHERE `LEK_languages`.`rfc5646` = 'hy-AM';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'id' WHERE `LEK_languages`.`rfc5646` = 'id-ID';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'is' WHERE `LEK_languages`.`rfc5646` = 'is-IS';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ch' WHERE `LEK_languages`.`rfc5646` = 'it-CH';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'it' WHERE `LEK_languages`.`rfc5646` = 'it-IT';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'jp' WHERE `LEK_languages`.`rfc5646` = 'ja-JP';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ge' WHERE `LEK_languages`.`rfc5646` = 'ka-GE';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'kz' WHERE `LEK_languages`.`rfc5646` = 'kk-KZ';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'kn-IN';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'kr' WHERE `LEK_languages`.`rfc5646` = 'ko-KR';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'kok';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'kok-IN';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'kg' WHERE `LEK_languages`.`rfc5646` = 'ky-KG';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'lt' WHERE `LEK_languages`.`rfc5646` = 'lt-LT';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'lv' WHERE `LEK_languages`.`rfc5646` = 'lv-LV';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'nz' WHERE `LEK_languages`.`rfc5646` = 'mi';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'nz' WHERE `LEK_languages`.`rfc5646` = 'mi-NZ';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'mk' WHERE `LEK_languages`.`rfc5646` = 'mk-MK';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'mn' WHERE `LEK_languages`.`rfc5646` = 'mn';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'mn' WHERE `LEK_languages`.`rfc5646` = 'mn-MN';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'mr-IN';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'bn' WHERE `LEK_languages`.`rfc5646` = 'ms-BN';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'my' WHERE `LEK_languages`.`rfc5646` = 'ms-MY';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'mt' WHERE `LEK_languages`.`rfc5646` = 'mt-MT';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'no' WHERE `LEK_languages`.`rfc5646` = 'nb-NO';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'nl' WHERE `LEK_languages`.`rfc5646` = 'nl-NL';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'no' WHERE `LEK_languages`.`rfc5646` = 'nn-NO';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'za' WHERE `LEK_languages`.`rfc5646` = 'ns';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'za' WHERE `LEK_languages`.`rfc5646` = 'ns-ZA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'pa-IN';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'pl' WHERE `LEK_languages`.`rfc5646` = 'pl-PL';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'af' WHERE `LEK_languages`.`rfc5646` = 'ps-AR';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'pt' WHERE `LEK_languages`.`rfc5646` = 'pt-PT';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'pe' WHERE `LEK_languages`.`rfc5646` = 'qu';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'bo' WHERE `LEK_languages`.`rfc5646` = 'qu-BO';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ec' WHERE `LEK_languages`.`rfc5646` = 'qu-EC';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'pe' WHERE `LEK_languages`.`rfc5646` = 'qu-PE';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ro' WHERE `LEK_languages`.`rfc5646` = 'ro-RO';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ru' WHERE `LEK_languages`.`rfc5646` = 'ru-RU';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'sa';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'sa-IN';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'fi' WHERE `LEK_languages`.`rfc5646` = 'se-FI';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'no' WHERE `LEK_languages`.`rfc5646` = 'se-NO';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'se' WHERE `LEK_languages`.`rfc5646` = 'se-SE';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'sk' WHERE `LEK_languages`.`rfc5646` = 'sk-SK';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'si' WHERE `LEK_languages`.`rfc5646` = 'sl-SI';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'al' WHERE `LEK_languages`.`rfc5646` = 'sq-AL';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'rs' WHERE `LEK_languages`.`rfc5646` = 'sr-BA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ba' WHERE `LEK_languages`.`rfc5646` = 'sr-Cyrl-BA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'rs' WHERE `LEK_languages`.`rfc5646` = 'sr-SP';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'rs' WHERE `LEK_languages`.`rfc5646` = 'sr-Cyrl-SP';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'fi' WHERE `LEK_languages`.`rfc5646` = 'sv-FI';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'se' WHERE `LEK_languages`.`rfc5646` = 'sv-SE';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ke' WHERE `LEK_languages`.`rfc5646` = 'sw-KE';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'sy' WHERE `LEK_languages`.`rfc5646` = 'syr';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'sy' WHERE `LEK_languages`.`rfc5646` = 'syr-SY';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'ta-IN';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'in' WHERE `LEK_languages`.`rfc5646` = 'te-IN';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'th' WHERE `LEK_languages`.`rfc5646` = 'th-TH';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ph' WHERE `LEK_languages`.`rfc5646` = 'tl-PH';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'za' WHERE `LEK_languages`.`rfc5646` = 'tn-ZA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'tr' WHERE `LEK_languages`.`rfc5646` = 'tr-TR';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ru' WHERE `LEK_languages`.`rfc5646` = 'tt';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ru' WHERE `LEK_languages`.`rfc5646` = 'tt-RU';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ua' WHERE `LEK_languages`.`rfc5646` = 'uk-UA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'pk' WHERE `LEK_languages`.`rfc5646` = 'ur';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'pk' WHERE `LEK_languages`.`rfc5646` = 'ur-PK';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'uz' WHERE `LEK_languages`.`rfc5646` = 'uz-UZ';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'uz' WHERE `LEK_languages`.`rfc5646` = 'uz-Cyrl-UZ';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'vn' WHERE `LEK_languages`.`rfc5646` = 'vi-VN';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'za' WHERE `LEK_languages`.`rfc5646` = 'xh-ZA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'cn' WHERE `LEK_languages`.`rfc5646` = 'zh';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'hk' WHERE `LEK_languages`.`rfc5646` = 'zh-HK';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'mo' WHERE `LEK_languages`.`rfc5646` = 'zh-MO';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'sg' WHERE `LEK_languages`.`rfc5646` = 'zh-SG';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'za' WHERE `LEK_languages`.`rfc5646` = 'zu-ZA';
UPDATE `LEK_languages` SET `ISO_3166-1_alpha-2` = 'ku' WHERE `LEK_languages`.`rfc5646` = 'ku';
