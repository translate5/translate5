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

use MittagQI\Translate5\Workflow\NextStepCalculator;

/**
 * Encapsulates the Default Notifications triggered by the Workflow
 * Basicly the Notifications are E-Mail based. But this class can be overwritten
 * to redirect the generated mailer texts to over notification channels
 * Warning: the here listed public methods are called as configured in LEK_workflow_action table!
 */
class editor_Workflow_Notification extends editor_Workflow_Actions_Abstract
{
    /**
     * @var ZfExtended_TemplateBasedMail
     */
    protected $mailer;

    /**
     * @var array
     */
    protected $xmlCache = [];

    /**
     * reusable $tua instance, instanced if needed, must be set explictly by the called notify method
     * @var editor_Models_TaskUserAssoc
     */
    protected $tua;

    public function __construct(
        private readonly NextStepCalculator $nextStepCalculator,
    ) {
    }

    public static function create(): self
    {
        return new self(
            NextStepCalculator::create(),
        );
    }

    /**
     * generates and returns the template path.
     * @param string $role the affected workflow role string
     * @param string $template the template name
     */
    protected function getMailTemplate(string $role, string $template, string $customPart = ''): string
    {
        return 'workflow/' . $role . '/' . (! empty($customPart) ? ($customPart . '/') : '') . $template . '.phtml';
    }

    /**
     * returns a list with PM Users (currently only one)
     * @return [array] array with Pm User Data Arrays
     */
    protected function getTaskPmUsers()
    {
        if (! isset($this->config->task)) {
            return [];
        }
        $task = $this->config->task;
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($task->getPmGuid());

        return [(array) $user->getDataObject()];
    }

    /**
     * perhaps this method should be moved to another location (into the workflow?)
     */
    protected function getStepSegments(string $step)
    {
        $task = $this->config->task;
        //attention, in context of increasing the stepNr, the current task from config always contains the old stepNr!
        // The new one must be loaded from DB!
        $stepNr = (int) $task->getWorkflowStep();
        $segment = ZfExtended_Factory::get('editor_Models_Segment');

        /* @var $segment editor_Models_Segment */
        return $segment->getWorkflowStepSegments($task, $step, $stepNr);
    }

    /**
     * creates the Notification and stores it internally
     * @throws Zend_Exception|ReflectionException
     */
    protected function createNotification(string $role, string $template, array $parameters): void
    {
        $this->mailer = ZfExtended_Factory::get('ZfExtended_TemplateBasedMail');
        $this->mailer->setParameters($parameters);

        $defaultTemplate = $this->getMailTemplate($role, $template);
        $customerBasedTemplate = $this->getMailTemplate($role, $template, $this->config->task?->getCustomerId());
        if ($this->mailer->hasCustomTemplate($customerBasedTemplate)) {
            $defaultTemplate = $customerBasedTemplate;
        }
        $this->mailer->setTemplate($defaultTemplate);

        $pm = $this->getTaskPmUsers();
        $pm = array_shift($pm);
        // Add reply-to with project-manager mail to all automated workflow-mails
        if (! empty($pm)) {
            $this->mailer->setReplyTo($pm['email'], $pm['firstName'] . ' ' . $pm['surName']);
        }
    }

    /**
     * send the latest created notification to the list of users
     */
    protected function notify(array $userData)
    {
        // if disabled by config, do not send email
        // TODO: this is a quick solution for the problem. To solve this really, this should be configurable for a workflow
        if ($this->isDisabled()) {
            return;
        }
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->init($userData);
        $this->mailer->sendToUser($user);
    }

    /**
     * send the latest created notification to single user
     */
    protected function notifyUser(ZfExtended_Models_User $user)
    {
        // if disabled by config, do not send email
        // TODO: this is a quick solution for the problem. To solve this really, this should be configurable for a workflow
        if ($this->isDisabled()) {
            return;
        }
        $this->mailer->sendToUser($user);
    }

    protected function addCopyToPM(stdClass $triggerConfig, string $pmGuid): void
    {
        if (empty($triggerConfig->copyToPM)) {
            return;
        }
        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $user->loadByGuid($pmGuid);
        if ($triggerConfig->copyToPM === 'bcc') {
            $this->mailer->addBcc($user->getEmail());
        } elseif ($triggerConfig->copyToPM === 'cc') {
            $this->mailer->addCc($user->getEmail(), $user->getUserName());
        }
    }

