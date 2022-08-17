<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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
 * @method void setId() setId(int $id)
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
 * @method int getStateId() getStateId()
 * @method void setStateId() setStateId(int $id)
 * @method integer getAutoStateId() getAutoStateId()
 * @method void setAutoStateId() setAutoStateId(int $id)
 * @method int getFileOrder() getFileOrder()
 * @method void setFileOrder() setFileOrder(int $order)
 * @method string getComments() getComments()
 * @method void setComments() setComments(string $comments)
 * @method integer getWorkflowStepNr() getWorkflowStepNr()
 * @method void setWorkflowStepNr() setWorkflowStepNr(int $stepNr)
 * @method string getWorkflowStep() getWorkflowStep()
 * @method void setWorkflowStep() setWorkflowStep(string $name)
 *
 * this are just some helper for the always existing segment fields, similar named methods exists for all segment fields:
 * @method string getSource() getSource()
 * @method void setSource() setSource(string $content)
 * @method void setSourceEdit() setSourceEdit(string $content)
 * @method void setSourceMd5() setSourceMd5(string $md5hash)
 * @method string getTarget() getTarget()
 * @method void setTarget() setTarget(string $content)
 * @method string getTargetEdit() getTargetEdit()
 * @method void setTargetEdit() setTargetEdit(string $content)
 * @method void setTargetMd5() setTargetMd5(string $md5hash)
 *
 */
class editor_Models_Segment extends ZfExtended_Models_Entity_Abstract
{
    const PM_SAME_STEP_INCLUDED = 'sameStepIncluded';
    const PM_ALL_INCLUDED = 'allIncluded';
    const PM_NOT_INCLUDED = 'notIncluded';

    /***
     * The default search type in search and replace
     * @var string
     */
    const DEFAULT_SEARCH_TYPE = 'normalSearch';

    /***
     * The default field when no search field is provided by search and repalce
     * @var string
     */
    const DEFAULT_SEARCH_FIELD = 'source';

    /**
     * if a segment was NOT pretranslated, use this value as pretrans
     * @var integer
     */
    const PRETRANS_NOTDONE = 0;

    /**
     * if a segment was pretranslated, use this value as initial pretrans value
     * @var integer
     */
    const PRETRANS_INITIAL = 1;

    /**
     * if translator confirms actively, or changes a pre-translated segment, the pretrans flag must be set to this value
     * @var integer
     */
    const PRETRANS_TRANSLATED = 2;
    
    /**
     * empty string hash to identify empty segments
     * generated with md5('');
     * @var string
     */
    const EMPTY_STRING_HASH = 'd41d8cd98f00b204e9800998ecf8427e';


        public static function createSegmentTagsStatusColumn(string $providerKey) : string{
        return 'status_'.$providerKey;
    }
    
    protected $dbInstanceClass          = 'editor_Models_Db_Segments';
    protected $validatorInstanceClass   = 'editor_Models_Validator_Segment';

    /**
     * @var Zend_Config
     */
    protected $config = null;

    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager = null;

    /**
     * @var [editor_Models_Db_SegmentDataRow]
     */
    protected $segmentdata = array();

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
     * @var Zend_Db_Table_Row_Abstract
     */
    protected $tagsModel = null;
    
    /**
     * @var editor_Models_Segment_UtilityBroker
     */
    protected editor_Models_Segment_UtilityBroker $utilityBroker;
    
    /**
     * static so that only one instance is used, for performance and logging issues
     * @var editor_Models_Segment_PixelLength
     */
    protected static $pixelLength;

    /**
     * init the internal segment field and the DB object
     */
    public function __construct()
    {
        $this->utilityBroker = ZfExtended_Factory::get('editor_Models_Segment_UtilityBroker');
        //FIXME replace all helpers with UtilityBroker usage if possible
        $this->segmentFieldManager = ZfExtended_Factory::get('editor_Models_SegmentFieldManager');
        $this->tagHelper = ZfExtended_Factory::get('editor_Models_Segment_InternalTag');
        $this->trackChangesTagHelper = ZfExtended_Factory::get('editor_Models_Segment_TrackChangeTag');
        $this->whitespaceHelper = ZfExtended_Factory::get('editor_Models_Segment_Whitespace');
        parent::__construct();
    }

    /**
     * "lazy load" for editor_Models_Segment_PixelLength (must fit to the segment's task!).
     */
    protected function getPixelLength(string $taskGuid)
    {
        if (!isset(self::$pixelLength) || self::$pixelLength->getTaskGuid() != $taskGuid) {
            self::$pixelLength = ZfExtended_Factory::get('editor_Models_Segment_PixelLength', [$taskGuid]);
        }
        return self::$pixelLength;
    }

    /***
     * Search the materialized view for given search field,search string and match case.
     * Only hits in the editable fields will be returned
     *
     * @param array $parameters
     * @return string|array
     */
    public function search(array $parameters)
    {
        $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView');
        /* @var $mv editor_Models_Segment_MaterializedView */
        $mv->setTaskGuid($parameters['taskGuid']);
        $viewName = $mv->getName();

        $this->reInitDb($parameters['taskGuid']);
        $this->segmentFieldManager->initFields($parameters['taskGuid']);

        //set the default search params when no values are given
        $parameters = $this->setDefaultSearchParameters($parameters);

        //get the search sql string
        $searchQuery = $this->buildSearchString($parameters);

        //the field where the search will be performed (toSort field)
        $searchInToSort = $parameters['searchInField'] . editor_Models_SegmentFieldManager::_TOSORT_PREFIX;

        //check if search in locked segment is clicked, if yes, remove the editable filter
        $searchLocked = false;
        if ($parameters['searchInLockedSegments']) {
            $searchLocked = $parameters['searchInLockedSegments'] === "true";
        }

        $select = $this->db->select()
            ->from($viewName, array('id', 'segmentNrInTask', $parameters['searchInField'], $searchInToSort, 'editable'))
            ->where($searchQuery);
        if (!$searchLocked) {
            $select->where('editable=1');
        }

        /* //TODO:The idea how we can use the search limitation
         *
         * SELECT id,rank FROM (
            	SELECT @rownum := @rownum + 1 AS rank,
            	   `id`, `segmentNrInTask`, `fileId`, `mid`, `userGuid`, `userName`, `taskGuid`, `timestamp`,
            	   `editable`, `pretrans`, `matchRate`, `matchRateType`, `stateId`, `autoStateId`, `fileOrder`,
            	   `comments`, `workflowStepNr`, `workflowStep`, `source`, `sourceMd5`, `sourceToSort`, `target`,
            	   `targetMd5`, `targetToSort`, `targetEdit`, `targetEditToSort`
            	   FROM `LEK_segment_view_10ba195a738894769f296aee08364626`, (SELECT @rownum := 0) r
            	   ORDER BY `fileOrder` asc, `id` asc LIMIT 100 OFFSET 100000
               ) sub
            WHERE targetEditToSort  REGEXP '[0-9]';
         */
        $this->addWatchlistJoin($select);
        return $this->loadFilterdCustom($select);
    }

    /***
     * Build search SQL string for given field based on the search type
     *
     * @param array $parameters
     * @return boolean|string
     */
    protected function buildSearchString($parameters)
    {
        $adapter = $this->db->getAdapter();

        $queryString = $parameters['searchField'];
        $searchInField = $parameters['searchInField'] . editor_Models_SegmentFieldManager::_TOSORT_PREFIX;
        $matchCase = isset($parameters['matchCase']) ? (strtolower($parameters['matchCase']) == 'true') : false;

        //search type regular expression
        if ($parameters['searchType'] === 'regularExpressionSearch') {
            //simples way to test if the regular expression is valid
            //try {
            //@preg_match($patern, 'Test string');
            //} catch (Exception $e) {
            //    return false;
            //}
            if (!$matchCase) {
                return $adapter->quoteIdentifier($searchInField) . ' REGEXP ' . $adapter->quote($queryString);
            }
            return 'CAST('.$adapter->quoteIdentifier($searchInField) . ' AS BINARY) REGEXP BINARY ' . $adapter->quote($queryString);
        }
        //search type regular wildcard
        if ($parameters['searchType'] === 'wildcardsSearch') {
            $queryString = str_replace("*", "%", $queryString);
            $queryString = str_replace("?", "_", $queryString);
        }
        //if match case, search without lower function
        if ($matchCase) {
            return $adapter->quoteIdentifier($searchInField) . ' like ' . $adapter->quote('%' . $queryString . '%') . ' COLLATE utf8mb4_bin';
        }
        return 'lower(' . $adapter->quoteIdentifier($searchInField) . ') like lower(' . $adapter->quote('%' . $queryString . '%') . ') COLLATE utf8mb4_bin';
    }

