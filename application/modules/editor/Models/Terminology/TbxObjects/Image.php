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
class editor_Models_Terminology_TbxObjects_Image {
    const TABLE_FIELDS = [
        'collectionId' => false,
        'targetId' => false,
        'name' => true,
        'encoding' => true,
        'format' => true,
        'xbase' => true
    ];

    protected int $collectionId = 0;
    protected string $targetId = '';
    protected string $name = '';
    protected string $encoding = '';
    protected string $format = '';
    protected string $xbase = '';

    /**
     * @param editor_Models_Terminology_TbxObjects_Image $element
     * @return string
     */
    public function getCollectionKey(editor_Models_Terminology_TbxObjects_Image $element): string
    {
        return $element->getCollectionId() . '-' . $element->getTargetId();
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
     * @return editor_Models_Terminology_TbxObjects_Image
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
     * @return editor_Models_Terminology_TbxObjects_Image
     */
    public function setEntryId(string $entryId): self
    {
        $this->entryId = $entryId;
        return $this;
    }

    /**
     * @return string
     */
    public function getTargetId(): string
    {
        return $this->targetId;
    }

    /**
     * @param string $targetId
     * @return editor_Models_Terminology_TbxObjects_Image
     */
    public function setTargetId(string $targetId): self
    {
        $this->targetId = $targetId;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return editor_Models_Terminology_TbxObjects_Image
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * @param string $encoding
     * @return editor_Models_Terminology_TbxObjects_Image
     */
    public function setEncoding(string $encoding): self
    {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param string $format
     * @return editor_Models_Terminology_TbxObjects_Image
     */
    public function setFormat(string $format): self
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @return string
     */
    public function getXbase(): string
    {
        return $this->xbase;
    }

    /**
     * @param string $xbase
     * @return editor_Models_Terminology_TbxObjects_Image
     */
    public function setXbase(string $xbase): self
    {
        $this->xbase = $xbase;
        return $this;
    }
}
