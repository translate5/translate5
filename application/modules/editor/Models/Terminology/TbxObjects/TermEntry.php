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
class editor_Models_Terminology_TbxObjects_TermEntry {
    /**
     * Table field for insert or update.
     * If:
     * 'fieldName' => false -> only insert no check for update attribute
     * 'fieldName' => true -> insert and update
     */
    const TABLE_FIELDS = [
        'collectionId',
        'termEntryId',
        'isCreatedLocally',
        'entryGuid',
        'descrip'
    ];
    protected int $collectionId = 0;
    protected string $termEntryTbxId = '';
    protected ?string $entryGuid = null;
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
    public function getTermEntryTbxId(): string
    {
        return $this->termEntryTbxId;
    }

    /**
     * @param string $termEntryTbxId
     * @return editor_Models_Terminology_TbxObjects_TermEntry
     */
    public function setTermEntryTbxId(string $termEntryTbxId): self
    {
        $this->termEntryTbxId = $termEntryTbxId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEntryGuid(): ?string
    {
        return $this->entryGuid;
    }

    /**
     * @param string|null $entryGuid
     * @return editor_Models_Terminology_TbxObjects_TermEntry
     */
    public function setEntryGuid(?string $entryGuid): self
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