    /**
     * Adds the users of the given cc/bcc step config to the email - if receiverStep is configured in config
     * @param stdClass $triggerConfig the config object given in action matrix
     * @param string $receiverStep the original receiver step of the notification to be sent
     */
    protected function addCopyReceivers(stdClass $triggerConfig, $receiverStep)
    {
        $task = $this->config->task;
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */

        $tua = empty($this->tua) ? ZfExtended_Factory::get('editor_Models_TaskUserAssoc') : $this->tua;

        $addReceivers = function ($receiverStepMap, $bcc = false) use ($receiverStep, $task, $user, $tua) {
            $users = [];
            foreach ($receiverStepMap as $recStep => $steps) {
                if ($recStep == '*' || $recStep == $receiverStep) {
                    foreach ($steps as $step) {
                        $users = array_merge($users, $tua->loadUsersOfTaskWithStep($task->getTaskGuid(), $step, ['deadlineDate']));
                    }
                }
                if ($recStep == 'byUserLogin') {
                    $userModel = ZfExtended_Factory::get('ZfExtended_Models_User');
                    /* @var $userModel ZfExtended_Models_User */
                    foreach ($steps as $singleUser) {
                        try {
                            $userModel->loadByLogin($singleUser);
                            $users[] = (array) $userModel->getDataObject();
                        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
                            // do nothing if the user was not found
                        }
                    }
                }
            }
            foreach ($users as $userData) {
                $user->init($userData);
                if ($bcc) {
                    $this->mailer->addBcc($user->getEmail());
                } else {
                    $this->mailer->addCc($user->getEmail(), $user->getUserName());
                }
            }
        };

        $addReceivers($triggerConfig->cc);
        $addReceivers($triggerConfig->bcc, true);
    }

    /**
     * Initiates the internal trigger configuration through the given parameters and returns it
     * currently the following configuration parameters exist:
     * pmBcc boolean, true if the pm of the task should also receive the notification
     * rolesBcc array, list of workflow roles which also should receive the notification
     * @return stdClass
     */
    protected function initTriggerConfig(array $config)
    {
        $defaultConfig = new stdClass();
        $defaultConfig->cc = [];
        $defaultConfig->bcc = [];
        if (empty($config)) {
            return $defaultConfig;
        }
        $config = reset($config);
        if (empty($config)) {
            return $defaultConfig;
        }
        foreach ($config as $key => $v) {
            $defaultConfig->{$key} = $v;
        }

        return $defaultConfig;
    }

    /**
     * Workflow specific Notification after all users of a role have finished a task
     */
    public function notifyAllFinishOfARole()
    {
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        $task = $this->config->task;
        $workflow = $this->config->workflow;
        $isCron = $this->config->isCalledByCron;
        $currentStep = $this->config->newTua->getWorkflowStepName();
        $this->tua = clone $this->config->newTua; //we just reuse the already used entity
        if ($currentStep === false) {
            $this->log->warn('E1013', 'No workflow step to Role {role} found! This is actually a workflow config error!', [
                'task' => $task,
                'step' => $currentStep,
            ]);
        }
        $segments = $this->getStepSegments($currentStep);

        $segmentHash = md5(print_r($segments, 1)); //hash to identify the given segments (for internal caching)

        $nextStep = (string) $this->nextStepCalculator->getNextStep($workflow, $task->getTaskGuid(), $currentStep);
        $nextRole = $workflow->getRoleOfStep($nextStep);

        $tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */
        $users = empty($nextStep) ? [] : $tua->loadUsersOfTaskWithStep($task->getTaskGuid(), $nextStep, ['deadlineDate']);
        $previousUsers = $tua->loadUsersOfTaskWithStep($task->getTaskGuid(), $currentStep, ['deadlineDate']);
        $params = [
            'triggeringRole' => $this->config->newTua->getRole(),
            'triggeringStep' => $currentStep,
            'nextStep' => $nextStep,
            'nextRole' => $nextRole,
            'segmentsHash' => $segmentHash,
            'segments' => $segments,
            'isCron' => $isCron,
            'users' => $users,
            'previousUsers' => $previousUsers,
            'task' => $task,
            'workflow' => $workflow,
        ];

        // Avoid automated e-mail to PM within some steps
        if (empty($triggerConfig->skipPm?->step) || ! preg_match('/' . $triggerConfig->skipPm->step . '/', $currentStep)) {
            //send to the PM
            $pms = $this->getTaskPmUsers();
            foreach ($pms as $pm) {
                //@todo PM currently not defined as WORKFLOW_ROLE, so hardcoded here
                $this->createNotification(ACL_ROLE_PM, __FUNCTION__, $params);
                $this->attachXliffSegmentList($segmentHash, $segments, $currentStep);
                $this->addCopyReceivers($triggerConfig, editor_Workflow_Default::STEP_PM_CHECK);
                $this->notify($pm);
            }
        }

        if (empty($nextStep)) {
            return;
        }
        // Avoid automated e-mail to users within some steps
        if (! empty($triggerConfig->skipUsers?->step) && preg_match('/' . $triggerConfig->skipUsers->step . '/', $currentStep)) {
            return;
        }
        // Avoid automated e-mail to users before some steps
        if (! empty($triggerConfig->skipUsers?->nextStep) && preg_match('/' . $triggerConfig->skipUsers->nextStep . '/', $nextStep)) {
            return;
        }

        //send to each user of the targetRole
        foreach ($users as $user) {
            $params['user'] = $user;
            $this->createNotification($nextRole, __FUNCTION__, $params);
            $this->attachXliffSegmentList($segmentHash, $segments, $currentStep);
            $this->addCopyReceivers($triggerConfig, $nextStep);
            $this->notify($user);
        }
    }

