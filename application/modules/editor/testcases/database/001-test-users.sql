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

INSERT INTO `Zf_users` (`userGuid` , `firstName` , `surName` , `gender` , `login` , `email` , `roles` , `passwd`, `editable`, `locale`)
VALUES
('{00000000-0000-0000-C100-CCDDEE000001}', 'manager', 'test', 'm', 'testmanager', 'support@translate5.net', 'pm,editor,admin,instantTranslate', '6a204bd89f3c8348afd5c77c717a097a', 0, 'en'),
('{00000000-0000-0000-C100-CCDDEE000002}', 'lector', 'test', 'm', 'testlector', 'support@translate5.net', 'editor', '6a204bd89f3c8348afd5c77c717a097a', 0, 'en'),
('{00000000-0000-0000-C100-CCDDEE000003}', 'translator', 'test', 'm', 'testtranslator', 'support@translate5.net', 'editor', '6a204bd89f3c8348afd5c77c717a097a', 0, 'en'),
('{00000000-0000-0000-C100-CCDDEE000004}', 'api', 'test', 'm', 'testapiuser', 'support@translate5.net', 'pm,editor,admin,api', '6a204bd89f3c8348afd5c77c717a097a', 0, 'en'),
('{00000000-0000-0000-C100-CCDDEE000005}', 'termproposer', 'test', 'm', 'testtermproposer', 'support@translate5.net', 'pm,editor,api,termProposer', '6a204bd89f3c8348afd5c77c717a097a', 0, 'en');

    
