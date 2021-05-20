<?php
/*
 START LICENSE AND COPYRIGHT
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
 
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt
 
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
