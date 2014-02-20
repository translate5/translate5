<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */
/**
 * DB Row Model for segment data fields
 * provides the available data fields for convenience
 */
class editor_Models_Db_SegmentDataRow extends Zend_Db_Table_Row_Abstract {
    /**
     * @property mixed $id id auto inc id of this segment data row item
     * @property mixed $taskGuid taskGuid of this segment
     * @property mixed $name name of this segment field (mapping to segment field)
     * @property mixed $segmentId segmentId segment id of this data row (mapping to segment)
     * @property mixed $mid mid of the segment
     * @property mixed $original original readonly segment content
     * @property mixed $originalMd5 originalMd5 md5 hash of the original unparsed content
     * @property mixed $originalToSort originalToSort shortened original content for sorting
     * @property mixed $edited edited user edited content
     * @property mixed $editedMd5 editedMd5 md5 hash of the edited content (FIXME with tags and qm tags this is nonsense! No alike would be found because of different ids in the tags)
     * @property mixed $editedToSort editedToSort shortened user edited content for sorting
     */
}