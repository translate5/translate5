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
 * Default Model for Plugin SegmentStatistics
 */
class editor_Plugins_SegmentStatistics_Models_Export_Xml extends editor_Plugins_SegmentStatistics_Models_Export_Abstract {
    const FILE_SUFFIX='.xml';
    
    /**
     * Writes the Statistics in the given Format to the disk
     * Filename without suffix, suffix is appended by this method
     * @param string $filename
     */
    public function writeToDisk(string $filename) {
        $xml = new SimpleXMLElement('<statistics/>');
        $xml->addChild('taskGuid', $this->taskGuid);
        $xml->addChild('taskName', htmlentities($this->statistics->taskName,ENT_XML1));
        $this->addFiltered($xml);
        $xml->addChild('segmentCount', $this->statistics->segmentCount);
        $xml->addChild('segmentCountEditable', $this->statistics->segmentCountEditable);
        
        if($this->type == self::TYPE_IMPORT) {
            $this->addTypeSpecificXml($xml->addChild('import'), $this->statistics, self::TYPE_IMPORT);
        }
        else {
            $this->addTypeSpecificXml($xml->addChild('import'), $this->statistics, self::TYPE_IMPORT);
            $this->addTypeSpecificXml($xml->addChild('export'), $this->statistics, self::TYPE_EXPORT);
        }
        $file = $filename.self::FILE_SUFFIX;
        $xml->asXML($file);
        $this->debugFormatXml($file);
    }
    
    /**
     * Adds the configured filters to the XML
     * @param SimpleXMLElement $xml
     */
    protected function addFiltered(SimpleXMLElement $xml) {
        $filtered = $xml->addChild('filtered');
        if(empty($this->statistics->filtered)){
            return;
        }
        foreach($this->statistics->filtered as $filter) {
            $filtered->addChild('filter', $filter);
        }
    }
    
    /**
     * converts the saved xml to a formatted XML file if debug for SegmentStatistics is enabled
     * @param string $file
     */
    protected function debugFormatXml($file) {
        if(!$this->debug) {
            return;
        }
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = FALSE;
        $dom->load($file);
        $dom->formatOutput = TRUE;
        $dom->save($file);
    }
    
    protected function addTypeSpecificXml(SimpleXMLElement $xml, $statistics, $type) {
        $files = $xml->addChild('files');
        
        $lastFileId = 0;
        $lastField = null;
        
        $filesField = 'files'.ucfirst($type);
        foreach($statistics->$filesField as $fileStat) {
            //implement next file:
            if($lastFileId != $fileStat['fileId']) {
                $file = $files->addChild('file');
                $file->addChild('fileName', htmlentities($fileStat['fileName'],ENT_XML1));
                $file->addChild('fileId', $fileStat['fileId']);
                $fields = $file->addChild('fields');
                $lastFileId = $fileStat['fileId'];
            }

            $field = $fields->addChild('field');
            $this->initField($field, $fileStat);
            
            if($fileStat['fieldName'] == 'source') {
                //<!-- only targets to sources with transNotFounds are counted: --> 
                $field->addChild('targetCharFoundCount', $fileStat['targetCharFoundCount']);
                $field->addChild('targetCharNotFoundCount', $fileStat['targetCharNotFoundCount']);
                $field->addChild('targetSegmentsPerFileFound', $fileStat['targetSegmentsPerFileFound']);
                $field->addChild('targetSegmentsPerFileNotFound', $fileStat['targetSegmentsPerFileNotFound']);
            }
        }
        
        //add the statistics per field for whole task
        $fields = $xml->addChild('fields');
        if(empty($statistics->taskFields[$type])) {
            return;
        }
        foreach($statistics->taskFields[$type] as $fieldName => $fieldStat) {
            $field = $fields->addChild('field');
            $field->addChild('fieldName', $fieldName);
            foreach($fieldStat as $key => $value) {
                $field->addChild($key, $value);
            }
        }
    }
    
    protected function initField(SimpleXMLElement $field, array $fileStat) {
        $fieldName = $fileStat['fieldName'];
        $field->addChild('fieldName', $fieldName);
        $field->addChild('charFoundCount', $fileStat['charFoundCount']);
        $field->addChild('charNotFoundCount', $fileStat['charNotFoundCount']);
        $field->addChild('wordFoundCount', $fileStat['wordFoundCount']);
        $field->addChild('wordNotFoundCount', $fileStat['wordNotFoundCount']);
        $field->addChild('termFoundCount', $fileStat['termFoundCount']);
        $field->addChild('termNotFoundCount', $fileStat['termNotFoundCount']);
        $field->addChild('segmentsPerFile', $fileStat['segmentsPerFile']);
        $field->addChild('segmentsPerFileFound', $fileStat['segmentsPerFileFound']);
        $field->addChild('segmentsPerFileNotFound', $fileStat['segmentsPerFileNotFound']);
        return $field;
    }
}