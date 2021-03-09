<?php
/*
 START LICENSE AND COPYRIGHT
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com
 
 This file is part of a plug-in for translate5.
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 For the license of this plug-in, please see below.
 
 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html
 
 There is a plugin exception available for use with this release of translate5 for
 translate5 plug-ins that are distributed under GNU GENERAL PUBLIC LICENSE version 3:
 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5.
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/gpl.html
 http://www.translate5.net/plugin-exception.txt
 
 END LICENSE AND COPYRIGHT
 */

/**
 * Represents an MQM tag
 * this tag "really" represents two image tags wrapping the content
 * Example as sent from the Frontend:
 * OPENER: <img class="open minor qmflag ownttip qmflag-13" data-seq="633" data-comment="No Comment" src="/modules/editor/images/imageTags/qmsubsegment-13-left.png" />
 * CLOSER: <img class="close minor qmflag ownttip qmflag-13" data-seq="633" data-comment="No Comment" src="/modules/editor/images/imageTags/qmsubsegment-13-right.png" />
 * TEMPLATE: <img class="%1$s qmflag ownttip %2$s qmflag-%3$d" data-seq="%4$d" data-comment="%5$s" src="%6$s" />
 * 
 * @method editor_Segment_Mqm_Tag clone(boolean $withDataAttribs)
 * @method editor_Segment_Mqm_Tag createBaseClone()
 */
final class  editor_Segment_Mqm_Tag extends editor_Segment_Tag {

    /**
     * @var string
     */
    const CSS_CLASS = 'qmflag';
    /**
     * @var string
     */
    const CSS_CLASS_OPEN = 'open';
    /**
     * @var string
     */
    const CSS_CLASS_CLOSE = 'close';
    /**
     * @var string
     */
    const CSS_CLASS_CRITICAL = 'critical';
    /**
     * @var string
     */
    const CSS_CLASS_MAJOR = 'major';
    /**
     * @var string
     */
    const CSS_CLASS_MINOR = 'minor';
    /**
     * @var string
     */
    const PATTERN_INDEX = '~qmsubsegment-([0-9]+)-(left|right)\.png~';
    /**
     * @var string
     */
    const IMAGE_SRC = 'qmsubsegment-{0}-{1}.png';

    protected static $type = editor_Segment_Tag::TYPE_MQM;

    protected static $nodeName = 'img';
    
    /**
     * @var string
     */
    private static $imgSrcTemplate = NULL;
    /**
     * 
     * @return string
     */
    private static function createImageSrcTemplate(){
        // when Testing, Zend_Config is not available. TODO: better setup/bootstrapping for classic unit test
        if(defined('T5_IS_UNIT_TEST')){
            return '/modules/editor/images/imageTags/'.self::IMAGE_SRC;
        }
        if(self::$imgSrcTemplate == NULL){
            $conf = Zend_Registry::get('config');
            self::$imgSrcTemplate = APPLICATION_RUNDIR.'/'.$conf->runtimeOptions->dir->tagImagesBasePath.'/'.self::IMAGE_SRC;
        }
        return self::$imgSrcTemplate;
    }
    /**
     *
     * @param int $typeIndex
     * @param string $position
     * @return string
     */
    private static function createImageSrc(int $typeIndex, string $position) : string {
        return str_replace('{0}', strval($typeIndex), str_replace('{1}', $position, self::createImageSrcTemplate()));
    }
    /**
     * Renders a basic MQM tag out of the given properties
     * 
     * @param int|string $qualityId
     * @param bool $isOpen
     * @param int $typeIndex
     * @param string $severity
     * @param string $comment
     * @return string
     */
    public static function renderTag($qualityId, bool $isOpen, int $typeIndex, string $severity, string $comment) : string {
        // we follow the original code here
        // <img class="%1$s qmflag ownttip %2$s qmflag-%3$d" data-seq="%4$d" data-comment="%5$s" src="%6$s" />
        $position = ($isOpen) ? 'left' : 'right';
        $posClass = ($isOpen) ? self::CSS_CLASS_OPEN : self::CSS_CLASS_CLOSE;
        $tag = editor_Tag::img(self::createImageSrc($typeIndex, $position));
        $tag
            ->addClass($severity)
            ->addClass(self::CSS_CLASS)
            ->addClass(editor_Segment_Tag::CSS_CLASS_TOOLTIP)
            ->addClass($posClass)
            ->addClass(self::CSS_CLASS.'-'.strval($typeIndex))
            ->setData('seq', strval($qualityId))
            ->setData('comment', $comment);
        return $tag->render();
    }
    /**
     *
     * @var bool
     */
    private $paired = false;
    /**
     * 
     * @var int
     */
    private $typeIndex = -1;
    /**
     *
     * @var string
     */
    private $severity = '';
    /**
     * 
     * @var string
     */
    private $comment = '';
    
    /**
     * 
     * @return int
     */
    public function getTypeIndex() : int {
        return $this->typeIndex;
    }
    /**
     * 
     * @return string
     */
    public function getSeverity() : string {
        return $this->severity;
    }
    /**
     * 
     * @return string
     */
    public function getComment() : string {
        return $this->comment;
    }
    /**
     * 
     * @return bool
     */
    public function isPaired() : bool {
        return $this->paired;
    }

    /* Overwritten API to reflect pairing */
    
