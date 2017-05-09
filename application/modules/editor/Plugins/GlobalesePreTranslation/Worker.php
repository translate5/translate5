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
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager;
    
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
        
        $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        $this->segmentFieldManager->initFields($this->taskGuid);
        
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
                    $this->convertAndPreTranslate($xliffConverter, $fileId, $oneFileSegments);
                }
                
                //new file
                $oneFileSegments = [];
                $fileId = (int) $segment->getFileId();
            }
            
            //store segment data for further processing
            $oneFileSegments[] = (array) $segment->getDataObject();
        }
        if(!empty($oneFileSegments)) {
            //save last stored segments
            $this->convertAndPreTranslate($xliffConverter, $fileId, $oneFileSegments);
        }
        return true;
    }
    
    /**
     * @param editor_Models_Converter_SegmentsToXliff $xliffConverter
     * @param integer $fileId
     * @param array $oneFileSegments
     */
    protected function convertAndPreTranslate(editor_Models_Converter_SegmentsToXliff $xliffConverter, integer $fileId, array $oneFileSegments) {
        $xliff = $xliffConverter->convert($this->task, $oneFileSegments);
        //FIXME Here am I! Proceed here with communication to Globalese of the XLIFF data
        // See also the FIXMEs in editor_Models_Converter_SegmentsToXliff!
        
        
        //FIXME Aleks communicate to globalese
        
        //We assume the xliff is pretranslated right now:
        $path = $this->storeXlf($fileId, $xliff);
        // $this->importPretranslated($fileId, $path);
    }
    
    /**
     * Stores the generated xliff on the disk to import it
     * @param integer $fileId
     * @param string $xliff
     */
    protected function storeXlf(integer $fileId, string $xliff) {
        $path = $this->task->getAbsoluteTaskDataPath();
        $path .= '/GlobalesePreTranslation/';
        if(!is_dir($path) && !@mkdir($path)) {
            throw new ZfExtended_Exception("Could not create directory ".$path);
        }
        $path .= 'file-'.$fileId.'.xlf';
        file_put_contents($path, $xliff);
        return $path;
    }
    
    /**
     * 
     * @param unknown $path
     */
    protected function importPretranslated(integer $fileId, string $path) {
        //define FileParser Constructor Parameters:
        $params = [
            $path,
            basename($path),
            $fileId, 
            $this->task,
        ];
        
        //start a hardcoded XLF FileParser, since this is the only Format we expect
        $parser = ZfExtended_Factory::get('editor_Models_Import_FileParser_Xlf',$params);
        /* var $parser editor_Models_Import_FileParser_Xlf */
        $parser->setSegmentFieldManager($this->segmentFieldManager);
        
        //add the custom Segment Processor to Update the segments
        $processor = ZfExtended_Factory::get('editor_Plugins_GlobalesePreTranslation_SegmentUpdateProcessor',[$this->task]);
        /* @var $processor editor_Plugins_GlobalesePreTranslation_SegmentUpdateProcessor */
        $parser->addSegmentProcessor($processor);
        
        $parser->parseFile();
    }
}
