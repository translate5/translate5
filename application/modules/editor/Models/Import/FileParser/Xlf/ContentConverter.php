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

/**
 * Converts XLF segment content chunks into translate5 internal segment content string
 * TODO Missing <mrk type="seg"> support! 
 */
class editor_Models_Import_FileParser_Xlf_ContentConverter {
    use editor_Models_Import_FileParser_TagTrait;
    
    /**
     * @var editor_Models_Import_FileParser_XmlParser
     */
    protected $xmlparser = null;
    
    /**
     * containing the result of the current parse call
     * @var array
     */
    protected $result = [];
    
    /**
     * @var editor_Models_Import_FileParser_Xlf_Namespaces
     */
    protected $namespaces;
    
    /**
     * @var array
     */
    protected $innerTag;
    
    /**
     * store the filename of the imported file for debugging reasons
     * @var string
     */
    protected $filename;
    
    /**
     * store the task for debugging reasons
     * @var editor_Models_Task
     */
    protected $task;
    
    /**
     * @param array $namespaces
     * @param editor_Models_Task $task for debugging reasons only
     * @param string $filename for debugging reasons only
     */
    public function __construct(editor_Models_Import_FileParser_Xlf_Namespaces $namespaces, editor_Models_Task $task, $filename) {
        $this->namespaces = $namespaces;
        $this->task = $task;
        $this->filename = $filename;
        $this->initImageTags();
        
        $this->xmlparser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        $this->xmlparser->registerElement('mrk', function($tag, $attributes){
            //test transunits with mrk tags are disabledd in the test xlf!
            $this->throwParseError('The file contains MRK tags, which are currently not supported! Stop Import.');
        });
        
        //since phs may contain only <sub> elements we have to handle text only inside a ph
        // that implies that the handling of <sub> elements is done in the main Xlf Parser and in the ph we get just a placeholder
        // see class description of parent Xlf Parser
        $this->xmlparser->registerElement('ph', function($tag, $attributes){
            $this->innerTag = [];
            $this->xmlparser->registerOther([$this, 'handlePhTagText']);
        }, function($tag, $key, $opener) {
            $this->xmlparser->registerOther([$this, 'handleText']);
            $originalContent = $this->xmlparser->getRange($opener['openerKey'], $key, true);
            $this->result[] = $this->createSingleTag($tag, $this->xmlparser->join($this->innerTag), $originalContent);
        });
        
        $this->xmlparser->registerElement('x', null, [$this, 'handleXTag']);
        $this->xmlparser->registerElement('g', [$this, 'handleGTagOpener'], [$this, 'handleGTagCloser']);
        
        $this->xmlparser->registerElement('*', [$this, 'handleUnknown']); // → all other tags
        $this->xmlparser->registerOther([$this, 'handleText']);
    }
    
    /**
     * creates an internal tag out of the given data
     * @param unknown $text
     * @return string
     */
    protected function createSingleTag($tag, $text, $originalContent) {
        $imgText = html_entity_decode($text, ENT_QUOTES, 'utf-8');
        $fileNameHash = md5($imgText);
        //generate the html tag for the editor
        $p = $this->getTagParams($originalContent, $this->shortTagIdent++, $tag, $fileNameHash, $text);
        $this->_singleTag->createAndSaveIfNotExists($imgText, $fileNameHash);
        return $this->_singleTag->getHtmlTag($p);
    }
    
    /**
     * parses the given chunks containing segment source, seg-source or target content
     * seg-source / target can be segmented into multiple mrk type="seg" which is one segment on our side
     * Therefore we return a list of segments here
     * @param array $chunks
     * @return array
     */
    public function convert(array $chunks) {
        $this->segments = [];
        $this->result = [];
        $this->shortTagIdent = 1;
        $this->xmlparser->parseList($chunks);
        
        //if there are no mrk type="seg" we have to move the bare result into the returned segments array   
        if(empty($this->segments) && !empty($this->result)) {
            $this->segments[] = $this->xmlparser->join($this->result);
        }
        
        //TODO use mrk seg mid as $this->segments index! 
        
        return $this->segments;
    }
    
    /**
     * default text handler
     * @param string $text
     */
    public function handleText($text) {
        //we have to decode entities here, otherwise our generated XLF wont be valid 
        $text = $this->protectWhitespace($text);
        $text = $this->whitespaceTagReplacer($text);
        $this->result[] = $text;
    }
    
    /**
     * Inner PH tag text handler
     * @param string $text
     */
    public function handlePhTagText($text) {
        $this->innerTag[] = $text;
    }
    
    /**
     * Handler for X tags
     * @param string $tag
     * @param integer $key
     * @param array $opener
     */
    public function handleXTag($tag, $key, $opener) {
        $single = $this->namespaces->getSingleTag($this->xmlparser->getChunk($key));
        if(!empty($single)) {
            $this->result[] = $single;
        }
        //FIXME if there is no tagMap result we have to convert the tag to an internal tag
        //doing stuff like: $this->_singleTag->create($text);
    }
    
    /**
     * Handler for G tags
     * @param string $tag
     * @param integer $key
     * @param array $opener
     */
    public function handleGTagOpener($tag, $attributes, $key) {
        $result = $this->namespaces->getPairedTag($this->xmlparser->getChunk($key), null);
        if(!empty($result)) {
            $this->result[] = $result[0];
        }
        //FIXME if there is no tagMap result we have to convert the tag to an internal tag
        //doing stuff like: $this->_singleTag->create($text);
    }
    
    /**
     * Handler for G tags
     * @param string $tag
     * @param integer $key
     * @param array $opener
     */
    public function handleGTagCloser($tag, $key, $opener) {
        $opener = $this->xmlparser->getChunk($opener['openerKey']);
        $closer = $this->xmlparser->getChunk($key);
        $result = $this->namespaces->getPairedTag($opener, $closer);
        if(!empty($result)) {
            $this->result[] = $result[1];
        }
        //FIXME if there is no tagMap result we have to convert the tag to an internal tag
        //doing stuff like: $this->_singleTag->create($text);
    }
    
    /**
     * Fallback for unknown tags
     * @param string $tag
     */
    public function handleUnknown($tag) {
        //below tags are given to the content converter, but they are known so far, just not handled by the converter
        switch ($tag) {
            case 'x': //must also be added here, since handleUnknown is called for the x start tag call (we have only registered a closer) 
            case 'g': //must also be added here, since handleUnknown is called for the x start tag call (we have only registered a closer) 
            case 'source':
            case 'target':
            case 'seg-source':
            return;
        }
        $this->throwParseError('The file contains '.$tag.' tags, which are currently not supported! Stop Import.');
    }
    
    /**
     * convenience method to throw exceptions
     * @param string $msg
     * @throws ZfExtended_Exception
     */
    protected function throwParseError($msg) {
        throw new ZfExtended_Exception('Task: '.$this->task->getTaskGuid().'; File: '.$this->filename.': '.$msg);
    }
}