    /**
     * Workflow specific PM Notification after one users of a role have finished a task
     */
    public function notifyOneFinishOfARole()
    {
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        $task = $this->config->task;
        $workflow = $this->config->workflow;
        $isCron = $this->config->isCalledByCron;
        if ($isCron) {
            //currently we do not trigger the notifyOne on cron actions (since currently there are all users set to finish)
            return;
        }
        $currentStep = $this->config->newTua->getWorkflowStepName();
        $this->tua = clone $this->config->newTua; //we just reuse the already used entity
        if ($currentStep === false) {
            $this->log->warn('E1013', 'No workflow step to Role {role} found! This is actually a workflow config error!', [
                'task' => $task,
                'step' => $currentStep,
            ]);
        }

        $currentUsers = $this->tua->loadUsersOfTaskWithStep($task->getTaskGuid(), $currentStep, ['state', 'deadlineDate']);
        $params = [
            'triggeringRole' => $this->config->newTua->getRole(),
            'triggeringStep' => $currentStep,
            'currentUsers' => $currentUsers,
            'task' => $task,
            'workflow' => $workflow,
        ];

        //set the triggering user
        $params['currentUser'] = [];
        foreach ($currentUsers as $user) {
            if ($user['userGuid'] == $this->tua->getUserGuid()) {
                $params['currentUser'] = $user;
            }
        }

        //send to the PM
        $pms = $this->getTaskPmUsers();
        foreach ($pms as $pm) {
            $this->createNotification(ACL_ROLE_PM, __FUNCTION__, $params); //@todo PM currently not defined as WORKFLOW_ROLE, so hardcoded here
            $this->addCopyReceivers($triggerConfig, editor_Workflow_Default::STEP_PM_CHECK);
            $this->notify($pm);
        }
    }

    /**
     * Sends a notification to users which are removed automatically from the task
     * The Users to be notified must be given in the parameter array key 'deleted'
     */
    public function notifyCompetitiveDeleted(array $parameter)
    {
        $triggerConfig = $this->initTriggerConfig([$parameter]);
        $this->tua = $this->config->newTua;
        settype($triggerConfig->deleted, 'array');

        if ($this->config->task->anonymizeUsers(false)) {
            $params = [];
        } else {
            $params = [
                //we do not pass the whole userObject to keep data private
                'responsibleUser' => [
                    'surName' => $triggerConfig->currentUser->surName,
                    'firstName' => $triggerConfig->currentUser->firstName,
                    'login' => $triggerConfig->currentUser->login,
                    'email' => $triggerConfig->currentUser->email,
                ],
            ];
        }

        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        foreach ($triggerConfig->deleted as $deleted) {
            $user->loadByGuid($deleted['userGuid']);
            $workflow = $this->config->workflow;
            $labels = $workflow->getLabels(false);
            $steps = $workflow->getSteps();
            $params['task'] = $this->config->task;
            $params['role'] = $labels[array_search($deleted['workflowStepName'], $steps)];

            $this->createNotification($deleted['role'], __FUNCTION__, $params);
            $this->addCopyReceivers($triggerConfig, $deleted['workflowStepName']);
            $this->notifyUser($user);
        }
    }

