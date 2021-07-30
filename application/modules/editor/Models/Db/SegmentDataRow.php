<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or 
 plugin-exception.txt in the root folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

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
     * @property mixed $editedToSort editedToSort shortened user edited content for sorting
     */
}