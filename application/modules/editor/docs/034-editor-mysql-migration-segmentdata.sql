--  /*
--  START LICENSE AND COPYRIGHT
--
--  This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
--
--  Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
--
--  Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com
--
--  This file may be used under the terms of the GNU General Public License version 3.0
--  as published by the Free Software Foundation and appearing in the file gpl3-license.txt
--  included in the packaging of this file.  Please review the following information
--  to ensure the GNU General Public License version 3.0 requirements will be met:
--  http://www.gnu.org/copyleft/gpl.html.
--
--  For this file you are allowed to make use of the same FLOSS exceptions to the GNU
--  General Public License version 3.0 as specified by Sencha for Ext Js.
--  Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue,
--  that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3.
--  For further information regarding this topic please see the attached license.txt
--  of this software package.
--
--  MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
--  brought in accordance with the ExtJs license scheme. You are welcome to support us
--  with legal support, if you are interested in this.
--
--
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
--              with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
--
--  END LICENSE AND COPYRIGHT
--  */

insert into LEK_segment_data
  (taskGuid, name, segmentId, mid, original, originalMd5, originalToSort, edited, editedMd5, editedToSort)
select
  taskGuid, 'source', id, mid, source, sourceMd5, sourceToSort, edited, MD5(edited), editedToSort
from LEK_segments;

insert into LEK_segment_field
  (taskGuid, name, label, rankable, editable)
select
  LEK_segments.taskGuid, 'source', 'source', 0, enableSourceEditing
from LEK_segments, LEK_task
where LEK_task.taskGuid = LEK_segments.taskGuid;


insert into LEK_segment_data
  (taskGuid, name, segmentId, mid, original, originalMd5, originalToSort)
select
  taskGuid, 'target', id, mid, target, targetMd5, targetToSort
from LEK_segments;

insert into LEK_segment_field
  (taskGuid, name, label, rankable, editable)
select
  taskGuid, 'target', 'target', 0, 1
from LEK_segments;


insert into LEK_segment_data
  (taskGuid, name, segmentId, mid, original, originalMd5, originalToSort)
select
  LEK_segments.taskGuid, 'relais', LEK_segments.id, mid, relais, relaisMd5, relaisToSort
from LEK_segments, LEK_task
where LEK_task.relaisLang > 0 and LEK_task.taskGuid = LEK_segments.taskGuid;

insert into LEK_segment_field
  (taskGuid, name, label, rankable, editable)
select
  LEK_segments.taskGuid, 'relais', 'relais', 0, 0
from LEK_segments, LEK_task
where LEK_task.relaisLang > 0 and LEK_task.taskGuid = LEK_segments.taskGuid;