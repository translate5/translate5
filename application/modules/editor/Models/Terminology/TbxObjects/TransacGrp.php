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
class editor_Models_Terminology_TbxObjects_TransacGrp extends editor_Models_Terminology_TbxObjects_Abstract{
    /**
     * Table field for insert or update.
     * If:
     * 'fieldName' => false -> only insert no check for update attribute
     * 'fieldName' => true -> insert and update
     */
    const TABLE_FIELDS = [
        'transac' => true,
        'date' => true,
        'language' => false,
        'attrLang' => false,
        'transacNote' => true,
        'transacType' => true,
        'ifDescripgrp' => false,
        'collectionId' => false,
        'termEntryId' => false,
        'termId' => false,
        'termTbxId' => true,
        'termGuid' => false,
        'termEntryGuid' => false,
        'langSetGuid' => false,
        'guid' => false,
        'elementName' => true
    ];
    protected int $collectionId = 0;

    protected int $termEntryId = 0;
    protected ?int $termId = null;
    protected ?string $termTbxId = null;
    protected ?string $termGuid = null;
    protected ?string $termEntryGuid = null;
    protected ?string $langSetGuid = null;
    protected ?string $descripGrpGuid = null;
    protected ?string $guid = null;
    protected string $elementName = '';
    protected ?string $language = null;
    protected string $attrLang = '';
    protected string $transac = '';
    protected string $date = '';
    protected string $transacNote = '';
    protected string $transacType = '';
    protected int $ifDescripGrp = 0;

    /**
     * @return string
     */
    public function getCollectionKey(): string
    {
        return $this->getElementName() . '-' . $this->getTransac() . '-' . $this->getIfDescripGrp() . '-' . $this->getTermEntryId().'-'.$this->getTermTbxId();
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
     * @return int
     */
    public function getTermEntryId(): int
    {
        return $this->termEntryId;
    }

    /**
     * @param int $termEntryId
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setTermEntryId(int $termEntryId): self
    {
        $this->termEntryId = $termEntryId;
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
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setTermId(?int $termId): self
    {
        $this->termId = $termId;
        return $this;
    }


    /**
     * @return string|null
     */
    public function getTermTbxId(): ?string
    {
        return $this->termTbxId;
    }

    /**
     * @param string|null $termTbxId
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setTermTbxId(?string $termTbxId): self
    {
        $this->termTbxId = $termTbxId;
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
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setTermGuid(?string $termGuid): self
    {
        $this->termGuid = $termGuid;
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
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
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
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
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
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
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
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setElementName(string $elementName): self
    {
        $this->elementName = $elementName;
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
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setDescripGrpGuid(?string $descripGrpGuid): self
    {
        $this->descripGrpGuid = $descripGrpGuid;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setLanguage(?string $language): self
    {
        $this->language = $language;
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
     * @return editor_Models_Terminology_TbxObjects_TransacGrp
     */
    public function setAttrLang(string $attrLang): self
    {
        $this->attrLang = $attrLang;
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
