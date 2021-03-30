<?php

class editor_Models_Terminology_TbxObjects_TermEntry {
    const TABLE_FIELDS = [
        'collectionId',
        'termEntryId',
        'isProposal',
        'entryGuid',
        'descrip'
    ];
    protected int $collectionId = 0;
    protected string $termEntryId = '';
    protected string $entryGuid = '';
    protected array $descrip = [];
    protected string $descripValue = '';
    protected array $transacGrp = [];
    protected array $xref = [];
    protected array $ref = [];

    /**
     * @return int
     */
    public function getCollectionId(): int
    {
        return $this->collectionId;
    }

    /**
     * @param int $collectionId
     * @return editor_Models_Terminology_TbxObjects_TermEntry
     */
    public function setCollectionId(int $collectionId): self
    {
        $this->collectionId = $collectionId;
        return $this;
    }

    /**
     * @return string
     */
    public function getTermEntryId(): string
    {
        return $this->termEntryId;
    }

    /**
     * @param string $termEntryId
     * @return editor_Models_Terminology_TbxObjects_TermEntry
     */
    public function setTermEntryId(string $termEntryId): self
    {
        $this->termEntryId = $termEntryId;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntryGuid(): string
    {
        return $this->entryGuid;
    }

    /**
     * @param string $entryGuid
     * @return editor_Models_Terminology_TbxObjects_TermEntry
     */
    public function setEntryGuid(string $entryGuid): self
    {
        $this->entryGuid = $entryGuid;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescrip(): string
    {
        $descripFromAttribute = '';
        foreach ($this->descrip as $descrip) {
            $descripFromAttribute = $descrip->getValue();
        }

        return $descripFromAttribute;
    }

    /**
     * $descrip[] have following keys:
     * - type
     * - target
     * - value
     *
     * @param array $descrip
     * @return editor_Models_Terminology_TbxObjects_TermEntry
     */
    public function setDescrip(array $descrip): self
    {
        $this->descrip = $descrip;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescripValue(): string
    {
        return $this->descripValue;
    }

    /**
     * @param string $descripValue
     * @return editor_Models_Terminology_TbxObjects_TermEntry
     */
    public function setDescripValue(string $descripValue): self
    {
        $this->descripValue = $descripValue;
        return $this;
    }

    /**
     * @return array
     */
    public function getTransacGrp(): array
    {
        return $this->transacGrp;
    }

    /**
     * $transacGrp[] have following keys:
     * - transac
     * - date
     * - transacType
     * - transacValue
     * @param array $transacGrp
     * @return editor_Models_Terminology_TbxObjects_TermEntry
     */
    public function setTransacGrp(array $transacGrp): self
    {
        $this->transacGrp[] = $transacGrp;

        return $this;
    }

    /**
     * @return array
     */
    public function getXref(): array
    {
        return $this->xref;
    }

    /**
     * @param array $xref
     * @return editor_Models_Terminology_TbxObjects_TermEntry
     */
    public function setXref(array $xref): self
    {
        $this->xref[] = $xref;
        return $this;
    }

    /**
     * @return array
     */
    public function getRef(): array
    {
        return $this->ref;
    }

    /**
     * @param array $ref
     * @return editor_Models_Terminology_TbxObjects_TermEntry
     */
    public function setRef(array $ref): self
    {
        $this->ref[] = $ref;
        return $this;
    }

}