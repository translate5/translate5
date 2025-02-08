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

/**
 * Helper to fill up a data object containing a task with user infos
 */
class Editor_Controller_Helper_TaskUserInfo extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * @var editor_Models_SegmentFieldManager
     */
    protected $segmentFieldManager;

    /**
     * @var editor_Workflow_Default
     */
    protected $workflow;

    /**
     * The entity instance in the controller
     * @var editor_Models_Task
     */
    protected $task;

    /**
     * @var editor_Workflow_Anonymize
     */
    protected $workflowAnonymize;

    /**
     * true if currently a task is opened
     */
    private bool $isInTaskContext;

    public function init()
    {
        $this->workflowAnonymize = ZfExtended_Factory::get('editor_Workflow_Anonymize');
        $this->segmentFieldManager = new editor_Models_SegmentFieldManager();
    }

    public function initForTask(editor_Workflow_Default $workflow, editor_Models_Task $task, bool $inTaskContext)
    {
        $this->task = $task;
        $this->workflow = $workflow;
        $this->isInTaskContext = $inTaskContext;
    }

    /**
     * Adds additional user based infos to the given array.
     * If the given taskguid is assigned to a client for anonymizing data, the added user-data is anonymized already.
     * @param array $row gets the row to modify as reference
     */
    public function addUserInfos(array &$row, $isEditAll)
    {
        $taskguid = $row['taskGuid'];

        $fields = ZfExtended_Factory::get(editor_Models_SegmentField::class);
        $userPref = ZfExtended_Factory::get(editor_Models_Workflow_Userpref::class);

        // we load alls fields, if we are in taskOverview and are allowed to edit all
        // or we have no userStep to filter / search by.
        // No userStep means indirectly that we do not have a TUA (pmCheck)
        // task in state import means in some point there will be no user pref record in the database
        if (
            (! $this->isInTaskContext && $isEditAll) ||
            empty($row['userStep']) ||
            $row['state'] === editor_Models_Task::STATE_IMPORT
        ) {
            try {
                $row['segmentFields'] = $fields->loadByTaskGuid($taskguid);
            } catch (ZfExtended_Models_Entity_NotFoundException $exception) {
                $row['segmentFields'] = [];
            }

            //the pm sees all, so fix userprefs
            $userPref->setNotEditContent(false);
            $userPref->setAnonymousCols(false);
            $userPref->setVisibility($userPref::VIS_SHOW);
            $allFields = array_map(function ($item) {
                return $item['name'];
            }, $row['segmentFields']);
            $userPref->setFields(join(',', $allFields));
        } else {
            try {
                $userPref->loadByTaskUserAndStep(
                    $taskguid,
                    $this->workflow->getName(),
                    ZfExtended_Authentication::getInstance()->getUserGuid(),
                    $row['userStep']
                );

                $row['segmentFields'] = $fields->loadByUserPref($userPref);
            } catch (ZfExtended_Models_Entity_NotFoundException) {
                $row['segmentFields'] = [];
            }
        }

        $row['userPrefs'] = $userPref->hasRow() ? [$userPref->getDataObject()] : [];

        $row['notEditContent'] = empty($row['userPrefs']) || $row['userPrefs'][0]->notEditContent;

        $config = Zend_Registry::get('config');
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();

        foreach ($row['segmentFields'] as &$field) {
            //TRANSLATE-318: replacing of a subpart of the column name is a client specific feature
            $needle = $config->runtimeOptions->segments->fieldMetaIdentifier;
            if (! empty($needle)) {
                $field['label'] = str_replace($needle, '', $field['label']);
            }
            $field['label'] = $translate->_($field['label']);
        }

        //sets the information if this task has default segment field layout or not
        $row['defaultSegmentLayout'] = $this->segmentFieldManager->isDefaultLayout(
            array_map(
                static fn ($field) => $field['name'],
                $row['segmentFields']
            )
        );
    }
}