    /**
     * updates the toSort attribute of the given attribute name (only if toSort exists!)
     * @param string $field
     */
    public function updateToSort($name)
    {
        $toSort = $name . 'ToSort';
        if (!$this->hasField($toSort)) {
            return;
        }
        $v = $this->__call('get' . ucfirst($name), array());
        $this->__call('set' . ucfirst($toSort), array($this->stripTags($v)));
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
        foreach ($datas as $data) {
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
    public function set($name, $value)
    {
        $loc = $this->segmentFieldManager->getDataLocationByKey($name);
        if ($loc !== false) {
            if (empty($this->segmentdata[$loc['field']])) {
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
    public function get($name)
    {
        $loc = $this->segmentFieldManager->getDataLocationByKey($name);
        if ($loc !== false) {
            //if we have a missing index here, that means,
            //the data field ist not existing yet, since the field itself was defined by another file!
            //so returning an empty string is OK here.
            if (empty($this->segmentdata[$loc['field']])) {
                return '';
            }
            return $this->segmentdata[$loc['field']]->__get($loc['column']);
        }
        return parent::get($name);
    }

    /**
     * set the match rate type, does not modify the value if it is a missing-mrk type before
     */
    public function setMatchRateType($type)
    {
        $oldValue = $this->getMatchRateType();
        if (editor_Models_Segment_MatchRateType::isUpdateable($oldValue)) {
            return $this->__call(__FUNCTION__, [$type]);
        }
        return $oldValue;
    }

    /**
     * integrates the segment fields into the hasfield check
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::hasField()
     */
    public function hasField($field)
    {
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
    public function isDataModifiedAgainstOriginal($typeFilter = null)
    {
        if (!is_null($this->isDataModifiedAgainstOriginal)) {
            return $this->isDataModifiedAgainstOriginal;
        }
        $this->isDataModifiedAgainstOriginal = false;
        foreach ($this->segmentdata as $data) {
            $field = $this->segmentFieldManager->getByName($data->name);
            $isEditable = $field->editable;
            if (!$isEditable || !empty($typeFilter) && $data->type !== $typeFilter) {
                continue;
            }
            if ($this->stripTermTagsAndTrackChanges($data->edited) !== $this->stripTermTagsAndTrackChanges($data->original)) {
                $this->isDataModifiedAgainstOriginal = true;
            }
        }
        return $this->isDataModifiedAgainstOriginal;
    }

    /**
     * Checks if segment data is changed in this entity, compared against last loaded content
     */
    public function isDataModified($typeFilter = null) {
        if (!is_null($this->isDataModified)) {
            return $this->isDataModified;
        }
        $this->isDataModified = false;
        foreach ($this->segmentdata as $data) {
            $field = $this->segmentFieldManager->getByName($data->name);
            $isEditable = $field->editable;
            $fieldName = $this->segmentFieldManager->getEditIndex($data->name);
            $edited = $this->isModified($fieldName);
            if (!$isEditable || !$edited || !empty($typeFilter) && $data->type !== $typeFilter) {
                continue;
            }
            $oldValue = $this->getOldValue($fieldName);
            if($this->stripTermTagsAndTrackChanges($data->edited) !== $this->stripTermTagsAndTrackChanges($oldValue)) {
                $this->isDataModified = true;
            } else {
                // when the text-contents are identical we check, if this may is a removal initiated by the accept/reject feature of trackchanges
                // therefore we compare the available track-changes tags, if they differ somehow the content was not modified
                if($this->utilityBroker->trackChangeTag->getUsedTagInfo($oldValue) !== $this->utilityBroker->trackChangeTag->getUsedTagInfo($data->edited)){
                    $this->isDataModified = true;
                }
            }
        }
        return $this->isDataModified;
    }
    /**
     * Convenience API to evaluate if a segment has been pretranslated (either from a TM or a MT)
     * This may also mean, that the status in an imported sdxliff was the like
     * @return boolean
     */
    public function isPretranslated() {
        return $this->getPretrans() !== 0;
    }
    /**
     * Convenience API to evaluate if a segment has been pretranslated by a machine translation
     * @return boolean
     */
    public function isPretranslatedMT() {
        return $this->getPretrans() !== 0 && editor_Models_Segment_MatchRateType::isFromMT($this->getMatchRateType());
    }
    /**
     * Convenience API to evaluate if a segment has been pretranslated by a translation memory
     * @return boolean
     */
    public function isPretranslatedTM() {
        return $this->getPretrans() !== 0 && editor_Models_Segment_MatchRateType::isFromTM($this->getMatchRateType());
    }
    /**
     * Convenience API to evaluate if a segment was taken over as a match by a machine translation
     * @return boolean
     */
    public function isEditedMT() {
        return editor_Models_Segment_MatchRateType::isEditedMT($this->getMatchRateType());
    }
    /**
     * Convenience API to evaluate if a segment was taken over as a match by a translation memory
     * @return boolean
     */
    public function isEditedTM() {
        return editor_Models_Segment_MatchRateType::isEditedTM($this->getMatchRateType());
    }
    /**
     * Convenience API to evaluate if a segment originates from a machine translation (either pretranslated or taken over later on)
     * @return boolean
     */
    public function isFromMT() {
        return editor_Models_Segment_MatchRateType::isTypeMT($this->getMatchRateType());
    }
    /**
     * Convenience API to evaluate if a segment originates from a translation memory (either pretranslated or taken over later on)
     * @return boolean
     */
    public function isFromTM() {
        return editor_Models_Segment_MatchRateType::isTypeTM($this->getMatchRateType());
    }
    /**
     * Convenience API to evaluate if a segment originates from a Language Resource (either pretranslated or taken over later on, either MT, TM or TermCollection)
     * @return boolean
     */
    public function isFromLanguageResource(){
        return editor_Models_Segment_MatchRateType::isTypeLanguageResource($this->getMatchRateType());
    }
    /**
     * restores segments with content not changed by the user to the original
     * (which contains termTags - this way no new termTagging is necessary, since
     * GUI removes termTags onSave)
     */
    public function restoreNotModfied()
    {
        if ($this->isDataModified()) {
            return;
        }
        foreach ($this->segmentdata as &$data) {
            $field = $this->segmentFieldManager->getByName($data->name);
            $isEditable = $field->editable;
            if (!$isEditable) {
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
    public function stripTags($segmentContent)
    {
        $segmentContent = $this->trackChangesTagHelper->removeTrackChanges($segmentContent);
        $segmentContent = $this->tagHelper->restore($segmentContent, true);
        $segmentContent = $this->spoofWhitespaceTagsWithPlaceholderChars($segmentContent);

        return strip_tags(preg_replace('#<span[^>]*>[^<]*<\/span>#', '', $segmentContent));
    }

    /**
     * Spoof whitespace-tags with their, so to say, placeholder-characters.
     *
     * This is done for several reasons:
     * 1.Prevent 'hello<tab..>world' to be converted to 'helloworld', so no even ordinary space between those words is kept
     * 2.Make it more visually obvious for developers what's inside by looking at segment's *EditToSort-column contents directly in database
     * 3.Make it simplier for Editor.plugins.SpellCheck.controller.Editor.mindTags() to calculate words/phrases coords adjustments
     *
     * @param $segmentContent
     * @return string|string[]|null
     */
    public function spoofWhitespaceTagsWithPlaceholderChars($segmentContent) {

        // <tab ts="09" length="1"/>
        $segmentContent = preg_replace('~<tab.*?/>~', "→", $segmentContent);

        // <softReturn/>
        $segmentContent = preg_replace('~<softReturn.*?/>~', "↵", $segmentContent);

        // <char ts="c2a0" length="1"/>
        $segmentContent = preg_replace('~<char ts="c2a0" length="1"/>~', "⎵", $segmentContent);

        return $segmentContent;
    }

    /**
     * Get length of a segment's text according to the segment's sizeUnit.
     * If the sizeUnit is set to 'pixel', we use pixelMapping, otherwise
     * we count by characters (this is for historical reasons of this code;
     * other than the XLF-specifications which are not relevant here!).
     * @param string $segmentContent
     * @param editor_Models_Segment_Meta $segmentMeta
     * @param integer $segmentFileId
     * @return integer
     */
    public function textLengthByMeta($segmentContent, editor_Models_Segment_Meta $segmentMeta, $segmentFileId)
    {
        $isPixelBased = ($segmentMeta->getSizeUnit() == editor_Models_Segment_PixelLength::SIZE_UNIT_XLF_DEFAULT);
        if ($isPixelBased) {
            return $this->textLengthByPixel($segmentContent, $segmentMeta->getTaskGuid(), $segmentMeta->getFont(), $segmentMeta->getFontSize(), $segmentFileId);
        }
        return $this->textLengthByChar($segmentContent);
    }

    /**
     * Same as textLengthByMeta(), but here we use the editor_Models_Import_FileParser_SegmentAttributes
     * instead of editor_Models_Segment_Meta (on import, the segment and it's meta don't exist yet).
     * @param string $content
     * @param editor_Models_Import_FileParser_SegmentAttributes $attributes
     * @param string $taskGuid (other than in $segmentMeta, the $attributes don't have a taskGuid)
     * @param int $fileId
     * @return integer
     */
    public function textLengthByImportattributes($content, editor_Models_Import_FileParser_SegmentAttributes $attributes, $taskGuid, $fileId)
    {
        $isPixelBased = ($attributes->sizeUnit == editor_Models_Segment_PixelLength::SIZE_UNIT_XLF_DEFAULT);
        if ($isPixelBased) {
            return $this->textLengthByPixel($content, $taskGuid, $attributes->font, $attributes->fontSize, $fileId);
        }
        return $this->textLengthByChar($content);
    }

    /**
     * Get pixel length of a segment's text according to the given assumed font and fontsize
     * @param string $segmentContent
     * @param string $taskGuid
     * @param string $font
     * @param string $fontSize
     * @param integer $fileId
     * @return integer
     */
    public function textLengthByPixel($segmentContent, $taskGuid, $font, $fontSize, $fileId)
    {
        $pixelLength = $this->getPixelLength($taskGuid); // make sure that the pixelLength we use is that for the segment's task!
        return $pixelLength->textLengthByPixel($segmentContent, $font, intval($fontSize), $fileId);
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
    public function textLengthByChar($segmentContent)
    {
        return mb_strlen($this->prepareForCount($segmentContent, true));
    }

    /**
     * Counts words; word boundary is used as defined in runtimeOptions.editor.export.wordBreakUpRegex
     * @param string $segmentContent
     * @return integer
     * @deprecated editor_Models_Segment_WordCount should be used instead
     */
    public function wordCount($segmentContent)
    {
        $config = Zend_Registry::get('config');
        $regexWordBreak = $config->runtimeOptions->editor->export->wordBreakUpRegex;

        $words = preg_split($regexWordBreak, $this->prepareForCount($segmentContent), flags: PREG_SPLIT_NO_EMPTY);
        return count($words);
    }

    /**
     * Reconverts html entities so that several count operations can be performed.
     * @param string $text
     * @param bool $padTagLength if true, replace tags with a length with a padded string in that length
     * @return string
     */
    protected function prepareForCount($text, $padTagLength = false)
    {
        $text = $this->trackChangesTagHelper->removeTrackChanges($text);
        $text = $this->tagHelper->replace($text, function ($matches) use ($padTagLength) {
            if ($padTagLength) {
                $length = max((int)$this->tagHelper->getLength($matches[0]), 0);
                return str_repeat('x', $length); //create a "x" string as long as the tag stored tag length
            } else {
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
    public function prepareForPixelBasedLengthCount($text)
    {
        $text = $this->trackChangesTagHelper->removeTrackChanges($text);
        $text = $this->restoreWhiteSpace($text);
        return $text;
    }

    /**
     * Restore whitespace to original real characters.
     * @param string $segmentContent
     * @return string $segmentContent
     */
    protected function restoreWhiteSpace($segmentContent)
    {
        $segmentContent = $this->tagHelper->restore($segmentContent, true);
        $segmentContent = $this->whitespaceHelper->unprotectWhitespace($segmentContent);
        $segmentContent = $this->tagHelper->protect($segmentContent);
        $segmentContent = html_entity_decode(strip_tags($segmentContent), ENT_QUOTES | ENT_XHTML);
        return $segmentContent;
    }

    /**
     * strips all tags including tag description
     * FIXME WARNING do not use this method other than it is used currently
     * see therefore TRANSLATE-487
     *
     * @param string $segmentContent
     * @return string $segmentContent (only containing internal and MQM tags, internal tags sanitized)
     */
    public function stripTermTagsAndTrackChanges($segmentContent)
    {
        $tag = $this->tagHelper;
        $segmentContent = $this->trackChangesTagHelper->removeTrackChanges($segmentContent);
        $segmentContent = $tag->protect($segmentContent);
        //keep internal tags and MQM, remove all other
        $segmentContent = strip_tags($segmentContent, '<img>' . $tag::PLACEHOLDER_TAG);
        $segmentContent = $tag->unprotect($segmentContent);
        //remove the class attribute of the span, since its position is changed by tag object usage
        return preg_replace('/(<span[^>]*)( class="[^"]+")([^>]*>)/', '$1$3', $segmentContent);
    }

    /**
     * using the find method of querypath implies to create an internal clone of the DOM node,
     * which then throws an duplicate id error which is completly nonsense at this place, so we filter them out.
     */
    protected function collectLibXmlErrors()
    {
        $otherErrors = array();
        foreach (libxml_get_errors() as $error) {
            $msg = $error->message;
            //Example error message: "ID NL-8-df250b2156c434f3390392d09b1c9563 already defined"
            if (strpos(trim($msg), 'ID ') === 0 && strpos(strrev(trim($msg)), strrev(' already defined')) === 0) {
                continue;
            }
            $otherErrors[] = $error;
        }
        libxml_clear_errors();
        if (!empty($otherErrors)) {
            throw new Exception("Collected LIBXML errors: " . print_r($otherErrors, 1));
        }
    }

    /**
     * loads the Entity by Primary Key Id
     * @param int $id
     * @return Zend_Db_Table_Row
     */
    public function load($id)
    {
        $row = parent::load($id);
        $this->segmentFieldManager->initFields($this->getTaskGuid());
        $this->initData($id);
        return $row;
    }

    public function loadByIds(array $ids)
    {
        $s = $this->db->select()
            ->where('id IN (?)', $ids);
        return $this->loadFilterdCustom($s);
    }

    /**
     * erzeugt ein neues, ungespeichertes SegmentHistory Entity
     * @return editor_Models_SegmentHistory
     */
    public function getNewHistoryEntity()
    {
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
     * @param int $divisor optional, default = 1; if greater than 1 divide the duration through this value (for changeAlikes)
     */
    public function setTimeTrackData(stdClass $durations, $divisor = 1)
    {
        $sfm = $this->segmentFieldManager;
        foreach ($this->segmentdata as $field => $data) {
            $field = $sfm->getEditIndex($field);
            if ($field !== false && isset($durations->$field)) {
                $data->duration = $durations->$field;
                if ($divisor > 1) {
                    $data->duration = (int)round($data->duration / $divisor);
                }
            }
        }
    }

    /**
     * gets the data from import, sets it into the data fields
     * check the given fields against the really available fields for this task.
     * @param editor_Models_SegmentFieldManager $sfm
     * @param array $segmentData key: fieldname; value: array with original and originalMd5
     */
    public function setFieldContents(editor_Models_SegmentFieldManager $sfm, array $segmentData)
    {
        $this->segmentFieldManager = $sfm;
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $db editor_Models_Db_SegmentData */
        foreach ($segmentData as $name => $data) {
            $row = $db->createRow($data);
            /* @var $row editor_Models_Db_SegmentDataRow */
            $row->name = $name;
            $field = $sfm->getByName($name);
            $row->originalToSort = $this->stripTags($row->original);
            $row->taskGuid = $this->getTaskGuid();
            $row->mid = $this->getMid();
            if (isset($field->editable) && $field->editable) {
                $row->edited = $row->original;
                $row->editedToSort = $row->originalToSort;
            }
            /* @var $row editor_Models_Db_SegmentDataRow */
            $this->segmentdata[$name] = $row;
        }
    }

    /**
     * loads segment entity by file id and mid, taskGuid must be set via setTaskGuid before
     * @param int $fileId
     * @param string $mid
     */
    public function loadByFileidMid(int $fileId, $mid)
    {
        $taskGuid = $this->getTaskGuid();
        $s = $this->db->select()
            ->where($this->tableName . '.taskGuid = ?', $taskGuid)
            ->where($this->tableName . '.fileId = ?', $fileId)
            ->where($this->tableName . '.mid = ?', $mid);
        $this->row = $this->db->fetchRow($s);
        if (empty($this->row)) {
            $this->notFound('#loadByFileidMid ', $fileId . '#' . $mid);
        }
        $this->segmentFieldManager->initFields($taskGuid);
        $this->initData($this->getId());
    }

    /**
     * loads segment entity by segmentNrInTask
     * @param int $segmentNrInTask
     * @param string $taskGuid
     */
    public function loadBySegmentNrInTask($segmentNrInTask, $taskGuid)
    {
        $s = $this->db->select()
            ->where($this->tableName . '.taskGuid = ?', $taskGuid)
            ->where($this->tableName . '.segmentNrInTask = ?', $segmentNrInTask);
        $this->row = $this->db->fetchRow($s);
        if (empty($this->row)) {
            $this->notFound('#loadBySegmentNrInTask ', $segmentNrInTask . '#' . $taskGuid);
        }
        $this->segmentFieldManager->initFields($this->getTaskGuid());
        $this->initData($this->getId());
    }

    /**
     * adds one single field content ([original => TEXT, originalMd5 => HASH]) to a given segment,
     * identified by MID and fileId. taskGuid MUST be given by setTaskGuid before!
     * due the internal implementation this method works only correctly before the materialized view is created!
     *
     * @param Zend_Db_Table_Row_Abstract $field
     * @param int $fileId
     * @param string $mid
     * @param array $data
     * @throws ZfExtended_Models_Entity_NotFoundException if the segment where the content should be added could not be found
     */
    public function addFieldContent(Zend_Db_Table_Row_Abstract $field, $fileId, $mid, array $data)
    {
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $db editor_Models_Db_SegmentData */

        $taskGuid = $this->getTaskGuid();
        $segmentId = $this->getId();

        $data = array(
            'taskGuid' => $taskGuid,
            'name' => $field->name,
            'segmentId' => $segmentId,
            'mid' => $mid,
            'original' => $data['original'],
            'originalMd5' => $data['originalMd5'],
            'originalToSort' => $this->stripTags($data['original']),
        );
        if ($field->editable) {
            $data['edited'] = $data['original'];
            $data['editedToSort'] = $this->stripTags($data['original']);
        }

        try {
            $db->insert($data);
        } catch (Zend_Db_Statement_Exception $e) {
            if (strpos($e->getMessage(), "Column 'segmentId' cannot be null") !== false) {
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
    protected function createData($field)
    {
        $db = ZfExtended_Factory::get('editor_Models_Db_SegmentData');
        /* @var $db editor_Models_Db_SegmentData */
        $row = $db->createRow();
        /* @var $row editor_Models_Db_SegmentDataRow */
        $row->taskGuid = $this->get('taskGuid');
        $row->name = $field;
        $row->segmentId = $this->get('id');
        $row->mid = $this->get('mid');
        $row->original = '';
        $row->originalMd5 = self::EMPTY_STRING_HASH;
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
    public function save()
    {
        if (!empty($this->dbWritable)) {
            if ($this->dbWritable->isView()) {
                //Unable to save the segment. The segment model tried to save to the materialized view directly.
                throw new editor_Models_Segment_Exception('E1155', [
                    'segmentId' => $this->getId(),
                    'taskGuid' => $this->getTaskGuid(),
                    'usedTableName' => $this->dbWritable->info($this->dbWritable::NAME)
                ]);
            }

            //clean unneeded materialized view data
            $this->unsetMaterializedViewData();
            $this->row->setTable($this->dbWritable);
        }
        $oldIdValue = $this->getId();
        $segmentId = parent::save();
        foreach ($this->segmentdata as $data) {
            /* @var $data editor_Models_Db_SegmentDataRow */
            if (empty($data->segmentId)) {
                $data->segmentId = $segmentId;
            }
            $data->save();
        }
        //only update the mat view if the segment was already in DB (so do not save mat view on import!)
        //same for meta data, since on import meta data is saved by the segment processor
        if (!empty($oldIdValue)) {
            $this->meta()->setSiblingData($this);
            $this->meta()->save();
            $matView = $this->segmentFieldManager->getView();
            /* @var $matView editor_Models_Segment_MaterializedView */
            if ($matView->exists()) {
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
    public function getDataObject()
    {
        $res = parent::getDataObject();
        $this->segmentFieldManager->mergeData($this->segmentdata, $res);
        /** @var $segmentUserAssoc editor_Models_SegmentUserAssoc */
        $segmentUserAssoc = ZfExtended_Factory::get('editor_Models_SegmentUserAssoc');
        try {
            $assoc = $segmentUserAssoc->loadByParams($res->userGuid, $res->id);
            $res->isWatched = true;
            $res->segmentUserAssocId = $assoc['id'];
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $res->isWatched = null;
            $res->segmentUserAssocId = null;
        }
        $matView = $this->segmentFieldManager->getView();
        if (property_exists($res, 'metaCache') || !$matView->exists()) {
            return $res;
        }
        $res->metaCache = $matView->getMetaCache($this);
        return $res;
    }

    /**
     * returns the original content of a field
     * @param string $field Fieldname
     */
    public function getFieldOriginal($field)
    {
        //since fields can be merged from different files, data for a field can be empty
        if (empty($this->segmentdata[$field])) {
            return '';
        }
        return $this->segmentdata[$field]->original;
    }

    /**
     * returns the edited content of a field
     * @param string $field Fieldname
     */
    public function getFieldEdited($field)
    {
        //since fields can be merged from different files, data for a field can be empty
        if (empty($this->segmentdata[$field])) {
            return '';
        }
        return $this->segmentdata[$field]->edited;
    }
    /**
     * Returns the edited content of a field preprocessed for export
     * @param string $field
     * @param editor_Models_Task $task
     * @param bool $edited: If set (default) the edited content is used, otherwise the original
     * @param bool $fixKnownFaultyTags: If set (default) Tag-faults are repaired automatically (usually these tags are removed)
     * @return editor_Segment_Export
     */
    public function getFieldExport(string $field, editor_Models_Task $task, bool $edited=true, bool $fixKnownFaultyTags=true) : ?editor_Segment_Export {
        //since fields can be merged from different files, data for a field can be empty
        if (empty($this->segmentdata[$field])) {
            return null;
        }
        $fieldTags = ($edited) ?
            new editor_Segment_FieldTags($task, $this->getId(), $this->segmentdata[$field]->edited, $field, $this->segmentFieldManager->getEditIndex($field)) :
            new editor_Segment_FieldTags($task, $this->getId(), $this->segmentdata[$field]->original, $field, $field);
            return editor_Segment_Export::create($fieldTags, $fixKnownFaultyTags);
    }

    /**
     * returns a list with editable dataindex
     * @param boolean $addOriginalTargetWhenDefaultLayout: special flag to enable manipulating the target when the fields are in default layout
     * @return array
     */
    public function getEditableDataIndexList($addOriginalTargetWhenDefaultLayout=false)
    {
        return $this->segmentFieldManager->getEditableDataIndexList($addOriginalTargetWhenDefaultLayout);
    }

    /**
     * Returns an array with just the editable field values (value) and field names (key)
     * @return array key: fieldname, value: field content
     */
    public function getEditableFieldData()
    {
        $editables = $this->segmentFieldManager->getEditableDataIndexList();
        $result = [];
        foreach ($editables as $field) {
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
    public function loadByTaskGuid($taskGuid, Closure $callback = null)
    {

        try {
            return $this->_loadByTaskGuid($taskGuid, $callback);
        } catch (Zend_Db_Statement_Exception $e) {
            $this->catchMissingView($e);
        }
        //fallback mechanism for not existing views. If not exists, we are trying to create it.
        $this->segmentFieldManager->initFields($taskGuid);
        $this->segmentFieldManager->getView()->create();
        return $this->_loadByTaskGuid($taskGuid, $callback);
    }

    /**
     * Loads segments by task-guid and file-id. Returns just a simple array of id and sgmentNrInTask ordered by sgmentNrInTask
     * @param string $taskGuid
     * @param int $fileId
     * @param boolean $ignoreLocked
     * @return array
     */
    public function loadByTaskGuidFileId(string $taskGuid, int $fileId, $ignoreLocked = true)
    {
        $s = $this->db->select()
            ->setIntegrityCheck(false)
            ->from($this->tableName, array('id', 'segmentNrInTask', 'editable', 'pretrans'))
            ->where($this->tableName . '.taskGuid = ?', $taskGuid)
            ->where($this->tableName . '.fileId = ?', $fileId);

        if ($ignoreLocked) {
            $s->join('LEK_segments_meta', $this->tableName . '.id = LEK_segments_meta.segmentId', array())
                ->where('LEK_segments_meta.locked != 1 OR LEK_segments_meta.locked IS NULL');
        }
        $s->order($this->tableName . '.segmentNrInTask ASC');
        return parent::loadFilterdCustom($s);
    }
    /**
     * Prepares the entity for using it in an editable finder
     * @return editor_Models_Segment
     */
    public function reInitForEditablesFinder(){
        $this->reInitDb($this->getTaskGuid());
        $this->initDefaultSort();
        return $this;
    }
    /**
     * inits and returns the editor_Models_Segment_EditablesFinder
     * @return editor_Models_Segment_EditablesFinder
     */
    protected function initSegmentFinder(){
        return ZfExtended_Factory::get(
            'editor_Models_Segment_EditablesFinder',
            array($this->reInitForEditablesFinder()));
    }
    /**
     * returns the first and the last EDITABLE segment of the actual filtered request
     * @param array $autoStateIds a list of autoStates where the prev/next page segments are additionaly compared to
     * @param int $total
     * @return array
     */
    public function findSurroundingEditables($next, array $autoStateIds = null)
    {
        return $this->initSegmentFinder()->find($next, $autoStateIds);
    }

    /**
     * returns the index/position of the current segment into the currently filtered/sorted list of all segments
     * @return integer|null
     */
    public function getIndex()
    {
        return $this->initSegmentFinder()->getIndex($this->getId());
    }

    /**
     * Loads the first segment of the given taskGuid.
     * The found segment is stored internally (like load).
     * First Segment is defined as the segment with the lowest id of the task
     *
     * @param string $taskGuid
     * @param int $fileId optional, loads first file of given fileId in task
     * @return editor_Models_Segment
     */
    public function loadFirst($taskGuid, $fileId = null)
    {
        $this->segmentFieldManager->initFields($taskGuid);
        //ensure that view exists (does nothing if already):
        $this->segmentFieldManager->getView()->create();
        $this->reInitDb($taskGuid);

        $seg = $this->loadNext($taskGuid, 0, $fileId);

        if (empty($seg)) {
            $this->notFound('first segment of task', $taskGuid);
        }
        return $seg;
    }

    /**
     * recalculates the isRepeated flag for the given target hashes
     * @param string $newHash
     * @param string $oldHash
     * @return boolean
     */
    public function updateIsTargetRepeated(string $newHash, string $oldHash)
    {
        //no change, so do nothing
        $emptyHash = self::EMPTY_STRING_HASH;
        if($newHash == $emptyHash) {
            $newHash = 'this-may-not-be-a-repetition';
        }
        if($oldHash == $emptyHash) {
            $oldHash = 'this-may-not-be-a-repetition';
        }
        if($newHash == $oldHash){
            return;
        }
        
        //updates the isRepeated flag for segments with
        //  the same old hash (remove target info from isRepeated)
        //  the same new hash (add target info to isRepeated if there are any repetitions)
        
        //IF count(targetMd5) > 1
        // THEN SET isRepeated = isRepeated | 2     calc 2 in
        // ELSE SET isRepeated = isRepeated & ~2    calc 2 out
        
        $sql = 'UPDATE %1$s v, LEK_segments s, (
            SELECT targetMd5, count(targetMd5) > 1 isRepeated
            FROM %1$s
            WHERE targetMd5 IN (?, ?)
            GROUP BY targetMd5
            ) srep
        SET v.isRepeated = IF(srep.isRepeated, v.isRepeated | 2, v.isRepeated & ~2),
            s.isRepeated = IF(srep.isRepeated, s.isRepeated | 2, s.isRepeated & ~2)
        WHERE v.targetMd5 = srep.targetMd5
        AND v.id = s.id';
        $this->db->getAdapter()->query(sprintf($sql, $this->segmentFieldManager->getView()->getName()),[$newHash, $oldHash]);
    }

    /**
     * synchronizes the isRepeated flag depending on if there are repetitions or not.
     * @param string $taskGuid
     * @param bool $resetIsRepeated by default true, not needed on import, since there are all flags already false
     */
    public function syncRepetitions(string $taskGuid, bool $resetIsRepeated = true)
    {
        if($resetIsRepeated) {
            $this->db->getAdapter()->query('UPDATE LEK_segments SET isRepeated = 0 WHERE taskGuid = ?', [$taskGuid]);
        }
        
        $sql = 'UPDATE LEK_segments s, LEK_segment_data d
        SET s.isRepeated = s.isRepeated | %1$s
        WHERE d.originalMd5 IN (
            SELECT originalMd5
            FROM LEK_segment_data
            WHERE taskGuid = ? AND
            originalMd5 != ? AND name = "%2$s" GROUP BY originalMd5 HAVING count(segmentId) > 1
        )
        AND s.id = d.segmentId AND d.taskGuid = ?';
        
        //update isRepeated for source repetitions add bit value of 1
        $this->db->getAdapter()->query(sprintf($sql, '1', 'source'),[$taskGuid, self::EMPTY_STRING_HASH, $taskGuid]);
        
        //update isRepeated for target repetitions add bit value of 2
        $this->db->getAdapter()->query(sprintf($sql, '2', 'target'),[$taskGuid, self::EMPTY_STRING_HASH, $taskGuid]);
        
        //sync the view too, if it exists
        $this->segmentFieldManager->initFields($taskGuid);
        $view = $this->segmentFieldManager->getView();
        if($view->exists()) {
            $segmentsViewName = $view->getName();
            $this->db->getAdapter()->query('UPDATE '.$segmentsViewName.' v, LEK_segments s
            SET v.isRepeated = s.isRepeated
            WHERE v.id = s.id AND s.taskGuid = ?', [$taskGuid]);
        }
    }

    /**
     * Loads the next segment after the given id from the given taskGuid
     * next is defined as the segment with the next higher segmentId
     * This method assumes that segmentFieldManager was already loaded internally
     * @param string $taskGuid
     * @param int $id
     * @param int|null $fileId optional, loads first file of given fileId in task
     * @return editor_Models_Segment | null if no next found
     */
    public function loadNext(string $taskGuid, int $id, int $fileId = null): ?static
    {
        $this->segmentFieldManager->initFields($taskGuid);

        $s = $this->db->select()->from($this->tableName);
        $this->applyFilterAndSort($s); //respecting filters if set any
        $s = $this->addWatchlistJoin($s, $this->tableName);
        $s = $this->addWhereTaskGuid($s, $taskGuid);
        $s->where($this->tableName . '.id > ?', $id)
            ->order($this->tableName . '.id ASC')
            ->limit(1);

        if (!empty($fileId)) {
            $s->where($this->tableName . '.fileId = ?', $fileId);
        }

        $row = $this->db->fetchRow($s);
        if (empty($row)) {
            return null;
        }
        //is needed since the join with isWatch is setting it true
        $row->setReadOnly(false);
        $this->row = $row;
        $this->initData($this->getId());
        return $this;
    }

    /**
     * returns the segment count of the given taskGuid
     * filters are not applied since the overall count is needed for statistics
     * @param string $taskGuid
     * @param bool $editable
     * @return integer the segment count
     */
    public function count($taskGuid, $onlyEditable = false)
    {
        $s = $this->db->select()
            ->from($this->db, array('cnt' => 'COUNT(id)'))
            ->where('taskGuid = ?', $taskGuid);
        if ($onlyEditable) {
            $s->where('editable = 1');
        }
        $row = $this->db->fetchRow($s);
        return $row->cnt;
    }

    /**
     * encapsulate the load by taskGuid code.
     * @param string $taskGuid
     * @param Closure $callback is called with the select statement as parameter before passing it to loadFilterdCustom Param: Zend_Db_Table_Select
     * @return array
     */
    protected function _loadByTaskGuid($taskGuid, Closure $callback = null)
    {

        $this->segmentFieldManager->initFields($taskGuid);
        $this->reInitDb($taskGuid);

        $this->initDefaultSort();

        $s = $this->db->select(false);
        $db = $this->db;
        $cols = $this->db->info($db::COLS);

        /**
         * FIXME reminder for TRANSLATE-113: Filtering out unused cols is needed for TaskManagement Feature (user dependent cols)
         * This is a example for field filtering.
         * if (!$loadSourceEdited) {
         * $cols = array_filter($cols, function($val) {
         * return strpos($val, 'sourceEdited') === false;
         * });
         * }
         */
        $s->from($this->db, $cols);
        $s = $this->addWatchlistJoin($s);
        $s = $this->addWhereTaskGuid($s, $taskGuid);

        if (!empty($callback)) {
            $callback($s, $this->tableName);
        }

        return parent::loadFilterdCustom($s);
    }

    /**
     * (non-PHPdoc)
     * @param string $taskGuid
     * @see ZfExtended_Models_Entity_Abstract::computeTotalCount()
     */
    public function totalCountByTaskGuid($taskGuid)
    {
        $s = $this->db->select();


        if (!empty($this->filter)) {
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
    protected function addWhereTaskGuid(Zend_Db_Table_Select $s, $taskGuid)
    {
        $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView', array($taskGuid));
        /* @var $mv editor_Models_Segment_MaterializedView */

        if ($this->tableName !== $mv->getName()) {
            $s->where($this->tableName . '.taskGuid = ?', $taskGuid);
        }
        return $s;
    }

    /**
     * Loads segments by a specific workflowStep, fetch only specific fields.
     * @param editor_Models_Task $task
     * @param string $workflowStep
     * @param int $workflowStepNr
     */
    public function loadByWorkflowStep(editor_Models_Task $task, string $workflowStep, $workflowStepNr)
    {
        $this->setConfig($task->getConfig());
        $pmChanges = $this->config->runtimeOptions->editor->notification->pmChanges;
        $this->segmentFieldManager->initFields($task->getTaskGuid());
        $this->reInitDb($task->getTaskGuid());

        $fields = array('id', 'mid', 'segmentNrInTask', 'stateId', 'autoStateId', 'matchRate', 'comments', 'fileId', 'userGuid', 'userName', 'timestamp');
        $fields = array_merge($fields, $this->segmentFieldManager->getDataIndexList());

        $this->initDefaultSort();
        $s = $this->db->select(false);
        $db = $this->db;
        $s->from($this->db, $fields);
        $s = $this->addWatchlistJoin($s);
        $s = $this->addWhereTaskGuid($s, $task->getTaskGuid());

        $autoStates = ZfExtended_Factory::get('editor_Models_Segment_AutoStates');
        /* @var $autoStates editor_Models_Segment_AutoStates */
        //get all required autostate ids
        $autoStates = $autoStates->getForWorkflowStepLoading(in_array($pmChanges, [self::PM_ALL_INCLUDED, self::PM_SAME_STEP_INCLUDED]));

        $s->where($this->tableName . '.autoStateId IN(?)', $autoStates);
        switch ($pmChanges) {
            case self::PM_ALL_INCLUDED:
                $s->where('(' . $this->tableName . '.workflowStep = ?', $workflowStep);
                $s->orWhere($this->tableName . '.workflowStep = ?)', editor_Workflow_Default::STEP_PM_CHECK);
                break;
            case self::PM_SAME_STEP_INCLUDED:
                $s->where('(' . $this->tableName . '.workflowStep = ?', $workflowStep);
                $s->orWhere('(' . $this->tableName . '.workflowStep = ?', editor_Workflow_Default::STEP_PM_CHECK);
                $s->where($this->tableName . '.workflowStepNr = ?))', $workflowStepNr);
                break;
            case self::PM_NOT_INCLUDED:
            default:
                $s->where($this->tableName . '.workflowStep = ?', $workflowStep);
                break;
        }
        $list = parent::loadFilterdCustom($s);
        
        // add the Segment's Qualities (which are stored in the qualities table) as names
        if(count($list) > 0){
            // create the list of segment Ids
            $segmentIds = [];
            foreach($list as $item){
                $segmentIds[] = $item['id'];
            }
            // we do not need to filter out locked segments here as locked segments are not fetched above anyway
            $qualityNotifications = new editor_Models_Quality_Notifications($task, $segmentIds);
            foreach($list as $item){
                $item['qualities'] = $qualityNotifications->get($item['id'], []);
            }
        }
        return $list;
    }

    /**
     * Gibt zurück ob das Segment editiertbar ist
     * @return boolean
     */
    public function isEditable()
    {
        $flag = $this->getEditable();
        return !empty($flag);
    }

    /**
     * returns Zend_Db_Table_Select joined with segment_user_assoc table if watchlistFilter is enabled
     * @param Zend_Db_Table_Select $s select statement to be modified with the watchlist join filter
     * @param string $tableName optional, for special joining purposes only, per default not needed
     * @return Zend_Db_Table_Select
     */
    public function addWatchlistJoin(Zend_Db_Table_Select $s, $tableName = null)
    {
        if (!$this->watchlistFilterEnabled) {
            return $s;
        }
        if (empty($tableName)) {
            $tableName = $this->tableName;
        }
        $db_join = ZfExtended_Factory::get('editor_Models_Db_SegmentUserAssoc');
        $userGuid = $_SESSION['user']['data']->userGuid;
        $this->filter->setDefaultTable($tableName);
        $this->filter->addTableForField('isWatched', 'sua');
        $on = 'sua.segmentId = ' . $tableName . '.id AND sua.userGuid = \'' . $userGuid . '\'';
        $s->joinLeft(array('sua' => $db_join->info($db_join::NAME)), $on, array('isWatched', 'id AS segmentUserAssocId'));
        $s->setIntegrityCheck(false);
        return $s;
    }

    /**
     * enables the watchlist filter join, for performance issues only if the user
     *   really wants to see the watchlist (isWatched is in the filter list)
     * @param bool $value optional, to force enable/disable watchlist
     */
    public function setEnableWatchlistJoin($value = null)
    {
        if (is_null($value)) {
            $value = $this->filter->hasFilter('isWatched') || $this->filter->hasSort('isWatched');
        }
        $this->watchlistFilterEnabled = $value;
    }

    /**
     * returns if the watchlist join should be enabled or not
     * @return boolean
     */
    public function getEnableWatchlistJoin()
    {
        return $this->watchlistFilterEnabled;
    }

    /**
     * returns a list with the mapping of fileIds to the segment Row Index. The Row Index is generated considering the given Filters
     * @param string $taskGuid
     * @return array
     */
    public function getFileMap($taskGuid)
    {
        //use loadByTaskGuid to initialize segmentfields and MV and so on
        //set limit = 1 to load only one record and not all records, latter one can leak memory
        $this->limit = 1;
        $this->loadByTaskGuid($taskGuid);

        $s = $this->db->select()
            ->from($this->db, array('cnt' => 'count(`' . $this->db . '`.id)', 'fileId'));
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

    protected function initDefaultSort()
    {
        if (empty($this->filter)) {
            return;
        }
        if (!$this->filter->hasSort()) {
            $this->filter->addSort('fileOrder');
        }
        if (!$this->filter->hasSort('id')) {
            $this->filter->addSort('id'); //add id as second permanent filter
        }
    }

    /**
     * Syncs the Files fileorder to the Segments Table, for faster sorted reading from segment table
     * @param string $taskguid
     * @param bool $omitView if true do not update the view
     */
    public function syncFileOrderFromFiles(string $taskguid, $omitView = false)
    {
        $infokey = Zend_Db_Table_Abstract::NAME;
        $segmentsTableName = $this->db->info($infokey);
        $filesTableName = ZfExtended_Factory::get('editor_Models_Db_Files')->info($infokey);
        $sql = $this->_syncFilesortSql($segmentsTableName, $filesTableName);
        $this->db->getAdapter()->query($sql, array($taskguid));

        if ($omitView) {
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
    protected function _syncFilesortSql(string $segmentsTable, string $filesTable)
    {
        return 'update ' . $segmentsTable . ' s, ' . $filesTable . ' f set s.fileOrder = f.fileOrder where s.fileId = f.id and f.taskGuid = ?';
    }


    /**
     * fetch the alikes of the actually loaded segment
     *
     * cannot handle alternate targets! can only handle source and target field! actually not refactored!
     *
     * @return array
     */
    public function getAlikes($taskGuid)
    {
        $this->segmentFieldManager->initFields($taskGuid);
        //if we are using alternates we cant use change alikes, that means we return an empty list here
        if (!$this->segmentFieldManager->isDefaultLayout()) {
            return array();
        }
        $segmentsViewName = $this->segmentFieldManager->getView()->getName();
        $sql = 'select id, segmentNrInTask, source, target, sourceMd5=? sourceMatch, targetMd5=? targetMatch, matchRate, autostateId
                from ' . $segmentsViewName . '
                where ((sourceMd5 = ? and sourceMd5 != ?)
                    or (targetMd5 = ? and targetMd5 != ?))
                    and taskGuid = ? and editable = 1
                order by fileOrder, id';
        //since alikes are only usable with segment field default layout we can use the following hardcoded methods
        $stmt = $this->db->getAdapter()->query($sql, array(
            $this->getSourceMd5(),
            $this->getTargetMd5(),
            $this->getSourceMd5(),
            self::EMPTY_STRING_HASH,
            $this->getTargetMd5(),
            self::EMPTY_STRING_HASH,
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
    protected function reInitDb($taskGuid)
    {
        $this->segmentFieldManager->initFields($taskGuid);
        $mv = $this->segmentFieldManager->getView();
        $mv->setTaskGuid($taskGuid);

        /* @var $mv editor_Models_Segment_MaterializedView */
        $this->db = ZfExtended_Factory::get($this->dbInstanceClass, array(array(), $mv->getName()));
        $this->dbWritable = ZfExtended_Factory::get($this->dbInstanceClass);
        $db = $this->db;
        //check if the materialized view exist, if not create it
        if (!$mv->exists()) {
            $mv->create();
        }
        $this->tableName = $db->info($db::NAME);
    }

    /**
     * overwrite for segment field integration
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::validatorLazyInstatiation()
     */
    protected function validatorLazyInstatiation()
    {
        $taskGuid = $this->getTaskGuid();
        if (empty($taskGuid)) {
            throw new Zend_Exception("For using the editor_Models_Validator_Segment Validator a taskGuid must be set in the segment!");
        }
        $this->segmentFieldManager->initFields($taskGuid);
        if (empty($this->validator)) {
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
    protected function getIdsAfterFilter(string $segmentsTableName, string $taskGuid)
    {
        $this->reInitDb($taskGuid);
        $s = $this->db->select()
            ->from($segmentsTableName, array('id'));
        $s = $this->addWatchlistJoin($s);
        $s = $this->addWhereTaskGuid($s, $taskGuid);

        //Achtung: die Klammerung von (source = ? or target = ?) beachten!
        $s->where('(' . $this->tableName . '.sourceMd5 ' . $this->_getSqlTextCompareOp() . ' ?', (string)$this->getSourceMd5())
            ->orWhere($this->tableName . '.targetMd5 ' . $this->_getSqlTextCompareOp() . ' ?)', (string)$this->getTargetMd5());
        $filteredIds = parent::loadFilterdCustom($s);
        $hasIdFiltered = array();
        foreach ($filteredIds as $ids) {
            $hasIdFiltered[$ids['id']] = true;
        }
        return $hasIdFiltered;
    }

    /**
     * Muss für MSSQL überschrieben werden und like anstatt = zurückgeben
     * @return string
     */
    protected function _getSqlTextCompareOp()
    {
        return ' = ';
        //return ' like ' bei MSSQL
    }
    
    /**
     * @param string $taskGuid
     * @return array
     */
    public function getAutoStateCount(string $taskGuid)
    {
        $this->reInitDb($taskGuid);
        $s = $this->db->select()->from($this->tableName, ['autoStateId', 'cnt' => 'count(id)'])
            ->group('autoStateId');
        return $this->db->fetchAll($s)->toArray();
    }

    /**
     * includes the fluent segment data
     * (non-PHPdoc)
     * @see ZfExtended_Models_Entity_Abstract::getModifiedData()
     */
    public function getModifiedData()
    {
        $result = parent::getModifiedData(); //assoc mit key = dataindex und value = modValue
        $modKeys = array_keys($result);
        $modFields = array_unique(array_diff($this->modified, $modKeys));
        foreach ($modFields as $field) {
            if ($this->segmentFieldManager->getDataLocationByKey($field) !== false) {
                $result[$field] = $this->get($field);
            }
        }
        return $result;
    }

    /**
     * convenient method to get the segment meta data
     * @return editor_Models_Segment_Meta
     */
    public function meta()
    {
        if (empty($this->meta)) {
            $this->meta = ZfExtended_Factory::get('editor_Models_Segment_Meta');
        } elseif ($this->getId() == $this->meta->getSegmentId()) {
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
    public function calculateSummary($taskGuid)
    {
        $cols = array('fileId', 'segmentsPerFile' => 'COUNT(id)');
        $s = $this->db->select()
            ->from($this->db, $cols);
        $s = $this->addWatchlistJoin($s);
        $s = $this->addWhereTaskGuid($s, $taskGuid);
        $s->group($this->tableName . '.fileId');
        $rows = $this->db->fetchAll($s);

        $result = array();
        foreach ($rows as $row) {
            $result[$row->fileId] = $row->segmentsPerFile;
        }
        return $result;
    }

    /**
     * returns true if all segments of the given taskGuid have empty original targets at the given moment
     * @param string $taskGuid
     * @return boolean
     */
    public function hasEmptyTargetsOnly($taskGuid)
    {
        $this->segmentFieldManager->initFields($taskGuid);
        $this->reInitDb($taskGuid);
        $s = $this->db->select(true)
            ->columns('count(*) as cnt')
            ->where('targetMd5 != ?', self::EMPTY_STRING_HASH);
        $x = $this->db->fetchRow($s);
        return ((int)$x->cnt) == 0;
    }

    /**
     * Get the total segment count for given taskGuid
     * @param string $taskGuid
     * @return number|mixed
     */
    public function getTotalSegmentsCount(string $taskGuid)
    {
        $s = $this->db->select(true)
            ->columns('count(*) as cnt')
            ->where('`taskGuid`=?', $taskGuid);

        $result = $this->db->fetchRow($s);
        return $result['cnt'] ?? 0;
    }

    /***
     * Get all segment IDs of segments which have a repetition
     * Segment repetitions are segments with the same sourceMd5 hash value.
     * If the segment does not have repetition, it will not be returned by this function.
     * The returned segments are ordered by segment id
     *
     * @param string $taskGuid
     * @return array
     */
    public function getRepetitions(string $taskGuid)
    {
        $adapter = $this->db->getAdapter();
        $mv = ZfExtended_Factory::get('editor_Models_Segment_MaterializedView');
        /* @var $mv editor_Models_Segment_MaterializedView */
        $mv->setTaskGuid($taskGuid);
        $viewName = $mv->getName();
        $sql = 'SELECT v1.id,v1.sourceMd5 FROM ' . $viewName . ' v1, (
	          SELECT sourceMd5, count(sourceMd5) cnt
               FROM ' . $viewName . '
               GROUP BY sourceMd5
              ) v2
              WHERE v2.cnt > 1 and v1.sourceMd5 = v2.sourceMd5
              ORDER BY v1.id';
        return $adapter->query($sql)->fetchAll();
    }

    /***
     * Unset row data columns which are not existing in the $dbWritable
     */
    protected function unsetMaterializedViewData()
    {
        $dataColumns = array_keys($this->row->toArray());
        $tableInfo = $this->dbWritable->info();
        $segmentColumns = $tableInfo['cols'];

        foreach ($dataColumns as $key) {
            //unset the rows not existing in the segment table
            if (!in_array($key, $segmentColumns)) {
                $this->row->__unset($key);
            }
        }
    }

    /**
     * returns true if at least one target has a translation set
     */
    public function isTargetTranslated()
    {
        foreach ($this->segmentdata as $name => $data) {
            $field = $this->segmentFieldManager->getByName($name);
            if ($field->type !== editor_Models_SegmentField::TYPE_TARGET) {
                continue;
            }
            if (!(empty($data['original']) && $data['original'] !== "0")) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieves, if the current (= editable in case of source-editing) source is empty
     * @return bool
     */
    public function hasEmptySource() : bool {
        $sourceField = $this->segmentFieldManager->getByName(editor_Models_SegmentField::TYPE_SOURCE);
        if($sourceField->editable){
            return (mb_strlen($this->getFieldEdited(editor_Models_SegmentField::TYPE_SOURCE)) === 0);
        }
        return (mb_strlen($this->getFieldOriginal(editor_Models_SegmentField::TYPE_SOURCE)) === 0);
    }

    /**
     * retrieves, if the current/edited first target is empty
     * @return bool
     */
    public function hasEmptyTarget() : bool {
        return (mb_strlen($this->getTargetEdit()) === 0);
    }

    /***
     * Set the default values for the required search parameters when no value is provided
     * @param array $parameters
     * @return array
     */
    public function setDefaultSearchParameters(array $parameters)
    {
        if (empty($parameters['searchInField'])) {
            $parameters['searchInField'] = self::DEFAULT_SEARCH_FIELD;
        }
        if (empty($parameters['searchType'])) {
            $parameters['searchType'] = self::DEFAULT_SEARCH_TYPE;
        }
        return $parameters;
    }

    /***
     * Get the last edited segment in task for the user and.
     */
    public function getLastEditedByUserAndTask(string $taskGuid, string $userGuid): int
    {
        $this->reInitDb($taskGuid);
        $mv = $this->segmentFieldManager->getView();
        //find the last edited segment for the user from the segment view table and the segments history
        $sql = 'SELECT * FROM (
                    SELECT id AS "segmentId", userGuid, timestamp AS "date" FROM '.$mv->getName().'
                    WHERE taskGuid = ?
                UNION
                    SELECT segmentId, userGuid, created AS "date" FROM
                    LEK_segment_history
                    WHERE taskGuid = ?
                ) AS merged
                WHERE userGuid = ?
                ORDER BY date DESC, segmentId ASC LIMIT 1';
        $stmt = $this->db->getAdapter()->query($sql, [$taskGuid, $taskGuid, $userGuid]);
        $result = $stmt->fetchAll();
        return $result[0]['segmentId'] ?? -1;
    }

    /***
     * The input config must be task specific.
     * @param Zend_Config $taskSpecificConfig
     */
    public function setConfig(Zend_Config $taskSpecificConfig)
    {
        $this->config = $taskSpecificConfig;
    }

    /***
     * Set the class config. If the config is not set, the taskspecific config will be loaded.
     * @return Zend_Config
     */
    public function getConfig()
    {
        if (!isset($this->config)) {
            $this->setConfig($this->getTask()->getConfig());
        }
        return $this->config;
    }

    /**
     * Get task
     *
     * @return editor_Models_Task
     */
    public function getTask() {
        return editor_ModelInstances::taskByGuid($this->getTaskGuid());
    }

    /**
     * Retrieves the Field-tags for a certain field
     * Keep in mind that the saveTo & termTaggerName fields will be set simply with the field name
     * @param editor_Models_Task $task
     * @param string $field
     * @return editor_Segment_FieldTags|NULL
     */
    public function getFieldTags(editor_Models_Task $task, string $field) : ?editor_Segment_FieldTags {
        $editField = $this->segmentFieldManager->getEditIndex($field);
        // error_log('getFieldTags: '.$field.' / '.$editField);
        // TODO: edit field may be null
        $location = $this->segmentFieldManager->getDataLocationByKey($editField);
        if($location !== false && array_key_exists($location['field'], $this->segmentdata)) {
            $fieldText = $this->segmentdata[$location['field']]->__get($location['column']);
            return new editor_Segment_FieldTags($task, $this->getId(), $fieldText, $location['field'], $editField);
        }
        return NULL;
    }
    /**
     *
     * @return Zend_Db_Table_Row_Abstract
     */
    private function getTagsModel() : Zend_Db_Table_Row_Abstract {
        // Crucial: as this model may loads multiple rows we have to make sure the id matches in case
        if($this->tagsModel == null || $this->tagsModel->segmentId != $this->getId()){
            $db = ZfExtended_Factory::get('editor_Models_Db_SegmentTags');
            /* @var $db editor_Models_Db_SegmentTags */
            $select = $db->select()->where('segmentId = ?', $this->getId());
            $this->tagsModel = $db->fetchRow($select);
            if($this->tagsModel == null){
                $this->tagsModel = $db->createRow();
                $this->tagsModel->segmentId = $this->getId();
                $this->tagsModel->taskGuid = $this->getTaskGuid();
                $this->tagsModel->tags = '';
            }
        }
        return $this->tagsModel;
    }
    /**
     *
     * @return bool
     */
    public function hasSegmentTagsJSON() : bool {
        return !empty($this->getTagsModel()->tags);
    }
    /**
     *
     * @return string
     */
    public function getSegmentTagsJSON() : string {
        return $this->getTagsModel()->tags;
    }
    /**
     * Saves the segment tags during an import and sets the status-flag for the given provider
     * @param string $json
     * @param string $providerKey
     */
    public function saveSegmentTagsJSON(string $json, string $providerKey){
        $row = $this->getTagsModel();
        $statusColumn = self::createSegmentTagsStatusColumn($providerKey);
        $row->tags = $json;
        $row->$statusColumn = 1;
        $row->save();
    }
}
