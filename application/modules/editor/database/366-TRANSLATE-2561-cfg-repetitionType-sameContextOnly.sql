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

insert into `Zf_configuration` (`name`, `confirmed`, `module`, `category`, `value`, `default`, `defaults`, `type`, `typeClass`, `description`, `level`, `guiName`, `guiGroup`, `comment`) values
('runtimeOptions.alike.repetitionType' ,'1','editor','system','bothOr','bothOr','source,target,bothAnd,bothOr','string',NULL,'Type of repetitions that should be propagated in case of propagation behaviour is \'always\'. Possible values: \'source\', \'target\', \'bothAnd\', \'bothOr\' - they refer to when automatic replacements are made with \'always\'-behaviour. ','32','Autopropagate / Always, if repetition type is','Editor: Miscellaneous options',''),
('runtimeOptions.alike.sameContextOnly','1','editor','system','0','0','','boolean',NULL,'Default behaviour, for \"Same context only\" checkbox in the repetition editor (auto-propgate): Only replace repetitions of a same context, e.g. having same content for their previous and next segments. This is the default behaviour, that can be changed by the user.','32','Autopropagate / Always / Same context only','Editor: Miscellaneous options','');


