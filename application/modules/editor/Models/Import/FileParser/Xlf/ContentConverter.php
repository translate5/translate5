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
    
    protected $shortTagNumbers = [];
    
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
        $this->xmlparser->registerElement('ph,it,bpt,ept', function($tag, $attributes){
            $this->innerTag = [];
            $this->xmlparser->registerOther([$this, 'handleContentTagText']);
        }, function($tag, $key, $opener) {
            $this->xmlparser->registerOther([$this, 'handleText']);
            $originalContent = $this->xmlparser->getRange($opener['openerKey'], $key, true);
            $rid = $this->xmlparser->getAttribute($opener['attributes'], 'rid');
            $this->result[] = $this->createTag($rid, $tag, $originalContent, $this->xmlparser->join($this->innerTag));
        });
        
        $this->xmlparser->registerElement('x,bx,ex', null, [$this, 'handleReplacerTag']);
        $this->xmlparser->registerElement('g', [$this, 'handleGTagOpener'], [$this, 'handleGTagCloser']);
        
        $this->xmlparser->registerElement('*', [$this, 'handleUnknown']); // → all other tags
        $this->xmlparser->registerOther([$this, 'handleText']);
    }
    
    /**
     * creates an internal tag out of the given data
     * @param string $rid ID to identify tag pairs (for tagNr calculation)
     * @param string $type valid types are: single, open, close
     * @param string $tag
     * @param string $originalContent this is value which is restored on export
     * @param string $text optional, this is the tag value which should be shown in the frontend
     * @return string
     */
    protected function createTag($rid, $tag, $originalContent, $text = null) {
        switch ($tag) {
            case 'x':
            case 'ph':
            case 'it':
                $type = '_singleTag';
                $rid = 0;
                break;
            case 'bpt':
            case 'bx':
                //hier ist die tagNr abhängig davon ob es schon einen Eintrag mit der RID gibt, 
                // wenn ja nimm den wert von dort
                // wenn nein increase und setze den Wert unter der RID
                // Beim g tag ist die RID = 'g-'.$openerKey;
            case 'g':
                $type = '_leftTag';
                break;
            case 'g-close':
                $tag = 'g'; //g-close tag is just a hack to distinguish between open and close
            case 'ept':
            case 'ex':
                $type = '_rightTag';
                break;
            default:
                return '<b>Programming Error! invalid tag type used!</b>';
        }
        if(empty($text)) {
            $text = htmlentities($originalContent);
        }
        $imgText = html_entity_decode($text, ENT_QUOTES, 'utf-8');
        $fileNameHash = md5($imgText);
        //generate the html tag for the editor
        $tagNr = $this->getShortTagNumber($rid);
        $p = $this->getTagParams($originalContent, $tagNr, $tag, $fileNameHash, $text);
        $this->{$type}->createAndSaveIfNotExists($imgText, $fileNameHash);
        return $this->{$type}->getHtmlTag($p);
    }
    
    /**
     * returns the short tag number to the given rid or creates one
     * @param string $rid
     * @return number
     */
    protected function getShortTagNumber($rid) {
        //single tags have an empty rid and must always get a new tagNr
        if(empty($rid)) {
            return $this->shortTagIdent++;
        }
        //pairedTags have a rid, we have to look it up or to create it
        if(empty($this->shortTagNumbers[$rid])) {
            $this->shortTagNumbers[$rid] = $this->shortTagIdent++;
        }
        return $this->shortTagNumbers[$rid];
    }
    
    /**
     * parses the given chunks containing segment source, seg-source or target content
     * seg-source / target can be segmented into multiple mrk type="seg" which is one segment on our side
     * Therefore we return a list of segments here
     * @param array $chunks
     * @return array
     */
    public function convert(array $chunks) {
        $this->result = [];
        $this->shortTagIdent = 1;
        $this->shortTagNumbers = [];
        $this->xmlparser->parseList($chunks);
        
        return $this->xmlparser->join($this->result);
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
    public function handleContentTagText($text) {
        $this->innerTag[] = $text;
    }
    
    /**
     * Handler for X tags
     * @param string $tag
     * @param integer $key
     * @param array $opener
     */
    public function handleReplacerTag($tag, $key, $opener) {
        $chunk = $this->xmlparser->getChunk($key);
        $single = $this->namespaces->getSingleTag($chunk);
        if(!empty($single)) {
            $this->result[] = $single;
            return;
        }
        //returns false if no rid found (default for x tags)
        $rid = $this->xmlparser->getAttribute($opener['attributes'], 'rid');
        $this->result[] = $this->createTag($rid, $tag, $chunk);
    }
    
    /**
     * Handler for G tags
     * @param string $tag
     * @param array $attributes
     * @param integer $key
     */
    public function handleGTagOpener($tag, $attributes, $key) {
        $chunk = $this->xmlparser->getChunk($key);
        $result = $this->namespaces->getPairedTag($chunk, null);
        if(!empty($result)) {
            $this->result[] = $result[0];
            return;
        }
        //for gTags we have to fake the RID for tag matching
        $this->result[] = $this->createTag('g-'.$key, $tag, $chunk);
    }
    
    /**
     * Handler for G tags
     * @param string $tag
     * @param integer $key
     * @param array $opener
     */
    public function handleGTagCloser($tag, $key, $opener) {
        $openerKey = $opener['openerKey'];
        $opener = $this->xmlparser->getChunk($openerKey);
        $closer = $this->xmlparser->getChunk($key);
        $result = $this->namespaces->getPairedTag($opener, $closer);
        if(!empty($result)) {
            $this->result[] = $result[1];
            return;
        }
        //to the $rid see handleGTagOpener
        $this->result[] = $this->createTag('g-'.$openerKey, $tag.'-close', $closer);
    }
    
    /**
     * Fallback for unknown tags
     * @param string $tag
     */
    public function handleUnknown($tag) {
        //below tags are given to the content converter, 
        // they are known so far, just not handled by the converter
        // or they are not intended to be handled since the main action happens in the closer handler not in the opener handler
        switch ($tag) {
            case 'x':  
            case 'g': 
            case 'bx':
            case 'ex':
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