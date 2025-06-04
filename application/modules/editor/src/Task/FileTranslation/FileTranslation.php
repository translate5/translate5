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

use editor_Models_LanguageResources_CustomerAssoc;
use editor_Models_LanguageResources_LanguageResource;
use editor_Models_Languages;
use editor_Models_Task;
use editor_Models_TaskUsageLog;
use editor_Task_Type;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\ZfExtended\ApiRequest;
use MittagQI\ZfExtended\ApiRequestDTO;
use ReflectionException;
use Zend_Acl_Exception;
use ZfExtended_Acl;
use ZfExtended_Authentication;
use ZfExtended_Factory;

/**
 * Helper for a translation of the given file with the given language-combination  (= ids as in LEK_languages).
 * 1) automatically create a hidden task for the document
 * 2) pre-translate it against the available language resources for the language combination for the customer
 *    in the same way pre-translations work for tasks through the GUI (first termCollections, second TMs, third MTs)
 */
class FileTranslation
{
    public static function create(string $loggerDomain = 'editor.filetranslation'): self
    {
        return new self(
            new UserRepository(),
            TaskRepository::create(),
            $loggerDomain
        );
    }

    /**
     * Collection of all associated langauge resources of the task to pretranslate
     */
    private array $assignedLanguageResources = [];

