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

/* * #@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 */

/**
 *
 */
class editor_Models_Export_FileParser_Xlf extends editor_Models_Export_FileParser {

    const SOURCE_TO_EMPTY_TARGET_SUFFIX = '.sourceInEmptyTarget.xlf';
    
    /**
     * @var string Klassenname des Difftaggers
     */
    protected $_classNameDifftagger = 'editor_Models_Export_DiffTagger_Sdlxliff';
    
    /**
     * Helper to call namespace specfic parsing stuff
     * @var editor_Models_Export_FileParser_Xlf_Namespaces
     */
    protected $namespaces;
    
    /**
     * @var array
     */
    protected $segmentIdsPerUnit = [];
    
    protected $segmentsToLog = [];
    
    /**
     * If feature copy source to empty target is enabled,
     * this string is filled with a  export duplicate containing source content in untranslated (empty) targets
     * @var array
     */
    protected $_exportChunksWithSourceFallback = [];
    
    /**
     * @param editor_Models_Task $task
     * @param int $fileId
     * @param string $path The absolute path to the file where the content is written to
     * @param array $options see $this->options for available options
     */
    public function __construct(editor_Models_Task $task, int $fileId, string $path, array $options = []) {
        //TODO let me come from a task level config, currently overwritten by extending fileparser
        //set the sourcetoemptytarget default value, may be overwritten via $options then
        $this->options['sourcetoemptytarget'] = false;
        
        parent::__construct($task, $fileId, $path, $options);
    }
    
