<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\MaintenanceCli\Command;

use editor_Models_Config;
use Exception;
use GuzzleHttp\Psr7\Uri;
use JsonException;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use editor_Services_OpenTM2_Connector as Connector;
use editor_Services_OpenTM2_Service as Service;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use Throwable;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Resource_DbConfig as DbConfig;

class OpenTm2MigrationCommand extends Translate5AbstractCommand
{
    private const ARGUMENT_ENDPOINT = 'endpoint';
    private const DATA_RELATIVE_PATH = '/../data/';
    private const EXPORT_FILE_EXTENSION = '.tmx';

    protected static $defaultName = 'otm2:migrate';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('Migrates all existing OpenTM2 language resources to t5memory')
            ->setHelp('Tool exports OpenTM2 language resources one by one and imports data to the t5memory provided as endpoint argument')
            ->addArgument(self::ARGUMENT_ENDPOINT, InputArgument::REQUIRED, 't5memory endpoint data to be imported to');
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws Zend_Exception
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $service = new Service();

        $url = $this->getUrl($input, $service);
        $otmResourceId = $this->getOtmResourceId($service);

        $this->updateConfig($url);

        $t5MemoryResourceId = $this->getT5MemoryResourceId($url);

        $processingErrors = [];
        $connector = new Connector();
        $languageResourcesData = ZfExtended_Factory::get(LanguageResource::class)->getByResourceId($otmResourceId);

        $progressBar = new ProgressBar($output, count($languageResourcesData));

        foreach ($languageResourcesData as $languageResourceData) {
            $progressBar->advance();

            $languageResource = ZfExtended_Factory::get(LanguageResource::class);
            $languageResource->load($languageResourceData['id']);

            $type = $connector->getValidExportTypes()['TMX'];
            $filenameWithPath = $this->getFilePath() . $this->generateFilename($languageResource);

            try {
                $this->export($connector, $languageResource, $filenameWithPath, $type);
            } catch (Throwable $e) {
                $processingErrors[] = [
                    'language resource id' => $languageResourceData['id'],
                    'message' => $e->getMessage()
                ];

                continue;
            }

            $languageResource->setResourceId($t5MemoryResourceId);

            try {
                $this->import($connector, $languageResource, $filenameWithPath, $type);
            } catch (Throwable $e) {
                $processingErrors[] = [
                    'language resource id' => $languageResourceData['id'],
                    'message' => $e->getMessage()
                ];

                $this->revertChanges($languageResource, $languageResourceData);
            }
        }

        $this->writeResult($processingErrors);

        if ($progressBar->getMaxSteps() === count($processingErrors)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function getUrl(InputInterface $input, Service $service): Uri
    {
        $url = new Uri($input->getArgument(self::ARGUMENT_ENDPOINT));

        foreach ($service->getResources() as $resource) {
            if ($resource->getUrl() === (string)$url) {
                throw new RuntimeException('Endpoint already exists');
            }
        }

        return $url;
    }

    private function getOtmResourceId(Service $service): ?string
    {
        $resourceId = null;
        foreach ($service->getResources() as $resource) {
            if (str_contains($resource->getUrl(), 'otmmemoryservice')) {
                $resourceId = $resource->getId();
            }
        }

        if (null === $resourceId) {
            throw new RuntimeException('No OpenTM2 resource found');
        }

        return $resourceId;
    }

    /**
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws JsonException
     */
    private function updateConfig(Uri $url): void
    {
        $config = ZfExtended_Factory::get(editor_Models_Config::class);
        $config->loadByName('runtimeOptions.LanguageResources.opentm2.server');
        $value = json_decode($config->getValue(), true, 512, JSON_THROW_ON_ERROR);
        $value[] = (string)$url;
        $config->setValue(json_encode($value, JSON_THROW_ON_ERROR));
        $config->save();

        $dbConfig = ZfExtended_Factory::get(DbConfig::class);
        $dbConfig->setBootstrap(Zend_Registry::get('bootstrap'));
        $dbConfig->init();
    }

    private function getT5MemoryResourceId(Uri $url): string
    {
        $service = new Service();

        foreach ($service->getResources() as $resource) {
            if ($resource->getUrl() === (string)$url) {
                return $resource->getId();
            }
        }

        throw new RuntimeException('Something went wrong: no t5memory resource id found');
    }

    private function generateFilename(LanguageResource $languageResource): string
    {
        return $languageResource->getSpecificData('fileName') . self::EXPORT_FILE_EXTENSION;
    }

    private function getFilePath(): string
    {
        return APPLICATION_PATH . self::DATA_RELATIVE_PATH;
    }

    private function export(
        Connector $connector,
        LanguageResource $languageResource,
        string $filenameWithPath,
        string $type
    ): void {
        $connector->connectTo($languageResource, $languageResource->getSourceLang(), $languageResource->getTargetLang());

        file_put_contents($filenameWithPath, $connector->getTm($type));

        if (!file_exists($filenameWithPath)) {
            throw new RuntimeException('Failed to export file to ' . $filenameWithPath);
        }
    }

    /**
     * @throws Zend_Exception
     */
    private function import(
        Connector $connector,
        LanguageResource $languageResource,
        string $filenameWithPath,
        string $type
    ): void {
        $fileInfo = [
            'tmp_name' => $filenameWithPath,
            'type' => $type,
            'name' => basename($filenameWithPath),
        ];

        $connector->connectTo($languageResource, $languageResource->getSourceLang(), $languageResource->getTargetLang());
        $successful = $connector->addTm($fileInfo);

        if (!$successful) {
            throw new RuntimeException('Failed to import file to ' . $filenameWithPath);
        }
    }

    /**
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     */
    private function revertChanges(LanguageResource $languageResource, array $primaryData): void
    {
        $languageResource->setSpecificData(json_decode($primaryData['specificData'], true, 512, JSON_THROW_ON_ERROR));
        $languageResource->setResourceId($primaryData['resourceId']);

        $languageResource->save();
    }

    private function writeResult(array $processingErrors): void
    {
        if (count($processingErrors) === 0) {
            $this->io->success('Processing done');

            return;
        }

        $this->io->warning('There were errors with migrating data');
        $headers = array_map('ucfirst', array_keys(reset($processingErrors)));
        $this->io->table($headers, $processingErrors);
    }
}
