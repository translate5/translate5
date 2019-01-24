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
 *
 */

/**
 * Segment Entity Object
 * 
 * @method integer getId() getId()
 * @method void setId() setId(integer $id)
 * @method int getSegmentNrInTask() getSegmentNrInTask()
 * @method void setSegmentNrInTask() setSegmentNrInTask(int $nr)
 * @method int getFileId() getFileId()
 * @method void setFileId() setFileId(int $id)
 * @method string getMid() getMid()
 * @method void setMid() setMid(string $mid)
 * @method string getUserGuid() getUserGuid()
 * @method void setUserGuid() setUserGuid(string $guid)
 * @method string getUserName() getUserName()
 * @method void setUserName() setUserName(string $name)
 * @method string getTaskGuid() getTaskGuid()
 * @method void setTaskGuid() setTaskGuid(string $guid)
 * @method int getTimestamp() getTimestamp()
 * @method void setTimestamp() setTimestamp(int $timestamp)
 * @method bool getEditable() getEditable()
 * @method void setEditable() setEditable(bool $editable)
 * @method bool getPretrans() getPretrans()
 * @method void setPretrans() setPretrans(bool $pretrans)
 * @method int getMatchRate() getMatchRate()
 * @method void setMatchRate() setMatchRate(int $matchrate)
 * @method string getMatchRateType() getMatchRateType()
 * @method string getQmId() getQmId()
 * @method void setQmId() setQmId(string $qmid)
 * @method int getStateId() getStateId()
 * @method void setStateId() setStateId(int $id)
 * @method integer getAutoStateId() getAutoStateId()
 * @method void setAutoStateId() setAutoStateId(integer $id)
 * @method int getFileOrder() getFileOrder()
 * @method void setFileOrder() setFileOrder(int $order)
 * @method string getComments() getComments()
 * @method void setComments() setComments(string $comments)
 * @method integer getWorkflowStepNr() getWorkflowStepNr()
 * @method void setWorkflowStepNr() setWorkflowStepNr(integer $stepNr)
 * @method string getWorkflowStep() getWorkflowStep()
 * @method void setWorkflowStep() setWorkflowStep(string $name)
 * 
 * this are just some helper for the always existing segment fields, similar named methods exists for all segment fields: 
 * @method void setSource() setSource(string $content)
 * @method void setSourceEdit() setSourceEdit(string $content)
 * @method void setSourceMd5() setSourceMd5(string $md5hash)
 * @method void setTarget() setTarget(string $content)
 * @method void setTargetEdit() setTargetEdit(string $content)
 * @method void setTargetMd5() setTargetMd5(string $md5hash)
 * 
 */
class editor_Models_Segment extends ZfExtended_Models_Entity_Abstract {
    const PM_SAME_STEP_INCLUDED = 'sameStepIncluded';
    const PM_ALL_INCLUDED = 'allIncluded';
    const PM_NOT_INCLUDED = 'notIncluded';
    
    protected $dbInstanceClass          = 'editor_Models_Db_Segments';
    protected $validatorInstanceClass   = 'editor_Models_Validator_Segment';
    
    /**
     * @var Zend_Config
     */
    protected $config           = null;
    
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager = null;
    
    /**
     * @var [editor_Models_Db_SegmentDataRow]
     */
    protected $segmentdata     = array();
    
    /**
     * @var editor_Models_Segment_Meta
     */
    protected $meta;
    
    /**
     * cached is modified info
     * @var boolean
     */
    protected $isDataModifiedAgainstOriginal = null;
    
    /**
     * cached is modified info
     * @var boolean
     */
    protected $isDataModified = null;
    
    /**
     * enables / disables watchlist (enabling makes only sense if called from Rest indexAction)
     * @var boolean
     */
    protected $watchlistFilterEnabled = false;

    /**
     * @var editor_Models_Segment_InternalTag
     */
    protected $tagHelper;
    
    /**
     * @var editor_Models_Segment_TrackChangeTag
     */
    protected $trackChangesTagHelper;
    
    /**
     * @var editor_Models_Segment_Whitespace
     */
    protected $whitespaceHelper;
    
    /**
     * @var editor_Models_Segment_PixelLength
     */
    protected $pixelLength;
    
    /**
     * init the internal segment field and the DB object
     */
    public function __construct()
    {
        $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        $this->tagHelper = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->trackChangesTagHelper = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        $this->whitespaceHelper = ZfExtended_Factory::get('editor_Models_Segment_Whitespace');
        parent::__construct();
    }
    
    /**
     * "lazy load" for editor_Models_Segment_PixelLength (must fit to the segment's task!).
     */
    protected function getPixelLength(string $taskGuid) {
        if (!isset($this->pixelLength) || $this->pixelLength->getTaskGuid() != $taskGuid) {
            $this->pixelLength = ZfExtended_Factory::get('editor_Models_Segment_PixelLength',[$taskGuid]);
        }
        return $this->pixelLength;
    }
    
    /***
     * Search the materialized view for given search field,search string and match case.
     * Only hits in the editable fields will be returned
     * 
     * @param array $parameters
     * @return string|array
     */
    public function search(array $parameters){
        $session = new Zend_Session_Namespace();
        $taskGuid=$session->taskGuid;
        if ($session->taskGuid !== $parameters['taskGuid']) {
            //nach außen so tun als ob das gewünschte Entity nicht gefunden wurde
            throw new ZfExtended_Models_Entity_NoAccessException();
        }
        
        $mv=ZfExtended_Factory::get('editor_Models_Segment_MaterializedView');
        /* @var $mv editor_Models_Segment_MaterializedView  */
        $mv->setTaskGuid($taskGuid);
        $viewName=$mv->getName();
        
        $this->reInitDb($taskGuid);
        $this->segmentFieldManager->initFields($taskGuid);
        
        //get the search sql string
        $searchQuery=$this->buildSearchString($parameters);
        
        //the field where the search will be performed (toSort field)
        $searchInToSort=$parameters['searchInField'].editor_Models_SegmentFieldManager::_TOSORT_PREFIX;
        
        //check if search in locked segment is clicked, if yes, remove the editable filter
        $searchLocked=false;
        if($parameters['searchInLockedSegments']){
            $searchLocked=$parameters['searchInLockedSegments']==="true";
        }
        
        $select= $this->db->select()
        ->from($viewName,array('id','segmentNrInTask',$parameters['searchInField'],$searchInToSort,'editable'))
        ->where($searchQuery);
        if(!$searchLocked){
            $select->where('editable=1');
        }
        
        /* //TODO:The idea how we can use the search limitation
         * 
         * SELECT id,rank FROM (
            	SELECT @rownum := @rownum + 1 AS rank, 
            	   `id`, `segmentNrInTask`, `fileId`, `mid`, `userGuid`, `userName`, `taskGuid`, `timestamp`, 
            	   `editable`, `pretrans`, `matchRate`, `matchRateType`, `qmId`, `stateId`, `autoStateId`, `fileOrder`, 
            	   `comments`, `workflowStepNr`, `workflowStep`, `source`, `sourceMd5`, `sourceToSort`, `target`, 
            	   `targetMd5`, `targetToSort`, `targetEdit`, `targetEditToSort` 
            	   FROM `LEK_segment_view_10ba195a738894769f296aee08364626`, (SELECT @rownum := 0) r
            	   ORDER BY `fileOrder` asc, `id` asc LIMIT 100 OFFSET 100000
               ) sub
            WHERE targetEditToSort  REGEXP '[0-9]';
         */
        $result = $this->loadFilterdCustom($select);
        
        return $result;
    }
    