    /**
     * übernimmt das eigentliche FileParsing
     *
     * - setzt an Stelle von <lekTargetSeg... wieder das überarbeitete Targetsegment ein
     * - befüllt $this->_exportFile
     */
    protected function parse() {
        $xmlparser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $xmlparser editor_Models_Import_FileParser_XmlParser */
        
        //namespaces are not available until the xliff start tag was parsed!
        $xmlparser->registerElement('xliff', function($tag, $attributes, $key) use ($xmlparser){
            $this->namespaces = ZfExtended_Factory::get("editor_Models_Export_FileParser_Xlf_Namespaces",[$xmlparser->getChunk($key)]);
            $this->namespaces->registerParserHandler($xmlparser, $this->_task);
        });
        
        $xmlparser->registerElement('lekTargetSeg', null, function($tag, $key, $opener) use ($xmlparser){
            $attributes = $opener['attributes'];
            if(empty($attributes['id']) && $attributes['id'] !== '0') {
                throw new Zend_Exception('Missing id attribute in '.$xmlparser->getChunk($key));
            }
            
            $id = $attributes['id'];
            //alternate field is optional, use target as default
            if(isset($attributes['field'])) {
                $field = $attributes['field'];
            }
            else {
                $field = editor_Models_SegmentField::TYPE_TARGET;
            }
            //$this->writeMatchRate(); refactor reapplyment of matchratio with XMLParser and namespace specific!
            $segmentContent = $this->getSegmentContent($id, $field);
            $xmlparser->replaceChunk($key, $segmentContent);
            if($this->options['sourcetoemptytarget'] && strlen($segmentContent) === 0) {
                $this->_exportChunksWithSourceFallback[$key] = $this->getSegmentContent($id, editor_Models_SegmentField::TYPE_SOURCE);
            }
        });
        
        //convert empty <target></target> tags to single ones: <target />
        $xmlparser->registerElement('target', null, function($tag, $key, $opener) use ($xmlparser){
            $content = $xmlparser->getRange($opener['openerKey']+1, $key-1, true);
            if($opener['openerKey']+1 === (int) $key || empty($content) && $content !== '0') {
                $xmlparser->replaceChunk($key, '');
                $xmlparser->replaceChunk($opener['openerKey'], $this->makeTag($tag, true, $opener['attributes']));
            }
        });
        
        $xmlparser->registerElement('trans-unit', function(){
            $this->transUnitLength = 0;
        }, function($tag, $key, $opener) use ($xmlparser){
            $segments = [];
            foreach($this->segmentIdsPerUnit as $segmentId) {
                $segments[] = $this->getSegment($segmentId);
            }
            //add the additional unit length for the final length calculation (once per transunit)
            if(!empty($segments)) {
                $firstSegment = reset($segments);
                $this->transUnitLength += (int) $firstSegment->meta()->getAdditionalUnitLength();
            }
            $event = new Zend_EventManager_Event();
            //get origanal attributes:
            $matches = null;
            if(preg_match_all('/([^\s]+)="([^"]*)"/', $xmlparser->getChunk($opener['openerKey']), $matches)){
                $originalAttributes = array_combine($matches[1], $matches[2]);
            } else {
                $originalAttributes = [];
            }
            $event->setParams([
                    //just the tag name, should be trans-unit here
                    'tag' => $tag,
                    //the affected segments:
                    'segments' => $segments,
                    //this attributes field should be manipulated by the listeners, since its played back into the transunit
                    'attributes' => $originalAttributes,
                    //the chunk key in $xmlparser of the closer tag
                    'key' => $key,
                    //all chunk information about the opener, for special manipulations in the handler
                    'tagOpener' => $opener,
                    //xmlparser for special manipulations in the handler
                    'xmlparser' => $xmlparser,
                    'task'      => $this->_task,
            ]);
            
            //trigger an event to allow custom transunit manipulations
            $this->events->trigger('writeTransUnit', $this, $event);

            //if attributes were changed in the handlers, replace the tag with the new ones
            if($originalAttributes !== $event->getParam('attributes')) {
                $xmlparser->replaceChunk($opener['openerKey'], $this->makeTag($tag, false, $event->getParam('attributes')));
                $originalAttributes = $event->getParam('attributes');
            }
            
            //reset segments per unit:
            $this->segmentIdsPerUnit = [];
            
            $sizeUnit = $xmlparser->getAttribute($originalAttributes, 'size-unit');
            if($sizeUnit == 'char' || $sizeUnit == editor_Models_Segment_PixelLength::SIZE_UNIT_XLF_DEFAULT) {
                $minWidth = $xmlparser->getAttribute($originalAttributes, 'minwidth', 0);
                $maxWidth = $xmlparser->getAttribute($originalAttributes, 'maxwidth', PHP_INT_MAX);
                if($this->transUnitLength < $minWidth) {
                    $this->logSegment($originalAttributes, 'segment to short');

                }
                if($this->transUnitLength > $maxWidth) {
                    $this->logSegment($originalAttributes, 'segment to long');
                }
            }
            
        });
        
        $preserveWhitespaceDefault = $this->config->runtimeOptions->import->xlf->preserveWhitespace;
        $this->_exportFile = $xmlparser->parse($this->_skeletonFile, $preserveWhitespaceDefault);
        
        if($this->options['sourcetoemptytarget']) {
            //UGLY: typecast to string here
            $this->_exportChunksWithSourceFallback = $xmlparser->join(array_replace($xmlparser->getAllChunks(), $this->_exportChunksWithSourceFallback));
        }
        
        $this->sendLog();
    }
    
    public function saveFile() {
        parent::saveFile();
        if(!$this->options['sourcetoemptytarget']) {
            return;
        }
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $file->load($this->_fileId);
        file_put_contents($this->path.self::SOURCE_TO_EMPTY_TARGET_SUFFIX, $this->convertEncoding($file, $this->_exportChunksWithSourceFallback));
    }
    
    /**
     * Generates a XML tag
     * @param string $tag the xml tag
     * @param boolean $single defines, if it should be self closing tag or not, default false
     * @param array $attributes the XML tag attributes, default empty
     * @return string
     */
    protected function makeTag(string $tag, bool $single = false, array $attributes = []): string {
        $attributesString = '';
        foreach($attributes as $attribute => $value) {
            $attributesString .= ' '.$attribute.'="'.$value.'"';
        }
        if($single) {
            $attributesString .= '/';
        }
        return '<'.$tag.$attributesString.'>';
    }
    
