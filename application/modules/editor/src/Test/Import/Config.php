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

namespace MittagQI\Translate5\Test\Import;

use MittagQI\Translate5\Test\Api\Helper;
use MittagQI\Translate5\Test\Enums\TestUser;

/**
 * Holds all tasks/resources to import, processes them and exposes the results of the requests
 */
final class Config
{
    private static int $counter = 0;

    private Helper $api;

    private string $testClass;

    private string $login;

    /**
     * @var Task[]
     */
    private array $tasks = [];

    /**
     * @var LanguageResource[]
     */
    private array $langResources = [];

    /**
     * @var TermCollection[]
     */
    private array $termCollections = [];

    /**
     * @var AbstractResource[]
     */
    private array $otherResources = [];

    private ?Operation $taskOperation = null;

    public function __construct(Helper $api, string $testClass, string $login)
    {
        $this->api = $api;
        $this->testClass = $testClass;
        $this->login = $login;
    }

    /**
     * @throws Exception
     * @throws \MittagQI\Translate5\Test\Api\Exception
     * @throws \Zend_Exception
     * @throws \Zend_Http_Client_Exception
     */
    public function setup(): void
    {
        // first other resources
        foreach ($this->otherResources as $resource) {
            $resource->import($this->api, $this);
        }
        // then import the language resources
        foreach ($this->langResources as $resource) {
            $resource->import($this->api, $this);
        }
        // then termcollections
        foreach ($this->termCollections as $termCollection) {
            $termCollection->import($this->api, $this);
        }
        // lastly the tasks
        foreach ($this->tasks as $task) {
            $task->import($this->api, $this);
        }
        // last step is to log the setup user in
        $this->api->login($this->login);
    }

    /**
     * @throws Exception
     */
    public function teardown(): void
    {
        $errors = [];
        // reversed order, first the tasks
        foreach ($this->tasks as $task) {
            $this->cleanupResource($task, $errors);
        }
        // then termcollections
        foreach ($this->termCollections as $termCollection) {
            $this->cleanupResource($termCollection, $errors);
        }
        // then language resources
        foreach ($this->langResources as $resource) {
            $this->cleanupResource($resource, $errors);
        }
        // lastly other resources
        foreach ($this->otherResources as $resource) {
            $this->cleanupResource($resource, $errors);
        }
        if (count($errors) > 0) {
            throw new Exception(implode("\n", $errors));
        }
    }

    /**
     * Helper to cleanup a resource. Will catch any exceptions and collects them as we want to cleanup as much as possible
     */
    private function cleanupResource(AbstractResource $resource, array &$errors)
    {
        if ($resource->wasImported()) {
            try {
                $resource->cleanup($this->api, $this);
            } catch (\Throwable $e) {
                // we dismiss any exceptions so following resources nevertheless can be cleaned ...
                $errors[] = $e->getMessage();
            }
        }
    }

    /**
     * Adds a single Task with the given props. return can be used to chain further specs
     * @param string|array|null $targetLanguage
     * @param string|null $filePathInTestFolder : if set, the file is added as upload
     */
    public function addTask(
        string $sourceLanguage = null,
        $targetLanguage = null,
        int $customerId = -1,
        string $filePathInTestFolder = null,
        int $maxImportWaitTime = -1
    ): Task {
        $next = count($this->tasks);
        $task = new Task($this->testClass, $next);
        if ($customerId > -1) {
            $task->customerId = $customerId;
        }
        if ($sourceLanguage != null) {
            $task->sourceLang = $sourceLanguage;
        }
        if ($targetLanguage != null) {
            $task->targetLang = $targetLanguage;
        }
        if ($filePathInTestFolder != null) {
            $task->addUploadFile($filePathInTestFolder);
        }
        if ($maxImportWaitTime > 0) {
            $task->setMaxWaitTime($maxImportWaitTime);
        }
        $this->tasks[] = $task;

        return $task;
    }

    /**
     * @param string $type : see LanguageResource::XXX or a fully qualified classname
     * @throws Exception
     */
    public function addLanguageResource(
        string $type,
        string $resourceFileName = null,
        int $customerId = -1,
        string $sourceLanguage = null,
        string $targetLanguage = null
    ): LanguageResource {
        $next = count($this->langResources);
        $resource = $this->createLanguageResource($type, $next);

        if ($resourceFileName !== null) {
            $resource->addUploadFile($resourceFileName);
        }

        if ($customerId > 0) {
            $resource->setProperty('customerIds', [$customerId]);
        }

        if ($sourceLanguage !== null && $resource->hasProperty('sourceLang')) {
            $resource->setProperty('sourceLang', $sourceLanguage);
        }

        if ($targetLanguage !== null && $resource->hasProperty('targetLang')) {
            $resource->setProperty('targetLang', $targetLanguage);
        }

        $this->langResources[] = $resource;

        return $resource;
    }

