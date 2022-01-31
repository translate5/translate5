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

alter table terms_term_status_map
    add tag varchar(64) default 'termNote' not null after id;

alter table terms_term_status_map change termNoteType tagAttributeType varchar(128) not null;
alter table terms_term_status_map change termNoteValue tagValue varchar(64) not null;

alter table terms_term_status_map
    drop key termNoteType;

alter table terms_term_status_map
    add constraint mapTagType unique (tag, tagAttributeType, tagValue);

insert into terms_term_status_map (tag, tagAttributeType, tagValue, mappedStatus)
values ('descrip', 'xBool_Forbidden', 'False', 'supersededTerm'),
       ('descrip', 'xBool_Forbidden', 'True', 'admittedTerm');