    /***
     * Build search SQL string for given field based on the search type
     * 
     * @param array $parameters
     * @return boolean|string
     */
    protected function buildSearchString($parameters){
        $adapter=$this->db->getAdapter();

        $queryString=$parameters['searchField'];
        $searchInField=$parameters['searchInField'].editor_Models_SegmentFieldManager::_TOSORT_PREFIX;
        $searchType=isset($parameters['searchType']) ? $parameters['searchType'] : null;
        $matchCase=isset($parameters['matchCase']) ? (strtolower($parameters['matchCase'])=='true'): false;
        
        //search type regular expression
        if($searchType==='regularExpressionSearch'){
            //simples way to test if the regular expression is valid
            //try {
                //@preg_match($patern, 'Test string');
            //} catch (Exception $e) {
            //    return false;
            //}
            if(!$matchCase){
                return $adapter->quoteIdentifier($searchInField).' REGEXP '.$adapter->quote($queryString);
            }
            return $adapter->quoteIdentifier($searchInField).' REGEXP BINARY '.$adapter->quote($queryString);
        }
        //search type regular wildcard
        if($searchType==='wildcardsSearch'){
            $queryString=str_replace("*","%",$queryString);
            $queryString=str_replace("?","_",$queryString);
        }
        //if match case, search without lower function
        if($matchCase){
            return $adapter->quoteIdentifier($searchInField).' like '.$adapter->quote('%'.$queryString.'%').' COLLATE utf8_bin';
        }
        return 'lower('.$adapter->quoteIdentifier($searchInField).') like lower('.$adapter->quote('%'.$queryString.'%').') COLLATE utf8_bin';
    }
    
    /**
     * updates the toSort attribute of the given attribute name (only if toSort exists!)
     * @param string $field
     */
    public function updateToSort($name) {
        $toSort = $name.'ToSort';
        if(!$this->hasField($toSort)) {
            return;
        }
        $v = $this->__call('get'.ucfirst($name), array());
        $this->__call('set'.ucfirst($toSort), array($this->stripTags($v)));
    }
    
    /**
     * loads the segment data hunks for this segment as Row Objects in segmentdata
     * @param $segmentId
     */
    protected function initData($segmentId)
    {
        $this->segmentdata = array();
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $db editor_Models_Db_SegmentData */
        $s = $db->select()->where('segmentId = ?', $segmentId);
        $datas = $db->fetchAll($s);
        foreach($datas as $data) {
            $this->segmentdata[$data['name']] = $data;
        }
        $this->isDataModified = null;
        $this->isDataModifiedAgainstOriginal = null;
    }

    /**
     * sets segment attributes, filters the fluent fields and stores them separatly
     * @param string $name
     * @param mixed $value
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::set()
     */
    public function set($name, $value) {
        $loc = $this->segmentFieldManager->getDataLocationByKey($name);
        if($loc !== false) {
            if(empty($this->segmentdata[$loc['field']])) {
                $this->segmentdata[$loc['field']] = $this->createData($loc['field']);
            }
            return $this->segmentdata[$loc['field']]->__set($loc['column'], $value);
        }
        return parent::set($name, $value);
    }
    
    

    /**
     * gets segment attributes, filters the fluent fields and gets them from a different location
     * @param string $name
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::get()
     */
    public function get($name) {
        $loc = $this->segmentFieldManager->getDataLocationByKey($name);
        if($loc !== false) {
            //if we have a missing index here, that means, 
            //the data field ist not existing yet, since the field itself was defined by another file!
            //so returning an empty string is OK here. 
            if(empty($this->segmentdata[$loc['field']])) {
                return '';
            }
            return $this->segmentdata[$loc['field']]->__get($loc['column']);
        }
        return parent::get($name);
    }

    /**
     * set the match rate type, does not modify the value if it is a missing-mrk type before
     */
    public function setMatchRateType($type) {
        $oldValue = $this->getMatchRateType();
        if(editor_Models_Segment_MatchRateType::isUpdateable($oldValue)) {
            return $this->__call(__FUNCTION__, [$type]);
        }
        return $oldValue;
    }
    
    /**
     * integrates the segment fields into the hasfield check
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::hasField()
     */
    public function hasField($field) {
        if ($field == 'isWatched') {
            return true; // for filters
        }
        $loc = $this->segmentFieldManager->getDataLocationByKey($field);
        return $loc !== false || parent::hasField($field);
    }
    
    /**
     * Loops over all data fields and checks if at least one of them was changed at all,
     * that means: compare original and edited content
     * @param string $typeFilter optional, checks only data fields of given type
     * @return boolean
     */
    public function isDataModifiedAgainstOriginal($typeFilter = null) {
        if(!is_null($this->isDataModifiedAgainstOriginal)){
            return $this->isDataModifiedAgainstOriginal;
        }
        $this->isDataModifiedAgainstOriginal = false;
        foreach ($this->segmentdata as $data) {
            $field = $this->segmentFieldManager->getByName($data->name);
            $isEditable = $field->editable;
            if(!$isEditable || !empty($typeFilter) && $data->type !== $typeFilter) {
                continue;
            }
            if($this->stripTermTagsAndTrackChanges($data->edited) !== $this->stripTermTagsAndTrackChanges($data->original)) {
                $this->isDataModifiedAgainstOriginal = true;
            }
        }
        return $this->isDataModifiedAgainstOriginal;
    }
    
    /**
     * Checks if segment data is changed in this entity, compared against last loaded content
     */
    public function isDataModified($typeFilter = null) {
        if(!is_null($this->isDataModified)){
            return $this->isDataModified;
        }
        $this->isDataModified = false;
        foreach ($this->segmentdata as $data) {
            $field = $this->segmentFieldManager->getByName($data->name);
            $isEditable = $field->editable;
            $fieldName = $this->segmentFieldManager->getEditIndex($data->name);
            $edited = $this->isModified($fieldName);
            if(!$isEditable || !$edited || !empty($typeFilter) && $data->type !== $typeFilter) {
                continue;
            }
            if($this->stripTermTagsAndTrackChanges($data->edited) !== $this->stripTermTagsAndTrackChanges($this->getOldValue($fieldName))) {
                $this->isDataModified = true;
            }
        }
        return $this->isDataModified;
    }
    
    /**
     * restores segments with content not changed by the user to the original
     * (which contains termTags - this way no new termTagging is necessary, since
     * GUI removes termTags onSave)
     */
    public function restoreNotModfied() {
        if($this->isDataModified()){
            return;
        }
        foreach ($this->segmentdata as &$data) {
            $field = $this->segmentFieldManager->getByName($data->name);
            $isEditable = $field->editable;
            if(!$isEditable) {
                continue;
            }
            $fieldName = $this->segmentFieldManager->getEditIndex($data->name);
            $data->edited = $this->getOldValue($fieldName);
        }
    }
    /**
     * strips all tags including internal tag content and del tag content
     * @return string $segmentContent
     */
    public function stripTags($segmentContent) {
        $segmentContent = $this->trackChangesTagHelper->removeTrackChanges($segmentContent);
        return strip_tags(preg_replace('#<span[^>]*>[^<]*<\/span>#','',$segmentContent));
    }
    
    /**
     * Get length of a segment's text according to the segment's sizeUnit. 
     * If the sizeUnit is set to 'pixel', we use pixelMapping, otherwise 
     * we count by characters (this is for historical reasons of this code; 
     * other than the XLF-specifications which are not relevant here!).
     * @param string $segmentContent
     * @param editor_Models_Segment_Meta $segmentMeta
     * @return integer
     */
    public function textLengthByMeta($segmentContent, editor_Models_Segment_Meta $segmentMeta) {
        $pixelLength = $this->getPixelLength($segmentMeta->getTaskGuid()); // make sure that the pixelLength we use is that for the segment's task!
        $isPixelBased = ($segmentMeta->getSizeUnit() == editor_Models_Segment_PixelLength::SIZE_UNIT_XLF_DEFAULT);
        if ($isPixelBased) {
            return $pixelLength->textLengthByPixel($segmentContent, $segmentMeta->getFont(), intval($segmentMeta->getFontSize()));
        }
        return $this->textLengthByChar($segmentContent);
    }
    
