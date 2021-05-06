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

alter table terms_term drop column descrip;
alter table terms_term drop column descripType;
alter table terms_term drop column descripTarget;
alter table terms_term modify langSetGuid varchar(36) null;
alter table terms_term modify termEntryGuid varchar(36) null;
alter table terms_term modify termEntryId int null;
alter table terms_term modify language varchar(36) null;

alter table terms_transacgrp drop column adminType;
alter table terms_transacgrp drop column adminValue;
alter table terms_transacgrp modify termId int null;
alter table terms_transacgrp add termGuid varchar(36) null after termId;

alter table terms_attributes alter column language set default null;
alter table terms_attributes modify termId int null;
alter table terms_attributes modify termEntryId int null;
alter table terms_attributes modify termEntryGuid varchar(36) null;
alter table terms_attributes modify langSetGuid varchar(36) null;
alter table terms_attributes add termGuid varchar(36) null after termId;
alter table terms_attributes add attrLang varchar(36) null after language;

alter table terms_transacgrp add language varchar(12) null after transacType;
alter table terms_transacgrp add attrLang varchar(12) null after language;
alter table terms_transacgrp modify langSetGuid varchar(36) null;
alter table terms_transacgrp modify termEntryGuid varchar(36) null;
alter table terms_transacgrp modify termEntryId int null;

alter table terms_images drop column xbase;
