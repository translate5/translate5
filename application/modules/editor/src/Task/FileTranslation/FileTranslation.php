<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/

declare(strict_types=1);

namespace MittagQI\Translate5\Task\FileTranslation;

use DateTime;
use editor_Models_Import_DataProvider_Factory;
use editor_Models_LanguageResources_CustomerAssoc;
use editor_Models_LanguageResources_LanguageResource;
use editor_Models_Languages;
use editor_Models_Task;
use editor_Models_TaskUsageLog;
use editor_Plugins_MatchAnalysis_Init;
use editor_Task_Type;
use Exception;
use MittagQI\Translate5\Customer\Exception\InexistentCustomerException;
use MittagQI\Translate5\EventDispatcher\EventDispatcher;
use MittagQI\Translate5\LanguageResource\Event\LanguageResourceTaskAssociationChangeEvent;
use MittagQI\Translate5\LanguageResource\Event\LanguageResourceTaskAssociationChangeType;
use MittagQI\Translate5\LanguageResource\Operation\AssociateTaskOperation;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\LanguageResourceRepository;
use MittagQI\Translate5\Repository\LanguageResourceTaskAssocRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Task\Import\ImportEventTrigger;
use MittagQI\Translate5\Task\Import\ImportService;
use MittagQI\Translate5\User\Exception\InexistentUserException;
use ReflectionException;
use SplFileInfo;
use Zend_Acl_Exception;
use Zend_Cache_Exception;
use Zend_Db_Statement_Exception;
use Zend_EventManager_Event;
use Zend_EventManager_StaticEventManager;
use Zend_Exception;
use Zend_Http_Client_Exception;
use Zend_Registry;
use ZfExtended_Authentication;
use ZfExtended_Exception;
use ZfExtended_Factory;
use ZfExtended_FileUploadException;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Utils;

/**
 * Helper for a translation of the given file with the given language-combination  (= ids as in LEK_languages).
 * 1) automatically create a hidden task for the document
 * 2) pre-translate it against the available language resources for the language combination for the customer
 *    in the same way pre-translations work for tasks through the GUI (first termCollections, second TMs, third MTs)
 */
