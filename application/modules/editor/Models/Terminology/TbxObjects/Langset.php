<?php

class editor_Models_Terminology_TbxObjects_Langset {
    const TABLE_FIELDS = [
        'descrip',
        'transacNote',
        'transacType',
        'collectionId',
        'entryId',
        'termEntryGuid',
        'langSetGuid',
        'guid'
    ];
    protected int $collectionId = 0;
    protected int $entryId = 0;
    protected string $termEntryGuid = '';
    protected string $langSetGuid = '';
    protected string $descripGrpGuid = '';
    protected string $language = '';
    protected string $languageId = '';
    protected string $descrip = '';
    protected string $descripType = '';
    protected string $descripTarget = '';
    protected array $descripGrp = [];

    /**
     * @return int
     */
    public function getCollectionId(): int
    {
        return $this->collectionId;
    }

    /**
     * @param int $collectionId
     * @return editor_Models_Terminology_TbxObjects_Langset
     */
    public function setCollectionId(int $collectionId): self
    {
        $this->collectionId = $collectionId;
        return $this;
    }

    /**
     * @return int
     */
    public function getEntryId(): int
    {
        return $this->entryId;
    }

    /**
     * @param int $entryId
     * @return editor_Models_Terminology_TbxObjects_Langset
     */
    public function setEntryId(int $entryId): self
    {
        $this->entryId = $entryId;
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
     * @return editor_Models_Terminology_TbxObjects_Langset
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
     * @return editor_Models_Terminology_TbxObjects_Langset
     */
    public function setLangSetGuid(string $langSetGuid): self
    {
        $this->langSetGuid = $langSetGuid;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescripGrpGuid(): string
    {
        return $this->descripGrpGuid;
    }

    /**
     * @param string $descripGrpGuid
     * @return editor_Models_Terminology_TbxObjects_Langset
     */
    public function setDescripGrpGuid(string $descripGrpGuid): self
    {
        $this->descripGrpGuid = $descripGrpGuid;
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
     * @return editor_Models_Terminology_TbxObjects_Langset
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguageId(): string
    {
        return $this->languageId;
    }

    /**
     * @param string $languageId
     * @return editor_Models_Terminology_TbxObjects_Langset
     */
    public function setLanguageId(string $languageId): self
    {
        $this->languageId = $languageId;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescrip(): string
    {
        return $this->descrip;
    }

    /**
     * @param string $descrip
     * @return editor_Models_Terminology_TbxObjects_Langset
     */
    public function setDescrip(string $descrip): self
    {
        $this->descrip = $descrip;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescripType(): string
    {
        return $this->descripType;
    }

    /**
     * @param string $descripType
     * @return editor_Models_Terminology_TbxObjects_Langset
     */
    public function setDescripType(string $descripType): self
    {
        $this->descripType = $descripType;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescripTarget(): string
    {
        return $this->descripTarget;
    }

    /**
     * @param string $descripTarget
     * @return editor_Models_Terminology_TbxObjects_Langset
     */
    public function setDescripTarget(string $descripTarget): self
    {
        $this->descripTarget = $descripTarget;
        return $this;
    }

    /**
     * @return array
     */
    public function getDescripGrp(): array
    {
        return $this->descripGrp;
    }

    /**
     * @param array $descripGrp
     * @return editor_Models_Terminology_TbxObjects_Langset
     */
    public function setDescripGrp(array $descripGrp): self
    {
        $this->descripGrp[] = $descripGrp;
        return $this;
    }

}