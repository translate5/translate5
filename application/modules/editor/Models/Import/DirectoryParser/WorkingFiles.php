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
 * Klasse zum Parsen und Importieren von Dateistrukturen
 */
class editor_Models_Import_DirectoryParser_WorkingFiles
{
    /**
     * collection of ignored files
     * @var array
     */
    protected static $notImportedFiles = [];

    protected static $filesFound = false;

    /**
     * Datei- oder Verzeichnisnamen in dieser Liste werden ignoriert. 100% match.
     * @var array
     */
    protected $ignoreList = ['.svn', '.git'];

    /**
     * This List of ignored extensions will be applied if supportedFiles is not set. Defined by constructor
     * @var array
     */
    protected $ignoreExtensionsList = [];

    /**
     * RootNode Container
     * @var StdClass
     */
    protected $rootNode;

    /**
     * @param string $ignoredUncheckedExtensions : comma seperated list of extensions to ignore if $checkFileTypes is false
     */
    public function __construct(
        protected editor_Models_Task $task,
        protected bool $doCheckFileTypes,
        string $ignoredUncheckedExtensions = ''
    ) {
        // if no check shall be done, no filter is set and all files are imported
        if (! $this->doCheckFileTypes && ! empty($ignoredUncheckedExtensions)) {
            // in case of an unchecked import there may be a extension blacklist defined
            $this->ignoreExtensionsList = explode(',', $ignoredUncheckedExtensions);
        }
    }

    /**
     * parses the given directory and returns a Object tree ready for output as JSON
     * @param string $directoryPath
     * @return object Directory Object Tree
     */
    public function parse($directoryPath)
    {
        self::$notImportedFiles = [];
        $rootNode = $this->getInitialRootNode();
        self::$filesFound = false;
        $this->iterateThrough($rootNode, $directoryPath);
        if ($this->doCheckFileTypes && ! self::$filesFound) {
            // 'E1135' => 'There are no importable files in the Task. The following file extensions can be imported: {extensions}',
            throw new editor_Models_Import_FileParser_NoParserException('E1135', [
                'extensions' => '.' . join(', .', $this->task->getFileTypeSupport()->getSupportedExtensions()),
                'task' => $this->task,
            ]);
        }

        return $rootNode->children;
    }

    protected function getInitialRootNode()
    {
        $rootNode = new stdClass();
        $rootNode->id = 0;
        $rootNode->children = [];
        $rootNode->path = '';
        $rootNode->filename = '';

        return $rootNode;
    }

    /**
     * iterates through the given DirectoryPath and processes the files and directories
     * @param StdClass $rootNode Root of the tree
     */
    protected function iterateThrough(StdClass $rootNode, string $directoryPath)
    {
        $this->rootNode = $rootNode;
        $filenames = [];
        $directories = [];
        $iterator = new DirectoryIterator($directoryPath);
        /* @var $fileinfo DirectoryIterator */
        foreach ($iterator as $fileinfo) {
            if ($this->isIgnored($fileinfo, $directoryPath)) {
                continue;
            }
            $fileName = ZfExtended_Utils::filesystemDecode($fileinfo->getFilename());
            if ($fileinfo->isFile()) {
                if (! self::$filesFound) {
                    self::$filesFound = true;
                }
                $filenames[$fileName] = $fileinfo->getPathname();
            }
            if ($fileinfo->isDir()) {
                $directories[$fileName] = $fileinfo->getPathname();
            }
        }
        $this->buildRecursiveTree($filenames, $directories);
    }

    /**
     * Sorts files and directories and builds then a recursive tree
     */
    protected function buildRecursiveTree(array $filenames, array $directories)
    {
        ksort($filenames);
        ksort($directories);
        foreach ($directories as $directory => $path) {
            $this->rootNode->children[] = $this->getDirectoryNodeAndIterate($directory, $path);
        }
        foreach ($filenames as $filename => $filepath) {
            $this->rootNode->children[] = $this->getFileNode($filename, $filepath);
        }
    }

    /**
     * checks if the given File/Directory should be ignored
     * @return boolean
     */
    protected function isIgnored(DirectoryIterator $file, string $directoryPath)
    {
        if ($file->isDot() || in_array($file->getFilename(), $this->ignoreList)) {
            return true;
        }
        if (is_dir($directoryPath . DIRECTORY_SEPARATOR . $file)) {
            return false;
        }
        if (! $this->doCheckFileTypes && in_array(strtolower($file->getExtension()), $this->ignoreExtensionsList)) {
            self::$notImportedFiles[] = $file->getFilename();

            return true;
        }
        //no extension filter set: pass all files
        if (! $this->doCheckFileTypes) {
            return false;
        }
        $extensions = $this->task->getFileTypeSupport()->getRegisteredExtensions();
        foreach ($extensions as $ext) {
            if (preg_match('"\.' . $ext . '$"i', $file)) {
                return $this->task->getFileTypeSupport()->isIgnored($ext);
            }
        }

        //file extensions which are not handled by supportedFiles at all (not supported and not activly ignore) are collected here
        self::$notImportedFiles[] = $file->getFilename();

        return true;
    }

    /**
     * Creates a FileNode out of given $file
     * @param string $filename
     * @param string $filepath
     * @return stdClass
     */
    protected function getFileNode($filename, $filepath)
    {
        $node = new stdClass();
        $node->id = 0; // from save to DB
        $node->parentId = 0; //from first sync to files call
        $node->cls = 'file';
        $node->isFile = true;
        $node->filename = $filename;
        $node->segmentid = 0;
        $node->segmentgridindex = 0;
        $node->path = $this->rootNode->path . $this->rootNode->filename . '/';

        //fire event, before the filenode is created/saved to the database
        $eventManager = ZfExtended_Factory::get('ZfExtended_EventManager', [__CLASS__]);
        /* @var $eventManager ZfExtended_EventManager */
        $eventManager->trigger('beforeFileNodeCreate', $this, [
            'node' => $node,
            'filePath' => $filepath,
        ]);

        return $node;
    }

    /**
     * Creates a DirectoryNode out of given Directory and iterate through it / build up the tree
     * @param string $directory
     * @return stdClass
     */
    protected function getDirectoryNodeAndIterate($directory, $path)
    {
        $node = $this->getDirectoryNode($directory);

        $iteration = new static($this->task, $this->doCheckFileTypes);
        $iteration->iterateThrough($node, $path);

        return $node;
    }

    /**
     * Creates a DirectoryNode out of given Directory
     * @param string $directory
     * @return stdClass
     */
    protected function getDirectoryNode($directory)
    {
        $node = new stdClass();
        $node->id = 0;  // from save to DB
        $node->filename = $directory;
        $node->path = $this->rootNode->path . $this->rootNode->filename . '/';
        $node->cls = 'folder';
        $node->children = [];

        return $node;
    }

    /**
     * returns the files which were ignored
     * @return array
     */
    public function getNotImportedFiles()
    {
        return self::$notImportedFiles;
    }
}
