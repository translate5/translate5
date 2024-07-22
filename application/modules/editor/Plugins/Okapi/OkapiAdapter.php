<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\Okapi;

use editor_Plugins_Okapi_Init;
use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use ReflectionException;
use SplFileInfo;
use Throwable;
use Zend_Config;
use Zend_Exception;
use Zend_Http_Client;
use Zend_Http_Client_Exception;
use Zend_Http_Response;
use Zend_Registry;
use ZfExtended_BadGateway;
use ZfExtended_Exception;
use ZfExtended_Factory;

/**
 * Upload/download file to okapi server, and converting it to xlf
 * One Connector Instance can contain one Okapi Project
 */
final class OkapiAdapter
{
    /**
     * Request timeout for the api
     *
     * @var integer
     */
    public const REQUEST_TIMEOUT_SECONDS = 360;

    /**
     * The file extenssion of the converted file
     *
     * @var string
     */
    public const OUTPUT_FILE_EXTENSION = '.xlf';

    /**
     * The input types used as path part in the Okapi upload URL
     * @var string
     */
    public const INPUT_TYPE_DEFAULT = ''; //needed for importing all files, export: the manifest.rkm

    public const INPUT_TYPE_ORIGINAL = 'original'; //needed for export, place for the original (html) files

    public const INPUT_TYPE_WORK = 'work';  //needed for export, place for the work (xlf) files

    /**
     * The url for the current  active project
     */
    private ?string $projectUrl;

    /**
     * Server to use (what can be configured on task & customer level)
     */
    private ?string $serverToUse;

    /**
     * The OKAPI service
     */
    private OkapiService $service;

    /**
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     */
    public function __construct(Zend_Config $config = null)
    {
        if($config === null){
            $config = Zend_Registry::get('config'); /** var Zend_Config $config */
        }
        $this->serverToUse = $config->runtimeOptions->plugins->Okapi->serverUsed;
        $this->service = editor_Plugins_Okapi_Init::createService('okapi');
    }

    /**
     * Get the okapi api url from the configured servers and server used
     * @throws OkapiException
     */
    public function getApiUrl(): string
    {
        return $this->service->getConfiguredServiceUrl($this->serverToUse) . '/';
    }

    /**
     * Create the http object, set the authentication and set the url
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     */
    private function getHttpClient(string $url): Zend_Http_Client
    {
        $http = ZfExtended_Factory::get(Zend_Http_Client::class);
        $http->setUri($url);
        $http->setConfig([
            'timeout' => self::REQUEST_TIMEOUT_SECONDS,
        ]);

        return $http;
    }

