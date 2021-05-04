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
     * The central unique type amongst quality providersKey to identify termtagger-related stuff. Must match editor_Plugins_TermTagger_QualityProvider::$type
     * @var string
     */
    protected static $type = self::TYPE;

    protected static $nodeName = 'div';
    
    protected static $identificationClass = self::TYPE;
    
    /**
     * Our css-classes are connected with the category, we must reflect that
     * {@inheritDoc}
     * @see editor_Segment_Tag::setCategory()
     */
    public function setCategory(string $category) : editor_Segment_Tag {
        $this
            ->removeClass($this->category)
            ->addClass($category);
        $this->category = $category;
        return $this;
    }
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