    /**
     * Logs export errors to the segment defined by the given transunit attributes
     * @param array $attributes
     * @param string $msg
     */
    protected function logSegment(array $attributes, $msg) {
        $this->segmentsToLog[] = [
            'transunitAttributes' => $attributes,
            'transunitLength' => $this->transUnitLength,
            'error' => $msg,
        ];
    }
    
    /**
     * send the tracked export warnings to the user
     */
    protected function sendLog() {
        if(empty($this->segmentsToLog)) {
            return;
        }
        $log = ZfExtended_Factory::get('ZfExtended_Log');
        //Some Segments are producing warnings on export
        $msg = 'Some Segments are producing warnings on export of Task: '."\n";
        $msg .= '  '.$this->_task->getTaskName(). ' (guid: '.$this->_task->getTaskGuid().')'."\n\n";
        $file = ZfExtended_Factory::get('editor_Models_File');
        /* @var $file editor_Models_File */
        $file->load($this->_fileId);
        $msg .= 'File: '.$file->getFileName().' (id: '.$this->_fileId.')'."\n\n";
        $msg .= 'Segments: '."\n";
        $msg .= print_r($this->segmentsToLog,1);
        $log->log('Export Warnings for task '.$this->_task->getTaskName(), $msg);
        $this->segmentsToLog = [];
    }
    
    /**
     * overwrites the parent to remove img tags, which contain currently MQM only (until we provide real MQM export)
     * {@inheritDoc}
     * @see editor_Models_Export_FileParser::parseSegment()
     */
    protected function parseSegment($segment) {
        $segment = preg_replace('/<img[^>]*>/','', $segment);
        return parent::parseSegment($segment);
    }
    
    /**
     * dedicated to write the match-Rate to the right position in the target format
     * @param array $file that contains file as array as splitted by parse function
     * @param int $i position of current segment in the file array
     * @return string
     *
     */
    protected function writeMatchRate(array $file, int $i) {
        // FIXME This code is disabled, because:
        //  - the mid is not unique (due multiple files in the XLF) this code is buggy
        //  - the tmgr:matchratio should only be exported for OpenTM2 XLF and not in general
        //  - the preg_match is leading to above problems, it would be better to use the XMLParser here to,
        //    and paste the new attributes on the parent trans-unit to one <lekSegmentPlaceholder>
        //
        //  SEE ALSO TRANSLATE-956
        //  must be implemented in editor_Models_Export_FileParser_Xlf_TmgrNamespace
        //
        return $file;
        
        $matchRate = $this->_segmentEntity->getMatchRate();
        $midArr = explode('_', $this->_segmentEntity->getMid());
        $mid = $midArr[0];
        $segPart =& $file[$i-1];
        //example string
        //<trans-unit id="3" translate="yes" tmgr:segstatus="XLATED" tmgr:matchinfo="AUTOSUBST" tmgr:matchratio="100">
        if(preg_match('#<trans-unit[^>]* id="'.$mid.'"[^>]*tmgr:matchratio="\d+"#', $segPart)===1){
            //if percent attribute is already defined
            $segPart = preg_replace('#(<trans-unit[^>]* id="'.$mid.'"[^>]*tmgr:matchratio=)"\d+"#', '\\1"'.$matchRate.'"', $segPart);
            return $file;
        }
        $segPart = preg_replace('#(<trans-unit[^>]* id="'.$mid.'" *)#', '\\1 tmgr:matchratio="'.$matchRate.'" ', $segPart);
        return $file;
    }
    
