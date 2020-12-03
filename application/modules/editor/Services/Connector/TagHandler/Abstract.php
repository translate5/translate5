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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Abstract Tag Handler for internal tags in text to be send to language resources
 */
abstract class editor_Services_Connector_TagHandler_Abstract {
    
    //FIXME original comment: this is just a temporary solution until TagTrait is refactored into smaller reusable classes, see TRANSLATE-1509
    use editor_Models_Import_FileParser_TagTrait;
    
    /**
     * This parser is used to restore whitespace tags
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected $xmlparser;
    
    /**
     * Flag if last restore call produced errors
     * @var boolean
     */
    protected $hasRestoreErrors = false;
    
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTag;
    
    /**
     * @var editor_Models_Segment_TrackChangeTag
     */
    protected $trackChange;
    
    /**
     * Counter how many real internal tags (excluding whitespace) the prepared query did contain
     * @var integer
     */
    protected $realTagCount = 0;
    
    /**
     * @var ZfExtended_Logger_Queued
     */
    public $logger;
    
    public function __construct() {
        $this->xmlparser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->trackChange = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        
        $this->initHelper();
        $this->initImageTags();
        
        $this->logger = ZfExtended_Factory::get('ZfExtended_Logger_Queued');
        
        //we have to use the XML parser to restore whitespace, otherwise protectWhitespace would destroy the tags
        $this->xmlparser->registerOther(function($textNode, $key){
            //for communication with OpenTM2 we assume that the segment content is XML/XLIFF therefore we assume xmlBased here
            $textNode = $this->whitespaceHelper->protectWhitespace($textNode, true);
            $textNode = $this->whitespaceTagReplacer($textNode);
            $this->xmlparser->replaceChunk($key, $textNode);
        });
    }
    
    /**
     * protects the internal tags for language resource processing as defined in the class
     * @param string $queryString
     * @return string
     */
    abstract public function prepareQuery(string $queryString): string;
    
    /**
     * protects the internal tags for language resource processing as defined in the class
     * @param string $queryString
     * @return string|NULL NULL on error
     */
    abstract public function restoreInResult(string $resultString): ?string;
    
    /**
     * Returns true if last restoreInResult call had errors
     * @return bool
     */
    public function hasRestoreErrors(): bool {
        return $this->hasRestoreErrors;
    }
    
    /**
     * @param string $text
     * @return string
     */
    protected function importWhitespaceFromTagLessQuery(string $text): string {
        $text = $this->whitespaceHelper->protectWhitespace($text, false);
        return $this->whitespaceTagReplacer($text);
    }

    protected function restoreWhitespaceForQuery (string $queryString): string {
        $qs = $this->trackChange->removeTrackChanges($queryString);
        //restore the whitespaces to real characters
        $qs = $this->internalTag->restore($qs, true);
        return $this->whitespaceHelper->unprotectWhitespace($qs);
    }
    
    /**
     * returns how many real internal tags (excluding whitespace tags) were contained by the prepared query
     * @return number
     */
    public function getRealTagCount() {
        return $this->realTagCount;
    }
    
    /**
     * returns the stored map of the internal tags
     * @return array
     */
    public function getTagMap(): array {
        return $this->map;
    }
    
    /**
     * set the stored map of the internal tags
     * @param array $map
     */
    public function setTagMap(array $map) {
        $this->map = $map;
    }
}
