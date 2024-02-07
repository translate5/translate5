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

namespace Translate5\MaintenanceCli\Command\T5Memory;

use editor_Models_Config;
use editor_Models_LanguageResources_CustomerAssoc as LanguageResourcesCustomerAssoc;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_LanguageResources_Languages as LanguageResourcesLanguages;
use editor_Services_OpenTM2_Connector as Connector;
use editor_Services_OpenTM2_Service as Service;
use Exception;
use GuzzleHttp\Psr7\Uri;
use JsonException;
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Throwable;
use Translate5\MaintenanceCli\Command\T5Memory\Traits\FilteringByNameTrait;
use Translate5\MaintenanceCli\Command\Translate5AbstractCommand;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Resource_DbConfig as DbConfig;

class T5MemoryMigrationCommand extends Translate5AbstractCommand
{
    use FilteringByNameTrait;

    private const ARGUMENT_TARGET_URL = 'targetUrl';
    private const ARGUMENT_SOURCE_URL = 'sourceUrl';
    private const OPTION_DO_NOT_WAIT_IMPORT_FINISHED = 'doNotWaitImportFinish';
    private const OPTION_WAIT_TIMEOUT = 'wait-timeout';
    private const OPTION_CLONE_LANGUAGE_RESOURCE = 'duplicate-language-resource';
    private const OPTION_CLONED_NAME_PART = 'cloned_name_part';
    private const OPTION_TM_NAME = 'tm-name';
    private const OPTION_CREATE_EMPTY = 'create-empty';
    private const OPTION_SOURCE_LANGUAGE  = 'source-language';
    private const OPTION_TARGET_LANGUAGE  = 'target-language';
    private const DATA_RELATIVE_PATH = '/../data/';
    private const EXPORT_FILE_EXTENSION = '.tmx';
    private const DEFAULT_WAIT_TIME_SECONDS = 600;
    private const DEFAULT_WAIT_TICK_TIME_SECONDS = 5;

    protected static $defaultName = 't5memory:migrate|memory:migrate';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('Migrates all existing OpenTM2 language resources to t5memory')
            ->setHelp('Tool exports OpenTM2 language resources one by one and '
                .'imports data to the t5memory provided as endpoint argument')
            ->addArgument(
                self::ARGUMENT_SOURCE_URL,
                InputArgument::REQUIRED,
                'Endpoint data is exported from (source), e.g. http://t5memory.local/t5memory'
            )
            ->addArgument(
                self::ARGUMENT_TARGET_URL,
                InputArgument::REQUIRED,
                't5memory endpoint data to be imported to (target), e.g. http://t5memory.local/t5memory'
            )
            ->addOption(
                self::OPTION_DO_NOT_WAIT_IMPORT_FINISHED,
                'd',
                InputOption::VALUE_NEGATABLE,
                'Skips waiting for import to finish before processing next language resource',
                false
            )
            ->addOption(
                self::OPTION_WAIT_TIMEOUT,
                't',
                InputOption::VALUE_OPTIONAL,
                'Timeout in seconds for waiting for import to finish',
                self::DEFAULT_WAIT_TIME_SECONDS
            )
            ->addOption(
                self::OPTION_CLONE_LANGUAGE_RESOURCE,
                'c',
                InputOption::VALUE_NEGATABLE,
                'If provided language resource will be cloned before migration. ' .
                    'New language resource will be named based on --name option value',
                false
            )
            ->addOption(
                self::OPTION_CLONED_NAME_PART,
                'name',
                InputOption::VALUE_OPTIONAL,
                'Name part for cloned language resource. ' .
                    'Name part can contain place where it should be placed e.g prefix: or suffix:.' .
                    'If not provided default prefix is used',
                'prefix:DUPLIKAT_TEST_'
            )
            ->addOption(
                self::OPTION_TM_NAME,
                'f',
                InputOption::VALUE_REQUIRED,
                'This will filter the list of all TMs if provided'
            )
            ->addOption(
                self::OPTION_CREATE_EMPTY,
                'e',
                InputOption::VALUE_REQUIRED,
                'UUID of the TM. If provided empty memory is created in the destination ' .
                't5memory omitting the export/import process'
            )
            ->addOption(
                self::OPTION_SOURCE_LANGUAGE,
                null,
                InputOption::VALUE_REQUIRED,
                'Source language code to filter language resources, doesn\'t work with --tm-name option'
            )
            ->addOption(
                self::OPTION_TARGET_LANGUAGE,
                null,
                InputOption::VALUE_REQUIRED,
                'Target language code to filter language resources, doesn\'t work with --tm-name option'
            );
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
        $sourceUrl = $input->getArgument(self::ARGUMENT_SOURCE_URL);
        $targetUrl = $this->getTargetUrl($input, $service);

