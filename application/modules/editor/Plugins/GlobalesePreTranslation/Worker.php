<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html
  
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU AFFERO GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the root
 folder of translate5.
  
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
			 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

END LICENSE AND COPYRIGHT
*/

class editor_Plugins_GlobalesePreTranslation_Worker extends editor_Models_Import_Worker_Abstract {
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = array()) {
        return empty($parameters);
    } 
    
    /**
     * {@inheritDoc}
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work() {
        $xliffConf = [
            editor_Models_Converter_SegmentsToXliff::CONFIG_INCLUDE_DIFF => false,
            editor_Models_Converter_SegmentsToXliff::CONFIG_PLAIN_INTERNAL_TAGS => false,
            editor_Models_Converter_SegmentsToXliff::CONFIG_ADD_ALTERNATIVES => false,
            editor_Models_Converter_SegmentsToXliff::CONFIG_ADD_COMMENTS => false,
            editor_Models_Converter_SegmentsToXliff::CONFIG_ADD_DISCLAIMER => false,
            editor_Models_Converter_SegmentsToXliff::CONFIG_ADD_PREVIOUS_VERSION => false,
            editor_Models_Converter_SegmentsToXliff::CONFIG_ADD_RELAIS_LANGUAGE => false,
            editor_Models_Converter_SegmentsToXliff::CONFIG_ADD_STATE_QM => false,
        ];
        
        $xliffConverter = ZfExtended_Factory::get('editor_Models_Converter_SegmentsToXliff', [$xliffConf]);
        /* @var $xliffConverter editor_Models_Converter_SegmentsToXliff */

        //returns an segment iterator where the segments are ordered by segmentid, 
        // that means they are ordered by files as well
        $segments = ZfExtended_Factory::get('editor_Models_Segment_Iterator', [$this->task->getTaskGuid()]);
        /* @var $segments editor_Models_Segment_Iterator */

        //get only segments for one file, process them, get the next segments
        $fileId = 0;
        foreach($segments as $segment) {
            if($segment->getFileId() != $fileId) {
                if($fileId > 0) {
                    //file changed, save stored segments as xliff
                    $this->convertAndPreTranslate($xliffConverter, $oneFileSegments);
                }
                
                //new file
                $oneFileSegments = [];
                $fileId = $segment->getFileId();
            }
            
            //store segment data for further processing
            $oneFileSegments[] = (array) $segment->getDataObject();
        }
        
        //save last stored segments
        $this->convertAndPreTranslate($xliffConverter, $oneFileSegments);
        return true;
    }
    
    /**
     * @param editor_Models_Converter_SegmentsToXliff $xliffConverter
     * @param array $oneFileSegments
     */
    protected function convertAndPreTranslate(editor_Models_Converter_SegmentsToXliff $xliffConverter, array $oneFileSegments) {
        $xliff = $xliffConverter->convert($this->task, $oneFileSegments);
        //FIXME Here am I! Proceed here with communication to Globalese of the XLIFF data
        // See also the FIXMEs in editor_Models_Converter_SegmentsToXliff!
    }
}
