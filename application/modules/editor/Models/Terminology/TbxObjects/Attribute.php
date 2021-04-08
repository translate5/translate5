<?php

class editor_Models_Terminology_TbxObjects_Attribute {
    const TABLE_FIELDS = [
        'elementName' => true,
        'language' => true,
        'value' => true,
        'type' => true,
        'target' => true,
        'collectionId' => true,
        'termEntryId' => true,
        'termEntryGuid' => false,
        'langSetGuid' => false,
        'termId' => false,
        'labelId' => false,
        'guid' => false,
        'userGuid' => true,
        'userName' => true
    ];

    protected int $collectionId = 0;
    protected int $termEntryId = 0;
    protected string $termEntryGuid = '';
    protected string $langSetGuid = '';
    protected string $termId = '';
    protected string $guid = '';
    protected string $elementName = '';
    protected string $language = '';
    protected string $value = '';
    protected string $target = '';
    protected string $type = '';
    protected string $labelId = '';
    protected string $userGuid = '';
    protected string $userName = '';

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
     * @return int
     */
    public function getTermEntryId(): int
    {
        return $this->termEntryId;
    }

    /**
     * @param int $termEntryId
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setTermEntryId(int $termEntryId): self
    {
        $this->termEntryId = $termEntryId;
        return $this;
    }

    /**
     * @return string
     */
    public function getTermEntryGuid(): string
    {
        return $this->termEntryGuid;
    }

    /**
     * @param string $termEntryGuid
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setTermEntryGuid(string $termEntryGuid): self
    {
        $this->termEntryGuid = $termEntryGuid;
        return $this;
    }

    /**
     * @return string
     */
    public function getLangSetGuid(): string
    {
        return $this->langSetGuid;
    }

    /**
     * @param string $langSetGuid
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setLangSetGuid(string $langSetGuid): self
    {
        $this->langSetGuid = $langSetGuid;
        return $this;
    }

    /**
     * @return string
     */
    public function getTermId(): string
    {
        return $this->termId;
    }

    /**
     * @param string $termId
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setTermId(string $termId): self
    {
        $this->termId = $termId;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabelId(): string
    {
        return $this->labelId;
    }

    /**
     * @param string $labelId
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setLabelId(string $labelId): self
    {
        $this->labelId = $labelId;
        return $this;
    }

    /**
     * @return string
     */
    public function getGuid(): string
    {
        return $this->guid;
    }

    /**
     * @param string $guid
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setGuid(string $guid): self
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
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setLanguage(string $language): self
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
     * @return string
     */
    public function getUserGuid(): string
    {
        return $this->userGuid;
    }

    /**
     * @param string $userGuid
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setUserGuid(string $userGuid): self
    {
        $this->userGuid = $userGuid;
        return $this;
    }

    /**
     * @return string
     */
    public function getUserName(): string
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     * @return editor_Models_Terminology_TbxObjects_Attribute
     */
    public function setUserName(string $userName): self
    {
        $this->userName = $userName;
        return $this;
    }
}
