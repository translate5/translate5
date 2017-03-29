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
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3
-- 			 http://www.gnu.org/licenses/agpl.html
-- 
-- END LICENSE AND COPYRIGHT
-- */

UPDATE  `Zf_configuration` SET  `value` = '[{"/":"Welcome"},{"/index/mission":"Mission"},{"/login":"translate5 Demo"},{"/index/usage":"Usage &amp; documentation"},  {"/index/install":"Installation"},{"/index/developer":"Developers"}, {"/index/testimonials":"Testimonials"}, {"/index/newsletter":"Newsletter"}]' WHERE  `Zf_configuration`.`name` ='runtimeOptions.content.mainMenu';
UPDATE  `Zf_configuration` SET  `default` = '[{"/":"Welcome"},{"/index/mission":"Mission"},{"/login":"translate5 Demo"},{"/index/usage":"Usage &amp; documentation"},  {"/index/install":"Installation"},{"/index/developer":"Developers"}, {"/index/testimonials":"Testimonials"}, {"/index/newsletter":"Newsletter"}]' WHERE  `Zf_configuration`.`name` ='runtimeOptions.content.mainMenu';