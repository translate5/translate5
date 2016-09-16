<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2015 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 open source applications that are distributed under a license other than AGPL:
 Please see Open Source License Exception for Development of Plugins for translate5
 http://www.translate5.net/plugin-exception.txt or as plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execptions
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 */
class editor_Models_Import_SegmentProcessor_RepetitionHash extends editor_Models_Import_SegmentProcessor {
    /**
     * @var boolean
     */
    protected $hasAlternates;
    
    /**
     * @var boolean
     */
    protected $isSourceEditing;
    
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $tagHelper;
    
    public function __construct(editor_Models_Task $task, editor_Models_SegmentFieldManager $sfm) {
        parent::__construct($task);
        $this->hasAlternates = !$sfm->isDefaultLayout();
        $this->isSourceEditing = (bool) $task->getEnableSourceEditing();
        $this->tagHelper = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_SegmentProcessor::process()
     */
    public function process(editor_Models_Import_FileParser $parser){
        $allFields = &$parser->getFieldContents();
        if($this->hasAlternates) {
            foreach($allFields as $field => &$data) {
                $this->hashField($data);
            }
            return false;
        }
        
        if(isset($allFields[editor_Models_SegmentField::TYPE_RELAIS])) {
            $this->hashField($allFields[editor_Models_SegmentField::TYPE_RELAIS]);
        }

        $source = &$allFields[editor_Models_SegmentField::TYPE_SOURCE];
        $target = &$allFields[editor_Models_SegmentField::TYPE_TARGET];
        
        $sourceTagCount = $this->getCountSourceEdting($source);
        $targetTagCount = $this->hashField($target, $sourceTagCount);
        $this->hashField($source, $targetTagCount);
        return false;
    }
    
    /**
     * makes the repetition hash out of the given segment data. 
     * Second optional value is appended to the string before hash generation
     * returns the count of replaced tags
     * @param array $data
     * @param string $additionalValue
     * @return unknown
     */
    protected function hashField(array & $data, $additionalValue = '') {
        //originalMd5 is prefilled with the original string value and is modified by the other processors.
        //here it will finally converted to the md5 hash
        $original = $data['originalMd5'];
        if(!empty($additionalValue)) {
            $original .= '#'.$additionalValue;
        }
        $original = $this->tagHelper->replace($original, '<internal-tag>', -1, $count);
        $data['originalMd5'] = md5($original);
        return $count;
    }
    
    /**
     * returns the tag count in the given segment data
     * returns an empty string when source editing is false
     * @return mixed
     */
    protected function getCountSourceEdting(array $data) {
        if(!$this->isSourceEditing) {
            return '';
        }
        return $this->tagHelper->count($data['original']);
    }
}