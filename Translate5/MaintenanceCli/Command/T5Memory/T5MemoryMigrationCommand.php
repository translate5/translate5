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
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_OpenTM2_Service as Service;
use Exception;
use GuzzleHttp\Psr7\Uri;
use JsonException;
use MittagQI\Translate5\LanguageResource\Adapter\Export\TmFileExtension;
use MittagQI\Translate5\LanguageResource\Operation\CloneLanguageResourceOperation;
use MittagQI\Translate5\LanguageResource\Operation\DeleteLanguageResourceOperation;
use MittagQI\Translate5\T5Memory\Api\T5MemoryApi;
use MittagQI\Translate5\T5Memory\CreateMemoryService;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use MittagQI\Translate5\T5Memory\ExportService;
use MittagQI\Translate5\T5Memory\ImportService;
use MittagQI\Translate5\T5Memory\MemoryNameGenerator;
use MittagQI\Translate5\T5Memory\PersistenceService;
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

    private const ARGUMENT_LANGUAGE_RESOURCE_ID = 'languageResourceId';

    private const OPTION_CLONE_LANGUAGE_RESOURCE = 'duplicate-language-resource';

    private const OPTION_CLONED_NAME_PART = 'cloned_name_part';

    private const OPTION_CREATE_EMPTY = 'create-empty';

    private const OPTION_SOURCE_LANGUAGE = 'source-language';

    private const OPTION_TARGET_LANGUAGE = 'target-language';

    private const OPTION_STRIP_FRAMING_TAGS_ON_IMPORT = 'strip-framing-tags-on-import';

    private const DATA_RELATIVE_PATH = '/../data/';

    private const EXPORT_FILE_EXTENSION = '.tmx';

    protected static $defaultName = 't5memory:migrate';

    private ExportService $exportService;

    private ImportService $importService;

    private CreateMemoryService $createMemoryService;

    private PersistenceService $persistenceService;

    private T5MemoryApi $t5MemoryApi;

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setDescription('Migrates all existing OpenTM2 language resources to t5memory')
            ->setHelp('Tool exports OpenTM2 language resources one by one and '
                . 'imports data to the t5memory provided as endpoint argument')
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
            ->addArgument(
                self::ARGUMENT_LANGUAGE_RESOURCE_ID,
                InputArgument::OPTIONAL,
                'Particular language resource ID to be migrated'
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
            )
            ->addOption(
                self::OPTION_STRIP_FRAMING_TAGS_ON_IMPORT,
                's',
                InputOption::VALUE_REQUIRED,
                'Option can have 3 possible values: `none`, `all`, `paired`. Default value is `none`' .
                ' And tells t5memory if it should strip framing tags on TMX import'
            )
            ->addOption(
                'yes',
                'y',
                InputOption::VALUE_NONE,
                'Automatic yes to prompts'
            )
        ;
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

        $this->exportService = ExportService::create();
        $this->importService = ImportService::create();
        $this->createMemoryService = CreateMemoryService::create();
        $this->persistenceService = PersistenceService::create();
        $this->t5MemoryApi = T5MemoryApi::create();

        $service = new Service();
        $sourceUrl = $input->getArgument(self::ARGUMENT_SOURCE_URL);
        $targetUrl = $this->getTargetUrl($input, $service);

        $sourceResourceId = $this->getSourceResourceId($sourceUrl, $service);
        $languageResourcesData = $this->getLanguageResourcesData($input, $sourceResourceId);

        if (count($languageResourcesData) === 0) {
            $this->io->warning('No language resources found for the given source URL');
            // Check if there are any language resources left for the source resource id and ask if it should be removed
            $languageResource = ZfExtended_Factory::get(LanguageResource::class);
            $lrsRemainWithResourceId = $languageResource->getByResourceId($sourceResourceId);

            if (count($lrsRemainWithResourceId) === 0) {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion(
                    'Do you want to remove ' . $sourceUrl . ' from config? (Y/N)',
                    false
                );

                if ($helper->ask($this->input, $this->output, $question)) {
                    $this->cleanupConfig($sourceUrl);
                }
            }

            return self::SUCCESS;
        }

        $stripFramingTagsOnImport = $this->getStripTagsOnImport($input);
        $targetResourceId = $this->getTargetResourceId($targetUrl);

        $cloneLanguageResource = $input->getOption(self::OPTION_CLONE_LANGUAGE_RESOURCE);

        $processingErrors = [];

        $progressBar = new ProgressBar($output, count($languageResourcesData));

        $logger = Zend_Registry::get('logger')->cloneMe('editor.languageresource');

        $questionText = count($languageResourcesData) . ' memories will be migrated from ' . $input->getArgument(self::ARGUMENT_SOURCE_URL)
            . ' to ' . $input->getArgument(self::ARGUMENT_TARGET_URL);
        $this->io->warning($questionText);
        $this->io->info(implode(PHP_EOL, array_column($languageResourcesData, 'id')));
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Do you really want to proceed? (Y/N)', false);

        $proceed = $input->getOption('yes') || $helper->ask($this->input, $this->output, $question);

        if (! $proceed) {
            return self::SUCCESS;
        }

        foreach ($languageResourcesData as $languageResourceData) {
            $progressBar->advance();

            $languageResource = new LanguageResource();
            $languageResource->load($languageResourceData['id']);

            $languageResource->markConversionStart();
            $languageResource->save();

            $logger->info('E0000', 'Language resource migrate start: {tm}', [
                'languageResource' => $languageResource,
                'tm' => $languageResource->getId() . ' - ' . $languageResource->getName(),
            ]);

            $filenameWithPath = $this->getFilePath() . $this->generateFilename($languageResource);

            try {
                $this->exportIfNeeded($languageResource, $filenameWithPath);
            } catch (Throwable $e) {
                $processingErrors[] = [
                    'language resource id' => $languageResource->getId(),
                    'message' => \ZfExtended_Logger::renderException($e),
                ];
                $languageResource->resetConversionMarks();
                $languageResource->save();

                continue;
            }

            $languageResource = $this->cloneLanguageResourceIfNeeded($languageResource, $cloneLanguageResource);
            $languageResource->setResourceId($targetResourceId);
            $languageResource->removeSpecificData('memories');
            $languageResource->removeSpecificData('version');

            try {
                $this->importOrCreateEmpty(
                    $languageResource,
                    $filenameWithPath,
                    $stripFramingTagsOnImport
                );
            } catch (Throwable $e) {
                $processingErrors[] = [
                    'language resource: ' => $languageResourceData['id'] . ' (' . $languageResource->getName() . ')',
                    'message' => $e->getMessage(),
                ];

                $this->revertChanges($languageResource, $languageResourceData);
            } finally {
                $languageResource->resetConversionMarks();
                $languageResource->save();
            }

            $logger->info('E0000', 'Language resource migrate finish: {tm}', [
                'languageResource' => $languageResource,
                'tm' => $languageResource->getId() . ' - ' . $languageResource->getName(),
            ]);
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
            if ($resource->getUrl() === (string) $url) {
                $urlFound = true;
            }
        }

        if (! $urlFound) {
            $this->addUrlToConfig($url);
        }

        return (string) $url;
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
        $value[] = (string) $url;
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

        return $fileName . self::EXPORT_FILE_EXTENSION;
    }

    private function getFilePath(): string
    {
        return APPLICATION_PATH . self::DATA_RELATIVE_PATH;
    }

    private function exportIfNeeded(
        LanguageResource $languageResource,
        string $filenameWithPath,
    ): void {
        if ($this->createEmptyRequested()) {
            return;
        }

        if (! $this->t5MemoryApi->ping($languageResource->getResource()->getUrl())) {
            throw new RuntimeException('Source t5memory endpoint is not reachable');
        }

        $filename = $this->exportService->export(
            $languageResource,
            TmFileExtension::TMX,
            unprotect: false,
        );

        rename($filename, $filenameWithPath);

        if (! file_exists($filenameWithPath)) {
            throw new RuntimeException('Failed to export file to ' . $filenameWithPath);
        }
    }

    /**
     * @throws Zend_Exception
     */
    private function importOrCreateEmpty(
        LanguageResource $languageResource,
        string $filenameWithPath,
        StripFramingTags $stripFramingTagsOnImport
    ): void {
        if (! $this->t5MemoryApi->ping($languageResource->getResource()->getUrl())) {
            throw new RuntimeException('Target t5memory endpoint is not reachable');
        }

        $tmName = $this->createMemoryService->createEmptyMemoryWithRetry(
            $languageResource,
            (new MemoryNameGenerator())->generateTmFilename($languageResource),
        );
        $this->persistenceService->addMemoryToLanguageResource($languageResource, $tmName);

        if ($this->createEmptyRequested()) {
            return;
        }

        $this->importService->importTmx(
            $languageResource,
            [$filenameWithPath],
            new ImportOptions(
                stripFramingTags: $stripFramingTagsOnImport,
                resegmentTmx: false,
                saveDifferentTargetsForSameSource: false,
                protectContent: false,
                forceLongWait: true,
            ),
        );
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
            DeleteLanguageResourceOperation::create()->delete($languageResource, forced: true);

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

    private function cloneLanguageResourceIfNeeded(LanguageResource $languageResource, bool $clone): LanguageResource
    {
        if (! $clone) {
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

        return CloneLanguageResourceOperation::create()->clone($languageResource, $name);
    }

    private function getLanguageResourcesData(InputInterface $input, string $sourceResourceId): array
    {
        $languageResource = new LanguageResource();

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

        if ($input->getArgument(self::ARGUMENT_LANGUAGE_RESOURCE_ID)) {
            /** @var \Zend_Db_Adapter_Abstract $db */
            $db = \Zend_Db_Table::getDefaultAdapter();

            $id = (int) $input->getArgument(self::ARGUMENT_LANGUAGE_RESOURCE_ID);

            $query = $db->select()
                ->from('LEK_languageresources')
                ->where('id = ?', $id)
                ->limit(1);

            return [$db->fetchRow($query)];
        }

        if (! $this->isFilteringByName() && ! $this->isFilteringByLanguages()) {
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
                array_map(
                    static fn ($data) =>
                        sprintf(
                            '%d | %s | %s | %s',
                            $data['id'],
                            $data['name'],
                            $data['sourceLangCode'],
                            $data['targetLangCode']
                        ),
                    $languageResourcesData
                ),
                null
            );

            $id = explode(' | ', $this->io->askQuestion($askMemories))[0];

            $languageResourcesData = [
                $languageResourcesData[array_search($id, array_column($languageResourcesData, 'id'), true)],
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

    private function getStripTagsOnImport(InputInterface $input): StripFramingTags
    {
        $requestedValue = $input->getOption(self::OPTION_STRIP_FRAMING_TAGS_ON_IMPORT) ?? '';

        $stripFramingTags = StripFramingTags::tryFrom($requestedValue);

        if (null !== $stripFramingTags) {
            return $stripFramingTags;
        }

        if ('' !== $requestedValue) {
            throw new RuntimeException(self::OPTION_STRIP_FRAMING_TAGS_ON_IMPORT . ' value is invalid.');
        }

        return StripFramingTags::None;
    }
}
