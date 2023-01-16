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

namespace MittagQI\Translate5\Task\Export\Package;

use editor_Models_Task;

class FileStructure
{
    public const PACKAGE_FOLDER_NAME = '_exportPackage';

    public const PACKAGE_FILE = 'xliff';

    public const PACKAGE_MEMORY = 'tmx';

    public const PACKAGE_COLLECTION = 'tbx';

    public const PACKAGE_REFERENCE = 'reference';

    /**
     * @param editor_Models_Task $task
     */
    public function __construct(private editor_Models_Task $task)
    {

    }


    /**
     * @return string
     */
    public function initFileStructure(): string
    {
        $root = $this->getRootFolder();
        mkdir($root) || is_dir($root);

        $xlff = $this->getFilesFolder();
        mkdir($xlff) || is_dir($xlff);

        $tmx = $this->getMemoryFolder();
        mkdir($tmx) || is_dir($tmx);

        $collection = $this->getCollectionFolder();
        mkdir($collection) || is_dir($collection);

        $reference = $this->getReferenceFolcer();
        mkdir($reference) || is_dir($reference);

        return $root;
    }

    /**
     * @return string
     */
    private function getRootFolder(): string
    {
        return $this->task->getAbsoluteTaskDataPath().DIRECTORY_SEPARATOR.self::PACKAGE_FOLDER_NAME;
    }

    /***
     * @return string
     */
    public function getFilesFolder(): string
    {
        return $this->getRootFolder().DIRECTORY_SEPARATOR . self::PACKAGE_FILE;
    }

    /***
     * @return string
     */
    public function getMemoryFolder(): string
    {
        return $this->getRootFolder().DIRECTORY_SEPARATOR . self::PACKAGE_MEMORY;
    }

    /***
     * @return string
     */
    public function getCollectionFolder(): string
    {
        return $this->getRootFolder().DIRECTORY_SEPARATOR . self::PACKAGE_COLLECTION;
    }

    public function getReferenceFolcer(): string
    {
        return $this->getRootFolder().DIRECTORY_SEPARATOR . self::PACKAGE_REFERENCE;
    }
}