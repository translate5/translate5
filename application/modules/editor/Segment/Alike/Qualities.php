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
 * Helper for the segment's qualities processing during an alike segment cloning
 */
final class editor_Segment_Alike_Qualities {
    
    /**    
     * 
     * @var editor_Models_Db_SegmentQuality
     */
    private $table;
    /**
     *
     * @var editor_Models_Db_SegmentQualityRow[]
     */
    private $existing = [];
    /**
     * 
     * @param int $segmentId
     * @param string $taskGuid
     */
    public function __construct(int $segmentId){
        $this->table = ZfExtended_Factory::get('editor_Models_Db_SegmentQuality');
        // QM-qualities will also be processed when cloning qualities for alike-segments. Thats the only case when the Quality Manaager handles them
        foreach($this->table->fetchFiltered(NULL, $segmentId) as $quality){
            /* @var $qualityRow editor_Models_Db_SegmentQualityRow */
            $this->existing[] = $quality;
        }
    }
    /**
     * Clones all qualities of the given type to the qualities object as new rows
     * @param string $type
     * @param editor_Segment_Qualities $qualities
     */
    public function cloneForType(string $type, editor_Segment_Qualities $qualities){
        foreach($this->existing as $row){
            if($row->type == $type){
                $data = $row->toArray();
                unset($data['id']);
                unset($data['segmentId']);
                $row = $this->table->createRow($data);
                $qualities->addNew($row);
            }
        }
    }
}
