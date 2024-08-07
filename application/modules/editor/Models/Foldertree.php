<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

/**
 * @method string getTaskGuid()
 * @method void setTaskGuid(string $taskGuid)
 */
class editor_Models_Foldertree extends ZfExtended_Models_Entity_Abstract
{
    public const TYPE_DIR = 'dir';

    public const TYPE_FILE = 'file';

    public const ALLOWED_TYPES = [self::TYPE_DIR, self::TYPE_FILE];

    protected $dbInstanceClass = 'editor_Models_Db_Foldertree';

    protected array|null $objectTree = null;

    /**
     * @var array array(id => 'filePath',...)
     */
    protected $_paths = [];

    /**
     * @var string prefix of the filepath, i. e. review/ or relais/
     */
    protected $_pathPrefix = null;

    public function __construct()
    {
        parent::__construct();
        $this->setPathPrefix(editor_Models_Import_Configuration::WORK_FILES_DIRECTORY);
    }

    public function setPathPrefix(string $prefix)
    {
        $this->_pathPrefix = $prefix;
    }

    /**
     * loads a Foldertree to the given TaskGuid
     * @return Zend_Db_Table_Row_Abstract
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function loadByTaskGuid(string $taskGuid)
    {
        return $this->row = $this->loadRow('taskGuid = ?', $taskGuid);
    }

    /**
     * gets the tree and decodes it interally from JSON
     * @return array
     */
    public function getTree()
    {
        if (is_null($this->objectTree)) {
            $this->objectTree = json_decode($this->get('tree'), false, 512, JSON_THROW_ON_ERROR);
        }

        return $this->objectTree;
    }

    /**
     * @throws JsonException
     */
    public function getTreeForStore(): ?array
    {
        $tree = $this->getTree();

        foreach ($tree as $t) {
            $this->normalizeStoreTree($t);
        }

        return $tree;
    }

    /**
     * Adopt the file tree, so it can be easily used for file store on the frontend
     * @return stdClass|void
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function normalizeStoreTree(stdClass $object)
    {
        $object->extension = pathinfo($object->filename, PATHINFO_EXTENSION);
        $object->leaf = $object->isFile ?? false;
        // in case the isReimportable flag is not set, set it by loading the file model from the database
        if (! isset($object->isReimportable) && isset($object->id) && $object->leaf) {
            $fileModel = ZfExtended_Factory::get(editor_Models_File::class);
            $fileModel->load($object->id);
            $object->isReimportable = (bool) $fileModel->getIsReimportable();
        }
        if (empty($object->children)) {
            return $object;
        }

        $object->expanded = true;
        foreach ($object->children as $child) {
            $this->normalizeStoreTree($child);
        }
    }

    /**
     * returns directly the JSON Repräsentation
     * @return string
     */
    public function getTreeAsJson()
    {
        return $this->get('tree');
    }

    /**
     * returns directly the JSON Repräsentation
     * @return string
     */
    public function getReferenceTreeAsJson()
    {
        return $this->get('referenceFileTree');
    }

    /**
     * stores the tree and converts it internally to JSON
     */
    public function setTree(array $tree)
    {
        $this->objectTree = $tree;
        $this->set('tree', json_encode($tree));
    }

    /**
     * stores the reference file tree and converts it internally to JSON
     */
    public function setReferenceFileTree(array $tree)
    {
        $this->set('referenceFileTree', json_encode($tree));
    }

    /**
     * syncs the Tree Data to Files.
     */
    public function syncTreeToFiles()
    {
        $sync = ZfExtended_Factory::get('editor_Models_Foldertree_SyncToFiles', [$this]);
        /* @var $sync editor_Models_Foldertree_SyncToFiles */
        $sync->recursiveSync();
    }

    /**
     * returns the fileIds mapped to file paths out of given foldertree JSON
     *
     * @param string $typeFilter allowed values dir|file
     * @return array array(id => 'filePath',...)
     *        Die Pfade sind in einer Reihenfolge, so dass in der Hierarchie höhere Verzeichnisse
     *        niedrigere Array-Indizes haben
     *        id ist für Verzeichnisse und Dateien eindeutig
     * @throws JsonException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getPaths(string $taskGuid, string $typeFilter): array
    {
        $this->_paths = [];
        if (! in_array($typeFilter, self::ALLOWED_TYPES)) {
            throw new LogicException('$typeFilter is ' . $typeFilter . ' but must be one of '
                . join(', ', self::ALLOWED_TYPES));
        }
        $this->loadByTaskGuid($taskGuid);
        $this->getTree();
        $nodeVisitor = 'get' . ucfirst($typeFilter) . 'PathsNodeVisitor';
        $this->$nodeVisitor($this->objectTree);

        return $this->_paths;
    }

    /***
     * Return the path of a file by given fileId
     *
     * @param string $taskGuid
     * @param int $fileId
     * @return string
     * @throws JsonException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getFileIdPath(string $taskGuid, int $fileId): string
    {
        $this->getPaths($taskGuid, self::TYPE_FILE);

        return $this->_paths[$fileId] ?? '';
    }

    /**
     * konvertiert einen Objektbaum mit Pfadangaben zu einer Liste mit den Pfaden
     * @param stdClass $tree
     * @return array
     */
    public function getFilePathsByTree($tree)
    {
        $this->getFilePathsNodeVisitor($tree);

        return $this->_paths;
    }

    /**
     * besucht jedes Kind im Baum und speichert dessen Filepath in $this->_paths
     * - Verzeichnisnamen werden übersprungen
     */
    protected function getFilePathsNodeVisitor(array $children, string $path = null)
    {
        foreach ($children as $index => $child) {
            if (! isset($child->isFile) || ! $child->isFile) {
                if (! empty($child->children)) {
                    $this->getFilePathsNodeVisitor($child->children, $path . $child->filename . DIRECTORY_SEPARATOR);
                }

                continue;
            }
            $this->handleFile($child, $path);
        }
    }

    /**
     * Wird im getFilePathsNodeVisitor für jede Datei aufgerufen
     * @param string $path
     */
    protected function handleFile(stdClass $child, $path)
    {
        $path = $this->_pathPrefix !== '' ? $this->_pathPrefix . DIRECTORY_SEPARATOR . $path : $path;
        $this->_paths[$child->id] = $path . $child->filename;
    }

    /**
     * besucht jedes Kind im Baum und speichert dessen Dirpath in $this->_paths
     * - Dateinamen werden übersprungen
     */
    protected function getDirPathsNodeVisitor(array $children, string $path = null)
    {
        foreach ($children as $index => $child) {
            if ($child->cls !== 'folder') {
                continue;
            }
            if (! empty($child->children)) {
                $this->getDirPathsNodeVisitor($child->children, $path . $child->filename . DIRECTORY_SEPARATOR);
            }
            $this->_paths[$child->id] = $path . $child->filename;
        }
    }
}
