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

ALTER TABLE `LEK_languages` 
ADD COLUMN `iso6393` VARCHAR(30) NULL AFTER `rtl`;

UPDATE `LEK_languages` SET `iso6393`='ger' WHERE `rfc5646`='de';
UPDATE `LEK_languages` SET `iso6393`='eng' WHERE `rfc5646`='en';
UPDATE `LEK_languages` SET `iso6393`='spa' WHERE `rfc5646`='es';
UPDATE `LEK_languages` SET `iso6393`='eng' WHERE `rfc5646`='en-GB';
UPDATE `LEK_languages` SET `iso6393`='eng' WHERE `rfc5646`='en-US';
UPDATE `LEK_languages` SET `iso6393`='fra' WHERE `rfc5646`='fr';
UPDATE `LEK_languages` SET `iso6393`='ita' WHERE `rfc5646`='it';
UPDATE `LEK_languages` SET `iso6393`='bul' WHERE `rfc5646`='bg';
UPDATE `LEK_languages` SET `iso6393`='dan' WHERE `rfc5646`='da';
UPDATE `LEK_languages` SET `iso6393`='est' WHERE `rfc5646`='ee';
UPDATE `LEK_languages` SET `iso6393`='fin' WHERE `rfc5646`='fi';
UPDATE `LEK_languages` SET `iso6393`='gre' WHERE `rfc5646`='el';
UPDATE `LEK_languages` SET `iso6393`='hrv' WHERE `rfc5646`='hr';
UPDATE `LEK_languages` SET `iso6393`='nld' WHERE `rfc5646`='nl';

