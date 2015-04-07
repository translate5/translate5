-- /*
-- START LICENSE AND COPYRIGHT
-- 
--  This file is part of translate5
--  
--  Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
-- 
--  Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
-- 
--  This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
--  as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
--  included in the packaging of this file.  Please review the following information 
--  to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
--  http://www.gnu.org/licenses/agpl.html
-- 
--  There is a plugin exception available for use with this release of translate5 for
--  open source applications that are distributed under a license other than AGPL:
--  Please see Open Source License Exception for Development of Plugins for translate5
--  http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
--  folder of translate5.
--   
--  @copyright  Marc Mittag, MittagQI - Quality Informatics
--  @author     MittagQI - Quality Informatics
--  @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
-- 			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt
-- 
-- END LICENSE AND COPYRIGHT
-- */

alter table LEK_segmentmetadata
add constraint LEK_segmentmetadata_segmentId_FK FOREIGN KEY ( segmentId ) references LEK_segments(id) ON DELETE CASCADE;

alter table LEK_segmentterms
add constraint LEK_segmentterms_segmentId_FK FOREIGN KEY ( segmentId ) references LEK_segments(id) ON DELETE CASCADE;

alter table LEK_internaltags
add constraint LEK_internaltags_segmentId_FK FOREIGN KEY ( segmentId ) references LEK_segments(id) ON DELETE CASCADE;

alter table LEK_segment_history
add constraint LEK_segment_history_segmentId_FK FOREIGN KEY ( segmentId ) references LEK_segments(id) ON DELETE CASCADE;

alter table LEK_segments
add constraint LEK_segments_fileId_FK FOREIGN KEY ( fileId ) references LEK_files(id) ON DELETE CASCADE;

alter table LEK_skeletonfiles
add constraint LEK_skeletonfiles_fileId_FK FOREIGN KEY ( fileId ) references LEK_files(id) ON DELETE CASCADE;
