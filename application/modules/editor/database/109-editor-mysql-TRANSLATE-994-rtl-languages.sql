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


DELIMITER ;;
CREATE PROCEDURE ALTER_LANGUAGES()
BEGIN
    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END;
    ALTER TABLE `LEK_languages` ADD COLUMN `rtl` tinyint(1) DEFAULT 0 COMMENT 'defines if the language is a rtl language';
END;;
DELIMITER ;
CALL ALTER_LANGUAGES();
DROP PROCEDURE ALTER_LANGUAGES;

-- rtl languages are so far:
-- ar      Arabic
-- arc     Aramaic
-- dv      Divehi
-- far     Farsi
-- ha      Hausa
-- he      Hebrew
-- khw     Khowar
-- ks      Kashmiri
-- ku      Kurdish
-- ps      Pashto
-- ur      Urdu
-- yi      Yiddish

UPDATE `LEK_languages` SET rtl = 1 where rfc5646 like 'ar%';
UPDATE `LEK_languages` SET rtl = 1 where rfc5646 like 'arc%';
UPDATE `LEK_languages` SET rtl = 1 where rfc5646 like 'dv%';
UPDATE `LEK_languages` SET rtl = 1 where rfc5646 like 'far%';
UPDATE `LEK_languages` SET rtl = 1 where rfc5646 like 'ha%';
UPDATE `LEK_languages` SET rtl = 1 where rfc5646 like 'he%';
UPDATE `LEK_languages` SET rtl = 1 where rfc5646 like 'khw%';
UPDATE `LEK_languages` SET rtl = 1 where rfc5646 like 'ks%';
UPDATE `LEK_languages` SET rtl = 1 where rfc5646 like 'ku%';
UPDATE `LEK_languages` SET rtl = 1 where rfc5646 like 'ps%';
UPDATE `LEK_languages` SET rtl = 1 where rfc5646 like 'ur%';
UPDATE `LEK_languages` SET rtl = 1 where rfc5646 like 'yi%';
