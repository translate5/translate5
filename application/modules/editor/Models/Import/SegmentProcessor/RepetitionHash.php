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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

/**
 */
class editor_Models_Import_SegmentProcessor_RepetitionHash extends editor_Models_Import_SegmentProcessor {
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $sfm;
    
    /**
     * @var editor_Models_Segment_RepetitionHash
     */
    protected $hasher;
    
    public function __construct(editor_Models_Task $task, editor_Models_SegmentFieldManager $sfm) {
        parent::__construct($task);
        $this->sfm = $sfm;
        $this->hasher = ZfExtended_Factory::get('editor_Models_Segment_RepetitionHash',[$task]);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_SegmentProcessor::process()
     */
    public function process(editor_Models_Import_FileParser $parser){
        $allFields = &$parser->getFieldContents();
        if(!$this->sfm->isDefaultLayout()) {
            foreach($allFields as $field => &$data) {
                $data['originalMd5'] = $this->hasher->hashAlternateTarget($data['originalMd5']);
            }
            return false;
        }
        
        if(isset($allFields[editor_Models_SegmentField::TYPE_RELAIS])) {
            $relais = &$allFields[editor_Models_SegmentField::TYPE_RELAIS];
            $relais['originalMd5'] = $this->hasher->hashRelais($relais['originalMd5']);
        }

        $source = &$allFields[editor_Models_SegmentField::TYPE_SOURCE];
        $target = &$allFields[editor_Models_SegmentField::TYPE_TARGET];
        
        //originalMd5 is prefilled with the original string value and is modified by other processors (for example MQM parser).
        //here it will finally converted to the md5 hash
        $target['originalMd5'] = $this->hasher->hashTarget($target['originalMd5'], $source['original']);
        
        // initially also for empty targets a count must be set in the sourceHash
        //  so we just use the source to count the tags there
        // Example:
        //          Source              Target
        // Seg1     <t>                 <t>         will be edited to: <t>Test
        // Seg1Hash <t>#1               <t>
        // Seg2     <t>                 - empty -   the 2. Segments target is empty
        // Seg2Hash <t>                 - empty -   so no tag count or just 0 from target could be added to the sourceHash
        //
        // The result ist Seg1Hash != Seg2Hash although they are the same.
        // The solution is to use the sourceTagCount for empty targets on import!
        if(empty($target['original'])) {
            $contentToGetTags = $source['original'];
        }
        else {
            $contentToGetTags = $target['original'];
        }
        $source['originalMd5'] = $this->hasher->hashSource($source['originalMd5'], $contentToGetTags);
        return false;
    }
}