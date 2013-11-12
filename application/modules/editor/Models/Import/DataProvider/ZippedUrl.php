<?php
 /*
 START LICENSE AND COPYRIGHT
 
 This file is part of Translate5 Editor PHP Serverside and build on Zend Framework
 
 Copyright (c) 2013 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ÄTT) MittagQI.com

 This file may be used under the terms of the GNU General Public License version 3.0
 as published by the Free Software Foundation and appearing in the file gpl3-license.txt 
 included in the packaging of this file.  Please review the following information 
 to ensure the GNU General Public License version 3.0 requirements will be met:
 http://www.gnu.org/copyleft/gpl.html.

 For this file you are allowed to make use of the same FLOSS exceptions to the GNU 
 General Public License version 3.0 as specified by Sencha for Ext Js. 
 Please be aware, that Marc Mittag / MittagQI take no warranty  for any legal issue, 
 that may arise, if you use these FLOSS exceptions and recommend  to stick to GPL 3. 
 For further information regarding this topic please see the attached license.txt
 of this software package.
 
 MittagQI would be open to release translate5 under EPL or LGPL also, if this could be
 brought in accordance with the ExtJs license scheme. You are welcome to support us
 with legal support, if you are interested in this.
 
 
 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU General Public License version 3.0 http://www.gnu.org/copyleft/gpl.html
             with FLOSS exceptions (see floss-exception.txt and ux-exception.txt at the root level)
 
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
     * @throws Zend_Exception
     */
    public function checkAndPrepare() {
        $this->checkAndMakeUnzipFolder();
        $this->importZip = $this->getZipArchivePath();
        $this->fetchFile();
        $this->unzip();
    }
    
    /**
     * fetch the zip file to import by HTTP 
     * @throws Zend_Exception
     */
    protected function fetchFile() {
        $client = new Zend_Http_Client();
        $client->setUri($this->zipUrl);
        $client->setConfig(array(
        		'maxredirects' => 0,
        		'timeout' => 30));
        $response = $client->request();
        if (!$response->isSuccessful()) {
        	throw new Zend_Exception('Es konnte keine Zip Datei zu Task ' . $this->task->getTaskGuid() . ' heruntergeladen werden!');
        }
        //im Folgenden werden 0 byte Große Dateien ebenfalls als Fehler betrachtet
        if (!file_put_contents($this->importZip, $response->getBody())) {
        	throw new Zend_Exception('Zip Datei zu Task ' . $this->task->getTaskGuid() . ' konnte nicht lokal zwischengespeichert werden! Pfad: '.$this->importZip);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see editor_Models_Import_DataProvider_Zip::archiveImportedData()
     */
    public function archiveImportedData() {
        //the archive zip already exists in this DataProvider, so delete it, if no archive is wanted. 
        $config = Zend_Registry::get('config');
        if(!$config->runtimeOptions->import->createArchivZip){
            unlink($this->importZip);
        }
    }
}