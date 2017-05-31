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
     * counter for internal tags
     * @var integer
     */
    protected $shortTagIdent = 1;
    
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
     */
    public function __construct($namespaces, editor_Models_Task $task, $filename) {
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
        
        $this->xmlparser->registerElement('', [$this, 'handleUnknown']); // → all other tags
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
    
    public function handleText($text) {
        //we have to decode entities here, otherwise our generated XLF wont be valid 
        $text = html_entity_decode($text, ENT_XML1);
        $text = $this->parseSegmentProtectWhitespace($text);
        $this->result[] = $text;
        
        //FIXME must be moved into the trait - whitespace unification must be done before! 
        
        //FIXME
        return;
        // Other replacement
        $search = array(
                '#<hardReturn/>#',
                '#<softReturn/>#',
                '#<macReturn/>#',
                '#<space ts="[^"]*"/>#',
        );
        
        //set data needed by $this->whitespaceTagReplacer
        $this->_segment = $subsegment;
        $subsegment = preg_replace_callback($search, array($this,'whitespaceTagReplacer'), $subsegment);
    }
    
    public function handlePhTagText($text) {
        $this->innerTag[] = $text;
    }
    
    /**
     * Fallback for unknown tags
     * @param string $tag
     */
    public function handleUnknown($tag) {
        //below tags are given to the content converter, but they are known so far, just not handled by the converter
        switch ($tag) {
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