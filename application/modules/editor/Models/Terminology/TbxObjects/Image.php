<?php

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