    /**
     * Sends a notification to users which are attached newly to a task with status open
     * The User to be notified is gathered from the current active TaskUserAssociation
     * @throws Zend_Exception
     */
    public function notifyNewTaskAssigned()
    {
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        $this->tua = $tua = $this->config->newTua;

        //the usage of this config is more a workaround,
        // since this was the easiest but also straight forward way to transport the information "yes notify"
        // from one task import wizard page to the final startImport action.
        // Not using the system config would mean to implement an own way to transport such config information.
        if (! ($this->config->task->getConfig()->runtimeOptions->workflow->notifyAllUsersAboutTask ?? false)) {
            return;
        }

        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $pm = clone $user;
        $pm->loadByGuid($this->config->task->getPmGuid());
        $user->loadByGuid($tua->getUserGuid());
        $workflow = $this->config->workflow;
        $labels = $workflow->getLabels(false);
        $roles = $workflow->getRoles();
        $params = [
            'pm' => $pm,
            'task' => $this->config->task,
            'role' => $labels[array_search($tua->getRole(), $roles)],
            'taskUserAssoc' => (array) $this->tua->getDataObject(),
        ];

        $this->createNotification($tua->getRole(), __FUNCTION__, $params);
        $this->addCopyReceivers($triggerConfig, $tua->getWorkflowStepName());
        $this->notifyUser($user);
    }

    /**
     * Notifies all associated users about the task association
     * Main differences to notifyNewTaskAssigned to a single user:
     *  This notification contains a list of all assigned users.
     */
    public function notifyAllAssociatedUsers()
    {
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        $task = $this->config->task;

        // For handle direct calls we do not evaluate the config.
        // Send the notification directly
        if ($this->config->isHandleDirect() === false) {
            //the usage of this config is more a workaround,
            // since this was the easiest but also straight forward way to transport the information "yes notify"
            // from one task import wizard page to the final startImport action.
            // Not using the system config would mean to implement an own way to transport such config information.
            if (! ($task->getConfig()->runtimeOptions->workflow->notifyAllUsersAboutTask ?? false)) {
                return;
            }
        }

        $this->tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');

        $tuas = $this->tua->loadUsersOfTaskWithStep($task->getTaskGuid(), $triggerConfig->step ?? null, ['state', 'workflowStepName', 'deadlineDate', 'assignmentDate', 'finishedDate']);

        $steps = array_column($tuas, 'workflowStepName');
        //sort first $roles, then sort $tuas to the same order (via the indexes)
        array_multisort($steps, SORT_ASC, SORT_STRING, $tuas);
        $aStepOccursMultipleTimes = count($steps) !== count(array_flip($steps));

        foreach ($tuas as &$tua) {
            $tua['role'] = $tua['workflowStepName'];
            $tua['originalWorkflowStepName'] = $tua['workflowStepName'];
        }
        unset($tua);

        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $pm = clone $user;
        $pm->loadByGuid($task->getPmGuid());

        $params = [
            'pm' => $pm,
            'task' => $this->config->task,
            'associatedUsers' => $tuas,
            //the disclaimer is needed only, if from one role multiple users are assigned. If it is only one reviewer then no dislaimer is needed
            'addCompetitiveDisclaimer' => $aStepOccursMultipleTimes && $task->getUsageMode() == $task::USAGE_MODE_COMPETITIVE,
        ];

        foreach ($tuas as $tua) {
            $params['role'] = $tua['workflowStepName'];
            $params['taskUserAssoc'] = $tua;
            //we assume the PM user for all roles, since it is always the same template
            $this->createNotification(ACL_ROLE_PM, 'notifyNewTaskAssigned', $params);
            $user->loadByGuid($tua['userGuid']);
            $this->addCopyReceivers($triggerConfig, $tua['originalWorkflowStepName']);
            $this->addCopyToPM($triggerConfig, $task->getPmGuid());
            $this->notifyUser($user);
        }
    }

