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

/**
 * Represents a termtagger segment tag
 */
class editor_Plugins_TermTagger_Tag extends editor_Segment_Tag {
    
    /**
     * Central Key to identify term tags & qualities
     * @var string
     */
    const TYPE = 'term';

    /**
     * Our related term-id
     * @var string
     */
    const DATA_NAME_TBXID = 'tbxid';

    /**
     * Filters a quality state out of an array of term tag css-classes (=states)
     * Returns an empty string if any found
     * @param array $cssClasses
     * @param bool $isSourceField
     * @return string
     */
    public static function getQualityState(array $cssClasses, bool $isSourceField): string
    {
        foreach ($cssClasses as $cssClass) {
            switch ($cssClass) {
                case editor_Models_Terminology_Models_TermModel::TRANSSTAT_NOT_FOUND:
                    if ($isSourceField) {
                        return editor_Plugins_TermTagger_QualityProvider::NOT_FOUND_IN_TARGET;
                    }
                    break;

                case editor_Models_Terminology_Models_TermModel::TRANSSTAT_NOT_DEFINED:
                    if ($isSourceField) {
                        return editor_Plugins_TermTagger_QualityProvider::NOT_DEFINED_IN_TARGET;
                    }
                    break;

                case editor_Models_Terminology_Models_TermModel::STAT_SUPERSEDED:
                case editor_Models_Terminology_Models_TermModel::STAT_DEPRECATED:
                    if ($isSourceField) {
                        return editor_Plugins_TermTagger_QualityProvider::FORBIDDEN_IN_SOURCE;
                    } else {
                        return editor_Plugins_TermTagger_QualityProvider::FORBIDDEN_IN_TARGET;
                    }
            }
        }
        return '';
    }
    /**
     * The central unique type amongst quality providersKey to identify termtagger-related stuff. Must match editor_Plugins_TermTagger_QualityProvider::$type
     * @var string
     */
    protected static $type = self::TYPE;

    protected static $nodeName = 'div';
    
    protected static $identificationClass = self::TYPE;
    
    /**
     * Adds the TBX Id to our additional data
     * {@inheritDoc}
     * @see editor_Segment_Tag::getAdditionalData()
     */
    public function getAdditionalData() : stdClass {
        $data = parent::getAdditionalData();
        if($this->hasData(self::DATA_NAME_TBXID)){
            $data->tbxid = $this->getData(self::DATA_NAME_TBXID);
        }
        return $data;
    }
    /**
     * We evaluate our category by the classes we have. Note, that additionally the originating field type is relevant for evaluation !
     * {@inheritDoc}
     * @see editor_Segment_Tag::finalize()
     */
    public function finalize(editor_TagSequence $tags, editor_Models_task $task){
        $this->category = static::getQualityState($this->classes, $tags->isSourceField());
    }
    /**
     * Compares the TBX Id instead of the content
     * {@inheritDoc}
     * @see editor_Segment_Tag::isQualityContentEqual()
     */
    protected function isQualityContentEqual(editor_Models_Db_SegmentQualityRow $quality) : bool {
        $data = $quality->getAdditionalData();
        return ($this->hasTbxId() && property_exists($data, 'tbxid') && $data->tbxid == $this->getTbxId());
    }
    /**
     * Retrieves the TBX Id if set, otherwise NULL
     * @return string
     */
    public function getTbxId() : ?string {
        if($this->hasTbxId()){
            return $this->getData(self::DATA_NAME_TBXID);
        }
        return NULL;
    }
    /**
     * Retrieves if a TBX Id is set as data attribute
     * @return bool
     */
    public function hasTbxId() : bool {
        return $this->hasData(self::DATA_NAME_TBXID);
    }
    /**
     * Sets the TBX Id
     * @param string $tbxId
     * @return editor_Plugins_TermTagger_Tag
     */
    public function setTbxId(string $tbxId) : editor_Plugins_TermTagger_Tag {
        if(strlen($tbxId) > 0){
            $this->setData(self::DATA_NAME_TBXID, $tbxId);
        }
        return $this;
    }
}