class FileTranslation
{
    /**
     * @throws ReflectionException
     * @throws Zend_Exception
     */
    public static function create(): self
    {
        return new self(
            new UserRepository(),
            AssociateTaskOperation::create(),
            LanguageResourceRepository::create(),
            CustomerRepository::create(),
            new ImportService(),
            ZfExtended_Authentication::getInstance(),
            Zend_Registry::get('PluginManager')->get('MatchAnalysis'),
            Zend_EventManager_StaticEventManager::getInstance(),
            LanguageResourceTaskAssocRepository::create(),
        );
    }

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AssociateTaskOperation $associateTaskOperation,
        private readonly LanguageResourceRepository $languageResourceRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly ImportService $importService,
        private readonly ZfExtended_Authentication $authentication,
        private readonly editor_Plugins_MatchAnalysis_Init $matchanalysisPlugin,
        private readonly Zend_EventManager_StaticEventManager $eventManager,
        private readonly LanguageResourceTaskAssocRepository $assocRepository
    ) {
    }

    /**
     * Prepare a task for import of the file with the given language-combination,
     * run the pretranslation and start the task-import.
     * 1) automatically create a hidden task for the document
     * 2) pre-translate it against the available language resources for the language combination for the customer
     *    in the same way pre-translations work for tasks through the GUI (first termCollections, second TMs, third MTs)
     *    or, when targetLanguageResourceAssignments is provided, use the explicitly selected resources for each task
     * 3) When the document is pre-translated, we will offer it for download
     *
     * @throws FileTranslationException
     * @throws ReflectionException
     * @throws Zend_Acl_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws Exception
     * @throws Zend_Http_Client_Exception
     * @throws ZfExtended_FileUploadException
     */
    public function importAndTranslate(
        SplFileInfo $importFile,
        int $sourceLang,
        array $targetLang,
        string $taskName,
        int $customerId,
        array $targetLanguageResourceAssignments = [],
    ): editor_Models_Task {
        $this->checkRights();

        // Explicit language resource assignments keyed by target language id. The queue ensures that each task receives the
        // next configured set of resource ids for its target language (if any).
        $targetLanguageResourceQueue = empty($targetLanguageResourceAssignments)
            ? null
            : new TargetLanguageResourceQueue($targetLanguageResourceAssignments);

        $task = $this->prepareTaskImport($taskName, $sourceLang, $customerId);

        $isSingle = $this->importService->prepareTaskType(
            $task,
            count($targetLang) > 1,
            $task->getTaskType()->id()
        );

        $dataprovider = ZfExtended_Factory::get(editor_Models_Import_DataProvider_Factory::class);

        $this->eventManager->attach(
            ImportEventTrigger::class,
            ImportEventTrigger::IMPORT_WORKER_QUEUED,
            function (Zend_EventManager_Event $event) use ($targetLanguageResourceQueue) {
                /** @var editor_Models_Task $task */
                $task = $event->getParam('task');
                $assignedLanguageResourceIds = $this->manageAssociation($task, $targetLanguageResourceQueue);
                // pretranslate the task
                /*
                 * (Marc:)
                 * "Bei der Analyse von Tasks kann man die Mindest-Matchrate für Vorübersetzungen einstellen.
                 * Default ist 100%. Wenn man dann anhakt, dass MT-Vorübersetzung aktiv ist, wird alles weitere
                 * was dann noch leer ist von der Maschine vorübersetzt. Das passt für Tasks so und
                 * genauso sollten wir es auch im InstantTranslate machen: Alles mit 100% oder mehr aus dem TM
                 * und alles andere durch die Maschine."
                 */
                $this->matchanalysisPlugin->queueInternalPretranslation($task, [
                    'internalFuzzy' => 0,
                    'pretranslateMatchrate' => 100,
                    'pretranslateTmAndTerm' => 1,
                    'pretranslateMt' => 1,
                    'isTaskImport' => 0,
                ]);

                // add usage log
                $this->insertTaskUsageLog($task, $assignedLanguageResourceIds);
            }
        );
        if ($isSingle) {
            $this->importService->importSingleTask(
                $task,
                $dataprovider->createFromPath($importFile->getPathname()),
                (int) reset($targetLang),
                $this->authentication->getUser()
            );
        } else {
            $this->importService->importProject(
                $task,
                $dataprovider->createFromPath($importFile->getPathname()),
                $targetLang,
                $this->authentication->getUser()
            );
        }

        $this->importService->startWorkers($task);

        return $task;
    }

    /**
     * @throws FileTranslationException
     */
    private function checkRights(): void
    {
        //only instanttranslate file translatations are allowed
        foreach (FileTranslationTypeChecker::getTranslationTypeTaskIds() as $id) {
            if ($this->authentication->isUserAllowed(editor_Task_Type::ID, $id)) {
                return;
            }
        }

        throw new FileTranslationException('E1213', [
            'msg' => "Initial tasktype for pretranslations does not match acl-rules.",
        ]);
    }

    /**
     * Validate and assign the provided language resources to the task. In case no resources are given, the automatic
     * resources assignment will take place.
     *
     * @throws FileTranslationException
     * @throws ReflectionException
     * @throws Zend_Cache_Exception
     * @throws Zend_Exception
     */
    private function manageAssociation(editor_Models_Task $task, ?TargetLanguageResourceQueue $targetLanguageResourceQueue): array
    {
        $explicitResources = $targetLanguageResourceQueue?->consume($task->getTargetLang());
        if (empty($explicitResources)) {
            return $this->assignLanguageResources($task);
        }

        // cleanup and left over MT resource assigned to the task before we proceed with custom selected MT resources
        // for this task. Manage association is only called once per task so this here is safe to do.
        $this->assocRepository->deleteMTsForTask($task->getTaskGuid());

        return $this->assignAvailableLanguageResources($task, $explicitResources);
    }

    private function assignAvailableLanguageResources(editor_Models_Task $task, array $resourceIds): array
    {
        $resourceIds = array_values(array_unique(array_filter(
            array_map('intval', $resourceIds),
            static fn (int $id): bool => $id > 0
        )));

        if (empty($resourceIds)) {
            return [];
        }

        $assignable = $this->getTaskAssignableResources($task, $resourceIds);
        $assignable = array_values(array_unique(array_map('intval', $assignable)));

        $invalid = array_values(array_diff($resourceIds, $assignable));
        if (! empty($invalid)) {
            throw new FileTranslationException('E1740', [
                'msg' => 'File-Translation: Some provided language resources are not assignable for this task.',
                'invalidResourceIds' => $invalid,
            ]);
        }

        $assignedLanguageResourceIds = [];
        foreach ($assignable as $resourceId) {
            $this->addLanguageResource((int) $resourceId, $task->getTaskGuid());
            $assignedLanguageResourceIds[] = (int) $resourceId;
        }

        return $assignedLanguageResourceIds;
    }

    /**
     * Prepare import for task with the given file.
     * Creates a task-entity for further handling, but does not run the import yet.
     * @throws FileTranslationException
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    private function prepareTaskImport(string $taskName, int $sourceLang, int $customerId): editor_Models_Task
    {
        try {
            $customer = $this->customerRepository->get($customerId);
        } catch (InexistentCustomerException $e) {
            throw new FileTranslationException('E1211', [
                'msg' => 'Task for importing file could not be created.',
            ], $e);
        }

        $config = $customer->getConfig();

        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        $task->createTaskGuidIfNeeded();
        $task->setImportAppVersion(ZfExtended_Utils::getAppVersion());

        $task->setLockLocked(1);
        $task->setSourceLang($sourceLang);
        $task->setTaskName($taskName);
        $currentUser = $this->authentication->getUser();
        $task->setPmGuid($currentUser->getUserGuid());
        $task->setPmName($currentUser->getUsernameLong());
        $task->setOrderdate((new DateTime())->format('Y-m-d H:i:s'));
        $task->setUsageMode($config->runtimeOptions->import->initialTaskUsageMode);
        $task->setWorkflow($config->runtimeOptions->workflow->initialWorkflow);
        $task->setEdit100PercentMatch((int) ($config->runtimeOptions->import->edit100PercentMatch));
        $task->setTaskType(FileTranslationType::ID);
        $task->setCustomerId($customer->getId());
        $task->setCreatedByUserGuid($currentUser->getUserGuid());

        $task->save();
        $task->setProjectId($task->getId());
        $task->save();

        if ($task->isErroneous()) {
            // If e.g. the file-type cannot be handled for import, we don't need the rest.
            throw new FileTranslationException('E1211', [
                'msg' => 'Task for importing file could not be created; '
                    . 'check file-format and if Okapi is running if needed.',
                'task' => $task,
            ]);
        }

        return $task;
    }

    /**
     * Assign LanguageResources to the task that match the language-combination.
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws Zend_Cache_Exception
     */
    public function assignLanguageResources(editor_Models_Task $task): array
    {
        $assignable = $this->getTaskAssignableResources($task);

        $langResTaskAssocs = ZfExtended_Factory::get(TaskAssociation::class);
        // some LanguageResources have been already auto-assigned on creating the task:
        $currentlyAssigned = $langResTaskAssocs->loadByTaskGuids([$task->getTaskGuid()]);
        $currentlyAssignedIds = array_map('intval', array_column($currentlyAssigned, 'languageResourceId'));

        //collect assigned
        $assignedLanguageResources = $currentlyAssignedIds;

        // add LanguageResources that have not been assigned already while creating the task:
        foreach ($assignable as $languageResource) {
            // is it not assigned already??
            if (in_array($languageResource, $currentlyAssignedIds)) {
                continue;
            }

            //collect the associated language resource id
            $assignedLanguageResources[] = $languageResource;

            // then assign it now:
            $this->addLanguageResource((int) $languageResource, $task->getTaskGuid());
        }

        return $assignedLanguageResources;
    }

    /**
     * Find all assignable resources for a task customer and language combination. Task tms will be ignored
     * @throws ReflectionException
     * @throws Zend_Cache_Exception
     * @throws Zend_Exception
     */
    private function getTaskAssignableResources(editor_Models_Task $task, array $restrictTo = []): array
    {
        $languageModel = ZfExtended_Factory::get(editor_Models_Languages::class);
        //get source and target language fuzzy
        $sourceLangs = $languageModel->getFuzzyLanguages((int) $task->getSourceLang(), 'id', true);
        $targetLangs = $languageModel->getFuzzyLanguages((int) $task->getTargetLang(), 'id', true);

        $langRes = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);
        //get the language resources to the current user and to the given languages. For file-translation
        // we can set the task customer. That is why we use the resource only of this customer.
        $toBeUsed = $langRes->loadByUserCustomerAssocs([], $sourceLangs, $targetLangs, [], [$task->getCustomerId()]);

        return $this->filterAssignableResources($toBeUsed, $restrictTo);
    }

    /**
     * Collect(filter) all resources which can be assigned to the file-translation tasks.
     * If $restrictTo is defined, only those resources will be used for file-translation.
     */
    private function filterAssignableResources(array $resources, array $restrictTo): array
    {
        $hasRestrictions = ! empty($restrictTo);
        $restrictToSet = $hasRestrictions ? array_flip($restrictTo) : [];
        $assignable = [];

        foreach ($resources as $resource) {
            if ($resource['isTaskTm']) {
                continue;
            }

            $resourceId = (int) $resource['id'];

            if (! $hasRestrictions || isset($restrictToSet[$resourceId])) {
                $assignable[] = $resourceId;
            }
        }

        return $assignable;
    }

    /**
     * Add a languageresource-task-assoc.
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    private function addLanguageResource(int $languageResourceId, string $taskGuid): void
    {
        $this->associateTaskOperation->associate($languageResourceId, $taskGuid);

        EventDispatcher::create()->dispatch(
            new LanguageResourceTaskAssociationChangeEvent(
                $this->languageResourceRepository->get($languageResourceId),
                $taskGuid,
                LanguageResourceTaskAssociationChangeType::Add,
            )
        );
    }

    /**
     * Return intersection between user customers and language resources customers.
     * Those customers will be used for logging the usage
     * @throws InexistentUserException
     * @throws ReflectionException
     */
    private function getCustomersForLogging(editor_Models_Task $task, array $assignedLanguageResourceIds): array
    {
        if (empty($assignedLanguageResourceIds)) {
            return [];
        }
        $customerAssoc = ZfExtended_Factory::get(editor_Models_LanguageResources_CustomerAssoc::class);
        //load all customers for the assigned language resources of the task
        // (those customers are also the current user customers)
        $resourceCustomers = $customerAssoc->loadLanguageResourcesCustomers($assignedLanguageResourceIds);
        $user = $this->userRepository->getByGuid($task->getPmGuid());

        return array_intersect($user->getCustomersArray(), $resourceCustomers);
    }

    /**
     * Insert task usage log for the current pretranslation request.
     * For each customer of the associated language resource, one log entry is inserted.
     * @throws ReflectionException
     * @throws Zend_Exception
     */
    private function insertTaskUsageLog(editor_Models_Task $task, array $assignedLanguageResourceIds): void
    {
        $customers = $this->getCustomersForLogging($task, $assignedLanguageResourceIds);

        //if no customers are found, it make no sence to log the ussage
        if (empty($customers)) {
            return;
        }
        //value of taskCount splited between used customers for pretranslation
        $taskCount = round(1 / count($customers), 2);

        $log = ZfExtended_Factory::get(editor_Models_TaskUsageLog::class);
        #id, taskType, sourceLang, targetLang, customerId, yearAndMonth, taskCount
        $log->setTaskType($task->getTaskType()->id());
        $log->setSourceLang($task->getSourceLang());
        $log->setTargetLang($task->getTargetLang());
        $log->setYearAndMonth(date('Y-m'));

        //foreach associated customer, add taskCount entry in the log table
        foreach ($customers as $customer) {
            $log->setCustomerId($customer);
            $log->updateInsertTaskCount($taskCount);
        }
    }
}
