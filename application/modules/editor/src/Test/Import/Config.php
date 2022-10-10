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

/**
 * Holds all tasks/resources to import, processes them and exposes the results of the requests
 */
final class Config
{
    /**
     * @var Helper
     */
    private Helper $api;

    /**
     * @var string
     */
    private string $testClass;

    /**
     * @var string
     */
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
     * @var Pretranslation|null
     */
    private ?Pretranslation $pretranslation = null;

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
        // first import the language resources
        foreach ($this->langResources as $resource) {
            $resource->import($this->api, $this);
        }
        // then termcollections
        foreach ($this->termCollections as $termCollection) {
            $termCollection->import($this->api, $this);
        }
        // then the tasks
        foreach ($this->tasks as $task) {
            $task->import($this->api, $this);
        }
        // last step is to log the setup user in
        $this->api->login($this->login);
    }

    /**
     * cleans all resources up
     */
    public function teardown(): void
    {
        // reversed order, first the tasks
        foreach ($this->tasks as $task) {
            $task->cleanup($this->api, $this);
        }
        // then termcollections
        foreach ($this->termCollections as $termCollection) {
            $termCollection->cleanup($this->api, $this);
        }
        // then language resources
        foreach ($this->langResources as $resource) {
            $resource->cleanup($this->api, $this);
        }
    }

    /**
     * Adds a single Task with the given props. return can be used to chain further specs
     * @param string|null $sourceLanguage
     * @param string|array|null $targetLanguage
     * @param int $customerId
     * @param string|null $filePathInTestFolder: if set, the file is added as upload
     * @return Task
     */
    public function addTask(string $sourceLanguage = null, $targetLanguage = null, int $customerId = -1, string $filePathInTestFolder = null): Task
    {
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
        $this->tasks[] = $task;
        return $task;
    }

    /**
     * @param string $type: see LanguageResource::XXX
     * @param string|null $resourceFileName
     * @param array|null $customerIds
     * @param string|null $sourceLanguage
     * @param string|null $targetLanguage
     * @return LanguageResource
     * @throws Exception
     */
    public function addLanguageResource(string $type, string $resourceFileName = null, array $customerIds = null, string $sourceLanguage = null, string $targetLanguage = null): LanguageResource
    {
        $next = count($this->langResources);
        $resource = $this->createLanguageResource($type, $next);

        if ($resourceFileName !== null) {
            $resource->addUploadFile($resourceFileName);
        }
        if ($customerIds !== null) {
            $resource->setProperty('customerIds', $customerIds);
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
     * @param string $tbxFile
     * @param array|int $customerIds
     * @param string $userlogin
     * @param bool $mergeTerms
     * @return TermCollection
     * @throws Exception
     */
    public function addTermCollection(string $tbxFile, $customerIds, string $userlogin = 'testtermproposer', bool $mergeTerms = true): TermCollection
    {
        $next = count($this->termCollections);
        $termCollection = new TermCollection($this->testClass, $next, $tbxFile, $userlogin);
        $termCollection->setProperty('customerIds', $customerIds);
        $termCollection->setProperty('mergeTerms', $mergeTerms);
        $this->termCollections[] = $termCollection;
        return $termCollection;
    }

    /**
     * @return Pretranslation
     */
    public function addPretranslation(): Pretranslation
    {
        $this->pretranslation = new Pretranslation($this->testClass, 0);
        return $this->pretranslation;
    }

    /**
     * @return Pretranslation
     */
    public function getPretranslation(): Pretranslation
    {
        return $this->pretranslation;
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

    /**
     * @return bool
     */
    public function hasPretranslation(): bool
    {
        return ($this->pretranslation !== null);
    }

    /**
     * @return bool
     */
    public function hasLanguageResources(): bool
    {
        return (count($this->langResources) > 0);
    }

    /**
     * @return bool
     */
    public function hasTasks(): bool
    {
        return (count($this->tasks) > 0);
    }

    /**
     * @param int $index
     * @return Task
     * @throws Exception
     */
    public function getTaskAt(int $index): Task
    {
        if (array_key_exists($index, $this->tasks)) {
            return $this->tasks[$index];
        }
        throw new Exception('No task with index "' . $index . '" present.');
    }

    /**
     * @return string
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * @return bool
     */
    public function hasTestlectorLogin(): bool
    {
        return $this->login === 'testlector';
    }

    /**
     * Can be used to add resources after the setup-phase of an test
     * Resources imported this way will be cleaned up automatically nevertheless
     * @param Resource $resource
     */
    public function import(Resource $resource)
    {
        $resource->import($this->api, $this);
    }

    /**
     * @param string $type
     * @param int $nextIndex
     * @return LanguageResource
     * @throws Exception
     */
    private function createLanguageResource(string $type, int $nextIndex): LanguageResource
    {
        switch (strtolower($type)) {
            case LanguageResource::OPEN_TM2:
                return new OpenTm2($this->testClass, $nextIndex);

            case LanguageResource::DUMMY_TM:
                return new DummyTm($this->testClass, $nextIndex);

            case LanguageResource::DEEPL:
                return new DeepL($this->testClass, $nextIndex);

            case LanguageResource::TERM_COLLECTION:
            case 'termcollectionresource':
                return new TermCollectionResource($this->testClass, $nextIndex);

            default:
                throw new Exception('Unknown resource-type "' . $type . '"');
        }
    }
}
