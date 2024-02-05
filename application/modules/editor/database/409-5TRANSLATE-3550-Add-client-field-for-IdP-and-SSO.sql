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
--              http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
--
-- END LICENSE AND COPYRIGHT
-- */


INSERT IGNORE INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`,
                                `typeClass`, `description`, `level`, `guiName`, `guiGroup`, `comment`)
VALUES ('runtimeOptions.customers.openid.claimsFieldName', '1', 'editor', 'system', '', '', '', 'string', NULL,
        'If this field is defined, translate5 will look for this attribute in the verified claims and in the user info. If there is value defined behind this key there, this value will be used to find or create customer in transalte5 and assign this customer to the authenticated user',
        '2', 'OpenID Connect: customer claim name', 'System setup: Authentication', '');

UPDATE IGNORE LEK_customer
SET domain = CONCAT( (
SELECT value FROM Zf_configuration WHERE name = 'runtimeOptions.server.name'
),'/') WHERE number = 'default for legacy data';

DELETE FROM Zf_configuration WHERE name = 'runtimeOptions.customers.openid.showOpenIdDefaultCustomerData';