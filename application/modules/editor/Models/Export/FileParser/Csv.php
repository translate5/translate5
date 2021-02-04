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

    /**
     * @param editor_Models_Task $task
     * @param int $fileId
     * @param string $path
     * @param array $options
     */
    public function __construct(editor_Models_Task $task, int $fileId, string $path, array $options = []) {
        parent::__construct($task, $fileId, $path, $options);
        //TODO move the following configs to the options array
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
        $segment = parent::parseSegment($segment);
        return $segment;
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
        //decoding the htmlspecialchars before exporting to CSV

        //TODO if we ever will export other tags in CSV as MQM or fix the MQM comments so that specialchars are allowed there,
        //then we must change the decoding here, so that only text nodes are decoded, and not the special chars in the tags too!
        $segment = htmlspecialchars_decode($segment, ENT_QUOTES);
        
        return str_replace($this->_enclosure,$this->_enclosure.$this->_enclosure,$segment);
    }
}
