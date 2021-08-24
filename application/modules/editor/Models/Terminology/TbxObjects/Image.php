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
class editor_Models_Terminology_TbxObjects_Image extends editor_Models_Terminology_TbxObjects_Abstract{
    /**
     * Table field for insert or update.
     * If:
     * 'fieldName' => false -> only insert no check for update attribute
     * 'fieldName' => true -> insert and update
     */
    const TABLE_FIELDS = [
        'collectionId' => false,
        'targetId' => false,
        'name' => true,
        'uniqueName' => false,
        'encoding' => true,
        'format' => true
    ];

    protected int $collectionId = 0;

    /**
     * Unique, points to the target element where image will be displayed
     * @var string $targetId
     */
    protected string $targetId = '';

    /**
     * name of image
     * @var string
     */
    protected string $name = '';

    /***
     * @var string Unique image name used to save the image on the disk
     */
    protected string $uniqueName = '';

    /**
     * what encoding format is used (HEX or xBase64)
     * @var string
     */
    protected string $encoding = '';

    /**
     * what format is the image (image/png, image/gif, image/jpg...)
     * @var string
     */
    protected string $format = '';

    /**
     * hex or xBase value from TBX
     * @var string
     */
    protected string $hexOrXbaseValue = '';

    /**
     * @return string
     */
    public function getCollectionKey(): string
    {
        return $this->getCollectionId() . '-' . $this->getTargetId();
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
    public function getUniqueName(): string
    {
        return $this->uniqueName;
    }

    /**
     * @param string $uniqueName
     * @return editor_Models_Terminology_TbxObjects_Image
     */
    public function setUniqueName(string $uniqueName): self
    {
        $this->uniqueName = $uniqueName;
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
     * temporary save hex or xBase string
     * @return string
     */
    public function getHexOrXbaseValue(): string
    {
        return $this->hexOrXbaseValue;
    }

    /**
     * @param string $hexOrXbaseValue
     * @return editor_Models_Terminology_TbxObjects_Image
     */
    public function setHexOrXbaseValue(string $hexOrXbaseValue): self
    {
        $this->hexOrXbaseValue = $hexOrXbaseValue;
        return $this;
    }

    /***
     * Create unique name for the current image object
     */
    public function createUniqueName(){
        $d = strrpos($this->getName(),".");
        $extension = ($d===false) ? "" : substr($this->getName(),$d+1);
        return ZfExtended_Utils::uuid().'.'.$extension;
    }
}
