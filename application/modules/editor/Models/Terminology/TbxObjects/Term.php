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
class editor_Models_Terminology_TbxObjects_Term {
    /**
     * Table field for insert or update.
     * If:
     * 'fieldName' => false -> only insert no check for update attribute
     * 'fieldName' => true -> insert and update
     */
    const TABLE_FIELDS = [
        'termTbxId' => false,
        'collectionId' => false,
        'termEntryId' => false,
        'termEntryTbxId' => false,
        'termEntryGuid' => false,
        'langSetGuid' => false,
        'guid' => false,
        'languageId' => false,
        'language' => false,
        'term' => true,
        'status' => false,
        'processStatus' => false,
        'definition' => false,
        'userGuid' => true,
        'created' => true,
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
    protected string $termTbxId = '';
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
    protected int $termEntryId = 0;
    protected string $termEntryTbxId = '';
    protected ?string $termEntryGuid = null;
    protected ?string $langSetGuid = null;
    protected ?string $guid = null;
    protected string $userGuid = '';
    protected string $userName = '';
    protected string $created = '';

    /**
     * @param editor_Models_Terminology_TbxObjects_Term $element
     * @return string
     */
    public function getCollectionKey(editor_Models_Terminology_TbxObjects_Term $element): string
    {
        return $element->getTermEntryId().'-'.$element->getLanguage().'-'.$element->getTermTbxId();
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
    public function getTermTbxId(): string
    {
        return $this->termTbxId;
    }

    /**
     * @param string $termTbxId
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setTermTbxId(string $termTbxId): self
    {
        $this->termTbxId = $termTbxId;
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
    public function getTermEntryId(): int
    {
        return $this->termEntryId;
    }

    /**
     * @param int $termEntryId
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setTermEntryId(int $termEntryId): self
    {
        $this->termEntryId = $termEntryId;
        return $this;
    }

    /**
     * @return string
     */
    public function getTermEntryTbxId(): string
    {
        return $this->termEntryTbxId;
    }

    /**
     * @param string $termEntryTbxId
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setTermEntryTbxId($termEntryTbxId): self
    {
        $this->termEntryTbxId = $termEntryTbxId;
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
     * @return editor_Models_Terminology_TbxObjects_Term
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
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setLangSetGuid(?string $langSetGuid): self
    {
        $this->langSetGuid = $langSetGuid;
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
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setGuid(?string $guid): self
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

    /**
     * @return string
     */
    public function getCreated(): string
    {
        return $this->created;
    }

    /**
     * @param string $created
     * @return editor_Models_Terminology_TbxObjects_Term
     */
    public function setCreated(string $created): self
    {
        $this->created = $created;
        return $this;
    }

}
