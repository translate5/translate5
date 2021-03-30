<?php

class editor_Models_Terminology_TbxObjects_TransacGrp {
    const TABLE_FIELDS = [
        'transac' => true,
        'date' => true,
        'adminType' => true,
        'adminValue' => true,
        'transacNote' => true,
        'transacType' => true,
        'ifDescripgrp' => false,
        'collectionId' => false,
        'entryId' => false,
        'termId' => false,
        'termEntryGuid' => false,
        'langSetGuid' => false,
        'guid' => false,
        'elementName' => true
    ];
    protected int $collectionId = 0;
    protected string $entryId = '';
    protected string $termId = '';
    protected string $termEntryGuid = '';
    protected string $langSetGuid = '';
    protected string $descripGrpGuid = '';
    protected string $guid = '';
    protected string $elementName = '';
    protected string $language = '';
    protected string $adminValue = '';
    protected string $adminType = '';
    protected string $transac = '';
    protected string $date = '';
    protected string $transacNote = '';
    protected string $transacType = '';
    protected int $ifDescripGrp = 0;

    /**
     * @param editor_Models_Terminology_TbxObjects_TransacGrp $element
     * @return string
     */
    public function getCollectionKey(editor_Models_Terminology_TbxObjects_TransacGrp $element): string
    {
        return $element->getElementName() . '-' . $element->getTransac() . '-' . $element->getIfDescripGrp() . '-' . $element->getTermId();
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
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setCollectionId(int $collectionId): self
    {
        $this->collectionId = $collectionId;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntryId(): string
    {
        return $this->entryId;
    }

    /**
     * @param string $entryId
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setEntryId(string $entryId): self
    {
        $this->entryId = $entryId;
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
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setTermId(string $termId): self
    {
        $this->termId = $termId;
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
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
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
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setLangSetGuid(string $langSetGuid): self
    {
        $this->langSetGuid = $langSetGuid;
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
     * @return string
     */
    public function getElementName(): string
    {
        return $this->elementName;
    }

    /**
     * @param string $elementName
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setElementName(string $elementName): self
    {
        $this->elementName = $elementName;
        return $this;
    }

    /**
     * @param string $guid
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setGuid(string $guid): self
    {
        $this->guid = $guid;
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
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
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
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return string
     */
    public function getAdminValue(): string
    {
        return $this->adminValue;
    }

    /**
     * @param string $adminValue
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setAdminValue(string $adminValue): self
    {
        $this->adminValue = $adminValue;
        return $this;
    }

    /**
     * @return string
     */
    public function getAdminType(): string
    {
        return $this->adminType;
    }

    /**
     * @param string $adminType
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setAdminType(string $adminType): self
    {
        $this->adminType = $adminType;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransac(): string
    {
        return $this->transac;
    }

    /**
     * @param string $transac
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setTransac(string $transac): self
    {
        $this->transac = $transac;
        return $this;
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param string $date
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setDate(string $date): self
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransacNote(): string
    {
        return $this->transacNote;
    }

    /**
     * @param string $transacNote
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setTransacNote(string $transacNote): self
    {
        $this->transacNote = $transacNote;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransacType(): string
    {
        return $this->transacType;
    }

    /**
     * @param string $transacType
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setTransacType(string $transacType): self
    {
        $this->transacType = $transacType;
        return $this;
    }

    /**
     * @return int
     */
    public function getIfDescripGrp(): int
    {
        return $this->ifDescripGrp;
    }

    /**
     * @param int $ifDescripGrp
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setIfDescripGrp(int $ifDescripGrp): self
    {
        $this->ifDescripGrp = $ifDescripGrp;
        return $this;
    }

}