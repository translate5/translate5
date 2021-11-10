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

/**
 *
 */
class editor_AttributeController extends ZfExtended_RestController
{
    /**
     * Use termportal trait
     */
    use editor_Controllers_Traits_TermportalTrait;

    /**
     * @var string
     */
    protected $entityClass = 'editor_Models_Terminology_Models_AttributeModel';

    /**
     * @var editor_Models_Terminology_Models_AttributeModel
     */
    protected $entity;

    /**
     * Collections, allowed for current user
     *
     * @var
     */
    protected $collectionIds = false;

    /**
     * @throws Zend_Session_Exception
     */
    public function init() {

        // Call parent
        parent::init();

        // If request contains json-encoded 'data'-param, decode it and append to request params
        $this->handleData();

        // Pick session
        $this->_session = (new Zend_Session_Namespace('user'))->data;

        // If current user has 'termPM_allClients' role, it means all collections are accessible
        // Else we should apply collectionsIds-restriction everywhere, so get accessible collections
        $this->collectionIds =
            in_array('termPM_allClients', $this->_session->roles)
                ?: ZfExtended_Factory::get('ZfExtended_Models_User')->getAccessibleCollectionIds();
    }

    /**
     *
     */
    public function indexAction()
    {
        // Term attributes are currently not listable via REST API
        throw new BadMethodCallException();
    }

    /**
     *
     */
    public function getAction() {
        throw new BadMethodCallException();
    }


    /**
     * Create attribute
     * @throws ZfExtended_Mismatch
     */
    public function postAction() {

        // Shortcut
        $dataType = $this->getParam('dataType');

        // Validate params
        $_ = $this->jcheck([
            'dataType' => [
                'req' => true,
                'rex' => is_numeric($dataType) ? 'int11' : '~^[a-zA-Z_]+$~',
                'key' => 'editor_Models_Terminology_Models_AttributeDataType.' . (is_numeric($dataType) ? 'id' : 'type')
            ],
            'termEntryId' => [
                'req' => !$this->getParam('termId'),
                'rex' => 'int11',
                'key' => 'terms_term_entry'
            ],
            'language' => [
                'rex' => 'rfc5646',
                'key' => 'LEK_languages.rfc5646'
            ],
            'termId' => [
                'req' => !$this->getParam('termEntryId'),
                'rex' => 'int11',
                'key' => 'editor_Models_Terminology_Models_TermModel'
            ],
        ]);

        // Set 'level' prop inside $_ to be passed further
        $_['level'] = $this->getParam('termId') ? 'term' : ($this->getParam('language') ? 'language' : 'entry');

        // Check that the level we're going to create attr at is allowed for that attr datatype
        $this->jcheck([
            'level' => [
                'fis' => $_['dataType']->getLevel() // FIND_IN_SET
            ]
        ], $_);

        // Set 'termOrEntry' prop (to have a place where collectionId is accessible)
        $_['termOrEntry'] = $_['level'] == 'term' ? $_['termId']->toArray() : $_['termEntryId'];

        // If no or only certain collections are accessible - validate collection accessibility
        if ($this->collectionIds !== true) $this->jcheck([
            'collectionId' => [
                'fis' => $this->collectionIds ?: 'invalid' // FIND_IN_SET
            ],
        ], $_['termOrEntry']);

        // Call the appropriate method depend on mode-param
        switch ($_['dataType']->getType()) {
            case 'xGraphic':
            case 'externalCrossReference': $this->xrefcreateAction($_);   break;
            case 'crossReference':         $this->refcreateAction($_);    break;
            case 'figure':                 $this->figurecreateAction($_); break;
            default:                       $this->attrcreateAction($_);   break;
        }

        // Update
        ZfExtended_Factory
            ::get('editor_Models_TermCollection_TermCollection')
            ->updateStats($_['termOrEntry']['collectionId'], [
                'termEntry' => 0,
                'term' => 0,
                'attribute' => 1
            ]);
    }

    /**
     * Update attribute
     *
     * @throws ZfExtended_Mismatch
     */
    public function putAction() {

        // Validate params and load entity
        $this->jcheck([
            'attrId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => $this->entity
            ],
        ]);

        // If no or only certain collections are accessible - validate collection accessibility
        if ($this->collectionIds !== true) $this->jcheck([
            'collectionId' => [
                'fis' => $this->collectionIds ?: 'invalid' // FIND_IN_SET
            ],
        ], $this->entity);

