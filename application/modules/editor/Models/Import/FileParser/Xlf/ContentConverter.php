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
 * Converts XLF segment content chunks into translate5 internal segment content string
 */
class editor_Models_Import_FileParser_Xlf_ContentConverter {
    use editor_Models_Import_FileParser_TagTrait;
    
    const RID_TYPE_RID = 'rid';
    const RID_TYPE_ID = 'id';
    const RID_TYPE_FAKE = 'fake';
    
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
     * map of content tag to tagNr, to get the correct tagNr for tag pairs and between source and target column
     * @var array
     */
    protected $shortTagNumbers = [];
    
    /**
     * @var boolean
     */
    protected $useTagContentOnlyNamespace;
    
    /**
     * @var boolean
     */
    protected $source = true;
    
    protected $preserveWhitespace = false;
    
    /**
     * Flag to switch normal mode and remove tags mode
     * @var string
     */
    protected $removeTags = false;
    
    /**
     * @var editor_Models_Segment_UtilityBroker
     */
    protected $utilities;
    
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
        
        $this->utilities = ZfExtended_Factory::get('editor_Models_Segment_UtilityBroker');
        
        $this->useTagContentOnlyNamespace = $this->namespaces->useTagContentOnly();
        
        $this->xmlparser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        $this->xmlparser->registerElement('mrk', function($tag, $attributes){
            //test transunits with mrk tags are disabledd in the test xlf!
            //The trans-unit content contains MRK tags other than type=seg, which are currently not supported! Stop Import.
            throw new editor_Models_Import_FileParser_Xlf_Exception('E1195', [
                'file' => $this->filename,
                'task' => $this->task,
            ]);
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
            if($this->useTagContentOnly($tag, $key, $opener)) {
                $text = $this->xmlparser->join($this->innerTag);
            }
            else {
                $text = null;
            }
            $this->result[] = $this->createTag($opener, $tag, $originalContent, $text);
        });
        
        $this->xmlparser->registerElement('x,bx,ex', null, [$this, 'handleReplacerTag']);
        $this->xmlparser->registerElement('g', [$this, 'handleGTagOpener'], [$this, 'handleGTagCloser']);
        
        $this->xmlparser->registerElement('sub', function() {
            //disable this parser until the end of the sub tag.
            $this->xmlparser->disableHandlersUntilEndtag();
        });
        