    /**
     * Notify project PM about that project was just created
     */
    public function notifyNewProjectForPm()
    {
        /** @var ZfExtended_Models_User $user */
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        $user->loadByGuid($this->config->task->getPmGuid());

        // Create notification
        $this->createNotification(ACL_ROLE_PM, __FUNCTION__, [
            'project' => $project = $this->config->task,
            'user' => (array) $user->getDataObject(),
            'createdByUser' => $this->config->createdByUser,
        ]);

        // Get trigger config
        $triggerConfig = $this->initTriggerConfig(func_get_args());

        // If notification should be sent only for specific project types, but current one is not like that
        if (is_array($triggerConfig->projectTypes ?? null)
            && ! in_array($project->getTaskType()->id(), $triggerConfig->projectTypes)) {
            // Prevent notification from being sent
            return;
        }

        // Do notify
        $this->notifyUser($user);
    }

    /**
     * Notifies the tasks PM over the new task, but only if PM != the user who has uploaded the task
     */
    public function notifyNewTaskForPm()
    {
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        $task = $this->config->task;
        $pmGuid = $task->getPmGuid();
        $importConf = $this->config->importConfig;

        //if the user who imports the task is the same as the PM, we don't send the mail
        // also this mail is not possible at all, if no import config is given
        if (empty($importConf) || $importConf->userGuid == $pmGuid) {
            return;
        }

        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByGuid($pmGuid);

        $params = [
            'task' => $task,
            'user' => (array) $user->getDataObject(),
            'sourceLanguage' => $importConf->sourceLang->getLangName(),
            'targetLanguage' => $importConf->targetLang->getLangName(),
            'relaisLanguage' => (empty($importConf->relaisLang) ? '' : $importConf->relaisLang->getLangName()),
        ];

        $this->createNotification(ACL_ROLE_PM, __FUNCTION__, $params);
        $this->addCopyReceivers($triggerConfig, editor_Workflow_Default::STEP_PM_CHECK);
        $this->notifyUser($user);
    }

    public function notifyAutoclosed(array $triggerConfigArgs, string $userGuid, string $workflowStepName): void
    {
        $triggerConfig = $this->initTriggerConfig($triggerConfigArgs);
        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $user->loadByGuid($this->config->task->getPmGuid());

        $this->createNotification(ACL_ROLE_PM, __FUNCTION__, [
            'task' => $this->config->task,
            'user' => (array) $user->getDataObject(),
        ]);

        // Notify PM
        $this->addCopyReceivers($triggerConfig, editor_Workflow_Default::STEP_PM_CHECK);
        $this->notifyUser($user);

        $user->loadByGuid($userGuid);

        $this->createNotification('', __FUNCTION__, [
            'task' => $this->config->task,
            'user' => (array) $user->getDataObject(),
        ]);

        // Notify the assigned user
        $this->addCopyReceivers($triggerConfig, $workflowStepName);
        $this->notifyUser($user);
    }

    /***
     * Notify the task assock when the delivery date is over the defined days in the config
     */
    public function notifyOverdueDeadline()
    {
        $this->deadlineNotifier($this->initTriggerConfig(func_get_args()), __FUNCTION__, false);
    }

    /***
     * Notify the associated users when the deadlineDate is approaching.
     * daysOffset config: how many days before the deadline an email is sended
     */
    public function notifyDeadlineApproaching(): void
    {
        $this->deadlineNotifier($this->initTriggerConfig(func_get_args()), __FUNCTION__, true);
    }

    /**
     * Sends by default only the summary to the tasks PM if another user has created the task (for example via API)
     * The summary can be send always if set "always": true in the config object
     */
    public function notifyImportErrorSummary()
    {
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        $always = $triggerConfig->always ?? false;
        $task = $this->config->task;
        $importer = $this->config->importConfig->userGuid;

        //if always is disabled and the PM is the importer, then we do nothing
        if (! $always && $task->getPmGuid() === $importer) {
            return;
        }

        $taskLog = ZfExtended_Factory::get(editor_Models_Logger_Task::class);
        $logEntries = $taskLog->loadByLevel(
            $task->getTaskGuid(),
            [
                ZfExtended_Logger::LEVEL_FATAL,
                ZfExtended_Logger::LEVEL_ERROR,
            ]
        );

        //if there is no or only the one default info log, we send no mail
        if (empty($logEntries) || count($logEntries) == 1 && ((object) reset($logEntries))->level == ZfExtended_Logger::LEVEL_INFO) {
            return;
        }

        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $user->loadByGuid($task->getPmGuid());

        $this->createNotification('pm', __FUNCTION__, [
            'task' => $task,
            'logs' => $logEntries,
        ]);
        $this->notifyUser($user);
    }