        // Call the appropriate method depend on attr's `elementName` or `type` prop
        if ($this->entity->getElementName() == 'xref') {
            $this->xrefupdateAction();
        } else if ($this->entity->getElementName() == 'ref') {
            $this->refupdateAction();
        } else if ($this->entity->getType() == 'figure') {
            $this->figureupdateAction();
        } else {
            $this->attrupdateAction();
        }
    }

    /**
     * Delete attribute
     *
     * @throws ZfExtended_Mismatch
     */
    public function deleteAction() {

        // Validate params and load entity
        $this->jcheck([
            'attrId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => $this->entity
            ]
        ]);

        // If no or only certain collections are accessible - validate collection accessibility
        if ($this->collectionIds !== true) $this->jcheck([
            'collectionId' => [
                'fis' => $this->collectionIds ?: 'invalid' // FIND_IN_SET
            ],
        ], $this->entity);

        // Prevent deletion of processStatus and administrativeStatus attributes
        $this->jcheck([
            'type' => [
                'dis' => 'processStatus,administrativeStatus'
            ]
        ], $this->entity);

        // Update collection stats
        ZfExtended_Factory
            ::get('editor_Models_TermCollection_TermCollection')
            ->updateStats($this->entity->getCollectionId(), [
                'termEntry' => 0,
                'term' => 0,
                'attribute' => -1
            ]);

        // Delete attribute
        $data = $this->entity->delete([
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid,
        ]);

        // Flush response data
        $this->view->assign($data);
    }

    /**
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Mismatch
     */
    public function xrefcreateAction($_) {

        // Init attribute model with data
        $this->_attrInit($_, [
            'value' => null,
            'target' => null,
        ]);

        // Save attr and affect transacgrp-records
        $updated = $this->entity->insert($misc = [
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid,
        ]);

        // Prepare inserted data to be flushed into response json
        $inserted = [
            'id' => $this->entity->getId(),
            'value' => '',
            'target' => '',
            'isValidUrl' => false,
            'created' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getCreatedAt())),
            'updated' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getUpdatedAt())),
        ];

        // Flush response data
        $this->view->assign(['inserted' => $inserted, 'updated' => $updated]);
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Mismatch
     */
    public function refcreateAction($_) {

        // Init attribute model with data
        $this->_attrInit($_, [
            'value' => null,
            'target' => null,
        ]);

        // Save attr and affect transacgrp-records
        $updated = $this->entity->insert($misc = [
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid,
        ]);

        // Prepare inserted data to be flushed into response json
        $inserted = [
            'id' => $this->entity->getId(),
            'value' => '',
            'target' => '',
            'language' => '',
            'created' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getCreatedAt())),
            'updated' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getUpdatedAt())),
        ];

        // Flush response data
        $this->view->assign(['inserted' => $inserted, 'updated' => $updated]);
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Mismatch
     */
    public function figurecreateAction($_) {

        // Init attribute model with data
        $this->_attrInit($_, [
            'value' => 'Image',
            'target' => ZfExtended_Utils::uuid(),
        ]);

        // Save attr and affect transacgrp-records
        $updated = $this->entity->insert($misc = [
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid,
        ]);

        // Prepare inserted data to be flushed into response json
        $inserted = [
            'id' => $this->entity->getId(),
            'elementName' => $this->entity->getElementName(),
            'target' => $this->entity->getTarget(),
            'value' => $this->entity->getValue(),
            'type' => $this->entity->getType(),
            'src' => '',
            'language' => $this->entity->getLanguage(),
            'dataTypeId' => $this->entity->getDataTypeId(),
            'created' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getCreatedAt())),
            'updated' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getUpdatedAt())),
        ];

        // Flush response data
        $this->view->assign(['inserted' => $inserted, 'updated' => $updated]);
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Mismatch
     */
    public function attrcreateAction($_) {

        // Prevent creating attribute with a dataTypeId, that one of already existing attributes has
        $this->jcheck([
            'id' => [
                'dis' => $_['dataType']->getAlreadyExistingFor(
                    $_['level'] == 'term' ? $_['termId']->getTermEntryId() : $_['termEntryId']['id'],
                    $_['level'] == 'term' ? $_['termId']->getLanguage() : $this->getParam('language'),
                    $_['level'] == 'term' ? $_['termId']->getId() : null
                )
            ]
        ], $_['dataType']);

        // If attribute we're going to add is not a part of TBX basic standard
        if (!$_['dataType']->getIsTbxBasic())
            $this->jcheck([
                'collectionId' => [
                    'fis' => $_['dataType']->getAllowedCollectionIds() // FIND_IN_SET
                ]
            ], $_['termOrEntry']);

        // Init attribute model with data
        $this->_attrInit($_, [
            'value' => $_['dataType']->getDataType() == 'picklist' ? explode(',', $_['dataType']->getPicklistValues())[0] : null,
            'target' => null,
        ]);

        // Save attr and affect transacgrp-records
        $updated = $this->entity->insert($misc = [
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid
        ]);

        // Prepare inserted data to be flushed into response json
        $inserted = [
            'id' => $this->entity->getId(),
            'target' => '',
            'value' => $this->entity->getValue(),
            'type' => $this->entity->getType(),
            'language' => $this->entity->getLanguage(),
            'dataTypeId' => $this->entity->getDataTypeId(),
            'created' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getCreatedAt())),
            'updated' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getUpdatedAt())),
        ];

        // Response data
        $data = ['inserted' => $inserted, 'updated' => $updated];

        // If term status changed - append new value to json
        if ($_['level'] == 'term')
            if ($status = $this->_updateTermStatus($_['termId'], $this->entity))
                $data['status'] = $status;

        // Flush response data
        $this->view->assign($data);
    }


    /**
     * @throws ZfExtended_Mismatch
     */
    public function xrefupdateAction() {

        // Validate params
        $this->jcheck([
            'dataIndex' => [
                'req' => true,
                'fis' => 'value,target' // FIND_IN_SET
            ],
            'value' => [
                'rex' => 'varchar255'
            ]
        ]);

        // Get request params
        $params = $this->getRequest()->getParams();

        // Set attribute's dataIndex-defined prop
        $this->entity->{'set' . ucfirst($params['dataIndex'])}($params['value']);

        // Update attribute
        $updated = $this->entity->update($misc = [
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid,
        ]);

        // Setup $isValidUrl flag indicating whether `target`-prop contains a valid url
        $isValidUrl = preg_match('~ href="([^"]+)"~', editor_Utils::url2a($this->entity->getTarget()));

        // Flush response data
        $this->view->assign(['updated' => $updated, 'isValidUrl' => $isValidUrl]);
    }

    /**
     * @throws ZfExtended_Mismatch
     */
    public function refupdateAction() {

        // Validate params
        $this->jcheck([
            'target' => [
                'rex' => 'xmlid',
            ],
            'termLang,mainLang' => [
                'req' => true,
                'rex' => 'rfc5646'
            ]
        ]);

        // Get request params
        $params = $this->getRequest()->getParams();

        // Set attribute's target-prop
        $this->entity->setTarget($params['target']);

        // Update attribute
        $data['updated'] = $this->entity->update($misc = [
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid,
        ]);

        // If given target is not empty
        if ($params['target']) {

            // Detect level
            $level = $this->entity->getTermId() ? 'term' : 'entry';

            // Prepare first 2 arguments to be used for $this->_refTarget(&$refA, $refTargetIdA, $prefLangA) call
            $refA[$level][$params['attrId']] = $this->entity->toArray();
            $refTargetIdA[$params['target']] = [$level, $params['attrId']];
            $prefLangA = array_unique([$params['termLang'], $params['mainLang']]);

            // Call $this->_refTarget() with that args
            editor_Models_Terminology_Models_AttributeModel::refTarget($refA, $refTargetIdA, $prefLangA, $level);

            // Append refTarget data to the response
            $data += $refA[$level][$params['attrId']];
        }

        // Flush response data
        $this->view->assign($data);
    }

    /**
     * @throws ZfExtended_Mismatch
     */
    public function figureupdateAction() {

        // Validate params and pick figure image file
        $_ = $this->jcheck([
            'figure' => [
                'req' => true,
                'ext' => '~^(gif|png|jpe?g)$~'
            ]
        ]);

        // Create `terms_images` model instance
        /** @var $i editor_Models_Terminology_Models_ImagesModel */
        $i = ZfExtended_Factory::get('editor_Models_Terminology_Models_ImagesModel');

        // Apply data
        $i->init([
            'targetId' => $this->entity->getTarget(),
            'name' => $_['figure']['name'],
            'uniqueName' => ZfExtended_Utils::uuid() . $_['figure']['.ext'],
            'encoding' => 'hex',
            'format' => $_['figure']['type'],
            'collectionId' => $this->entity->getCollectionId()
        ]);

        // If uploaded file is successfully moved into proper location
        if ($i->moveImage($_['figure']['tmp_name'], $this->entity->getCollectionId())) {

            // Save `terms_images` record
            $i->save();

            // Update `date` and `transacNote` of 'modification'-records
            // for all levels starting from term-level and up to top
            $updated = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->affectLevels(
                    $this->_session->userName,
                    $this->_session->userGuid,
                    $this->entity->getTermEntryId(),
                    $this->entity->getLanguage()
                );

            // Flush response data
            $this->view->assign(['src' => $i->getPublicPath(), 'updated' => $updated]);

            // Else flush empty src
        } else $this->view->assign(['src' => ''] + $_);
    }

    /**
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Mismatch
     */
    public function attrupdateAction() {

        // Check request params and return an array, containing records
        // fetched from database by dataTypeId-param (and termId-param, if given)
        $_ = $this->_attrupdateCheck();

        // Default response data to be flushed in case of attribute change
        $data = ['success' => true, 'updated' => $this->_session->userName . ', ' . date('d.m.Y H:i:s')];

        // If attr was not yet changed after importing from tbx - append current value to response
        if (!$this->entity->getIsCreatedLocally()) $data['imported'] = $this->entity->getValue();

        // If it's a processStatus-attribute
        if ($this->entity->getType() == 'processStatus') {

            // Check whether current user is allowed to change processStatus from it's current value to given value
            $this->_attrupdateCheckProcessStatusChangeIsAllowed($_);

            // Do process status change, incl. detaching proposal if need, etc
            $data += $_['termId']->doProcessStatusChange(
                $this->getParam('value'),
                $this->_session->id,
                $this->_session->userName,
                $this->_session->userGuid,
                $this->entity
            );

        // Else
        } else {

            // Update attribute value
            $this->entity->setValue($this->getParam('value'));
            $this->entity->setUpdatedBy($this->_session->id);
            $this->entity->setIsCreatedLocally(1);
            $this->entity->update();

            // If it's a definition-attribute
            if ($this->entity->getType() == 'definition' && !$this->entity->getTermId()) {

                // Replicate new value of definition-attribute to `terms_term`.`definition` where needed
                // and return array containing new value and ids of affected `terms_term` records for
                // being able to apply that on client side
                $data['definition'] = $this->entity->replicateDefinition('updated');
            }
        }

        // The term status is updated in in anycase (due implicit normativeAuthorization changes above),
        // not only if a attribute is changed mapped to the term status
        if (isset($_['termId']))
            if ($status = $this->_updateTermStatus($_['termId'], $this->entity))
                $data['status'] = $status;

        // Update `date` and `transacNote` of 'modification'-records
        // for all levels starting from term-level and up to top
        $data['updated'] = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
            ->affectLevels(
                $this->_session->userName,
                $this->_session->userGuid,
                $this->entity->getTermEntryId(),
                $this->entity->getLanguage(),
                $this->entity->getTermId()
            );

        // Flush response data
        $this->view->assign($data);
    }

    /**
     * Update term's status.
     * This is intended to be called after some term-level attribute was created/updated,
     * so this method get all term's attributes that may affect term's `status` and recalculate
     * and return the value for `status`
     *
     * @param editor_Models_Terminology_Models_TermModel $termM
     * @param editor_Models_Terminology_Models_AttributeModel $attrM
     * @return array
     * @throws ZfExtended_Exception
     */
    protected function _updateTermStatus(editor_Models_Terminology_Models_TermModel $termM, editor_Models_Terminology_Models_AttributeModel $attrM): array {

        /* @var $termNoteStatus editor_Models_Terminology_TermNoteStatus */
        $termNoteStatus = ZfExtended_Factory::get('editor_Models_Terminology_TermNoteStatus');
        // Get attributes, that may affect term status
        $termNotes = $attrM->loadByTerm($termM->getId(), ['termNote'], $termNoteStatus->getAllTypes());

        $others = [];
        if($termNoteStatus->isStatusRelevant($attrM)) {
            // in this case we sync the changed attribute to the other status relevant attributes
            $status = $termNoteStatus->fromTermNotes($termNotes, $attrM->getType(), $others);
        }
        else {
            // in this case the administrativeStatus may be changed implictly, so we sync its value to the others
            $status = $termNoteStatus->fromTermNotes($termNotes, $termNoteStatus::DEFAULT_TYPE_ADMINISTRATIVE_STATUS, $others);
        }

        //update the other attributes with the new value
        foreach($others as $id => $other) {
            if($other['status'] === null) {
                $attrM->db->delete(['id = ?' => $id]);
            }
            else {
                $attrM->db->update(['value' => $other['status']], ['id = ?' => $id]);
            }
        }

        // Recalculate term status
        $termM->setStatus($status);

        // If status is modified save it to the DB
        if ($termM->isModified('status')) {
            $termM->setUpdatedBy($this->_session->id);
            $termM->update(['updateProcessStatusAttr' => false]);

            // Return new status
            return [
                'status' => $termM->getStatus(),
                'others' => array_column($others, 'status', 'dataTypeId')
            ];
        }
        return [];
    }

    /**
     * Check request params and return an array, containing records
     * fetched from database by dataTypeId-param (and termId-param, if given)
     *
     * @return array
     * @throws ZfExtended_Mismatch
     */
    protected function _attrupdateCheck() {

        // Get attribute meta load term model instance, if current attr has non-empty termId
        $_ = $this->jcheck([
            'dataTypeId' => [
                'key' => 'terms_attributes_datatype'
            ],
            'termId' => [
                'rex' => 'int11',
                'key' => 'editor_Models_Terminology_Models_TermModel'
            ]
        ], $this->entity);

        // If attribute is a picklist - make sure given value is in the list of allowed values
        if ($_['dataTypeId']['dataType'] == 'picklist')
            $this->jcheck([
                'value' => [
                    'req' => true,
                    'fis' => $_['dataTypeId']['picklistValues'] // FIND_IN_SET
                ]
            ]);

        // Return records, fetched by dataTypeId-param (and termId-param, if given)
        return $_;
    }

    /**
     * Check if current user is allowed to change the processStatus.
     * If is not allowed - exception will be thrown
     *
     * @param array $_ data, picked by previous $this->jcheck() call
     * @throws ZfExtended_Mismatch
     */
    protected function _attrupdateCheckProcessStatusChangeIsAllowed($_) {

        // Get current value of processStatus attribute, that should be involved in validation
        $current = $_['termId']->getProposal() ? 'unprocessed' : $this->entity->getValue();

        // Define which old values can be changed to which new values
        $allow = false; $allowByRole = [
            'termCustomerSearch' => false, // no change allowed
            'termReviewer' =>  ['unprocessed' => ['provisionallyProcessed' => true, 'rejected' => true]],
            'termFinalizer' => ['provisionallyProcessed' => ['finalized' => true, 'rejected' => true]],
            'termProposer' =>  [],
            'termPM' => true, // any change allowed
            'termPM_allClients' => true,
        ];

        // Setup roles
        $role = array_flip($this->_session->roles); array_walk($role, fn(&$a) => $a = true);

        // Merge allowed
        foreach ($allowByRole as $i => $info)
            if ($role[$i] ?? 0)
                $allow = is_bool($info) || is_bool($allow)
                    ? $info
                    : $info + $allow;

        // Prepare list of allowed values
        $allowed = []; foreach(explode(',', $_['dataTypeId']['picklistValues']) as $possible)
            if ($allow === true || (is_array($allow[$current] ?? 0) && ($allow[$current][$possible] ?? 0)))
                $allowed []= $possible;

        // Make sure only allowed values can be set as new value of processStatus attribute
        $this->jcheck([
            'value' => [
                'fis' => implode(',', $allowed ?: ['wontpass']) // FIND_IN_SET
            ]
        ]);
    }

    /**
     * Init attribute with common data, picked from jcheck() return value ($_-arg)
     * and custom data, given by $data arg
     *
     * @param array $_ Common data holder
     * @param array $data
     */
    protected function _attrInit(array $_, array $data = []) {

        // Init
        $this->entity->init($data + [
            'dataTypeId'  => $_['dataType']->getId(),
            'type'        => $_['dataType']->getType(),
            'elementName' => $_['dataType']->getLabel(),

            'collectionId' => $_['termOrEntry']['collectionId'],
            'termEntryId'  =>      $this->getParam('termId') ? $_['termId']->getTermEntryId()  : $_['termEntryId']['id'],
            'language'     => $l = $this->getParam('termId') ? $_['termId']->getLanguage() : $this->getParam('language'),
            'termId'       => $this->getParam('termId'),
            'termTbxId'    => $this->getParam('termId') ? $_['termId']->getTermTbxId() : null,
            'isCreatedLocally' => 1,
            'createdBy' => $this->_session->id,
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedBy' => $this->_session->id,
            'updatedAt' => date('Y-m-d H:i:s'),
            'termEntryGuid' => $this->getParam('termId') ? $_['termId']->getTermEntryGuid() : $_['termEntryId']['entryGuid'],
            //'langSetGuid' => null,
            'termGuid' => $this->getParam('termId') ? $_['termId']->getGuid() : null,
            'guid' => ZfExtended_Utils::uuid(),
            'attrLang' => $l,
            //'dataType' => null
        ]);
    }
}
