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

/**#@+
 * @author Marc Mittag
 * @package editor
 * @version 1.0
 *
 */
/**
 * Imports the reference file data structure
 *
 * Note to several unset($node->id) calls used in this class:
 *  In Working Files the ID ist afterwards generated by the sync to the files table.
 *  For the reference no id must be set, so that auto ids are generated on client side
 */
class editor_Models_Import_DirectoryParser_ReferenceFiles extends editor_Models_Import_DirectoryParser_WorkingFiles
{
    /**
     * Defines the file-types we do not want as reference files for security reasons
     */
    public const FORBIDDEN_EXTENSIONS = ['exe', 'com', 'bat', 'js', 'sh', 'php', 'py'];

    /**
     * @throws Zend_Exception
     */
    public static function getDirectory(): string
    {
        return Zend_Registry::get('config')->runtimeOptions->import->referenceDirectory;
    }

    /**
     * Delete unwanted files from the unzipped import
     * @throws Zend_Exception
     */
    public static function cleanImportDirectory(string $importFolder)
    {
        $referenceFilesDir = $importFolder . DIRECTORY_SEPARATOR . self::getDirectory();
        if (is_dir($referenceFilesDir)) {
            // for security reasons we want to get rid of all unwanted reference files (so they won't be archived)
            ZfExtended_Utils::recursiveDelete($referenceFilesDir, self::FORBIDDEN_EXTENSIONS);
        }
    }

    /**
     * Delete unwanted files from the import archive
     */
    public static function cleanImportArchive(ZipArchive $zip)
    {
        $idxToDelete = [];
        $refDir = self::getDirectory();
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $pathParts = explode('/', trim($zip->getNameIndex($i), '/'));
            $numParts = count($pathParts);
            if ($numParts > 1 && $pathParts[0] === $refDir && in_array(pathinfo($pathParts[$numParts - 1], PATHINFO_EXTENSION), self::FORBIDDEN_EXTENSIONS)) {
                $idxToDelete[] = $i;
            }
        }
        if (count($idxToDelete) > 0) {
            foreach ($idxToDelete as $index) {
                $zip->deleteIndex($index);
            }
        }
    }

    public static function createFileNode(string $filename, string $relativePath): stdClass
    {
        $node = editor_Models_Import_DirectoryParser_WorkingFiles::createFileNode($filename, $relativePath);
        if ($node->isFile) {
            $node->href = $node->path . $node->filename;
            $node->hrefTarget = '_blank';
        }
        unset($node->id); //@see class head comment

        return $node;
    }

    public function __construct(editor_Models_Task $task)
    {
        $this->task = $task;
        //disable (empty) the filter for reference files:
        $this->doCheckFileTypes = false;
        // disable any ignored extensions
        $this->ignoreExtensionsList = self::FORBIDDEN_EXTENSIONS;
    }

    /**
     * Adds reference file specific infos to the tree node
     * @param string $filename
     * @param string $filepath
     * @return stdClass
     */
    protected function getFileNode($filename, $filepath)
    {
        $node = parent::getFileNode($filename, $filepath);
        if ($node->isFile) {
            $node->href = $node->path . $node->filename;
            $node->hrefTarget = '_blank';
        }
        unset($node->id); //@see class head comment

        return $node;
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DirectoryParser_WorkingFiles::getDirectoryNode()
     */
    protected function getDirectoryNode($directory)
    {
        $node = parent::getDirectoryNode($directory);
        unset($node->id); //@see class head comment

        return $node;
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DirectoryParser_WorkingFiles::getInitialRootNode()
     */
    protected function getInitialRootNode()
    {
        $node = parent::getInitialRootNode();
        $node->path = 'referencefile';
        unset($node->id); //@see class head comment

        return $node;
    }
}