    /**
     * Notify the configured user with term and term attribute proposals of the configured or all termcollections
     * The attached export data in the mail will be in excel format.
     */
    public function notifyTermProposals()
    {
        $triggerConfig = $this->initTriggerConfig(func_get_args());
        if (! isset($triggerConfig->receiverUser) || empty($triggerConfig->receiverUser)) {
            return;
        }

        //use all collections if no collections are given in workflow action config
        if (empty($triggerConfig->collections)) {
            $service = ZfExtended_Factory::get('editor_Services_TermCollection_Service');
            /* @var $service editor_Services_TermCollection_Service */
            $lr = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
            /* @var $lr editor_Models_LanguageResources_LanguageResource */

            //load all existing term collections
            $collections = $lr->loadByResourceId($service->getServiceNamespace());
            $collections = array_column($collections, 'id');
        } else {
            $collections = $triggerConfig->collections;
            if (! is_array($collections)) {
                $collections = [$collections];
            }
        }

        if (empty($collections)) {
            return;
        }

        $proposals = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        /* @var $proposals editor_Models_Terminology_Models_TermModel */

        //load the term and term entry proposals data for all term collections and younger as $exportDate
        $rows = $proposals->loadProposalExportData($collections);
        if (empty($rows)) {
            return;
        }

        $file = tempnam(APPLICATION_PATH . '/../data/tmp/', '') . 'xlsx';

        //create tmp file in the tmp directory of translate5
        $proposals->exportProposals($rows, $file);

        //create the notification with the xlsx file
        $attachment = [
            'body' => file_get_contents($file),
            'mimeType' => Zend_Mime::TYPE_OCTETSTREAM,
            'disposition' => Zend_Mime::DISPOSITION_ATTACHMENT,
            'encoding' => Zend_Mime::ENCODING_BASE64,
            'filename' => 'Proposals.xlsx',
        ];
        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */
        $user->loadByLogin($triggerConfig->receiverUser);

        $this->createNotification('visitor', __FUNCTION__, []);
        $this->mailer->setAttachment([$attachment]);
        $this->notifyUser($user);

        //remove the tmp file from the disc
        unlink($file);
    }

    /**
     * attaches the segmentList as attachment to the internal mailer object
     * @param string $segmentHash
     * @param string $currentStep
     */
    protected function attachXliffSegmentList($segmentHash, array $segments, $currentStep)
    {
        $config = $this->config->task->getConfig();
        $notifyConfig = $config->runtimeOptions->editor->notification;

        //load the customer specific config
        $xlfAttachment = (bool) $notifyConfig->enableSegmentXlfAttachment;
        $xlfFile = (bool) $notifyConfig->saveXmlToFile;

        if (empty($segments) || (! $xlfAttachment && ! $xlfFile)) {
            return;
        }
        if (empty($this->xmlCache[$segmentHash])) {
            $xliffConverter = editor_Models_Converter_SegmentsToXliffFactory::create($currentStep, $config);

            try {
                $this->xmlCache[$segmentHash] = $xliff = $xliffConverter->convert($this->config->task, $segments);
            } catch (Exception $e) {
                $msg = 'changes.xliff could not be created';
                $this->log->warn('E1013', $msg, [
                    'task' => $this->config->task,
                ]);
                $this->log->exception($e, [
                    'level' => $this->log::LEVEL_WARN,
                ]);
                //if file saving is enabled we save the file with the debug content
                $this->xmlCache[$segmentHash] = $xliff = $msg;
                //but we disable attaching it to the mail:
                $xlfAttachment = false;
            }

            if ($xlfFile) {
                $this->saveXmlToFile($xliff);
            }
        }

        if (! $xlfAttachment) {
            return;
        }
        $attachment = [
            'body' => $this->xmlCache[$segmentHash],
            'mimeType' => Zend_Mime::TYPE_OCTETSTREAM,
            'disposition' => Zend_Mime::DISPOSITION_ATTACHMENT,
            'encoding' => Zend_Mime::ENCODING_BASE64,
            'filename' => 'changes.xliff',
        ];
        $this->mailer->setAttachment([$attachment]);
    }

