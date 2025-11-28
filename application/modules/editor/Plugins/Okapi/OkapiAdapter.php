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

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\Okapi;

use editor_Plugins_Okapi_Init;
use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use ReflectionException;
use SplFileInfo;
use Throwable;
use Zend_Config;
use Zend_Exception;
use Zend_Http_Client_Exception;
use Zend_Http_Response;
use Zend_Registry;
use ZfExtended_BadGateway;
use ZfExtended_Exception;

/**
 * Upload/download file to okapi server, and converting it to xlf
 * One Connector Instance can contain one Okapi Project
 */
final class OkapiAdapter
{
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
     * The OKAPI service
     */
    private OkapiService $service;

    /**
     * Creates an OkapiAdapter for the given configuration
     * (system config is usedwhen not given)
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     */
    public function __construct(Zend_Config $config = null)
    {
        if ($config === null) {
            $config = Zend_Registry::get('config'); /** var Zend_Config $config */
        }
        $this->service = editor_Plugins_Okapi_Init::createService(OkapiService::ID, $config);
    }

    /**
     * Creates a new project that files can be uploaded to
     * @throws OkapiException
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     */
    public function createProject(): void
    {
        $http = $this->service->createClient('/projects/new');
        $response = $http->request('POST');
        $this->processResponse($response);
        $url = $response->getHeader('Location');
        $this->projectUrl = $url;
    }

    /**
     * Remove the project from Okapi server.
     * @throws OkapiException
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     */
    public function removeProject(): void
    {
        if (empty($this->projectUrl)) {
            return;
        }
        $http = $this->service->createClient($this->projectUrl);
        $response = $http->request('DELETE');
        $this->processResponse($response);
    }

    /**
     * Uploads a bconf to the current project
     * @throws OkapiException
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
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
        $http = $this->service->createClient($this->projectUrl . '/batchConfiguration');
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
     * Upload the source file (the file which will be converted)
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
     * Run the file conversion. For each uploaded files converted file will be created
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     */
    public function executeTask(string $source, string $target): void
    {
        $http = $this->service->createClient($this->projectUrl . '/tasks/execute/' . $source . '/' . $target);
        $response = $http->request('POST');
        $this->processResponse($response);
    }

    /**
     * Checks the configured Okapi Service
     * @throws OkapiException
     */
    public function ping(): string
    {
        $url = $this->service->getServiceUrl();
        if (empty($url)) {
            return 'Okapi NOT configured!';
        }

        try {
            $http = $this->service->createClient();
            //for ping just 15 seconds
            $http->setConfig([
                'timeout' => 15,
            ]);
            $response = $http->request('GET');
            $this->processResponse($response);
        } catch (Throwable) {
            return 'Okapi ' . $url . ' DOWN!';
        }

        return 'Okapi ' . $url . ' UP!';
    }

    /**
     * Download the converted file from okapi, and save the file on the disk.
     * @throws OkapiException
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     */
    public function downloadFile(string $fileName, string $manifestFile, SplFileInfo $dataDir): string
    {
        $downloadedFile = $dataDir . '/' . $fileName . self::OUTPUT_FILE_EXTENSION;
        $http = $this->service->createClient(
            $this->projectUrl
            . '/outputFiles/pack1/work/'
            . $fileName . self::OUTPUT_FILE_EXTENSION
        );
        $response = $http->request('GET');
        $responseFile = $this->processResponse($response);
        file_put_contents($downloadedFile, $responseFile);

        //additionaly we save the manifest.rkm file to the disk, needed for export
        $http = $this->service->createClient($this->projectUrl . '/outputFiles/pack1/manifest.rkm');
        $response = $http->request('GET');
        file_put_contents($dataDir . '/' . $manifestFile, $this->processResponse($response));

        return $downloadedFile;
    }

    /**
     * Download the converted file from okapi, and save the file on the disk.
     * @param string $fileName filename in okapi to get the file
     * @throws OkapiException
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     */
    public function downloadMergedFile(string $fileName, SplFileInfo $targetFile): void
    {
        $http = $this->service->createClient($this->projectUrl . '/outputFiles/' . $fileName);
        $response = $http->request('GET');
        file_put_contents((string) $targetFile, $this->processResponse($response));
    }

    /**
     * Upload a file to the current project
     * @throws ReflectionException
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_BadGateway
     */
    private function uploadFile(string $fileName, SplFileInfo $realFilePath, string $type): void
    {
        //PUT http://{host}/okapi-longhorn/projects/1/inputFiles/help.html
        //Ex.: Uploads a file that will have the name 'help.html'

        if (! empty($type)) {
            //add the upload type to the URL
            $fileName = $type . '/' . $fileName;
        }
        $http = $this->service->createClient($this->projectUrl . '/inputFiles/' . $fileName);
        $http->setFileUpload((string) $realFilePath, 'inputFile');
        $response = $http->request('PUT');
        $this->processResponse($response);
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
}
