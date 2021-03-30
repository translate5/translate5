<?php

class editor_Models_Terminology_TbxObjects_Term {
    const TABLE_FIELDS = [
        'termId' => false,
        'collectionId' => false,
        'entryId' => false,
        'termEntryGuid' => false,
        'langSetGuid' => false,
        'guid' => false,
        'languageId' => false,
        'language' => false,
        'term' => true,
        'descrip' => false,
        'descripType' => false,
        'descripTarget' => false,
        'status' => false,
        'processStatus' => false,
        'definition' => false,
        'userGuid' => true,
        'userName' => true
    ];

    const TERM_DEFINITION = 'definition';
    const TERM_STANDARD_PROCESS_STATUS= 'finalized';
    const STAT_PREFERRED = 'preferredTerm';
    const STAT_ADMITTED = 'admittedTerm';
    const STAT_LEGAL = 'legalTerm';
    const STAT_REGULATED = 'regulatedTerm';
    const STAT_STANDARDIZED = 'standardizedTerm';
    const STAT_DEPRECATED = 'deprecatedTerm';
    const STAT_SUPERSEDED = 'supersededTerm';
    const STAT_NOT_FOUND = 'STAT_NOT_FOUND'; //Dieser Status ist nicht im Konzept definiert, sondern wird nur intern verwendet!
    const TRANSSTAT_FOUND = 'transFound';
    const TRANSSTAT_NOT_FOUND = 'transNotFound';
    const TRANSSTAT_NOT_DEFINED ='transNotDefined';
    const CSS_TERM_IDENTIFIER = 'term';

    protected string $term = '';
    protected string $termId = '';
    protected string $language = '';
    protected int $languageId = 0;
    protected string $descrip = '';
    protected string $descripType = '';
    protected string $descripTarget = '';
    protected string $status = '';
    protected string $processStatus = '';
    protected string $definition = '';
    protected array $admin = [];
    protected array $xref = [];
    protected array $ref = [];
    protected array $transacGrp = [];
    protected array $transacNote = [];
    protected int $collectionId = 0;
    protected array $termNote = [];
    protected array $note = [];
    protected int $entryId = 0;
    protected string $termEntryGuid = '';
    protected string $langSetGuid = '';
    protected string $guid = '';
    protected string $userGuid = '';
    protected string $userName = '';

    /**
     * @param editor_Models_Terminology_TbxObjects_Term $element
     * @return string
     */
    public function getCollectionKey(editor_Models_Terminology_TbxObjects_Term $element): string
    {
        return $element->getEntryId().'-'.$element->getLanguage().'-'.$element->getTermId();
    }

    /**
     * @return string
     */
    public function getTerm(): string
    {
        return $this->term;
    }

    /**
     * @param string $term
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setTerm(string $term): self
    {
        $this->term =  $term;
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
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setTermId(string $termId): self
    {
        $this->termId = $termId;
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
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return int
     */
    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    /**
     * @param int $languageId
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setLanguageId(int $languageId): self
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
     * @return editor_Models_Terminology_TbxObjects_Term
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
     * @return editor_Models_Terminology_TbxObjects_Term
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
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setDescripTarget(string $descripTarget): self
    {
        $this->descripTarget = $descripTarget;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getProcessStatus(): string
    {
        return $this->processStatus;
    }

    /**
     * @param string $processStatus
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setProcessStatus(string $processStatus): self
    {
        $this->processStatus = $processStatus;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefinition(): string
    {
        return $this->definition;
    }

    /**
     * @param string $definition
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setDefinition(string $definition): self
    {
        $this->definition = $definition;
        return $this;
    }

    /**
     * @return array
     */
    public function getTermNote(): array
    {
        return $this->termNote;
    }

    /**
     * @param array $termNote
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setTermNote(array $termNote): self
    {
        $this->termNote[] = $termNote;
        return $this;
    }

    /**
     * @return array
     */
    public function getNote(): array
    {
        return $this->note;
    }

    /**
     * @param array $note
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setNote(array $note): self
    {
        $this->note[] = $note;
        return $this;
    }

    /**
     * @return array
     */
    public function getAdmin(): array
    {
        return $this->admin;
    }

    /**
     * @param array $admin
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setAdmin(array $admin): self
    {
        $this->admin[] = $admin;
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
     * @return editor_Models_Terminology_TbxObjects_Term
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
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setRef(array $ref): self
    {
        $this->ref[] = $ref;
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
     * @param array $transacGrp
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setTransacGrp(array $transacGrp): self
    {
        $this->transacGrp[] = $transacGrp;
        return $this;
    }

    /**
     * @return array
     */
    public function getTransacNote(): array
    {
        return $this->transacNote;
    }

    /**
     * @param array $transacNote
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setTransacNote(array $transacNote): self
    {
        $this->transacNote[] = $transacNote;
        return $this;
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
     * @return editor_Models_Terminology_TbxObjects_Term
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
     * @return editor_Models_Terminology_TbxObjects_Term
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
     * @return editor_Models_Terminology_TbxObjects_Term
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
     * @return editor_Models_Terminology_TbxObjects_Term
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
     * @param string $guid
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setGuid(string $guid): self
    {
        $this->guid = $guid;
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
     * @return editor_Models_Terminology_TbxObjects_Term
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
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setUserName(string $userName): self
    {
        $this->userName = $userName;
        return $this;
    }

}