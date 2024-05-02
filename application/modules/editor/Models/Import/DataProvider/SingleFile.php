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

/**
 * Gets the Import Data from single uploaded files
 */
class editor_Models_Import_DataProvider_SingleFile extends editor_Models_Import_DataProvider_Directory
{
    protected string $fileToProcess;

    /**
     * consumes all the given file paths
     */
    public function __construct(string $fileToProcess)
    {
        $this->fileToProcess = $fileToProcess;
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Abstract::checkAndPrepare()
     */
    public function checkAndPrepare(editor_Models_Task $task)
    {
        $this->setTask($task);
        $this->checkAndMakeTempImportFolder();

        $this->importFolder = tempnam(sys_get_temp_dir(), 'import') . '.d';
        $workfiles = $this->importFolder . '/workfiles/';
        mkdir($workfiles, recursive: true);

        parent::checkAndPrepare($task);

        copy($this->fileToProcess, $workfiles . basename($this->fileToProcess));
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Abstract::postImportHandler()
     */
    public function postImportHandler()
    {
        $this->removeTempFolder();
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Abstract::handleImportException()
     */
    public function handleImportException(Exception $e)
    {
        $this->removeTempFolder();
    }
}
