<?php

class editor_Plugins_Okapi_Bconf_File
{
    public const NUMPLUGINS = 0;
    public const descFile = "content.json";

    protected static string $dataDir;
    protected static string $fileName;

    /**
     * @var mixed|null[]
     */
    protected mixed $content;

    protected editor_Plugins_Okapi_Models_Bconf $entity;

    /**
     * @param editor_Plugins_Okapi_Models_Bconf $entity
     */
    public function __construct(editor_Plugins_Okapi_Models_Bconf $entity)
    {
        $this->entity = $entity;
        $filepath = $entity->getFilePath($entity->getId());
        self::$dataDir = dirname($filepath);
        self::$fileName = basename($filepath);
    }

    public function pack(): mixed
    {
        return editor_Plugins_Okapi_Bconf_Composer::doPack($this->entity->getId());
    }

    public function unpack(string $filePath): mixed
    {
        return editor_Plugins_Okapi_Bconf_Parser::doUnpack($filePath);
    }


}