        $this->xmlparser->registerElement('*', [$this, 'handleUnknown']); // → all other tags
        $this->xmlparser->registerOther([$this, 'handleText']);
    }
    
    /**
     * creates an internal tag out of the given data
     * @param string $openerMeta openerMeta array to get the ID to identify tag pairs (for tagNr calculation)
     * @param string $tag
     * @param string $originalContent this is value which is restored on export
     * @param string $text optional, this is the tag value which should be shown in the frontend
     * @return string
     */
    protected function createTag($openerMeta, $tag, $originalContent, $text = null): ?editor_Models_Import_FileParser_Tag {
        if($this->removeTags){
            return null;
        }
        $ridType = null;
        $rid = $this->getRid($openerMeta, $ridType);
        switch ($tag) {
            case 'x':
            case 'ph':
            case 'it':
            case 'bx':
            case 'ex':
                $tagType = editor_Models_Import_FileParser_Tag::TYPE_SINGLE;
                $type = '_singleTag';
                
                //we use the content as rid, so we can match tag numbers in source and target
                // if there is sub content, it must be removed since the sub content in different languages produces different md5 hashes
                // since sub tags can contained nested content wthe greedy approach is ok to remove from first <sub> to last </sub>
                $rid = md5(preg_replace('#<sub>.*</sub>#','<sub/>',$originalContent));
                break;
            case 'bpt':
                //the tagNr depends here on the existence of an entry with the same RID
                // if yes, take this value
                // if no, increase and set the new value as new tagNr to that RID
                // for g tags: RID = 'g-'.$openerKey;
            case 'g':
                $tagType = editor_Models_Import_FileParser_Tag::TYPE_OPEN;
                $type = '_leftTag';
                break;
            case 'g-close':
                //g-close tag is just a hack to distinguish between open and close
                $tag = 'g';
            case 'ept':
                $tagType = editor_Models_Import_FileParser_Tag::TYPE_CLOSE;
                $type = '_rightTag';
                break;
            default:
                return '<b>Programming Error! invalid tag type used!</b>';
        }
        if(strlen($text) === 0) {
            $text = htmlentities($originalContent);
        }
        $tagObj = new editor_Models_Import_FileParser_Tag($tagType);
        $tagObj->tag = $tag;
        $tagObj->tagNr = $this->getShortTagNumber($ridType, $rid);
        $tagObj->text = $text;
        $tagObj->rid = $rid;
        $tagObj->originalContent = $originalContent;
        $tagObj->renderedTag = $this->{$type}->getHtmlTag($this->getTagParams($originalContent, $tagObj->tagNr, $rid, $text));
        return $tagObj;
    }
    
    /**
     * Calculates an identifier of a tag, to match opener and closer tag (for tag numbering).
     * @param array $openerMeta
     * @param array $ridType will receive the rid type
     */
    protected function getRid($openerMeta, & $ridType) {
        $rid = $this->xmlparser->getAttribute($openerMeta['attributes'], 'rid');
        if($rid !== false) {
            $ridType = self::RID_TYPE_RID;
            return $rid;
        }
        $id = $this->xmlparser->getAttribute($openerMeta['attributes'], 'id');
        if($id !== false) {
            $ridType = self::RID_TYPE_ID;
            return $id;
        }
        //according to the spec all tags must have an ID, so if we get here,
        // this is invalid and we have to fake an identifier.
        // tagnumbering may then not be correct!
        if(empty($openerMeta['fakedRid'])){
            $openerMeta['fakedRid'] = $openerMeta['tag'].'-'.$openerMeta['openerKey'];
        }
        $ridType = self::RID_TYPE_FAKE;
        return $openerMeta['fakedRid'];
    }
    
    /**
     * returns the short tag number to the given rid or creates one
     * rid can be given as tag attribute, if nothing found the ID is used as fallback.
     * A rid should always be given, since it is also needed to map the tags in source to target,
     *  so that same tagNr are used for same tags there
     * @param string $ridType
     * @param string $rid
     * @return number
     */
    protected function getShortTagNumber($ridType, $rid) {
        if(empty($rid)) {
            return $this->shortTagIdent++;
        }
        //pairedTags have a rid, we have to look it up or to create it
        // we need the ridType here too, since a trans-unit may mix tags using ids only and tags using rid (which can be the same as the id in other, not related tags)
        $rid = $ridType.'-'.$rid;
        if(empty($this->shortTagNumbers[$rid])) {
            $this->shortTagNumbers[$rid] = $this->shortTagIdent++;
        }
        return $this->shortTagNumbers[$rid];
    }
    
    /**
     * returns true if the tag content should only be used as text for the internal tags.
     * On false the surrounding tags (ph, ept, bpt, it) are also displayed.
     * @param string $tag
     * @param int $key
     * @param array $opener
     * @return boolean
     */
    protected function useTagContentOnly($tag, $key, $opener) {
        //if the namespace defines a way how to use the tag content, use that way
        if(!is_null($this->useTagContentOnlyNamespace)) {
            return $this->useTagContentOnlyNamespace;
        }
        //the native way is to check for a ctype in the tag, if there is one, show the tags also
        if(array_key_exists('ctype', $opener['attributes'])) {
            return false;
        }
        // same if the tag contains only tags, then the surrounding tag also must be shown
        if($key - $opener['openerKey'] <= 2) {
            //if there is only one chunk in between, we mask only that text excluding tags
            return true;
        }
        $contentRange = trim($this->xmlparser->getRange($opener['openerKey']+1, $key-1, true)).'<end>';
        //FIXME a img tag with sub inside a ph is not exposed as <img> but as <ph> in the GUI
        //returns false if contentRange starts with <sub and ends with sub>, what means contains a sub text only
        return (stripos($contentRange, '<sub') !== 0 || stripos($contentRange, 'sub><end>') === false);
    }
    
    /**
     * parses the given chunks containing segment source, seg-source or target content, or their child elements content like sub or mrk mtype="seg"
     * the result is not returned as string but as array for post processing of the generated chunks
     *
     * @param array|string $chunks can be either an array of chunks or a string which then will be parsed
     * @param bool $source
     * @param bool $preserveWhitespace defines if the whitespace in the XML nodes should be preserved or not
     * @return array
     */
    public function convert($chunks, $source, $preserveWhitespace = false) {
        $this->result = [];
        $this->removeTags = false;
        //this assumes that source tags come always before target tags
        if($source) {
            $this->shortTagIdent = 1;
            $this->shortTagNumbers = [];
        }
        else {
            //if we parse the target, we have to reuse the tagNrs found in source
            $this->shortTagIdent = empty($this->shortTagNumbers) ? 1 : (max($this->shortTagNumbers) + 1);
        }
        $this->source = $source;
        //get the flag just from outside, must not be parsed by inline element parser, since xml:space may occur only outside of inline content
        $this->preserveWhitespace = $preserveWhitespace;
        if(is_array($chunks)) {
            $this->xmlparser->parseList($chunks);
        }
        else {
            $this->xmlparser->parse($chunks);
        }
        
        if(!empty($this->result) && !$this->preserveWhitespace) {
            $lastIdx = count($this->result) - 1;
            $this->result[0] = ltrim($this->result[0]);
            $this->result[$lastIdx] = rtrim($this->result[$lastIdx]);
        }
        return $this->result;
    }
    
    /**
     * removes all Xlf Tags and the masked content from the given string
     * @param string $content
     * @return string
     */
    public function removeXlfTags($content) {
        $this->result = [];
        //by setting removeTags to false, currently all side effects related to $this->shortTag* fields are prevented.
        // Keep in mind if changing getShortTagNumber code or usage,
        // since this is the only place manipulating the fields - and which deacticated with remove tags implicitly.
        $this->removeTags = true;
        $this->preserveWhitespace = false;
        $this->xmlparser->parse($content);
        return $this->xmlparser->join($this->result);
    }
    
    /**
     * default text handler
     * @param string $text
     */
    public function handleText($text) {
        if(!$this->preserveWhitespace) {
            $text = preg_replace("/[ \t\n\r]+/u", ' ', $text);
            if(is_null($text)) {
                $errorMsg = array_flip(array_filter(get_defined_constants(true)['pcre'], function($item){
                    return is_int($item);
                }))[preg_last_error()] ?? 'Unknown Error';
                
                // Whitespace in text content can not be cleaned by preg_replace. Error Message: "{msg}". Stop Import.
                throw new editor_Models_Import_FileParser_Xlf_Exception('E1196', [
                    'file' => $this->filename,
                    'task' => $this->task,
                    'pregMsg' => $errorMsg
                ]);
            }
        }
        //we have to decode entities here, otherwise our generated XLF wont be valid
        // although the whitespace of the content may not be preserved here, if there remain multiple spaces or other space characters,
        // we have to protect them here
        
        $wh = $this->utilities->whitespace;
        if($this->task->getConfig()->runtimeOptions->import->fileparser->options->protectTags ?? false) {
            //since we are in a XML file format, plain tags in the content are encoded, which we have to undo first
            //$text is here for example: Dies &lt;strong&gt;ist ein&lt;/strong&gt; Test. &amp;nbsp;
            $text = html_entity_decode($text);
            //$text is now: Dies <strong>ist ein</strong> Test. &nbsp;
            
            $text = $this->utilities->tagProtection->protectTags($text);
            $text = $wh->protectWhitespace($text, $wh::ENTITY_MODE_OFF); //disable entity handling here, since already done in tagProtection
        }
        else {
            $text = $wh->protectWhitespace($text);
        }
        
        $text = $this->replacePlaceholderTags($text);
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
     * @param int $key
     * @param array $opener
     */
    public function handleReplacerTag($tag, $key, $opener) {
        $chunk = $this->xmlparser->getChunk($key);
        $single = $this->namespaces->getSingleTag($chunk);
        if(!empty($single)) {
            $this->result[] = $single;
            return;
        }
        //hack so that we can replace original tags with <x> tag internally
        // and here we restore then the original content to be visible in the frontend
        // (<ph>orig</ph> tag would be correcter instead <x>, but can not be used due index shifting of the xml chunks then)
        if($originalTagData = $this->xmlparser->getAttribute($opener['attributes'], 'translate5OriginalContent')) {
            $chunk = htmlspecialchars_decode($originalTagData);
        }
        $this->result[] = $this->createTag($opener, $tag, $chunk);
    }
    
    /**
     * Handler for G tags
     * @param string $tag
     * @param array $attributes
     * @param int $key
     */
    public function handleGTagOpener($tag, $attributes, $key) {
        $chunk = $this->xmlparser->getChunk($key);
        $result = $this->namespaces->getPairedTag($chunk, null);
        if(!empty($result)) {
            $this->result[] = $result[0];
            return;
        }
        $this->result[] = $this->createTag($this->xmlparser->current(), $tag, $chunk);
    }
    
    /**
     * Handler for G tags
     * @param string $tag
     * @param int $key
     * @param array $opener
     */
    public function handleGTagCloser($tag, $key, $opener) {
        $openerKey = $opener['openerKey'];
        $openChunk = $this->xmlparser->getChunk($openerKey);
        $closeChunk = $this->xmlparser->getChunk($key);
        $result = $this->namespaces->getPairedTag($openChunk, $closeChunk);
        if(!empty($result)) {
            $this->result[] = $result[1];
            return;
        }
        $this->result[] = $this->createTag($opener, $tag.'-close', $closeChunk);
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
                return;
            default:
                break;
        }
        // The file "{file}" contains "{tag}" tags, which are currently not supported! Stop Import.
        throw new editor_Models_Import_FileParser_Xlf_Exception('E1194', [
            'file' => $this->filename,
            'task' => $this->task,
            'tag' => $tag
        ]);
    }
    
    public function getFileName() {
        return $this->filename;
    }
}
