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
     * Init tagmapping
     */
    public function __construct(string $path, string $fileName, integer $fileId, boolean $edit100PercentMatches, editor_Models_Languages $sourceLang, editor_Models_Languages $targetLang, editor_Models_Task $task) {
        parent::__construct($path, $fileName, $fileId, $edit100PercentMatches, $sourceLang, $targetLang, $task);

        $this->protectUnicodeSpecialChars();
    }

    /**
     * Global parsing method.
     * Calls sub-methods to do the job.
     */
    protected function parse() {
        $this->_skeletonFile = $this->_origFileUnicodeProtected;
        $this->qp = qp($this->_skeletonFile, ':root',array('format_output'=> false, 'encoding'=>'UTF-8','use_parser'=>'xml'));
        $this->setSegmentAttribs('fake - not needed, except for declaration');
        $this->extractSegment('fake - not needed, except for declaration');
        $this->_skeletonFile = $this->qp->xml();
    }

    /**
     * Sets $this->_matchRateSegment and $this->_autopropagated
     * for the segment currently worked on
     *
     * @param array transunit
     */
    protected function setSegmentAttribs($transunit) {
        $id = $this->segmentCount++;
        $this->_matchRateSegment[$id] = 0;
        $this->_autopropagated[$id] = false;
        $this->setMid($id);
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
            'original' => $source->innerHTML(),
            'originalMd5' => md5($source->innerHTML())
        );

        $this->segmentData[$targetName] = array(
            'original' => $target->innerHTML(),
            'originalMd5' => md5($target->innerHTML())
        );
        $segmentId = $this->setAndSaveSegmentValues();
        $tempTargetPlaceholder = $this->getFieldPlaceholder($segmentId, $targetName);
        
        $target->text($tempTargetPlaceholder);
    }

    /**
     * is obsolete for testcase, since testcase expects internal tags in translate5 internal tag format.
     * If this is consistent should be checked by test and not by import-class
     * 
     * @param string $segment
     * @return string $segment with replaced (ExtJs-compatible) tags
     */
    protected function parseSegment($segment, $isSource) {
        return $segment;
    }

}
