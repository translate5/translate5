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
use Symfony\Component\Console\Input\InputOption;
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
    private const ARGUMENT_TARGET_URL = 'targetUrl';
    private const OPTION_SOURCE_URL = 'sourceUrl';
    private const OPTION_DO_NOT_WAIT_IMPORT_FINISHED = 'doNotWaitImportFinish';
    private const OPTION_WAIT_TIMEOUT = 'wait-timeout';
    private const DATA_RELATIVE_PATH = '/../data/';
    private const EXPORT_FILE_EXTENSION = '.tmx';
    private const DEFAULT_WAIT_TIME_SECONDS = 300;
    private const DEFAULT_WAIT_TICK_TIME_SECONDS = 5;

    protected static $defaultName = 't5memory:migrate';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('Migrates all existing OpenTM2 language resources to t5memory')
            ->setHelp('Tool exports OpenTM2 language resources one by one and imports data to the t5memory provided as endpoint argument')
            ->addArgument(self::ARGUMENT_TARGET_URL, InputArgument::REQUIRED, 't5memory endpoint data to be imported to, e.g. http://t5memory.local/t5memory')
            ->addOption(self::OPTION_SOURCE_URL, 's', InputOption::VALUE_OPTIONAL, 'Endpoint data is exported from, e.g. http://t5memory.local/t5memory')
            ->addOption(self::OPTION_DO_NOT_WAIT_IMPORT_FINISHED, 'd', InputOption::VALUE_NEGATABLE, 'Skips waiting for import to finish before processing next language resource', false)
            ->addOption(self::OPTION_WAIT_TIMEOUT, 't', InputOption::VALUE_OPTIONAL, 'Timeout in seconds for waiting for import to finish', self::DEFAULT_WAIT_TIME_SECONDS);
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

        $targetUrl = $this->getTargetUrl($input, $service);
        $sourceResourceId = $this->getSourceResourceId($input, $service);

        $languageResourcesData = ZfExtended_Factory::get(LanguageResource::class)->getByResourceId($sourceResourceId);

        if (count($languageResourcesData) === 0) {
            $this->io->warning('Nothing to process. Exit.');

            return self::SUCCESS;
        }

        $this->addUrlToConfig($targetUrl);

        $targetResourceId = $this->getTargetResourceId($targetUrl);

        $processingErrors = [];
        $connector = new Connector();

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

            $languageResource->setResourceId($targetResourceId);

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

        if (count($processingErrors) === 0) {
            $this->cleanupConfig($sourceResourceId);
            $targetResourceId = $this->getTargetResourceId($targetUrl);
            $this->updateLanguageResources(array_column($languageResourcesData, 'id'), $targetResourceId);
        }

        $this->writeResult($processingErrors);

        if ($progressBar->getMaxSteps() === count($processingErrors)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function getTargetUrl(InputInterface $input, Service $service): Uri
    {
        $url = new Uri($input->getArgument(self::ARGUMENT_TARGET_URL));

        foreach ($service->getResources() as $resource) {
            if ($resource->getUrl() === (string)$url) {
                throw new RuntimeException('Endpoint already exists');
            }
        }

        return $url;
    }

    private function getSourceResourceId(InputInterface $input, Service $service): ?string
    {
        $sourceUrl = $input->getOption(self::OPTION_SOURCE_URL);

        $resourceId = null;
        foreach ($service->getResources() as $resource) {
            if (($sourceUrl && $resource->getUrl() === $sourceUrl)
                || str_contains($resource->getUrl(), 'otmmemoryservice')
            ) {
                $resourceId = $resource->getId();

                break;
            }
        }

        if (null === $resourceId) {
            throw new RuntimeException('No resource for given url found');
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
    private function addUrlToConfig(Uri $url): void
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

    /**
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws JsonException
     */
    private function cleanupConfig(string $otmResourceId): void
    {
        $service = new Service();

        foreach ($service->getResources() as $resource) {
            if ($resource->getId() === $otmResourceId) {
                $url = $resource->getUrl();
                break;
            }
        }

        if (!isset($url)) {
            throw new \RuntimeException('Something went wrong, OpenTM2 url not found, can not cleanup');
        }

        $config = ZfExtended_Factory::get(editor_Models_Config::class);
        $config->loadByName('runtimeOptions.LanguageResources.opentm2.server');
        $value = json_decode($config->getValue(), true, 512, JSON_THROW_ON_ERROR);

        array_splice($value, array_search($url, $value, true), 1);

        $config->setValue(json_encode($value, JSON_THROW_ON_ERROR));
        $config->save();

        $dbConfig = ZfExtended_Factory::get(DbConfig::class);
        $dbConfig->setBootstrap(Zend_Registry::get('bootstrap'));
        $dbConfig->init();
    }

    private function getTargetResourceId(Uri $url): string
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

        $this->waitUntilImportFinished($connector);
    }

    /**
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws JsonException
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

    private function updateLanguageResources(array $languageResourcesIds, string $t5MemoryResourceId)
    {
        $sql = "UPDATE `LEK_languageresources` 
                SET `resourceId` = '{$t5MemoryResourceId}'
                WHERE `id` IN (" . implode(', ', $languageResourcesIds) . ")";

        $languageResource = ZfExtended_Factory::get(LanguageResource::class);
        $languageResource->db->getAdapter()->query($sql);
    }

    private function waitUntilImportFinished(Connector $connector): void
    {
        if ($this->input->getOption(self::OPTION_DO_NOT_WAIT_IMPORT_FINISHED)) {
            $this->io->text("\nSkip waiting for import finished");

            return;
        }

        $this->io->text("\nWaiting until import finished");

        $timeElapsed = 0;
        $maxWaitTime = (int)$this->input->getOption(self::OPTION_WAIT_TIMEOUT);
        $waitTimeBetweenChecks = self::DEFAULT_WAIT_TICK_TIME_SECONDS;

        $progressBar = $this->io->createProgressBar($maxWaitTime);
        $progressBar->start();

        while ($timeElapsed < $maxWaitTime) {
            $status = $connector->getStatus($connector->getResource());

            if ($status === \editor_Services_Connector_Abstract::STATUS_AVAILABLE) {
                $this->io->success('Import finished');
                $progressBar->finish();

                return;
            }

            sleep($waitTimeBetweenChecks);
            $timeElapsed += $waitTimeBetweenChecks;
            $progressBar->advance($waitTimeBetweenChecks);
        }

        $progressBar->finish();

        $this->io->warning('Import not finished after ' . $maxWaitTime . ' seconds');
    }
}