    protected function saveXmlToFile($xml)
    {
        $path = $this->config->task->getAbsoluteTaskDataPath();
        if (! is_dir($path) || ! is_writeable($path)) {
            $this->log->warn('E1013', 'cant write changes.xliff file to path: {path}', [
                'task' => $this->config->task,
                'path' => $path,
            ]);

            return;
        }
        $suffix = '.xliff';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            //windows can not deal with the : in the filename
            $filename = 'changes-' . date('Y-m-d\TH-i-s');
        } else {
            //for linux we leave it for compatibility reasons
            $filename = 'changes-' . date('Y-m-d\TH:i:s');
        }
        $i = 0;
        $outFile = $path . DIRECTORY_SEPARATOR . $filename . $suffix;
        while (file_exists($outFile)) {
            $outFile = $path . DIRECTORY_SEPARATOR . $filename . '-' . ($i++) . $suffix;
        }
        if (file_put_contents($outFile, $xml) == 0) {
            $this->log->warn('E1013', 'Error on writing XML File: {path}', [
                'task' => $this->config->task,
                'path' => $outFile,
            ]);
        }
    }

    /**
     * Deadline notifier. It will send notification to the configured user assocs days before or after the current day (days +/- can be defined in config default to 1)
     * When the trignotification trigger is periodical, the deadline date select will be between "CRON_PERIODICAL_CALL_FREQUENCY_MIN" minutes period of time
     * @param string $template  template to be used for the mail
     * @param bool $isApproaching default will notify daysOffset before deadline
     */
    protected function deadlineNotifier(stdClass $triggerConfig, string $template, bool $isApproaching = false)
    {
        if (isset($triggerConfig->template)) {
            $template = $triggerConfig->template;
        }

        $deadlineHelper = ZfExtended_Factory::get('editor_Workflow_Actions_DeadlineHelper');
        /* @var $deadlineHelper editor_Workflow_Actions_DeadlineHelper */

        $deadlineHelper->init($this->config);

        //get all user assocs not notifiead
        $tuas = $deadlineHelper->getDeadlineUnnotifiedAssocs($triggerConfig, $isApproaching);

        if (empty($tuas)) {
            return;
        }

        $this->tua = ZfExtended_Factory::get('editor_Models_TaskUserAssoc');
        /* @var $tua editor_Models_TaskUserAssoc */

        $user = ZfExtended_Factory::get('ZfExtended_Models_User');
        /* @var $user ZfExtended_Models_User */

        if (isset($triggerConfig->receiverUser)) {
            $user->loadByLogin($triggerConfig->receiverUser);
        }

        foreach ($tuas as $tua) {
            $this->config->task = ZfExtended_Factory::get('editor_Models_Task');
            $this->config->task->loadByTaskGuid($tua['taskGuid']);

            $assoc = $tua;
            //if the receiverUser user is configured, send mail only to receiverUser
            if (isset($triggerConfig->receiverUser)) {
                $assoc = (array) $user->getDataObject();
            }

            $params = [
                'task' => $this->config->task,
                'taskUserAssoc' => $tua,
                'daysOffset' => $triggerConfig->daysOffset ?? 1,
            ];

            $this->createNotification($tua['role'], $template, $params);
            $this->addCopyReceivers($triggerConfig, $tua['workflowStepName']);
            $this->addCopyToPM($triggerConfig, $this->config->task->getPmGuid());
            $this->notify($assoc);
            $deadlineHelper->logDeadlineNotified($assoc, $isApproaching);
        }
    }

    /***
     * Check if the workflow notifications are disabled by config. If the current request is in task context, task config will be used.
     * Otherwise, the system config value will be used.
     * @return bool
     * @throws editor_Models_ConfigException|Zend_Exception
     */
    protected function isDisabled(): bool
    {
        if (isset($this->config->task)) {
            $config = $this->config->task->getConfig();
        } else {
            $config = Zend_Registry::get('config');
        }

        return (bool) $config->runtimeOptions->workflow->disableNotifications;
    }
}