    /**
     * @throws ReflectionException
     */
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TaskRepository $taskRepository,
        private readonly string $loggerDomain
    ) {
    }

    /**
     * Prepare a task for import of the file with the given language-combination,
     * run the pretranslation and start the task-import.
     * 1) automatically create a hidden task for the document
     * 2) pre-translate it against the available language resources for the language combination for the customer
     *    in the same way pre-translations work for tasks through the GUI (first termCollections, second TMs, third MTs)
     * 3) When the document is pre-translated, we will offer it for download#
     *
     * @throws FileTranslationException
     * @throws Zend_Acl_Exception
     * @throws \Zend_Exception
     * @throws \Zend_Http_Client_Exception
     * @throws \ZfExtended_Exception
     * @throws \ZfExtended_FileUploadException
     */
    public function importAndTranslate(array $importFile, int $sourceLang, int $targetLang): editor_Models_Task
    {
        $acl = ZfExtended_Acl::getInstance();
        if (! $acl->isInAllowedRoles(
            ZfExtended_Authentication::getInstance()->getUserRoles(),
            editor_Task_Type::ID,
            FileTranslationType::ID
        )) {
            throw new FileTranslationException('E1213', [
                'msg' => "Initial tasktype for pretranslations does not match acl-rules.",
            ]);
        }

        $task = $this->prepareTaskImport($importFile, $sourceLang, $targetLang);
        $this->assignLanguageResources($task);
        // pretranslate the task
        /*
         * (Marc:)
         * "Bei der Analyse von Tasks kann man die Mindest-Matchrate für Vorübersetzungen einstellen.
         * Default ist 100%. Wenn man dann anhakt, dass MT-Vorübersetzung aktiv ist, wird alles weitere
         * was dann noch leer ist von der Maschine vorübersetzt. Das passt für Tasks so und
         * genauso sollten wir es auch im InstantTranslate machen: Alles mit 100% oder mehr aus dem TM
         * und alles andere durch die Maschine."
         */
        $requestData = new ApiRequestDTO(
            'PUT',
            'editor/task/' . $task->getId() . '/pretranslation/operation',
            [
                'internalFuzzy' => 0,
                'pretranslateMatchrate' => 100,
                'pretranslateTmAndTerm' => 1,
                'pretranslateMt' => 1,
                'isTaskImport' => 0,
            ]
        );
        $requestData->loggerDomain = $this->loggerDomain;
        ApiRequest::requestApi($requestData);

        // import the task
        $requestData = new ApiRequestDTO('GET', 'editor/task/' . $task->getId() . '/import');
        $requestData->loggerDomain = $this->loggerDomain;
        ApiRequest::requestApi($requestData);

        // add usage log
        $this->insertTaskUsageLog($task);

        return $task;
    }

    /**
     * Prepare import for task with the given file.
     * Creates a task-entity for further handling, but does not run the import yet.
     * @param array $importFile
     * @param int $sourceLang
     * @param int $targetLang
     */
    private function prepareTaskImport($importFile, $sourceLang, $targetLang): editor_Models_Task
    {
        // this selects the FIRST entry from the List configured in the user-management
        $customerId = ZfExtended_Authentication::getInstance()->getUser()->getPrimaryCustomerId();
        $requestData = new ApiRequestDTO('POST', 'editor/task/');
        $requestData->loggerDomain = $this->loggerDomain;
        $requestData->params = [
            'taskName' => $importFile['name'],
            'sourceLang' => $sourceLang,
            'targetLang' => $targetLang,
            'customerId' => $customerId,
            'lockLocked' => '1',
            'orderdate' => date('Y-m-d H:i:s'),
            'autoStartImport' => '0',
            //pmGuid is set implicitly since the same session is used
            'taskType' => FileTranslationType::ID,
        ];
        $requestData->files = [
            'importUpload' => $importFile,
        ];

        $result = ApiRequest::requestApi($requestData);

        if (empty($result) || empty($result->rows)) {
            throw new FileTranslationException('E1211', [
                'msg' => 'Task for importing file could not be created.',
            ]);
        }

        $task = $this->taskRepository->get((int) $result->rows->id);
        if ($task->isErroneous()) {
            // If e.g. the file-type cannot be handled for import, we don't need the rest.
            throw new FileTranslationException('E1211', [
                'msg' => 'Task for importing file could not be created; check file-format and if Okapi is running if needed.',
                'task' => $task,
            ]);
        }

        return $task;
    }

    /**
     * Assign LanguageResources to the task that match the language-combination.
     */
    private function assignLanguageResources(editor_Models_Task $task): void
    {
        $languageModel = ZfExtended_Factory::get(editor_Models_Languages::class);
        //get source and target language fuzzies
        $sourceLangs = $languageModel->getFuzzyLanguages((int) $task->getSourceLang(), 'id', true);
        $targetLangs = $languageModel->getFuzzyLanguages((int) $task->getTargetLang(), 'id', true);

        $langRes = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);
        //get the languageresources to the current user and to the given languages
        $tobeUsed = $langRes->loadByUserCustomerAssocs([], $sourceLangs, $targetLangs);

        $langResTua = ZfExtended_Factory::get(TaskAssociation::class);
        // some LanguageResources have been already auto assigned on creating the task:
        $allAssigned = $langResTua->loadByTaskGuids([$task->getTaskGuid()]);
        $allAssignedIds = array_column($allAssigned, 'languageResourceId');

        //collect assigned
        $this->assignedLanguageResources = array_merge($this->assignedLanguageResources, $allAssignedIds);

        // add LanguageResources that have not been assigned already while creating the task:
        foreach ($tobeUsed as $languageResource) {
            //collect the associated language resource id
            $this->assignedLanguageResources[] = $languageResource['id'];

            // is it not assigned already??
            if (in_array($languageResource['id'], $allAssignedIds)) {
                continue;
            }
            // then assign it now:
            $this->addLanguageResource((int) $languageResource['id'], $task->getTaskGuid());
        }
        //remove the duplicate ids
        $this->assignedLanguageResources = array_unique($this->assignedLanguageResources);
    }

    /**
     * Add a languageresource-task-assoc.
     */
    private function addLanguageResource(int $languageResourceId, string $taskGuid): void
    {
        $requestData = new ApiRequestDTO('POST', 'editor/languageresourcetaskassoc');
        $requestData->loggerDomain = $this->loggerDomain;
        $requestData->params = [
            "data" => '{"languageResourceId":"' . $languageResourceId . '",' .
                '"taskGuid":"' . $taskGuid . '","segmentsUpdateable":"false"}',
        ];
        ApiRequest::requestApi($requestData);
    }

    /**
     * Return intersection between user customers and language resources customers.
     * Those customers will be used for logging the usage
     */
    private function getCustomersForLogging(editor_Models_Task $task): array
    {
        $customerAssoc = ZfExtended_Factory::get(editor_Models_LanguageResources_CustomerAssoc::class);
        //load all customers for the assigned language resources of the task (those customers are also the current user customers)
        $resourceCustomers = $customerAssoc->loadLanguageResourcesCustomers($this->assignedLanguageResources);
        $user = $this->userRepository->getByGuid($task->getPmGuid());

        return array_intersect($user->getCustomersArray(), $resourceCustomers);
    }

    /**
     * Insert task usage log for the current pretranslation request.
     * For each customer of the associated language resource, one log entry is inserted.
     */
    private function insertTaskUsageLog(editor_Models_Task $task): void
    {
        $customers = $this->getCustomersForLogging($task);

        //if no customers are found, it make no sence to log the ussage
        if (empty($customers)) {
            return;
        }
        //value of taskCount splited between used customers for pretranslation
        $taskCount = round(1 / count($customers), 2);

        $log = ZfExtended_Factory::get(editor_Models_TaskUsageLog::class);
        #id, taskType, sourceLang, targetLang, customerId, yearAndMonth, taskCount
        $log->setTaskType($task->getTaskType()->id());
        $log->setSourceLang((int) $task->getSourceLang());
        $log->setTargetLang((int) $task->getTargetLang());
        $log->setYearAndMonth(date('Y-m'));

        //foreach associated customer, add taskCount entry in the log table
        foreach ($customers as $customer) {
            $log->setCustomerId($customer);
            $log->updateInsertTaskCount($taskCount);
        }
    }
}
