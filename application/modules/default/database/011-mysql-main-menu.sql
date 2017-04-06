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
--  translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
--  Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
--  folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

UPDATE  `Zf_configuration` SET  
`name` =  'runtimeOptions.content.mainMenu',
`value` = '[{"/":"Startseite"},{"/login":"translate5 Demo"},{"/index/usage":"User infos"},  {"/index/source":"Developers"}, {"/index/newsletter":"Newsletter"}, {"/index/testimonials":"Testimonials"}, {"/index/download":"Download"}]',
`default` = '[{"/":"Startseite"},{"/login":"translate5 Demo"},{"/index/usage":"User infos"},  {"/index/source":"Developers"}, {"/index/newsletter":"Newsletter"}, {"/index/testimonials":"Testimonials"}, {"/index/download":"Download"}]',
`description` = 'list of menu-entries for the logged out status of translate5. Other view scripts will lead to 404 in logged out status, even if they exist' where name = 'runtimeOptions.content.viewTemplatesAllowed';