    /**
     * Same as textLengthByMeta(), but here we use the editor_Models_Import_FileParser_SegmentAttributes
     * instead of editor_Models_Segment_Meta (on import, the segment and it's meta don't exist yet).
     * @param string $content
     * @param editor_Models_Import_FileParser_SegmentAttributes $attributes
     * @param string $taskGuid (other than in $segmentMeta, the $attributes don't have a taskGuid)
     * @return integer
     */
    public function textLengthByImportattributes($content, editor_Models_Import_FileParser_SegmentAttributes $attributes, $taskGuid) {
        error_log('textLengthByImportattributes for task ' . $taskGuid);
        $pixelLength = $this->getPixelLength($taskGuid); // make sure that the pixelLength we use is that for the segment's task!
        $isPixelBased = ($attributes->sizeUnit == editor_Models_Segment_PixelLength::SIZE_UNIT_XLF_DEFAULT);
        if ($isPixelBased) {
            return $pixelLength->textLengthByPixel($content, $attributes->font, intval($attributes->fontSize));
        }
        return $this->textLengthByChar($content);
    }
    
    
    
    /**
     * dedicated method to count chars of given segment content
     * does a htmlentitydecode, so that 5 char "&amp;" is converted to one char "&" for counting
     * Further:
     * - content in &gt;del&lt; tags is ignored
     * - all other tags are ignored, if the tag has a length attribute, this length is added
     * @param string $segmentContent
     * @return integer
     */
    public function textLengthByChar($segmentContent) {
        return mb_strlen($this->prepareForCount($segmentContent, true));
    }
    
    /**
     * Counts words; word boundary is used as defined in runtimeOptions.editor.export.wordBreakUpRegex
     * @param string $segmentContent
     * @return integer
     */
    public function wordCount($segmentContent) {
        $config = Zend_Registry::get('config');
        $regexWordBreak = $config->runtimeOptions->editor->export->wordBreakUpRegex;
        
        $words = preg_split($regexWordBreak, $this->prepareForCount($segmentContent), NULL, PREG_SPLIT_NO_EMPTY);
        return count($words);
    }
    