    public function clone($withDataAttribs=false){
        $clone = parent::clone($withDataAttribs);
        /* @var $clone editor_Segment_Mqm_Tag */
        $clone->setMqmProps($this->paired, $this->typeIndex, $this->severity, $this->comment);
        return $clone;
    }
    
    protected function renderStart($withDataAttribs=true) : string {
        if($this->paired){
            return $this->renderImageTag(true);
        }
        return parent::renderStart($withDataAttribs);
    }

    protected function renderEnd() : string {
        if($this->paired){
            return $this->renderImageTag(false);
        }
        return parent::renderEnd();
    }
    /**
     * 
     * @param boolean $isOpener
     * @return string
     */
    private function renderImageTag(bool $isOpener) : string {
        $tag = $this->cloneProps(editor_Tag::img(), true);
        $position = ($isOpener) ? 'left' : 'right';
        $className = ($isOpener) ? self::CSS_CLASS_OPEN : self::CSS_CLASS_CLOSE;
        $tag->setSource(self::createImageSrc($this->typeIndex, $position));
        // to resemble the otherwise used structure we prepend this class. In General, CSS-classes should be independent from position ...
        $tag->prependClass($className);
        return $tag->render();
    }
    /**
     * Adds additional clone properties
     * @param bool $paired
     * @param int $typeIndex
     * @param string $severity
     * @param string $comment
     * @return editor_Segment_Mqm_Tag
     */
    private function setMqmProps(bool $paired, int $typeIndex, string $severity, string $comment) : editor_Segment_Mqm_Tag{
        $this->paired = $paired;
        $this->singular = !$paired;
        $this->typeIndex = $typeIndex;
        $this->severity = $severity;
        $this->comment = $comment;
        return $this;
    }
    
    /* Consolidation API */
    
    public function isPairedOpener() : bool {
        return ($this->hasClass(self::CSS_CLASS_OPEN));
    }

    public function isPairedCloser() : bool {
        return ($this->hasClass(self::CSS_CLASS_CLOSE));
    }
    
    public function isObsolete() : bool {
        // we discard any invalid mqm tags, e.g. those spanning no text
        return (!$this->paired || $this->startIndex == $this->endIndex || $this->typeIndex == -1 || $this->severity == '');
    }
    
    public function onConsolidationRemoval() {
        // we don't want exceptions on empty mqm tags (which do not make sense but also are no real error and will be removed silently) or on unit tests
        if($this->startIndex == $this->endIndex || defined('T5_IS_UNIT_TEST')){
            return;
        }
        // TODO AUTOQA: Code is copied from editor_Models_Qmsubsegments, needed ??
        // tags spanning no text will be removed silently
        if($this->getData('seq') == null){
            throw new Zend_Exception('MQM Tag found, but no quality-id (data-seq) was set in: '.$this->renderStart());
        } else if($this->typeIndex == -1){
            throw new Zend_Exception('MQM Tag found, but no type index was set in: '.$this->renderStart());
        } else if($this->severity == ''){
            throw new Zend_Exception('MQM Tag found, but no severity was set in: '.$this->renderStart());
        }
    }
    /**
     * The passed $tag is a tag if the same type and is a paired closer
     * This is the place where a un-paired tag changes to a "real" paired MQM Tag
     * {@inheritDoc}
     * @see editor_Segment_Tag::pairWith()
     */
    public function pairWith(editor_Segment_Tag $tag) : bool {
        if($this->getData('seq') == null || $tag->getData('seq') != $this->getData('seq')){
            return false;
        }
        if(editor_Segment_FieldTags::VALIDATION_MODE && $this->endIndex != $this->startIndex || $tag->endIndex != $tag->startIndex){
            error_log("\n##### MQM TAG: INVALID INDEXES FOUND [open: (".$this->startIndex."|".$this->endIndex.") close: (".$tag->startIndex."|".$tag->endIndex.")] #####\n");
        }
        $this->paired = true;
        $this->singular = false;
        $this->endIndex = $tag->startIndex;
        $this->removeClass(self::CSS_CLASS_OPEN)->removeClass(self::CSS_CLASS_OPEN);
        if($this->hasClass(self::CSS_CLASS_CRITICAL)){
            $this->severity = self::CSS_CLASS_CRITICAL;
        } else if($this->hasClass(self::CSS_CLASS_MAJOR)){
            $this->severity = self::CSS_CLASS_MAJOR;
        } else if($this->hasClass(self::CSS_CLASS_MINOR)){
            $this->severity = self::CSS_CLASS_MINOR;
        }
        $src = $this->getAttribute('src');
        $matches = array();
        if(preg_match(self::PATTERN_INDEX, $src, $matches)){
            $this->typeIndex = intval($matches[1]);
        }
        $this->comment = htmlspecialchars_decode($this->getData('comment'));
 
        return true;
    }
    
    protected function furtherSerialize(stdClass $data){
        $data->paired = $this->paired;
        $data->typeIndex = $this->typeIndex;
        $data->comment = $this->comment;
        $data->severity = $this->severity;
    }
    
    protected function furtherUnserialize(stdClass $data){
        $this->paired = $data->paired;
        $this->typeIndex = $data->typeIndex;
        $this->comment = $data->comment;
        $this->severity = $data->severity;
    }
}