    /**
     * {@inheritDoc}
     * @see editor_Models_Export_FileParser::getSegmentContent()
     */
    protected function getSegmentContent($segmentId, $field) {
        $this->segmentIdsPerUnit[] = $segmentId;
        $content = parent::getSegmentContent($segmentId, $field);
        $this->transUnitLength += $this->lastSegmentLength;
        //without sub tags, no sub tags must be restored
        if(stripos($content, '<sub') === false) {
            return $content;
        }
        
        //get the transunit part of the root segment
        $transunitMid = $this->_segmentEntity->getMid();
        $transunitMid = explode('_', $transunitMid)[0];
        
        $xmlparser = ZfExtended_Factory::get('editor_Models_Import_FileParser_XmlParser');
        /* @var $xmlparser editor_Models_Import_FileParser_XmlParser */
        
        //restoring of sub tags is working only if the parent tag has a valid id - this is the identifier for the sub segment content
        $xmlparser->registerElement('sub', function($tag, $attributes, $key) use($xmlparser){
            //disable handling of tags if we reach a sub, this is done recursivly in the loaded content of the found sub
            $xmlparser->disableHandlersUntilEndtag();
        }, function($tag, $key, $opener) use ($xmlparser, $transunitMid, $field){
            $tagId = $this->getParentTagId($xmlparser);
            if(empty($tagId) && $tagId !== '0') {
                error_log("Could not restore sub tag content since there is no id in the surrounding <ph>,<bpt>,<ept>,<it> tag!"); //FIXME better logging
                return;
            }
            //now we need the segmentId to the found MID:
            // since the MID of a <sub> segment is defined as:
            // SEGTRANSUNITID _ SEGNR -sub- TAGID
            // and we have only the first and the last part, we have to use like to get the segmentId
            $s = $this->_segmentEntity->db->select('id')
                ->where('taskGuid = ?', $this->_taskGuid)
                ->where('mid like ?', $transunitMid.'_%-sub-'.$tagId);
            $segmentRow = $this->_segmentEntity->db->fetchRow($s);
            
            //if we got a segment we have to get its segmentContent and set it as the new content in our resulting XML
            // since we are calling getSegmentContent recursivly, the <sub> segments are replaced from innerst one out
            if($segmentRow) {
                //remove all chunks between the sub tag
                $xmlparser->replaceChunk($opener['openerKey']+1,'', $key-$opener['openerKey']-1);
                //fill one chunk with the segment content
                $xmlparser->replaceChunk($opener['openerKey']+1,$this->getSegmentContent($segmentRow->id, $field));
            }
        });
        return $xmlparser->parse($content);
    }
    
    /**
     * returns the parent tag id of the current SUB element,
     *  since this ID is part of the Segment MID of the created segment for the sub element
     * @param editor_Models_Import_FileParser_XmlParser $xmlparser
     * @return string|NULL
     */
    protected function getParentTagId(editor_Models_Import_FileParser_XmlParser $xmlparser) {
        //loop through all valid parent tags
        $validParents = ['ph[id]','it[id]','bpt[id]','ept[id]'];
        $parent = false;
        while(!$parent && !empty($validParents)) {
            $parent = $xmlparser->getParent(array_shift($validParents));
            if($parent) {
                //if we have found a valid parent (ID must be given)
                // we create the same string as it was partly used for the segments MID
                return $parent['tag'].'-'.$parent['attributes']['id'];
            }
        }
        //without the parent id no further processing is possible for that segment
        return null;
    }
    
    /**
     * Since on XLF import with tagprotection we did a html_entity_decode, we have now to htmlspecialchars them again
     * {@inheritDoc}
     * @see editor_Models_Export_FileParser::unprotectContent()
     */
    protected function unprotectContent(string $segment): string {
        $segment = parent::unprotectContent($segment);
        if($this->config->runtimeOptions->import->fileparser->options->protectTags ?? false) {
            return $this->utilities->tagProtection->unprotect($segment);
        }
        return $segment;
    }
}
