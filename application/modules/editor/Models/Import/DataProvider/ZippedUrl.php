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
 * Gets the Import Data from a Zip File
 */
class editor_Models_Import_DataProvider_ZippedUrl extends editor_Models_Import_DataProvider_Zip {
    protected $zipUrl;

    public function __construct($urlToZipFile){
        $this->zipUrl = $urlToZipFile;
    }

    /**
     * @see editor_Models_Import_DataProvider_Zip::checkAndPrepare()
     */
    public function checkAndPrepare(editor_Models_Task $task) {
        $this->setTask($task);
        $this->checkAndMakeTempImportFolder();
        $this->importZip = $this->getZipArchivePath();
        $this->fetchFile();
        $this->unzip();
    }

    /**
     * fetch the zip file to import by HTTP
     */
    protected function fetchFile() {
        $client = new Zend_Http_Client();
        $client->setUri($this->zipUrl);
        $client->setConfig(array(
        		'maxredirects' => 0,
        		'timeout' => 30));
        $response = $client->request();
        if (!$response->isSuccessful()) {
            //DataProvider ZippedUrl: ZIP file could not be fetched from URL
            throw new editor_Models_Import_DataProvider_Exception('E1250', [
                'task' => $this->task,
                'url' => $this->zipUrl,
                'httpStatus' => $response->getStatus(),
                'httpMessage' => $response->getMessage(),
            ]);
        }
        //im Folgenden werden 0 byte Große Dateien ebenfalls als Fehler betrachtet
        if (!file_put_contents($this->importZip, $response->getBody())) {
            //DataProvider ZippedUrl: fetched file can not be saved to path
            throw new editor_Models_Import_DataProvider_Exception('E1249', [
                'task' => $this->task,
                'path' => $this->importZip,
            ]);
        }
    }

    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Zip::archiveImportedData()
     */
    public function archiveImportedData($filename = null) {
        //the archive zip already exists in this DataProvider, so do nothing here
        //a given filename is ignored so far
    }
}