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

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'noRights', 'editor_termportal', 'all');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('editor', 'pm', 'editor_termportal', 'all');


INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES ('runtimeOptions.termportal.defaultlanguages', '1', 'editor', 'system', '[\"de-de\", \"en-gb\"]', '[\"de-de\", \"en-gb\"]', '', 'list', 'Default languages in the termportal term searhc');
INSERT INTO `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `description`) VALUES ('runtimeOptions.termportal.searchTermsCount', '1', 'editor', 'system', '10', '10', '', 'integer', 'The maximum count of the search results in the autocomplete');

INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('default', 'editor', 'initial_page', 'editor');
INSERT INTO `Zf_acl_rules` (`module`, `role`, `resource`, `right`) VALUES ('default', 'termCustomerSearch', 'initial_page', 'termPortal');

UPDATE `Zf_acl_rules` SET `resource`="editor_termcollection" WHERE `role`="termCustomerSearch" AND `resource`="editor_index" AND `right`="all";