    /**
     * Check for the status of the response. If the status is different than 200 or 201,
     * ZfExtended_BadGateway exception is thrown.
     * Also the function checks for the invalid decoded json.
     *
     * @throws ZfExtended_BadGateway
     */
    private function processResponse(Zend_Http_Response $response): string
    {
        $validStates = [200, 201, 401];

        //check for HTTP State (REST errors)
        if (! in_array($response->getStatus(), $validStates)) {
            throw new ZfExtended_BadGateway("HTTP Status was not 200/201/401 body: " . $response->getBody(), 500);
        }

        return $response->getBody();
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     * @throws OkapiException
     */
    public function createProject(): void
    {
        $http = $this->getHttpClient($this->getApiUrl() . 'projects/new');
        $response = $http->request('POST');
        $this->processResponse($response);
        $url = $response->getHeader('Location');
        $this->projectUrl = $url;
    }

    /**
     * Remove the project from Okapi server.
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     */
    public function removeProject(): void
    {
        if (empty($this->projectUrl)) {
            return;
        }
        $http = $this->getHttpClient($this->projectUrl);
        $response = $http->request('DELETE');
        $this->processResponse($response);
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     * @throws OkapiException
     */
    public function uploadOkapiConfig(string $bconfPath, bool $forImport): void
    {
        if (empty($bconfPath) || ! file_exists($bconfPath)) {
            // 'Okapi Plug-In: Bconf not given or not found: {bconfFile}',
            throw new OkapiException('E1055', [
                'bconfFile' => $bconfPath,
            ]);
        }
        // $bconfPath = '/var/www/translate5/application/modules/editor/Plugins/Okapi/data/okapi_default_import.bconf';
        $url = $this->projectUrl . '/batchConfiguration';
        $http = $this->getHttpClient($url);
        $http->setFileUpload($bconfPath, 'batchConfiguration');
        $response = $http->request('POST');

        try {
            $this->processResponse($response);
        } catch (ZfExtended_BadGateway $e) {
            // to improve support we add the name of the bconf as used in the Frontend (if possible) for import bconf's
            $msg = $e->getMessage();
            if ($forImport) {
                try {
                    $bconfId = (int) basename(dirname($bconfPath));
                    $bconf = new BconfEntity();
                    $bconf->load($bconfId);
                    $bconfName = $bconf->getName();
                    $e->setMessage($msg . " \n" . 'Import-bconf used was \'' . $bconfName . '\' (id ' . $bconfId . ')');
                } catch (Throwable) {
                    $e->setMessage($msg . " \n" . 'Import-bconf used was \'' . basename($bconfPath) . '\'');
                }
            } else {
                $e->setMessage($msg . " \n" . 'Export-bconf used was \'' . basename($bconfPath) . '\'');
            }

            throw $e;
        }
    }

    /**
     * Upload the source file(the file which will be converted)
     * @param string $fileName file name to be used in okapi
     * @param SplFileInfo $realFilePath path to the file to be uploaded
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     */
    public function uploadInputFile(string $fileName, SplFileInfo $realFilePath): void
    {
        $this->uploadFile($fileName, $realFilePath, self::INPUT_TYPE_DEFAULT);
    }

    /**
     * Upload the original file for merging the XLF data into
     * @param string $fileName file name to be used in okapi
     * @param SplFileInfo $realFilePath path to the file to be uploaded
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     */
    public function uploadOriginalFile(string $fileName, SplFileInfo $realFilePath): void
    {
        $this->uploadFile($fileName, $realFilePath, self::INPUT_TYPE_ORIGINAL);
    }

    /**
     * Upload the work file (XLF) to be merged into the original file
     * @param string $fileName file name to be used in okapi
     * @param SplFileInfo $realFilePath path to the file to be uploaded
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     */
    public function uploadWorkFile(string $fileName, SplFileInfo $realFilePath): void
    {
        $this->uploadFile($fileName, $realFilePath, self::INPUT_TYPE_WORK);
    }

    /**
     * Upload the source file(the file which will be converted)
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     */
    protected function uploadFile(string $fileName, SplFileInfo $realFilePath, string $type): void
    {
        //PUT http://{host}/okapi-longhorn/projects/1/inputFiles/help.html
        //Ex.: Uploads a file that will have the name 'help.html'

        if (! empty($type)) {
            //add the upload type to the URL
            $fileName = $type . '/' . $fileName;
        }
        $url = $this->projectUrl . '/inputFiles/' . $fileName;
        $http = $this->getHttpClient($url);
        $http->setFileUpload($realFilePath, 'inputFile');
        $response = $http->request('PUT');
        $this->processResponse($response);
    }

    /**
     * Run the file conversion. For each uploaded files converted file will be created
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     */
    public function executeTask(string $source, string $target): void
    {
        $url = $this->projectUrl . '/tasks/execute/' . $source . '/' . $target;
        $http = $this->getHttpClient($url);
        $response = $http->request('POST');
        $this->processResponse($response);
    }

    /**
     * Checks the default configured /system level) Okapi Service
     * TODO FIXME: this should be implemented in MittagQI\Translate5\Plugins\Okapi\OkapiAdapter ...
     */
    public function ping(): string
    {
        $url = $this->service->getServiceUrl();
        if (empty($url)) {
            return 'Okapi NOT configured!';
        }

        try {
            $http = $this->getHttpClient($url);
            $http->setConfig([
                'timeout' => 15,
            ]); //for ping just 15 seconds
            $response = $http->request('GET');
            $this->processResponse($response);
        } catch (Throwable) {
            return 'Okapi ' . $url . ' DOWN!';
        }

        return 'Okapi ' . $url . ' UP!';
    }

    /**
     * Download the converted file from okapi, and save the file on the disk.
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     */
    public function downloadFile(string $fileName, string $manifestFile, SplFileInfo $dataDir): string
    {
        $downloadedFile = $dataDir . '/' . $fileName . self::OUTPUT_FILE_EXTENSION;
        $url = $this->projectUrl . '/outputFiles/pack1/work/' . $fileName . self::OUTPUT_FILE_EXTENSION;
        $http = $this->getHttpClient($url);
        $response = $http->request('GET');
        $responseFile = $this->processResponse($response);
        file_put_contents($downloadedFile, $responseFile);

        //additionaly we save the manifest.rkm file to the disk, needed for export
        $url = $this->projectUrl . '/outputFiles/pack1/manifest.rkm';
        $http = $this->getHttpClient($url);
        $response = $http->request('GET');
        file_put_contents($dataDir . '/' . $manifestFile, $this->processResponse($response));

        return $downloadedFile;
    }

    /**
     * Download the converted file from okapi, and save the file on the disk.
     * @param string $fileName filename in okapi to get the file
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     */
    public function downloadMergedFile(string $fileName, SplFileInfo $targetFile): void
    {
        $http = $this->getHttpClient($this->projectUrl . '/outputFiles/' . $fileName);
        $response = $http->request('GET');
        file_put_contents($targetFile, $this->processResponse($response));
    }
}