    /**
     * @param array|int $customerIds
     * @throws Exception
     */
    public function addTermCollection(string $tbxFile, $customerIds, string $userlogin = null, bool $mergeTerms = true): TermCollection
    {
        $userlogin = $userlogin ?? TestUser::TestTermProposer->value;

        $next = count($this->termCollections);
        $termCollection = new TermCollection($this->testClass, $next, $tbxFile, $userlogin);
        $termCollection->setProperty('customerIds', $customerIds);
        $termCollection->setProperty('mergeTerms', $mergeTerms);
        $this->termCollections[] = $termCollection;

        return $termCollection;
    }

    /**
     * Queues a Pretranslation for the task
     * Note, that by default pretranslateTmAndTerm is active, the other options not
     * Will overwrite any already queued Operation
     */
    public function addPretranslation(): Pretranslation
    {
        $this->taskOperation = new Pretranslation($this->testClass, 0);

        return $this->taskOperation;
    }

    /**
     * Queues a Pivot Batch-Pretranslation for the task
     * Will overwrite any already queued Operation
     */
    public function addPivotBatchPretranslation(): PivotBatchPretranslation
    {
        $this->taskOperation = new PivotBatchPretranslation($this->testClass, 0);

        return $this->taskOperation;
    }

    /**
     * Adds an Operation.
     * This resource must inherit from MittagQI\Translate5\Test\Import\Operation
     * It will only be created, nor requested
     */
    public function addTaskOperation(
        Task $task,
        string $className,
        array $params = [],
        int $maxOperatioWaitTime = -1
    ): Operation {
        $operation = new $className($this->testClass, 0, ...$params);
        $operation->setTask($task);
        if ($maxOperatioWaitTime > 0) {
            $task->setMaxWaitTime($maxOperatioWaitTime);
        }

        return $operation;
    }

    /**
     * Requests the task-operation that was added with ::addTaskOperation
     */
    public function requestOperation(Operation $operation): void
    {
        $operation->import($this->api, $this);
    }

    public function addBconf(string $name, string $bconfFileName, int $customerId = -1): Bconf
    {
        $next = count($this->otherResources);
        // HINT: in the bconf-DB the name must be unique. So we add the counter also to the DB-name to guarantee unique names for th ewhole testsuite
        $bconf = new Bconf($this->testClass, $next, $name . '-' . $this->getUniqueIndex(), $bconfFileName);
        if ($customerId > 0) {
            $bconf->customerId = $customerId;
        }
        $this->otherResources[] = $bconf;

        return $bconf;
    }

    /**
     * @return Pretranslation
     */
    public function getTaskOperation(): Operation
    {
        return $this->taskOperation;
    }

    /**
     * @return Task[]
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    /**
     * @return LanguageResource[]
     */
    public function getLanguageResources(): array
    {
        return $this->langResources;
    }

    public function hasTaskOperation(): bool
    {
        return ($this->taskOperation !== null);
    }

    public function hasLanguageResources(): bool
    {
        return (count($this->langResources) > 0);
    }

    public function hasTasks(): bool
    {
        return (count($this->tasks) > 0);
    }

    /**
     * @throws Exception
     */
    public function getTaskAt(int $index): Task
    {
        if (array_key_exists($index, $this->tasks)) {
            return $this->tasks[$index];
        }

        throw new Exception('No task with index "' . $index . '" present.');
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function hasTestlectorLogin(): bool
    {
        return $this->login === TestUser::TestLector->value;
    }

    /**
     * Can be used to add resources after the setup-phase of an test
     * Resources imported this way will be cleaned up automatically nevertheless
     */
    public function import(AbstractResource $resource)
    {
        $resource->import($this->api, $this);
    }

    /**
     * @throws Exception
     */
    private function createLanguageResource(string $type, int $nextIndex): LanguageResource
    {
        // Private Plugins may provide a class-name as type
        if (str_contains($type, 'MittagQI\\')) {
            return new $type($type, $nextIndex);
        }
        switch (strtolower($type)) {
            case LanguageResource::T5_MEMORY:
                return new T5Memory($this->testClass, $nextIndex);

            case LanguageResource::DEEPL:
                return new DeepL($this->testClass, $nextIndex);

            case LanguageResource::DUMMY_TM:
                return new DummyTm($this->testClass, $nextIndex);

            case LanguageResource::ZDemo_MT:
                return new ZDemoMT($this->testClass, $nextIndex);

            case LanguageResource::TERM_COLLECTION:
            case 'termcollectionresource':
                return new TermCollectionResource($this->testClass, $nextIndex);

            case LanguageResource::MICROSOFT_TRANSLATOR:
            case 'mstranslator':
                return new MicrosoftTranslator($this->testClass, $nextIndex);

            case LanguageResource::GOOGLE_TRANSLATE:
                return new GoogleTranslate($this->testClass, $nextIndex);

            default:
                throw new Exception('Unknown language-resource type "' . $type . '"');
        }
    }

    /**
     * Helper to create unique indices across the whole test-suite
     */
    private function getUniqueIndex(): int
    {
        static::$counter++;

        return static::$counter;
    }
}