        $sourceResourceId = $this->getSourceResourceId($sourceUrl, $service);
        $languageResourcesData = $this->getLanguageResourcesData($input, $sourceResourceId);

        if (count($languageResourcesData) === 0) {
            $this->io->warning('No language resources found for the given source URL');

            if (!$this->isFilteringByName() // We are not filtering by name, but processing all resources
                && !$this->createEmptyRequested() // We are not creating empty resource in target t5memory
            ) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion(
                    'Do you want to remove ' . $sourceUrl . ' from config? (Y/N)',
                    false
                );

                if ($helper->ask($this->input, $this->output, $question)) {
                    $this->cleanupConfig($sourceUrl, $targetUrl);
                }
            }

            return self::SUCCESS;
        }

        if (!$this->isFilteringByName()) {
            $questionText = 'All memories will be migrated from ' . $input->getArgument(self::ARGUMENT_SOURCE_URL)
                .  ' to ' . $input->getArgument(self::ARGUMENT_TARGET_URL);
            $this->io->warning($questionText);
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Do you really want to proceed? (Y/N)', false);

            if (!$helper->ask($this->input, $this->output, $question)) {
                return self::SUCCESS;
            }
        }

        $targetResourceId = $this->getTargetResourceId($targetUrl);

        $cloneLanguageResource = $input->getOption(self::OPTION_CLONE_LANGUAGE_RESOURCE);

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
                $this->exportIfNeeded($connector, $languageResource, $filenameWithPath, $type);
            } catch (Throwable $e) {
                $processingErrors[] = [
                    'language resource id' => $languageResourceData['id'],
                    'message' => $e->getMessage()
                ];

                continue;
            }

            $languageResource = $this->cloneLanguageResourceIfNeeded($languageResource, $cloneLanguageResource);
            $languageResource->setResourceId($targetResourceId);

            try {
                $this->importOrCreateEmpty($connector, $languageResource, $filenameWithPath, $type);
            } catch (Throwable $e) {
                $processingErrors[] = [
                    'language resource: ' => $languageResourceData['id'] . ' (' . $languageResource->getName() . ')',
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

    private function getTargetUrl(InputInterface $input, Service $service): string
    {
        $url = new Uri($input->getArgument(self::ARGUMENT_TARGET_URL));
        $urlFound = false;

        foreach ($service->getResources() as $resource) {
            if ($resource->getUrl() === (string)$url) {
                $urlFound = true;
            }
        }

        if (!$urlFound) {
            $this->addUrlToConfig($url);
        }

        return (string)$url;
    }

    private function getSourceResourceId(string $sourceUrl, Service $service): ?string
    {
        $resourceId = null;
        foreach ($service->getResources() as $resource) {
            if ($sourceUrl && $resource->getUrl() === $sourceUrl) {
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
    private function cleanupConfig(string $sourceUrl): void
    {
        $service = new Service();
        $namespace = $service->getServiceNamespace();

        $config = ZfExtended_Factory::get(editor_Models_Config::class);
        $config->loadByName('runtimeOptions.LanguageResources.opentm2.server');
        $value = json_decode($config->getValue(), true, 512, JSON_THROW_ON_ERROR);

        array_splice($value, array_search($sourceUrl, $value, true), 1);

        $newIndexes = [];
        foreach ($service->getResources() as $resource) {
            $newIndexes[$resource->getId()] = array_search($resource->getUrl(), $value, true);
        }

        $config->setValue(json_encode($value, JSON_THROW_ON_ERROR));
        $config->save();

        foreach ($newIndexes as $resourceId => $newIndex) {
            if ($newIndex === false) {
                continue;
            }

            $this->updateLanguageResources($resourceId, $namespace . '_' . ++$newIndex);
        }
    }

    private function getTargetResourceId(string $url): string
    {
        $service = new Service();

        foreach ($service->getResources() as $resource) {
            if ($resource->getUrl() === $url) {
                return $resource->getId();
            }
        }

        throw new RuntimeException('Something went wrong: no t5memory resource id found');
    }

    private function generateFilename(LanguageResource $languageResource): string
    {
        $fileName = $languageResource->getSpecificData('memories', true)[0]['filename'];
        $fileName = str_replace('ID' . $languageResource->getId(), '', $fileName);

        return $fileName . self::EXPORT_FILE_EXTENSION;
    }

    private function getFilePath(): string
    {
        return APPLICATION_PATH . self::DATA_RELATIVE_PATH;
    }

    private function exportIfNeeded(
        Connector $connector,
        LanguageResource $languageResource,
        string $filenameWithPath,
        string $type
    ): void {
        if ($this->createEmptyRequested()) {
            return;
        }

        $connector->connectTo(
            $languageResource,
            $languageResource->getSourceLang(),
            $languageResource->getTargetLang()
        );

        $filename = $connector->export($type);
        rename($filename, $filenameWithPath);

        if (!file_exists($filenameWithPath)) {
            throw new RuntimeException('Failed to export file to ' . $filenameWithPath);
        }
    }

    /**
     * @throws Zend_Exception
     */
    private function importOrCreateEmpty(
        Connector $connector,
        LanguageResource $languageResource,
        string $filenameWithPath,
        string $type
    ): void {
        if (!$this->createEmptyRequested()) {
            $fileInfo = [
                'tmp_name' => $filenameWithPath,
                'type' => $type,
                'name' => basename($filenameWithPath),
            ];
        } else {
            $fileInfo = [];
        }

        $connector->connectTo($languageResource, $languageResource->getSourceLang(), $languageResource->getTargetLang());
        $successful = $connector->addTm($fileInfo);

        if (!$successful) {
            throw new RuntimeException('Failed to import file to ' . $filenameWithPath);
        }

        $this->waitUntilImportFinished($connector, $languageResource);
    }

    /**
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws JsonException
     */
    private function revertChanges(LanguageResource $languageResource, array $primaryData): void
    {
        if ($languageResource->getId() !== $primaryData['id']) {
            $remover = ZfExtended_Factory::get(
                \editor_Models_LanguageResources_Remover::class,
                [$languageResource]
            );
            $remover->remove(forced: true);

            return;
        }

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

    private function updateLanguageResources(string $sourceResourceId, string $targetResourceId): void
    {
        $sql = "UPDATE `LEK_languageresources` 
                SET `resourceId` = '{$targetResourceId}'
                WHERE `resourceId` = '{$sourceResourceId}'";

        $languageResource = ZfExtended_Factory::get(LanguageResource::class);
        $languageResource->db->getAdapter()->query($sql);
    }

    private function waitUntilImportFinished(Connector $connector, LanguageResource $languageResource): void
    {
        if ($this->input->getOption(self::OPTION_DO_NOT_WAIT_IMPORT_FINISHED)) {
            $this->io->text("\nSkip waiting for import finished");

            return;
        }

        $this->io->text("\nWaiting until import is finished");

        $timeElapsed = 0;
        $maxWaitTime = (int)$this->input->getOption(self::OPTION_WAIT_TIMEOUT);
        $waitTimeBetweenChecks = self::DEFAULT_WAIT_TICK_TIME_SECONDS;

        $progressBar = $this->io->createProgressBar($maxWaitTime);
        $progressBar->start();

        while ($timeElapsed < $maxWaitTime) {
            $status = $connector->getStatus($connector->getResource(), $languageResource);

            if ($status === LanguageResourceStatus::AVAILABLE) {
                $this->io->success('Import finished');
                $progressBar->finish();

                return;
            }

            if ($status === LanguageResourceStatus::ERROR) {
                $progressBar->finish();
                $this->io->warning('Error occurred during importing');

                throw new RuntimeException('Error occurred during importing');
            }

            sleep($waitTimeBetweenChecks);
            $timeElapsed += $waitTimeBetweenChecks;
            $progressBar->advance($waitTimeBetweenChecks);
        }

        $progressBar->finish();

        $this->io->warning('Import not finished after ' . $maxWaitTime . ' seconds');

        throw new RuntimeException('Import not finished after ' . $maxWaitTime . ' seconds');
    }

    private function cloneLanguageResourceIfNeeded(LanguageResource $languageResource, bool $clone): LanguageResource
    {
        if (!$clone) {
            return $languageResource;
        }

        $namePart = $this->input->getOption(self::OPTION_CLONED_NAME_PART);
        $nameParts = explode(':', $namePart);

        if (count($nameParts) === 2) {
            if ($nameParts[0] === 'prefix') {
                $name = $nameParts[1] . $languageResource->getName();
            } else {
                $name = $languageResource->getName() . $nameParts[1];
            }
        } else {
            $name = $namePart . $languageResource->getName();
        }

        // TODO we have similar code in PrivatePlugins think about moving this to factory or something like that
        $newLanguageResource = new LanguageResource();
        $newLanguageResource->init([
            'resourceId' => $languageResource->getResourceId(),
            'resourceType' => $languageResource->getResourceType(),
            'serviceType' => $languageResource->getServiceType(),
            'serviceName' => $languageResource->getServiceName(),
            'color' => $languageResource->getColor(),
            'name' => $name,
        ]);
        $newLanguageResource->createLangResUuid();
        $newLanguageResource->validate();
        $newLanguageResource->save();

        $resourceLanguages = ZfExtended_Factory::get(LanguageResourcesLanguages::class);
        $resourceLanguages->setSourceLang($languageResource->getSourceLang());
        $resourceLanguages->setSourceLangCode($languageResource->getSourceLangCode());
        $resourceLanguages->setTargetLang($languageResource->getTargetLang());
        $resourceLanguages->setTargetLangCode($languageResource->getTargetLangCode());
        $resourceLanguages->setLanguageResourceId($newLanguageResource->getId());
        $resourceLanguages->save();

        foreach ($languageResource->getCustomers() as $customerId) {
            $customerAssoc = ZfExtended_Factory::get(LanguageResourcesCustomerAssoc::class);
            $customerAssoc->setCustomerId($customerId);
            $customerAssoc->setLanguageResourceId($newLanguageResource->getId());
            $customerAssoc->save();
        }

        $newLanguageResource->refresh();

        return $newLanguageResource;
    }

    private function getLanguageResourcesData(InputInterface $input, string $sourceResourceId): array
    {
        $languageResource = ZfExtended_Factory::get(LanguageResource::class);

        $createEmptyUUID = $input->getOption(self::OPTION_CREATE_EMPTY);
        if ($createEmptyUUID !== null) {
            try {
                $data = $languageResource->loadByUuid($createEmptyUUID);
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                $this->io->error('Language resource with UUID "' . $createEmptyUUID . '" not found.');

                return [];
            }

            if ($data['resourceId'] !== $sourceResourceId) {
                $this->io->error('Language resource has different resource id.');

                return [];
            }

            return [$data];
        }

        if (!$this->isFilteringByName() && !$this->isFilteringByLanguages()) {
            // Return all language resources for given source resource id in case there is no filter by name
            return $languageResource->getByResourceId($sourceResourceId);
        }

        if ($this->isFilteringByLanguages()) {
            if ($this->isFilteringByName()) {
                throw new RuntimeException(
                    'Filtering by name and languages at the same time is not supported'
                );
            }

            return $languageResource->getByResourceIdFilteredByLanguageCodes(
                $sourceResourceId,
                $input->getOption(self::OPTION_SOURCE_LANGUAGE),
                $input->getOption(self::OPTION_TARGET_LANGUAGE)
            );
        }

        $languageResourcesData = $languageResource->getByResourceIdFilteredByNamePart(
            $sourceResourceId,
            $input->getOption(self::OPTION_TM_NAME)
        );

        if (count($languageResourcesData) > 1) {
            $askMemories = new ChoiceQuestion(
                'Please choose a Memory:',
                array_values(array_map(
                    static fn($data) =>
                        sprintf('%s | %s | %s', $data['name'], $data['sourceLangCode'], $data['targetLangCode']),
                    $languageResourcesData
                )),
                null
            );
            $id = $this->io->askQuestion($askMemories);

            $languageResourcesData = [
                $languageResourcesData[array_search($id, array_column($languageResourcesData, 'name'), true)]
            ];
        }

        return $languageResourcesData;
    }

    private function createEmptyRequested(): bool
    {
        return $this->getInput()->getOption(self::OPTION_CREATE_EMPTY) !== null;
    }

    protected function getInput(): InputInterface
    {
        return $this->input;
    }

    private function isFilteringByLanguages(): bool
    {
        $sourceLanguage = $this->input->getOption(self::OPTION_SOURCE_LANGUAGE);
        $targetLanguage = $this->input->getOption(self::OPTION_TARGET_LANGUAGE);

        if (($sourceLanguage !== null && $targetLanguage === null)
            || ($sourceLanguage === null && $targetLanguage !== null)
        ) {
            throw new RuntimeException('Both source and target language must be provided');
        }

        return $sourceLanguage !== null
            && $targetLanguage !== null;
    }
}
