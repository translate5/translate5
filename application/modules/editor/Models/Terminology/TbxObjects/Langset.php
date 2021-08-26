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
class editor_Models_Terminology_TbxObjects_Langset extends editor_Models_Terminology_TbxObjects_Abstract{
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
    protected ?string $termEntryGuid = null;
    protected ?string $langSetGuid = null;
    protected ?string $descripGrpGuid = null;
    protected string $language = '';
    protected string $languageId = '';
    protected string $descrip = '';
    protected string $descripType = '';
    protected string $descripTarget = '';
    protected array $descripGrp = [];
    protected array $note = [];

    /**
     * @return string
     */
    public function getCollectionKey(): string
    {
        return $this->getTermEntryId().'-'.$this->getLanguage();
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
     * @return string|null
     */
    public function getTermEntryGuid(): ?string
    {
        return $this->termEntryGuid;
    }

    /**
     * @param string|null $termEntryGuid
     * @return editor_Models_Terminology_TbxObjects_Langset
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
     * @return editor_Models_Terminology_TbxObjects_Langset
     */
    public function setLangSetGuid(?string $langSetGuid): self
    {
        $this->langSetGuid = $langSetGuid;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescripGrpGuid(): ?string
    {
        return $this->descripGrpGuid;
    }

    /**
     * @param string|null $descripGrpGuid
     * @return editor_Models_Terminology_TbxObjects_Langset
     */
    public function setDescripGrpGuid(?string $descripGrpGuid): self
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

}
