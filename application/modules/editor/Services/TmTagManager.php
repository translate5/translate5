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

/**
 * Prepare segment content for saveing into tm.
 *
 */
class editor_Services_TmTagManager {
    
    use editor_Models_Import_FileParser_TagTrait;
    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $internalTag;
    
    /**
     * @var editor_Models_Segment_TrackChangeTag
     */
    protected $trackChange;
    
    /**
     * @var editor_Models_Segment_Whitespace
     */
    protected $whitespaceHelper;
    
    /***
     * 
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected $xmlParser;
    
    protected $map=[];
    
    /**
     */
    public function __construct() {
        $this->internalTag = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->trackChange = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        $this->whitespaceHelper = ZfExtended_Factory::get('editor_Models_Segment_Whitespace');
        $this->xmlParser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
    }
    
    /**
     * 
     */
    public function prepareForTm(string $queryString) {
        $qs = $this->trackChange->removeTrackChanges($queryString);
        //restore the whitespaces to real characters
        $qs = $this->internalTag->restore($qs, true);
        $qs =$this->whitespaceHelper->unprotectWhitespace($qs);
        
        //$map is set by reference
        $this->map=[];
        $queryString = $this->internalTag->toXliffPaired($queryString, true, $this->map);
        $this->mapCount = count($this->map);
        
        $this->shortTagIdent = $this->mapCount + 1;
        $xmlParser=$this->xmlParser;
        $this->xmlParser->registerOther(function($textNode, $key) use ($xmlParser){
            //we assume that the segment content is XML/XLIFF therefore we assume xmlBased here
            $textNode = $this->whitespaceHelper->protectWhitespace($textNode, true);
            $textNode = $this->whitespaceTagReplacer($textNode);
            $xmlParser->replaceChunk($key, $textNode);
        });
        return $queryString;
    }
    
    public function convertFromTm(string $resultString){
        //since protectWhitespace should run on plain text nodes we have to call it before the internal tags are reapplied,
        // since then the text contains xliff tags and the xliff tags should not contain affected whitespace
        $target = $this->xmlParser->parse($resultString);
        return $this->internalTag->reapply2dMap($target, $this->map);
    }
}
