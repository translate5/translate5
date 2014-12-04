<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
 END LICENSE AND COPYRIGHT 
 */

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *

  /**
 * Parses files parsed with mit editor_Models_Import_FileParser_Csv for the export
 */

class editor_Models_Export_FileParser_Csv extends editor_Models_Export_FileParser {
    /**
     * @var string classname of difftagger
     */

    protected $_classNameDifftagger = 'editor_Models_Export_DiffTagger_Csv';
    /**
     * @var string 
     */
    protected $_delimiter;
    /**
     * @var string 
     */
    protected $_enclosure;

    public function __construct(integer $fileId, boolean $diff,editor_Models_Task $task,string $path) {
        parent::__construct($fileId, $diff,$task,$path);
        $this->_delimiter = $this->config->runtimeOptions->import->csv->delimiter;
        $this->_enclosure = $this->config->runtimeOptions->import->csv->enclosure;
    }
    /**
     * reconstructs segment to the original source format
     * - nothing todo here for csv so far, cause tags are not supported so far
     *
     * @param string $segment
     * @return string $segment 
     */

    protected function parseSegment($segment){
        $segment = $this->convertQmTags2XliffFormat($segment);
        return htmlspecialchars_decode(parent::parseSegment($segment));
    }
    
    /**
     * unescape the CSV enclosures
     * (non-PHPdoc)
     * @see editor_Models_Export_FileParser::preProcessReplacement()
     */
    protected function preProcessReplacement($attributes) {
        return str_replace($this->_enclosure.$this->_enclosure,$this->_enclosure,$attributes);
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Export_FileParser::getSegmentContent()
     */
    protected function getSegmentContent($segmentId, $field) {
        $segment = parent::getSegmentContent($segmentId, $field);
        return str_replace($this->_enclosure,$this->_enclosure.$this->_enclosure,$segment);
    }
}
