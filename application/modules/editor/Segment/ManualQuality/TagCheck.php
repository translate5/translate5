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
 * 
 * Adds the Segment Quality Entries for the MQM tags that may have been added by the frontend only
 * 
 */
class editor_Segment_ManualQuality_TagCheck extends editor_Segment_Quality_Provider {

    /**
     * Using the internal tag type
     * @var string
     */
    protected static $type = editor_Segment_Tag::TYPE_MANUALQUALITY;
    
    public function processSegment(editor_Models_Task $task, Zend_Config $taskConfig, editor_Segment_Tags $tags, bool $forImport) : editor_Segment_Tags {
        
        if($taskConfig->runtimeOptions->editor->enableQmSubSegments == 1){
        
            foreach($tags->getTagsByType(static::$type) as $mqmTag){
                /* @var $mqmTag editor_Segment_ManualQuality_Tag */
                $qualityId = $tags->saveManualQuality($mqmTag->field, $mqmTag->getTypeIndex(), $mqmTag->getSeverity(), $mqmTag->getComment(), $mqmTag->startIndex, $mqmTag->endIndex);
                // update the sequence-id with the database-id of the bound quality
                $mqmTag->setData('seq', strval($qualityId));
            }
        }
        return $tags;
    }
    
    public function translateType(ZfExtended_Zendoverwrites_Translate $translate) : string {
        return $translate->_('MQM');
    }
}