    /**
     * Reconverts html entities so that several count operations can be performed.
     * @param string $text
     * @param boolean $padTagLength if true, replace tags with a length with a padded string in that length
     * @return string
     */
    protected function prepareForCount($text, $padTagLength = false) {
        $text = $this->trackChangesTagHelper->removeTrackChanges($text);
        $text = $this->tagHelper->replace($text, function($matches) use ($padTagLength) {
            if($padTagLength) {
                $length = max((int) $this->tagHelper->getLength($matches[0]), 0);
                return str_repeat('x', $length); //create a "x" string as long as the tag stored tag length
            }
            else {
                return ''; //just remove the internal tags
            }
        });
        return html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_XHTML);
    }
    
    /**
     * Remove TrackChange-tags and restore whitespace.
     * @param string $text
     * @return string
     */
    public function prepareForPixelBasedLengthCount($text) {
        $text = $this->trackChangesTagHelper->removeTrackChanges($text);
        $text = $this->restoreWhiteSpace($text);
        return $text;
    }
    
    /**
     * Restore whitespace to original real characters.
     * @param string $segmentContent
     * @return string $segmentContent
     */
    protected function restoreWhiteSpace ($segmentContent) {
        $segmentContent = $this->tagHelper->restore($segmentContent, true);
        $segmentContent = $this->whitespaceHelper->unprotectWhitespace($segmentContent);
        $segmentContent = $this->tagHelper->protect($segmentContent);
        $segmentContent = html_entity_decode(strip_tags($segmentContent), ENT_QUOTES | ENT_XHTML);
        return $segmentContent;
    }
    
    /**
     * strips all tags including tag description
     * FIXME WARNING do not use this method other than it is used currently
     * @see therefore TRANSLATE-487
     * 
     * @param string $segmentContent
     * @return string $segmentContent
     */
    public function stripTermTagsAndTrackChanges($segmentContent) {
        try {
            $options = array(
                    'format_output' => false,
                    'encoding' => 'utf-8',
                    'convert_to_encoding' => 'utf-8',
                    'convert_from_encoding' => 'utf-8',
                    'ignore_parser_warnings' => true,
            );
            $segmentContent= $this->trackChangesTagHelper->removeTrackChanges($segmentContent);
            
            $seg = qp('<div id="root">'.$segmentContent.'</div>', NULL, $options);
            /* @var $seg QueryPath\\DOMQuery */
            //advise libxml not to throw exceptions, but collect warnings and errors internally:
            libxml_use_internal_errors(true);
            foreach ($seg->find('div.term') as $element){
                $element->replaceWith($element->innerHTML());
            }
            $this->collectLibXmlErrors();
            $seg = $seg->find('div#root');
            $segmentContent = $seg->innerHTML();
        } catch (Exception $exc) {
            $log = new ZfExtended_Log();
            $msg = 'Notice: No valid HTML in translate5 segment';
            if(ZfExtended_Debug::hasLevel('core', 'Segment')){
                $msg .= (string) $exc;
                $msg .= "\n#".'<div id="root">'.$segmentContent."#\n";
            }
            $log->logError($msg);
        }
        return $segmentContent;
    }
    
    /**
     * using the find method of querypath implies to create an internal clone of the DOM node, 
     * which then throws an duplicate id error which is completly nonsense at this place, so we filter them out. 
     */
    protected function collectLibXmlErrors() {
        $otherErrors = array();
        foreach(libxml_get_errors() as $error) {
            $msg = $error->message;
            //Example error message: "ID NL-8-df250b2156c434f3390392d09b1c9563 already defined"
            if(strpos(trim($msg), 'ID ') === 0 && strpos(strrev(trim($msg)), strrev(' already defined')) === 0) {
                continue;
            }
            $otherErrors[] = $error;
        }
        libxml_clear_errors();
        if(!empty($otherErrors)) {
            throw new Exception("Collected LIBXML errors: ".print_r($otherErrors, 1));
        }
    }
    
    /**
     * loads the Entity by Primary Key Id
     * @param integer $id
     * @return Zend_Db_Table_Row
     */
    public function load($id) {
        $row = parent::load($id);
        $this->segmentFieldManager->initFields($this->getTaskGuid());
        $this->initData($id);
        return $row;
    }
    
    public function loadByIds(array $ids){
        $s=$this->db->select()
        ->where('id IN (?)',$ids);
        return $this->loadFilterdCustom($s);
    }
    /**
     * erzeugt ein neues, ungespeichertes SegmentHistory Entity
     * @return editor_Models_SegmentHistory
     */
    public function getNewHistoryEntity() {
        $history = ZfExtended_Factory::get('editor_Models_SegmentHistory');
        /* @var $history editor_Models_SegmentHistory */
        $history->setSegmentFieldManager($this->segmentFieldManager);
        
        $history->setSegmentId($this->getId());

        $fields = $history->getFieldsToUpdate();
        //TRANSLATE-885 
        $fields[] = 'targetMd5';
        $fields[] = 'target';
        $fields = array_merge($fields, $this->segmentFieldManager->getEditableDataIndexList());

        foreach ($fields as $field) {
            $history->__call('set' . ucfirst($field), array($this->get($field)));
        }
        
        $durations = array();
        foreach ($this->segmentdata as $data) {
            $durations[$data->name] = $data->duration;
        }
        $history->setTimeTrackData($durations);
        return $history;
    }
    
    /**
     * gets the time tracking information as stdClass and sets the values into the separated data objects per field
     * @param stdClass $durations
     * @param integer $divisor optional, default = 1; if greater than 1 divide the duration through this value (for changeAlikes)
     */
    public function setTimeTrackData(stdClass $durations, $divisor = 1) {
        $sfm = $this->segmentFieldManager;
        foreach($this->segmentdata as $field => $data) {
            $field = $sfm->getEditIndex($field);
            if($field !== false && isset($durations->$field)) {
                $data->duration = $durations->$field;
                if($divisor > 1) {
                    $data->duration = (int) round($data->duration / $divisor);
                }
            }
        }
    }
    
    public function setQmId($qmId) {
        return parent::setQmId(trim($qmId, ';'));
    }

    /**
     * gets the data from import, sets it into the data fields
     * check the given fields against the really available fields for this task.
     * @param editor_Models_SegmentFieldManager $sfm
     * @param array $segmentData key: fieldname; value: array with original and originalMd5
     */
    public function setFieldContents(editor_Models_SegmentFieldManager $sfm, array $segmentData) {
        $this->segmentFieldManager = $sfm;
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $db editor_Models_Db_SegmentData */
        foreach($segmentData as $name => $data) {
            $row = $db->createRow($data);
            /* @var $row editor_Models_Db_SegmentDataRow */
            $row->name = $name;
            $field = $sfm->getByName($name);
            $row->originalToSort = $this->stripTags($row->original);
            $row->taskGuid = $this->getTaskGuid();
            $row->mid = $this->getMid();
            if(isset($field->editable) && $field->editable) {
                $row->edited = $row->original;
                $row->editedToSort = $row->originalToSort;
            }
            /* @var $row editor_Models_Db_SegmentDataRow */
            $this->segmentdata[$name] = $row;
        }
    }
    
    /**
     * loads segment entity
     * @param integer $fileId
     * @param type $mid
     */
    public function loadByFileidMid(integer $fileId, $mid) {
        $taskGuid = $this->getTaskGuid();
        $s = $this->db->select()->from($this->tableName, array('id'));
        $segmentId = new Zend_Db_Expr('('.$s
                            ->where($this->tableName.'.taskGuid = ?', $taskGuid)
                            ->where($this->tableName.'.fileId = ?', $fileId)
                            ->where($this->tableName.'.mid = ?', $mid).')');
        $this->load($segmentId);
    }
    
    /**
     * loads segment entity
     * @param integer $segmentNrInTask
     * @param string $taskGuid
     */
    public function loadBySegmentNrInTask($segmentNrInTask, $taskGuid) {
        $s = $this->db->select()->from($this->tableName, array('id'));
        $segmentId = new Zend_Db_Expr('('.$s
                            ->where($this->tableName.'.taskGuid = ?', $taskGuid)
                            ->where($this->tableName.'.segmentNrInTask = ?', $segmentNrInTask).')');
        $this->load($segmentId);
    }
    
    /**
     * adds one single field content ([original => TEXT, originalMd5 => HASH]) to a given segment, 
     * identified by MID and fileId. taskGuid MUST be given by setTaskGuid before!
     * 
     * @param Zend_Db_Table_Row_Abstract $field
     * @param integer $fileId
     * @param string $mid
     * @param array $data
     * @throws ZfExtended_Models_Entity_NotFoundException if the segment where the content should be added could not be found
     */
    public function addFieldContent(Zend_Db_Table_Row_Abstract $field, $fileId, $mid, array $data) {
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $db editor_Models_Db_SegmentData */

        $taskGuid = $this->getTaskGuid();
        $segmentId = $this->getId();
        if(is_null($segmentId)){
            $this->loadByFileidMid($fileId, $mid);
            $segmentId = $this->getId();
        }
        
        $data = array(
            'taskGuid' => $taskGuid,
            'name' => $field->name,
            'segmentId' => $segmentId,
            'mid' => $mid,
            'original' => $data['original'],
            'originalMd5' => $data['originalMd5'],
            'originalToSort' => $this->stripTags($data['original']),
        );
        if($field->editable) {
            $data['edited'] = $data['original'];
            $data['editedToSort'] = $this->stripTags($data['original']);
        }
        
        try {
            $db->insert($data);
        }
        catch(Zend_Db_Statement_Exception $e) {
            if(strpos($e->getMessage(), "Column 'segmentId' cannot be null") !== false) {
                $msg = 'Segment with fileId %s and MID %s in task %s not found!';
                throw new ZfExtended_Models_Entity_NotFoundException(sprintf($msg, $fileId, $mid, $taskGuid));
            }
        }
    }
    
    /**
     * method to add a data hunk later on 
     * (edit a alternate which was defined by another file, and is therefore empty in this segment)
     * @param string $field the field name
     * @return editor_Models_Db_SegmentDataRow
     */
    protected function createData($field) {
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $db editor_Models_Db_SegmentData */
        $row = $db->createRow();
        /* @var $row editor_Models_Db_SegmentDataRow */
        $row->taskGuid = $this->get('taskGuid');
        $row->name = $field;
        $row->segmentId = $this->get('id');
        $row->mid = $this->get('mid');
        $row->original = '';
        $row->originalMd5 = 'd41d8cd98f00b204e9800998ecf8427e'; //empty string md5 hash
        $row->originalToSort = '';
        $row->edited = '';
        $row->editedToSort = '';
        $row->save();
        return $row;
    }
    
    /**
     * save the segment and the associated segmentd data hunks
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::save()
     */
    public function save() {
        if(!empty($this->dbWritable)) {
            if($this->dbWritable->isView()) {
                //throw exception that we are saveing the view;
                //TODO the actual table name in the exception
                throw new ZfExtended_Exception("Unable to save the segment. The segment table name is set to matirialized view. Segment id:".$this->getId().", taskGuid:".$this->getTaskGuid());
            }
            
            //clean unneded materialized view data
            $this->unsetMaterializedViewData();
            $this->row->setTable($this->dbWritable);
        }
        $oldIdValue = $this->getId();
        $segmentId = parent::save();
        foreach($this->segmentdata as $data) {
            /* @var $data editor_Models_Db_SegmentDataRow */
            if(empty($data->segmentId)) {
                $data->segmentId = $segmentId;
            }
            $data->save();
        }
        //only update the mat view if the segment was already in DB (so do not save mat view on import!)
        //same for meta data, since on import meta data is saved by the segment processor 
        if(!empty($oldIdValue)) {
            $this->meta()->setSiblingData($this);
            $this->meta()->save();
            $matView = $this->segmentFieldManager->getView();
            /* @var $matView editor_Models_Segment_MaterializedView */
            if($matView->exists()) {
                $matView->updateSegment($this);
                $matView->updateSiblingMetaCache($this);
            }
        }
        return $segmentId;
    }
    
    /**
     * merges the segment data into the result set
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::getDataObject()
     */
    public function getDataObject() {
        $res = parent::getDataObject();
        $this->segmentFieldManager->mergeData($this->segmentdata, $res);
        $segmentUserAssoc = ZfExtended_Factory::get('editor_Models_SegmentUserAssoc');
        try {
            $assoc = $segmentUserAssoc->loadByParams($res->userGuid, $res->id);
            $res->isWatched = true;
            $res->segmentUserAssocId = $assoc['id'];
        }
        catch(ZfExtended_Models_Entity_NotFoundException $e)
        {
            $res->isWatched = null;
            $res->segmentUserAssocId = null;
        }
        $matView = $this->segmentFieldManager->getView();
        if(property_exists($res, 'metaCache') || !$matView->exists()) {
            return $res;
        }
        $res->metaCache = $matView->getMetaCache($this);
        return $res;
    }

    /**
     * returns the original content of a field 
     * @param string $field Fieldname
     */
    public function getFieldOriginal($field) {
        //since fields can be merged from different files, data for a field can be empty
        if(empty($this->segmentdata[$field])) {
            return '';
        }
        return $this->segmentdata[$field]->original;
    }

    /**
     * returns the edited content of a field 
     * @param string $field Fieldname
     */
    public function getFieldEdited($field) {
        //since fields can be merged from different files, data for a field can be empty
        if(empty($this->segmentdata[$field])) {
            return '';
        }
        return $this->segmentdata[$field]->edited;
    }
    
    /**
     * returns a list with editable dataindex
     * @return array
     */
    public function getEditableDataIndexList() {
        return $this->segmentFieldManager->getEditableDataIndexList();
    }
    
    /**
     * Returns an array with just the editable field values (value) and field names (key)
     * @return array key: fieldname, value: field content
     */
    public function getEditableFieldData() {
        $editables = $this->segmentFieldManager->getEditableDataIndexList();
        $result = [];
        foreach($editables as $field){
            $result[$field] = $this->get($field);
        }
        return $result;
    }
    
    /**
     * Load segments by taskGuid.
     * @param string $taskGuid
     * @param Closure $callback is called with the select statement as parameter before passing it to loadFilterdCustom Param: Zend_Db_Table_Select
     * @return array
     */
    public function loadByTaskGuid($taskGuid, Closure $callback = null) {
        try {
            return $this->_loadByTaskGuid($taskGuid,$callback);
        }
        catch(Zend_Db_Statement_Exception $e) {
            $this->catchMissingView($e);
        }
        //fallback mechanism for not existing views. If not exists, we are trying to create it.
        $this->segmentFieldManager->initFields($taskGuid);
        $this->segmentFieldManager->getView()->create();
        return $this->_loadByTaskGuid($taskGuid,$callback);
    }
    
    /**
     * inits and returns the editor_Models_Segment_EditablesFinder
     * @return editor_Models_Segment_EditablesFinder
     */
    protected function initSegmentFinder() {
        $this->reInitDb($this->getTaskGuid());
        $this->initDefaultSort();
        
        return ZfExtended_Factory::get('editor_Models_Segment_EditablesFinder', array($this));
    }
    
    /**
     * returns the first and the last EDITABLE segment of the actual filtered request
     * @param array $autoStateIds a list of autoStates where the prev/next page segments are additionaly compared to
     * @param integer $total
     * @return array
     */
    public function findSurroundingEditables($next, array $autoStateIds = null) {
        return $this->initSegmentFinder()->find($next, $autoStateIds);
    }

    /**
     * returns the index/position of the current segment into the currently filtered/sorted list of all segments
     * @return integer|null
     */
    public function getIndex() {
        return $this->initSegmentFinder()->getIndex($this->getId());
    }
    
    /**
     * Loads the first segment of the given taskGuid.
     * The found segment is stored internally (like load).
     * First Segment is defined as the segment with the lowest id of the task
     * 
     * @param string $taskGuid
     * @param integer $fileId optional, loads first file of given fileId in task
     * @return editor_Models_Segment
     */
    public function loadFirst($taskGuid, $fileId = null) {
        $this->segmentFieldManager->initFields($taskGuid);
        //ensure that view exists (does nothing if already):
        $this->segmentFieldManager->getView()->create();
        $this->reInitDb($taskGuid);

        $seg = $this->loadNext($taskGuid, 0, $fileId);
        
        if(empty($seg)) {
            $this->notFound('first segment of task', $taskGuid);
        }
        return $seg;
    }
    
    /**
     * Loads the next segment after the given id from the given taskGuid
     * next is defined as the segment with the next higher segmentId
     * This method assumes that segmentFieldManager was already loaded internally
     * @param string $taskGuid
     * @param integer $id
     * @param integer $fileId optional, loads first file of given fileId in task
     * @return editor_Models_Segment | null if no next found
     */
    public function loadNext($taskGuid, $id, $fileId = null) {
        $s = $this->db->select();
        $s = $this->addWatchlistJoin($s);
        $s = $this->addWhereTaskGuid($s, $taskGuid);
        $s->where($this->tableName.'.id > ?', $id)
            ->order($this->tableName.'.id ASC')
            ->limit(1);

        if(!empty($fileId)) {
            $s->where($this->tableName.'.fileId = ?', $fileId);
        }
        
        $row = $this->db->fetchRow($s);
        if(empty($row)) {
            return null;
        }
        $this->row = $row;
        $this->initData($this->getId());
        return $this;
    }
    
    /**
     * returns the segment count of the given taskGuid
     * filters are not applied since the overall count is needed for statistics
     * @param string $taskGuid
     * @param boolean $editable
     * @return integer the segment count
     */
    public function count($taskGuid,$onlyEditable=false) {
        $s = $this->db->select()
            ->from($this->db, array('cnt' => 'COUNT(id)'))
            ->where('taskGuid = ?', $taskGuid);
        if($onlyEditable){
            $s->where('editable = 1');
        }
        $row = $this->db->fetchRow($s);
        return $row->cnt;
    }
    
    /**
     * If the given exception was thrown because of a missing view do nothing.
     * If it was another Db Exception throw it!
     * @param Zend_Db_Statement_Exception $e
     */
    protected function catchMissingView(Zend_Db_Statement_Exception $e) {
        $m = $e->getMessage();
        if(strpos($m,'SQLSTATE') !== 0 || strpos($m,'Base table or view not found') === false) {
            throw $e;
        }
    }
    
    /**
     * encapsulate the load by taskGuid code.
     * @param string $taskGuid
     * @param Closure $callback is called with the select statement as parameter before passing it to loadFilterdCustom Param: Zend_Db_Table_Select
     * @return array
     */
    protected function _loadByTaskGuid($taskGuid, Closure $callback = null) {
        $this->segmentFieldManager->initFields($taskGuid);
        $this->reInitDb($taskGuid);
        
        $this->initDefaultSort();
       
        $s = $this->db->select(false);
        $db = $this->db;
        $cols = $this->db->info($db::COLS);

        /**
         * FIXME reminder for TRANSLATE-113: Filtering out unused cols is needed for TaskManagement Feature (user dependent cols)
         * This is a example for field filtering. 
         if (!$loadSourceEdited) {
            $cols = array_filter($cols, function($val) {
                        return strpos($val, 'sourceEdited') === false;
                    });
        }
         */
        $s->from($this->db, $cols);
        $s = $this->addWatchlistJoin($s);
        $s = $this->addWhereTaskGuid($s, $taskGuid);
        
        if(!empty($callback)) {
            $callback($s,$this->tableName);
        }
        
        return parent::loadFilterdCustom($s);
    }
    
    /**
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::computeTotalCount()
     * @param string $taskGuid
     */
    public function totalCountByTaskGuid($taskGuid){
        $s = $this->db->select();
        
        
        if(!empty($this->filter)) {
            $this->filter->applyToSelect($s, false);
        }
        $name = $this->db->info(Zend_Db_Table_Abstract::NAME);
        $schema = $this->db->info(Zend_Db_Table_Abstract::SCHEMA);
        $s->from($name, array('numrows' => 'count(*)'), $schema);
        
        //this method does exactly the same as computeTotalCount expect that it adds this both where statements
        // but this is only possible AFTER the from() call so far!
        $s = $this->addWhereTaskGuid($s, $taskGuid);
        $s = $this->addWatchlistJoin($s);
        
        $totalCount = $this->db->fetchRow($s)->numrows;
        $s->reset($s::COLUMNS);
        $s->reset($s::FROM);
        return $totalCount;
    }
    
    /**
     * adds the where taskGuid = ? statement only to the given statement,
     * if it is needed. Needed means the current table is not the mat view to the taskguid
     * This "unneeded" where is a performance issue for big tasks. 
     */
    protected function addWhereTaskGuid(Zend_Db_Table_Select $s, $taskGuid) {
        $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView', array($taskGuid));
        /* @var $mv editor_Models_Segment_MaterializedView */
        
        if($this->tableName !== $mv->getName()) {
            $s->where($this->tableName.'.taskGuid = ?', $taskGuid);
        }
        return $s;
    }

    /**
     * Loads segments by a specific workflowStep, fetch only specific fields.
     * @param string $taskGuid
     * @param string $workflowStep
     * @param integer $workflowStepNr
     */
    public function loadByWorkflowStep(string $taskGuid, string $workflowStep, $workflowStepNr) {
        $config = Zend_Registry::get('config');
        $pmChanges = $config->runtimeOptions->editor->notification->pmChanges;
        $this->segmentFieldManager->initFields($taskGuid);
        $this->reInitDb($taskGuid);
        
        $fields = array('id', 'mid', 'segmentNrInTask', 'stateId', 'autoStateId', 'matchRate', 'qmId', 'comments', 'fileId', 'userGuid', 'userName', 'timestamp');
        $fields = array_merge($fields, $this->segmentFieldManager->getDataIndexList());
        
        $this->initDefaultSort();
        $s = $this->db->select(false);
        $db = $this->db;
        $s->from($this->db, $fields);
        $s = $this->addWatchlistJoin($s);
        $s = $this->addWhereTaskGuid($s, $taskGuid);
        switch ($pmChanges) {
            case self::PM_ALL_INCLUDED:
                $s->where('('.$this->tableName.'.workflowStep = ?', $workflowStep);
                $s->orWhere($this->tableName.'.workflowStep = ?)', editor_Workflow_Abstract::STEP_PM_CHECK);
                break;
            case self::PM_SAME_STEP_INCLUDED:
                $s->where('('.$this->tableName.'.workflowStep = ?', $workflowStep);
                $s->orWhere('('.$this->tableName.'.workflowStep = ?', editor_Workflow_Abstract::STEP_PM_CHECK);
                $s->where($this->tableName.'.workflowStepNr = ?))', $workflowStepNr);
                break;
            case self::PM_NOT_INCLUDED:
            default:
                $s->where($this->tableName.'.workflowStep = ?', $workflowStep);
                break;
        }
        return parent::loadFilterdCustom($s);
    }

    /**
     * Gibt zurück ob das Segment editiertbar ist
     * @return boolean
     */
    public function isEditable() {
        $flag = $this->getEditable();
        return !empty($flag);
    }
    
    /**
     * returns Zend_Db_Table_Select joined with segment_user_assoc table if watchlistFilter is enabled
     * @param Zend_Db_Table_Select $s select statement to be modified with the watchlist join filter
     * @param string $tableName optional, for special joining purposes only, per default not needed 
     * @return Zend_Db_Table_Select
     */
    public function addWatchlistJoin(Zend_Db_Table_Select $s, $tableName = null){
        if(!$this->watchlistFilterEnabled) {
            return $s;
        }
        if(empty($tableName)) {
            $tableName = $this->tableName;
        }
        $db_join = ZfExtended_Factory::get('editor_Models_Db_SegmentUserAssoc');
        $userGuid = $_SESSION['user']['data']->userGuid;
        $this->filter->setDefaultTable($tableName);
        $this->filter->addTableForField('isWatched', 'sua');
        $on = 'sua.segmentId = '.$tableName.'.id AND sua.userGuid = \''.$userGuid.'\'';
        $s->joinLeft(array('sua' => $db_join->info($db_join::NAME)), $on, array('isWatched', 'id AS segmentUserAssocId'));
        $s->setIntegrityCheck(false);
        return $s;
    }
    
    /**
     * enables the watchlist filter join, for performance issues only if the user 
     *   really wants to see the watchlist (isWatched is in the filter list)
     * @param boolean $value optional, to force enable/disable watchlist
     */
    public function setEnableWatchlistJoin($value = null) {
        if(is_null($value)) {
            $value = $this->filter->hasFilter('isWatched');
        }
        $this->watchlistFilterEnabled = $value;
    }
    
    /**
     * returns if the watchlist join should be enabled or not
     * @return boolean
     */
    public function getEnableWatchlistJoin() {
        return $this->watchlistFilterEnabled;
    }

    /**
     * returns a list with the mapping of fileIds to the segment Row Index. The Row Index is generated considering the given Filters
     * @param string $taskGuid
     * @return array
     */
    public function getFileMap($taskGuid) {
        //use loadByTaskGuid to initialize segmentfields and MV and so on
        //set limit = 1 to load only one record and not all records, latter one can leak memory
        $this->limit = 1;
        $this->loadByTaskGuid($taskGuid);
        
        $s = $this->db->select()
                ->from($this->db, array('cnt' => 'count(`'.$this->db.'`.id)', 'fileId'));
        $s = $this->addWatchlistJoin($s);
        $s = $this->addWhereTaskGuid($s, $taskGuid);
        
        $s->group('fileId');
        
        if (!empty($this->filter)) {
            $this->filter->applyToSelect($s);
        }

        $rowindex = 0;
        $result = array();
        $dbResult = $this->db->fetchAll($s)->toArray();
        foreach ($dbResult as $row) {
            $result[$row['fileId']] = $rowindex;
            $rowindex += $row['cnt'];
        }
        return $result;
    }

    protected function initDefaultSort() {
        if(empty($this->filter)) {
            return;
        }
        if (!$this->filter->hasSort()) {
            $this->filter->addSort('fileOrder');
        }
        if(!$this->filter->hasSort('id')) {
            $this->filter->addSort('id'); //add id as second permanent filter
        }
    }

    /**
     * Syncs the Files fileorder to the Segments Table, for faster sorted reading from segment table
     * @param string $taskguid
     * @param boolean $omitView if true do not update the view
     */
    public function syncFileOrderFromFiles(string $taskguid, $omitView = false) {
        $infokey = Zend_Db_Table_Abstract::NAME;
        $segmentsTableName = $this->db->info($infokey);
        $filesTableName = ZfExtended_Factory::get('editor_Models_Db_Files')->info($infokey);
        $sql = $this->_syncFilesortSql($segmentsTableName, $filesTableName);
        $this->db->getAdapter()->query($sql, array($taskguid));
        
        if($omitView) {
            return true;
        }
        //do the resort also for the view!
        $this->segmentFieldManager->initFields($taskguid);
        $segmentsViewName = $this->segmentFieldManager->getView()->getName();
        $sql = $this->_syncFilesortSql($segmentsViewName, $filesTableName);
        $this->db->getAdapter()->query($sql, array($taskguid));
    }

    /**
     * internal function, returns specific sql. To be overridden if needed.
     * @param string $segmentsTable
     * @param string $filesTable
     * @return string
     */
    protected function _syncFilesortSql(string $segmentsTable, string $filesTable) {
        return 'update ' . $segmentsTable . ' s, ' . $filesTable . ' f set s.fileOrder = f.fileOrder where s.fileId = f.id and f.taskGuid = ?';
    }

    /**
     * fetch the alikes of the actually loaded segment
     * 
     * cannot handle alternate targets! can only handle source and target field! actually not refactored!
     * 
     * @return array
     */
    public function getAlikes($taskGuid) {
        $this->segmentFieldManager->initFields($taskGuid);
        //if we are using alternates we cant use change alikes, that means we return an empty list here
        if(!$this->segmentFieldManager->isDefaultLayout()) {
            return array(); 
        }
        $segmentsViewName = $this->segmentFieldManager->getView()->getName();
        $sql = $this->_getAlikesSql($segmentsViewName);
        //since alikes are only usable with segment field default layout we can use the following hardcoded methods
        $stmt = $this->db->getAdapter()->query($sql, array(
            $this->getSourceMd5(),
            $this->getTargetMd5(),
            $this->getSourceMd5(),
            $this->getTargetMd5(),
            $taskGuid));
        $alikes = $stmt->fetchAll();
        //gefilterte Segmente bestimmen und flag setzen
        $hasIdFiltered = $this->getIdsAfterFilter($segmentsViewName, $taskGuid);
        foreach ($alikes as $key => $alike) {
            $alikes[$key]['infilter'] = isset($hasIdFiltered[$alike['id']]);
            //das aktuelle eigene Segment, zu dem die Alikes gesucht wurden, aus der Liste entfernen
            if ($alike['id'] == $this->get('id')) {
                unset($alikes[$key]);
            }
        }
        return array_values($alikes); //neues numerisches Array für JSON Rückgabe, durch das unset oben macht json_decode ein Object draus
    }
    
    /**
     * reset the internal used db object to the view to the given taskGuid
     * @param string $taskGuid
     */
    protected function reInitDb($taskGuid) {
        $this->segmentFieldManager->initFields($taskGuid);
        $mv = $this->segmentFieldManager->getView();
        $mv->setTaskGuid($taskGuid);
        
        /* @var $mv editor_Models_Segment_MaterializedView */
        $this->db = ZfExtended_Factory::get($this->dbInstanceClass, array(array(), $mv->getName()));
        $this->dbWritable = ZfExtended_Factory::get($this->dbInstanceClass);
        $db = $this->db;
        try {
            $this->tableName = $db->info($db::NAME);
        }
        catch(Zend_Db_Statement_Exception $e) {
            if(stripos($e->getMessage(), 'SQLSTATE[42S02]: Base table or view not found') === false) {
                throw $e;
            }
            $mv->create();
            $this->tableName = $db->info($db::NAME);
        }
    }

    /**
     * overwrite for segment field integration
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::validatorLazyInstatiation()
     */
    protected function validatorLazyInstatiation() {
        $taskGuid = $this->getTaskGuid();
        if(empty($taskGuid)) {
            throw new Zend_Exception("For using the editor_Models_Validator_Segment Validator a taskGuid must be set in the segment!");
        }
        $this->segmentFieldManager->initFields($taskGuid);
        if(empty($this->validator)) {
            $this->validator = ZfExtended_Factory::get($this->validatorInstanceClass, array($this->segmentFieldManager, $this));
        }
    }
    
    /**
     * For ChangeAlikes: Gibt ein assoziatives Array mit den Segment IDs zurück, die nach Anwendung des Filters noch da sind.
     * ArrayKeys: SegmentId, ArrayValue immer true
     * @param string $segmentsTableName
     * @param string $taskGuid
     * @return array
     */
    protected function getIdsAfterFilter(string $segmentsTableName, string $taskGuid) {
        $this->reInitDb($taskGuid);
        $s = $this->db->select()
                ->from($segmentsTableName, array('id'));
        $s = $this->addWatchlistJoin($s);
        $s = $this->addWhereTaskGuid($s, $taskGuid);
        
        //Achtung: die Klammerung von (source = ? or target = ?) beachten!
        $s->where('('.$this->tableName.'.sourceMd5 ' . $this->_getSqlTextCompareOp() . ' ?', (string) $this->getSourceMd5())
        ->orWhere($this->tableName.'.targetMd5 ' . $this->_getSqlTextCompareOp() . ' ?)', (string) $this->getTargetMd5());
        $filteredIds = parent::loadFilterdCustom($s);
        $hasIdFiltered = array();
        foreach ($filteredIds as $ids) {
            $hasIdFiltered[$ids['id']] = true;
        }
        return $hasIdFiltered;
    }

    /**
     * Gibt das SQL (mysql) für die Abfrage der Alikes eines Segmentes zurück.
     * Muss für MSSQL überschrieben werden!
     *
     * Für MSSQL (getestet mit konkreten Werten und ohne die letzte Zeile in MSSQL direkt):
     * select id, source, target,
     * case when sourceMd5 like '?' then 1 else 0 end as sourceMatch,
     * case when targetMd5 like '?' then 1 else 0 end as targetMatch
     * from LEK_segments where (sourceMd5 like '?' or targetMd5 like '?')
     * and taskGuid = ? and editable = 1 order by fileOrder, id;
     *
     * @param string $viewName
     * @return string
     */
    protected function _getAlikesSql(string $viewName) {
        return 'select id, segmentNrInTask, source, target, sourceMd5=? sourceMatch, targetMd5=? targetMatch, matchRate, autostateId
    from '.$viewName.' 
    where ((sourceMd5 = ? and source != \'\' and source IS NOT NULL) 
        or (targetMd5 = ? and target != \'\' and target IS NOT NULL)) 
        and taskGuid = ? and editable = 1
    order by fileOrder, id';
    }

    /**
     * Muss für MSSQL überschrieben werden und like anstatt = zurückgeben
     * @return string
     */
    protected function _getSqlTextCompareOp() {
        return ' = ';
        //return ' like ' bei MSSQL
    }

    /**
     * Updates - if enabled - the QM Sub Segments with correct IDs in the given String and stores it with the given Method in the entity
     * Also, corrects overlapped image tags between which there is no text node.
     * @param string $field
     */
    public function updateQmSubSegments(string $dataindex) {
        $field = $this->segmentFieldManager->getDataLocationByKey($dataindex);
        $config = Zend_Registry::get('config');
        if(! $config->runtimeOptions->editor->enableQmSubSegments) {
            return;
        }
        $qmsubsegments = ZfExtended_Factory::get('editor_Models_Qmsubsegments');
        /* @var $qmsubsegments editor_Models_Qmsubsegments */
        $withQm = $qmsubsegments->updateQmSubSegments($this->get($dataindex), (int)$this->getId(), $field['field']);
        $correctedOverlappedTags = $qmsubsegments->correctQmSubSegmentsOverlappedTags($withQm);
        $this->set($dataindex, $correctedOverlappedTags);
    }
    
    /**
     * Bulk updating a specific autoState of a task, affects only non edited segments
     * If userGuid and username are set in this segment instance, the this values are also set in the affected segments
     * FIXME test me for translation workflow!
     * @param string $taskGuid
     * @param integer $oldState
     * @param integer $newState
     * @param boolean $emptyEditedOnly if true only segments where all alternative targets are empty are affected
     */
    public function updateAutoState(string $taskGuid, integer $oldState, integer $newState, $emptyEditedOnly = false) {
        $sfm = $this->segmentFieldManager;
        $sfm->initFields($taskGuid);
        $db = $this->db;
        $segTable = $db->info($db::NAME);
        $viewName = $sfm->getView()->getName();
        
        //if this segment instance have a userGuid and userName he segments are also changed with that data
        $userGuid = $this->getUserGuid();
        $username = $this->getUserName();
        
        $changeUser = !empty($userGuid) && !empty($username);
        if($changeUser) {
            $sql_tpl = 'UPDATE `%s` set autoStateId = ?, userGuid = ?, userName = ? where autoStateId = ? and taskGuid = ?';
            $bind = array($newState, $userGuid, $username, $oldState, $taskGuid);
        }
        else {
            $sql_tpl = 'UPDATE `%s` set autoStateId = ? where autoStateId = ? and taskGuid = ?';
            $bind = array($newState, $oldState, $taskGuid);
        }
        $sql = sprintf($sql_tpl, $segTable);
        $sql_view = sprintf($sql_tpl, $viewName);
        
        $db->getAdapter()->beginTransaction();
        
        if(!$emptyEditedOnly) {
            //updates the view (if existing)
            $this->queryWithExistingView($sql_view, $bind);
            //updates LEK_segments directly
            $db->getAdapter()->query($sql, $bind);
            $db->getAdapter()->commit();
            return;
        }
        
        $sfm->initFields($taskGuid);
        $fields = $sfm->getFieldList();
        $affectedFieldNames = array();
        foreach($fields as $field) {
            if($field->type == editor_Models_SegmentField::TYPE_TARGET && $field->editable) {
                $sql_view .= ' and '.$sfm->getEditIndex($field->name)." = ''";
                $affectedFieldNames[] = $field->name;
            }
        }
        //updates the view (if existing)
        $this->queryWithExistingView($sql_view, $bind);
        
        //updates LEK_segments directly, but only where all above requested fields are empty
        $sql  = 'UPDATE `%s` segment, %s subquery set segment.autoStateId = ? ';
        if($changeUser) {
            $bind = array($taskGuid, $newState, $userGuid, $username, $oldState, $taskGuid);
            $sql .= ', segment.userGuid = ?, segment.userName = ? ';
        }
        else {
            $bind = array($taskGuid, $newState, $oldState, $taskGuid);
        }
        $sql .= 'where segment.autoStateId = ? and segment.taskGuid = ? ';
        $sql .= 'and subquery.segmentId = segment.id and subquery.cnt = %s';
        
        //subQuery to get the count of empty fields, fields as requested above
        //if empty field count equals the the count of requested fiels,
        //that means all fields are empty and the corresponding segment has to be changed. 
        $subQuery  = '(select segmentId, count(*) cnt from LEK_segment_data where taskGuid = ? and ';
        $subQuery .= "edited = '' and name in ('".join("','", $affectedFieldNames)."') group by segmentId)";
        
        $sql = sprintf($sql, $segTable, $subQuery, count($affectedFieldNames));
        $db->getAdapter()->query($sql, $bind);
        $db->getAdapter()->commit();
    }
    
    /***
     * Find last editor from segment history, and update it in the lek segment table
     * @param string $taskGuid
     * @param integer $autoState
     */
    public function updateLastAuthorFromHistory(string $taskGuid,integer $autoState){
        if(empty($taskGuid) || empty($autoState)){
            return;
        }
        $adapter=$this->db->getAdapter();
        $bind=array(
                $taskGuid,
                $autoState
        );
        $sql='UPDATE LEK_segments as seg,
            (
                SELECT hist.id,hist.userGuid,hist.userName,hist.segmentId
                FROM LEK_segment_history hist
                INNER JOIN LEK_segments s
                ON s.id = hist.segmentId
                WHERE s.taskGuid=?
                AND s.autoStateId=?
                AND hist.id= (SELECT id FROM LEK_segment_history WHERE segmentId=s.id ORDER BY created DESC LIMIT 1 )
            ) as h
            SET seg.userGuid = h.userGuid,seg.userName = h.userName WHERE seg.id=h.segmentId';
        
        $adapter->query($sql,$bind);
    }
    
    /**
     * @param string $taskGuid
     * @return array
     */
    public function getAutoStateCount(string $taskGuid) {
        $this->reInitDb($taskGuid);
        $s = $this->db->select()->from($this->tableName, ['autoStateId', 'cnt' => 'count(id)'])
        ->group('autoStateId');
        return $this->db->fetchAll($s)->toArray();
    }
    
    /**
     * shortcut to db->query catching errors complaining missing segment view
     * returns true if query was successfull, returns false if view was missing
     * @param string $sql
     * @param array $bind
     * @return boolean
     */
    protected function queryWithExistingView($sql, array $bind){
        try {
            $this->db->getAdapter()->query($sql, $bind);
            return true;
        }
        catch(Zend_Db_Statement_Exception $e) {
            $this->catchMissingView($e);
        }
        return false;
    }
    
    
    /**
     * includes the fluent segment data
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::getModifiedData()
     */
    public function getModifiedData() {
        $result = parent::getModifiedData(); //assoc mit key = dataindex und value = modValue
        $modKeys = array_keys($result);
        $modFields = array_unique(array_diff($this->modified, $modKeys));
        foreach($modFields as $field) {
            if($this->segmentFieldManager->getDataLocationByKey($field) !== false) {
                $result[$field] = $this->get($field);
            }
        }
        return $result;
    }
    
    /**
     * convenient method to get the segment meta data
     * @return editor_Models_Segment_Meta
     */
    public function meta() {
        if(empty($this->meta)) {
            $this->meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        }
        elseif($this->getId() == $this->meta->getSegmentId()) {
            return $this->meta;
        }
        try {
            $this->meta->loadBySegmentId($this->getId());
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $this->meta->init(array('taskGuid' => $this->getTaskGuid(), 'segmentId' => $this->getId()));
        }
        return $this->meta;
    }
     
    /**
     * returns the statistics summary for the given taskGuid
     * @param string $taskGuid
     * @return array id => fileId, value => segmentsPerFile count
     */
    public function calculateSummary($taskGuid) {
        $cols = array('fileId', 'segmentsPerFile' => 'COUNT(id)');
        $s = $this->db->select()
            ->from($this->db, $cols);
        $s = $this->addWatchlistJoin($s);
        $s = $this->addWhereTaskGuid($s, $taskGuid);
        $s->group($this->tableName.'.fileId');
        $rows = $this->db->fetchAll($s);
        
        $result = array();
        foreach($rows as $row) {
            $result[$row->fileId] = $row->segmentsPerFile;
        }
        return $result;
    }
    
    /**
     * returns true if all segments of the given taskGuid have empty original targets at the given moment
     * @param string $taskGuid
     * @return boolean
     */
    public function hasEmptyTargetsOnly($taskGuid) {
        $this->segmentFieldManager->initFields($taskGuid);
        $this->reInitDb($taskGuid);
        $s = $this->db->select(true)
            ->columns('count(*) as cnt')
            ->where('targetMd5 != ?', 'd41d8cd98f00b204e9800998ecf8427e');
        $x = $this->db->fetchRow($s);
        return ((int) $x->cnt) == 0;
    }
    
    /***
     * Get all segment repetitions from the task view.
     * Segment repetitions are segments with the same sourceMd5 hash value.
     * If the segment does not have repetition, it will not be returned by this function.
     * The returned segments are ordered by segment id
     * 
     * @param string $taskGuid
     */
    public function getRepetitions($taskGuid){
        $adapter=$this->db->getAdapter();
        $mv=ZfExtended_Factory::get('editor_Models_Segment_MaterializedView');
        /* @var $mv editor_Models_Segment_MaterializedView  */
        $mv->setTaskGuid($taskGuid);
        $viewName=$mv->getName();
        $sql='SELECT v1.id,v1.sourceMd5 FROM '.$viewName.' v1, (
	          SELECT sourceMd5, count(sourceMd5) cnt
               FROM '.$viewName.'
               GROUP BY sourceMd5
              ) v2
              WHERE v2.cnt > 1 and v1.sourceMd5 = v2.sourceMd5
              ORDER by v1.id';
        return $adapter->query($sql)->fetchAll();
    }
    
    /***
     * Unset row data collumns which are not existin in the $dbWritable
     */
    protected function unsetMaterializedViewData(){
        $dataColumns=$this->row->toArray();
        $tableInfo=$this->dbWritable->info();
        $segmentColumns=$tableInfo['cols'];
        
        foreach ($dataColumns as $key=>$value){
            //unset the rows not existing in the segment table
            if(!in_array($key,$segmentColumns)){
                $this->row->__unset($key);
            }
        }
    }
}
