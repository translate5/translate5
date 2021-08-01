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

/** #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */

/**
 * Fileparsing for import of IBM-XLIFF files
 *
 */
class editor_Models_Import_FileParser_Testcase extends editor_Models_Import_FileParser {

    private $segmentCount = 1;
    /**
     *
     * @var \QueryPath\DOMQuery
     */
    protected $qp;
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::getFileExtensions()
     */
    public static function getFileExtensions() {
        return ['testcase'];
    }
    
    /**
     * Init tagmapping
     */
    public function __construct(string $path, string $fileName, int $fileId, editor_Models_Task $task) {
        parent::__construct($path, $fileName, $fileId, $task);
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_FileParser::parse()
     */
    protected function parse() {
        $this->_skeletonFile = $this->_origFile;
        $this->qp = qp($this->_skeletonFile, ':root',array('format_output'=> false, 'encoding'=>'UTF-8','use_parser'=>'xml'));
        
        $id = $this->segmentCount++;
        //just create a segment attributes object with default values
        $this->createSegmentAttributes($id);
        $this->setMid($id);
        
        $this->extractSegment('fake - not needed, except for declaration');
        $this->_skeletonFile = $this->qp->xml();
    }

    /**
     * sub-method of parse();
     * extract source- and target-segment from a trans-unit element
     * adn saves this segements into databse
     *
     */
    protected function extractSegment($transUnit) {
        $this->segmentData = array();
        $sourceName = $this->segmentFieldManager->getFirstSourceName();
        $targetName = $this->segmentFieldManager->getFirstTargetName();
        
        $source = $this->qp->find('testcase > assertion > input > source');
        $target = $this->qp->find('testcase > assertion > input > target');

        $this->segmentData[$sourceName] = array(
            'original' => $source->innerHTML()
        );

        $this->segmentData[$targetName] = array(
            'original' => $target->innerHTML()
        );
        $segmentId = $this->setAndSaveSegmentValues();
        $tempTargetPlaceholder = $this->getFieldPlaceholder($segmentId, $targetName);
        
        $target->text($tempTargetPlaceholder);
    }
}
