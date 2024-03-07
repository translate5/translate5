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

-- prevent cclientpm elevating himself to term-pm
DELETE FROM `Zf_acl_rules` WHERE `Zf_acl_rules`.`module` = 'editor' AND `Zf_acl_rules`.`role` = 'clientpm' AND `Zf_acl_rules`.`resource` = 'setaclrole' AND `Zf_acl_rules`.`right` = 'termPM';

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES
    ('editor', 'pm', 'frontend', 'customerOpenIdAdministration'),
    ('editor', 'clientpm', 'frontend', 'pluginMatchAnalysisCustomerPricingPreset'),
    ('editor', 'clientpm', 'editor_plugins_matchanalysis_pricingpresetrange', 'all'),
    ('editor', 'clientpm', 'editor_plugins_matchanalysis_pricingpresetprices', 'all'),
    ('editor', 'clientpm', 'editor_plugins_matchanalysis_pricingpreset', 'all'),
    ('editor', 'clientpm', 'frontend', 'pluginOkapiBconfCustomerPrefs'),
    ('editor', 'clientpm', 'editor_plugins_okapi_bconfdefaultfilter', 'all'),
    ('editor', 'clientpm', 'editor_plugins_okapi_bconffilter', 'all'),
    ('editor', 'clientpm', 'editor_plugins_okapi_bconf', 'all');
