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
class editor_Models_Terminology_TbxObjects_Attribute {
    CONST ATTRIBUTE_LEVEL_ENTRY = 'entry';
    CONST ATTRIBUTE_LEVEL_LANGUAGE = 'language';
    CONST ATTRIBUTE_LEVEL_TERM = 'term';
    CONST ATTRIBUTE_DEFAULT_DATATYPE = 'plainText';

    const TABLE_FIELDS = [
        'collectionId' => true,
        'termEntryId' => true,
        'language' => true,
        'termId' => false,
        'dataTypeId' => true,
        'type' => true,
        'value' => true,
        'target' => true,
        // 'isCreatedLocally' => ?
        'createdBy' => false,
        'createdAt' => false,
        // 'updatedBy' => ?
        // 'updatedAt' => ?
        'termEntryGuid' => false,
        'langSetGuid' => false,
        'termGuid' => false,
        'guid' => false,
        'elementName' => true,
        'attrLang' => false,
    ];

    protected int $collectionId = 0;
    protected ?int $termEntryId = null;
    protected ?string $language = '';
    protected ?int $termId = null;
    protected int $dataTypeId = 0;
    protected string $type = '';
    protected string $value = '';
    protected string $target = '';
    //protected int $isCreatedLocally = 0;
    protected int $createdBy = 0;
    protected string $createdAt = '';
    //protected int $updatedBy = 0;
    //protected string $updatedAt = '';
    protected ?string $termEntryGuid = null;
    protected ?string $langSetGuid = null;
    protected ?string $termGuid = null;
    protected ?string $guid = null;
    protected string $elementName = '';
    protected string $attrLang = '';

    /**
     * @param editor_Models_Terminology_TbxObjects_Attribute $element
     * @return string
     */
    public function getCollectionKey(editor_Models_Terminology_TbxObjects_Attribute $element): string
    {
        return $element->getElementName() . '-' . $element->getLanguage() . '-' . $element->getTermId();
    }

    /**
     * @return int
     */
    public function getCollectionId(): int
    {
        return $this->collectionId;
    }

    /**
     * @param int $collectionId
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setCollectionId(int $collectionId): self
    {
        $this->collectionId = $collectionId;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getTermEntryId(): ?int
    {
        return $this->termEntryId;
    }

    /**
     * @param int|null $termEntryId
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setTermEntryId(?int $termEntryId): self
    {
        $this->termEntryId = $termEntryId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTermEntryGuid(): ?string
    {
        return $this->termEntryGuid;
    }

    /**
     * @param string|null $termEntryGuid
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setTermEntryGuid(?string $termEntryGuid): self
    {
        $this->termEntryGuid = $termEntryGuid;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLangSetGuid(): ?string
    {
        return $this->langSetGuid;
    }

    /**
     * @param string|null $langSetGuid
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setLangSetGuid(?string $langSetGuid): self
    {
        $this->langSetGuid = $langSetGuid;
        return $this;
    }

    /**
     * @return string
     */
    public function getAttrLang(): string
    {
        return $this->attrLang;
    }

    /**
     * @param string $attrLang
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setAttrLang(string $attrLang): self
    {
        $this->attrLang = $attrLang;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getTermId(): ?int
    {
        return $this->termId;
    }

    /**
     * @param int|null $termId
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setTermId(?int $termId): self
    {
        $this->termId = $termId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTermGuid(): ?string
    {
        return $this->termGuid;
    }

    /**
     * @param string|null $termGuid
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setTermGuid(?string $termGuid): self
    {
        $this->termGuid = $termGuid;
        return $this;
    }

    /**
     * @return int
     */
    public function getDataTypeId(): int
    {
        return $this->dataTypeId;
    }

    /**
     * @param int $dataTypeId
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setDataTypeId(int $dataTypeId): self
    {
        $this->dataTypeId = $dataTypeId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGuid(): ?string
    {
        return $this->guid;
    }

    /**
     * @param string|null $guid
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setGuid(?string $guid): self
    {
        $this->guid = $guid;
        return $this;
    }

    /**
     * @return string
     */
    public function getElementName(): string
    {
        return $this->elementName;
    }

    /**
     * @param string $elementName
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setElementName(string $elementName): self
    {
        $this->elementName = $elementName;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @param string|null $language
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setLanguage(?string $language): self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @param string $target
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setTarget(string $target): self
    {
        $this->target = $target;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    /**
     * @param int $userId
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setCreatedBy(int $userId): self
    {
        $this->createdBy = $userId;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    /**
     * @param string $createdAt
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return string
     */
    public function getUpdatedBy(): int
    {
        return $this->updatedBy;
    }

    /**
     * @param int $userId
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setUpdatedBy(int $userId): self
    {
        $this->updatedBy = $userId;
        return $this;
    }

    /**
     * @return string
     */
    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    /**
     * @param string $updatedAt
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setUpdatedAt(string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getLevel(){
        if(empty($this->language)){
            return self::ATTRIBUTE_LEVEL_ENTRY;
        }
        if(empty($this->termGuid) && !empty($this->termEntryGuid)){
            return self::ATTRIBUTE_LEVEL_LANGUAGE;
        }
        if(!empty($this->termGuid) && !empty($this->termEntryGuid)){
            return self::ATTRIBUTE_LEVEL_TERM;
        }
        // TODO: error code for non existing level. this should never happen, if it does, this is not a valid attribute
        throw new ZfExtended_ErrorCodeException();
    }
}
