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
     * Flag indicating whether we're in batch-mode
     *
     * @var bool
     */
    protected $batch = false;

    /**
     * Responses collected during batch-editing mode
     *
     * @var
     */
    public $responseA = [];

    /**
     * Info about new values of processStatus and administrativeStatus for terms, affected by attribs batch editing
     *
     * @var array
     */
    public $affectedA = [];

    /**
     * Return values of $this->jcheck() calls for each termEntryId-language-termId params combination
     *
     * @var array
     */
    public $jchecked = [];

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
     * Get 'termId'-param from request
     * If 'except'-param is given, id-values of all records found matching last used search params
     * would be fetched and returned, except the ones given by termId-param
     *
     * @return array
     */
    private function _termIds() {

        // Get termId-param from request
        $termIdA = array_unique(editor_Utils::ar($this->getParam('termId')));

        // If except-param is given as true
        if ($this->getParam('except')) {

            // If $_SESSION['lastParams'] is not set - flush error msg
            if (!isset($_SESSION['lastParams'])) {
                $this->jflush(false, 'Your should run search at least once');
            }

            // 2nd arg required to be passed by reference (see below)
            $total = false;

            // Fetch ids of ALL terms matching last search, excluding ids given by 'except'-param
            $termIdA = ZfExtended_Factory
                ::get('editor_Models_Terminology_Models_TermModel')
                ->searchTermByParams(
                    $_SESSION['lastParams'] + ['except' => join(',', $termIdA)],
                    $total
                );

            // If nothing found - flush error msg
            if (!$termIdA) {
                $this->jflush(false, 'Nothing to edit');
            }
        }

        // Return ids
        return array_unique($termIdA);
    }

    /**
     * Create attribute
     * @throws ZfExtended_Mismatch
     */
    public function postAction() {

        // Shortcut
        $dataType = $this->getParam('dataType');

        // Validate termId-param and also validate dataType-param and load it's model instance
        $_ = $this->jcheck([
            'termId' => [
                'req' => true,
                'rex' => $this->getParam('batch') ? 'int11list' : 'int11'
            ],
            'level' => [
                'req' => true,
                'fis' => 'entry,language,term'
            ],
            'dataType' => [
                'req' => true,
                'rex' => is_numeric($dataType) ? 'int11' : '~^[a-zA-Z_]+$~',
                'key' => 'editor_Models_Terminology_Models_AttributeDataType.' . (is_numeric($dataType) ? 'id' : 'type')
            ],
            'batch,except' => [
                'rex' => 'bool'
            ]
        ]);

        // Set 'level' prop inside $_ to be passed further
        $_['level'] = $this->getParam('level');

        // Check that the level we're going to create attr at is allowed for that attr datatype
        $this->jcheck([
            'level' => [
                'fis' => $_['dataType']->getLevel() // FIND_IN_SET
            ]
        ], $_);

        // Detect whether we're in batch mode
        $this->batch = $this->getParam('batch');

        // If batch-mode was detected
        if ($this->batch) {

            // Foreach termId
            foreach ($this->_termIds() as $termId) {

                // Use it to spoof 'termId' request-param
                $this->setParam('termId', $termId);

                // Validate request params (and pull records if need) as if we would not be in batch-mode
                $this->_postCheck($_);
            }

        // Validate request params (and pull records if need)
        } else $this->_postCheck($_);

        // Foreach jchecked params combination
        foreach ($this->jchecked as $key => $jchecked) {

            // Get params
            list ($params['termEntryId'], $params['language'], $params['termId']) = explode(':', $key);

            // Spoof params
            foreach ($params as $name => $value) $this->setParam($name, $value);

            // Call the appropriate method depend on mode-param
            switch ($_['dataType']->getType()) {
                case 'xGraphic':
                case 'externalCrossReference': $this->xrefcreateAction  ($jchecked); break;
                case 'crossReference':         $this->refcreateAction   ($jchecked); break;
                case 'figure':                 $this->figurecreateAction($jchecked); break;
                default:                       $this->attrcreateAction  ($jchecked); break;
            }
        }

        // Get first response, but make that inserted.id to contain comma-separated of all responses rather that first one only
        // When not in batch mode - only one response will be there, so inserted.id will contain only one id
        $response = $this->responseA[0];
        $response['inserted']['id'] = join(',', array_column(array_column($this->responseA, 'inserted'), 'id'));

        // Merge insertedId => existingId pairs among all responses
        $existing = [];
        foreach ($this->responseA as $responseI)
            if ($responseI['existing'] ?? 0)
                $existing += $responseI['existing'];

        // If not empty - append merged to respone
        if ($existing) $response['existing'] = $existing;

        // Flush response
        $this->view->assign($response);
    }

    /**
     * Validate request params responsible for single attribute creation
     *
     * @param $_
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Mismatch
     */
    protected function _postCheck($_) {

        // Validate other params
        $_ += $this->jcheck([
            'termId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => 'editor_Models_Terminology_Models_TermModel'
            ],
        ]);

        // If no or only certain collections are accessible - validate collection accessibility
        if ($this->collectionIds !== true) $this->jcheck([
            'collectionId' => [
                'fis' => $this->collectionIds ?: 'invalid' // FIND_IN_SET
            ],
        ], $_['termId']);

        // If attribute we're going to add is not a part of TBX basic standard
        if (!$_['dataType']->getIsTbxBasic())
            $this->jcheck([
                'collectionId' => [
                    'fis' => $_['dataType']->getAllowedCollectionIds() // FIND_IN_SET
                ]
            ], $_['termId']);

        // Build params key
        $key = $_['termId']->getTermEntryId()
            . ':' . ($_['level'] != 'entry' ? $_['termId']->getLanguage() : '')
            . ':' . ($_['level'] == 'term'  ? $_['termId']->getId() : '');

        // Save jcheck return data
        $this->jchecked[$key] = $_;
    }

    /**
     * Update attribute
     *
     * @throws ZfExtended_Mismatch
     */
    public function putAction() {

        // Validate params
        $this->jcheck([
            'attrId' => [
                'req' => !$this->getParam('draft0'),
                'rex' => 'int11list'
            ],
            'dropId' => [
                'rex' => 'int11list'
            ],
            'draft0' => [
                'req' => !$this->getParam('attrId') && !$this->getParam('value'),
                'rex' => 'int11list'
            ]
        ]);

        // Get attr ids array
        $attrIdA = editor_Utils::ar($this->getParam('attrId'));

        // If dropId-param is given
        if ($dropId = $this->getParam('dropId')) {

            // Split by comma
            $dropIdA = editor_Utils::ar($dropId);

            // Make sure that qties of $attrIdA and $dropIdA are equal
            $this->jcheck(['qty' => ['eql' => count($attrIdA)]], ['qty' => count($dropIdA)]);
        }

        // Attribute model instances whose value/target we're going to update
        $attrA = [];

        // Attribute model instances that we're going to delete, but, with preliminary
        // usage of their values to apply to attribute model instances from above ($attrA) array
        // Note: this is used only in batch-mode
        $dropA = [];

        // Array of dataTypeIds to check that attributes we're going to update all are of same dataTypeId
        // Note: this is not used in batch-mode
        $dataTypeIdA = [];

        // Foreach comma-separated value inside attrId-param
        foreach ($attrIdA as $idx => $attrId) {

            // Load attribute model instance, make a clone and append it to $attrA
            $this->entity->load($attrId); $attrA[$attrId] = clone $this->entity;

            // If no or only certain collections are accessible - validate collection accessibility
            if ($this->collectionIds !== true) $this->jcheck([
                'collectionId' => [
                    'fis' => $this->collectionIds ?: 'invalid' // FIND_IN_SET
                ],
            ], $this->entity);

            // Setup an $unique flag inidcating whether this attribute should be unique having it's dataTypeId within it's level
            $unique = !in_array($this->entity->getType(), ['xGraphic', 'externalCrossReference', 'crossReference', 'figure']);

            // If attr should be unique and dropId-param is given
            if ($unique && $dropId ?? 0) {

                // Load entity, make a clone and append it to $dropA under $attrId key
                $this->entity->load($dropIdA[$idx]); $dropA[$attrId] = clone $this->entity;

                // If no or only certain collections are accessible - validate collection accessibility
                if ($this->collectionIds !== true) $this->jcheck([
                    'collectionId' => [
                        'fis' => $this->collectionIds ?: 'invalid' // FIND_IN_SET
                    ],
                ], $this->entity);

                // Make sure that both instances in [attr => drop] pair has equal dataTypeId, termEntryId, language and termId props
                $this->jcheck([
                    'dataTypeId'  => ['eql' => $attrA[$attrId]->getDataTypeId()],
                    'termEntryId' => ['eql' => $attrA[$attrId]->getTermEntryId()],
                    'language'    => ['eql' => $attrA[$attrId]->getLanguage()],
                    'termId'      => ['eql' => $attrA[$attrId]->getTermId()],
                ], $this->entity);
            }

            // Collect distinct dataTypeIds
            if (!isset($dropId)) $dataTypeIdA[$this->entity->getDataTypeId()] = true;
        }

        // Check that all attributes belong to the same dataTypeId
        if (!isset($dropId)) $this->jcheck(['qty' => ['eql' => 1]], ['qty' => count($dataTypeIdA)]);

        // Foreach attribute model instance
        foreach ($attrA as $attrId => $entity) {

            // Spoof $this->entity
            $this->entity = $entity;

            // Call the appropriate method depend on attribute type
            switch ($this->entity->getType()) {
                case 'xGraphic':
                case 'externalCrossReference': $this->xrefupdateAction();   break;
                case 'crossReference':         $this->refupdateAction();    break;
                case 'figure':     $tmp_name = $this->figureupdateAction(); break;
                default:                       $this->attrupdateAction($dropA[$attrId] ?? null);   break;
            }
        }

        // Delete uploaded temporary file
        if ($tmp_name ?? 0) unlink($tmp_name);

        // If draft0-param is given
        if ($draft0 = $this->getParam('draft0')) {

            // Setup isDraft=0 on attributes identified by that param and return array of special attributes ids.
            // Attribute is considered special if it requires special processing
            // Currently only processStatus-, definition- and administrativeStatus-attrs are special
            $attrIdA_special = $this->entity->undraftByIds($draft0);

            // Foreach special attrId
            foreach ($attrIdA_special as $attrId) {

                // Load attribute model instance
                $this->entity->load($attrId);

                // Setup value-param for it to be further picked
                $this->setParam('value', $this->entity->getValue());

                // Call attrupdateAction()
                $this->attrupdateAction();
            }
        }

        // Flush response. Actually, $this->responseA is contain responses only if attrId-param is not empty
        if ($attrIdA) $this->view->assign($this->responseA[0]); else if ($draft0) $this->view->assign(['success' => true]);

        // Add into response
        if ($this->affectedA['icons'] ?? 0) {
            $this->view->assign('icons', $this->affectedA['icons']);
        }
    }

    /**
     * Delete attribute
     *
     * @throws ZfExtended_Mismatch
     */
    public function deleteAction() {

        // Validate attrId-param
        $this->jcheck([
            'attrId' => [
                'req' => true,
                'rex' => 'int11list'
            ]
        ]);

        // Model instances and dataTypeIds
        $entityA = []; $dataTypeIdA = [];

        // Foreach attribute - do checks
        foreach (editor_Utils::ar($this->getParam('attrId')) as $attrId) {

            // Load entity, make a clone and append it to $entityA
            $this->entity->load($attrId); $entityA[$attrId] = clone $this->entity;

            // If no or only certain collections are accessible - validate collection accessibility
            if ($this->collectionIds !== true) $this->jcheck([
                'collectionId' => [
                    'fis' => $this->collectionIds ?: 'invalid' // FIND_IN_SET
                ],
            ], $this->entity);

            // Prevent deletion of processStatus and administrativeStatus attributes, if those are not drafts
            if (!$this->entity->getIsDraft()) $this->jcheck([
                'type' => [
                    'dis' => 'processStatus,administrativeStatus'
                ]
            ], $this->entity);

            // Collect distinct dataTypeIds
            $dataTypeIdA[$this->entity->getDataTypeId()] = true;
        }

        // If current user can't delete any attribute, for example
        // has none of termPM, termPM_allClients or admin roles,
        // but has other roles allowed to delete attributes only in certain curcumstances
        if (!$this->isAllowed('editor_attribute', 'deleteAny')) {

            // Get attribute ids
            $attrIds = array_keys($entityA);

            // Get [attrId => readonly] pairs
            $readonlyA = $this->entity->getReadonlyByIds(
                $attrIds,
                $this->_session->id, // here we're inside deleteAction, so we do have access
                $this->_session->roles
            );

            // If at least one is readonly - flush failure
            foreach ($readonlyA as $attrId => $readonly)
                if ($readonly) $this->jflush(false, count($entityA) == 1
                    ? 'This attribute is not deletable'
                    : 'Some of the attributes are not delatable');
        }

        // Foreach attribute - do delete
        foreach ($entityA as $attrId => $entity) {

            // Spoof $this->entity
            $this->entity = $entity;

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
            $this->responseA []= $data;
        }

        // Flush response
        $this->view->assign($this->responseA[0]);
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
            'deletable' => true,
            'isValidUrl' => false,
            'created' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getCreatedAt())),
            'updated' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getUpdatedAt())),
        ];

        // Append response data
        $this->responseA []= ['inserted' => $inserted, 'updated' => $updated];
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
            'deletable' => true,
            'created' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getCreatedAt())),
            'updated' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getUpdatedAt())),
        ];

        // Append response data
        $this->responseA []= ['inserted' => $inserted, 'updated' => $updated];
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
            'deletable' => true,
            'language' => $this->entity->getLanguage(),
            'dataTypeId' => $this->entity->getDataTypeId(),
            'created' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getCreatedAt())),
            'updated' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getUpdatedAt())),
        ];

        // Append response data
        $this->responseA []= ['inserted' => $inserted, 'updated' => $updated];
    }

    /**
     * @throws ZfExtended_Mismatch
     */
    public function attrcreateAction($_) {

        // Get dataTypeId => id pairs of already existing attributes
        $existingA = $_['dataType']->getAlreadyExistingFor(
            $this->getParam('termEntryId') ?: null,
            $this->getParam('language') ?: null,
            $this->getParam('termId') ?: null
        );

        // If we're going to create attribute with a dataTypeId,
        // that one of already existing attributes has - get that attribute id
        $attrId = $existingA[$_['dataType']->getId()] ?? false;

        // If we're not in batch mode - prevent creating attribute with such a dataTypeId
        if (!$this->batch && $attrId) $this->jflush(false, 'Duplicates are not allowed');

        // Default value for the attribute
        $value = $_['dataType']->getDataType() == 'picklist' ? explode(',', $_['dataType']->getPicklistValues())[0] : null;

        // Init attribute model with data
        $this->_attrInit($_, [
            'value' => $value,
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
            'deletable' => true,
            'dataTypeId' => $this->entity->getDataTypeId(),
            'created' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getCreatedAt())),
            'updated' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($this->entity->getUpdatedAt())),
        ];

        // Response data
        $data = ['inserted' => $inserted, 'updated' => $updated];

        // If term status changed - append new value to json
        if ($_['level'] == 'term' && !$this->entity->getIsDraft())
            if ($status = $this->_updateTermStatus($_['termId'], $this->entity))
                $data['status'] = $status;

        // Pass existing attrId into response as well, if detected
        if ($attrId) $data['existing'][$inserted['id']] = (int) $attrId;

        // Append response data
        $this->responseA []= $data;
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

        // Set attribute's dataIndex-defined prop
        $this->entity->{'set' . ucfirst($this->getParam('dataIndex'))}($this->getParam('value'));

        // Update attribute
        $updated = $this->entity->update($misc = [
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid,
        ]);

        // Setup $isValidUrl flag indicating whether `target`-prop contains a valid url
        $isValidUrl = preg_match('~ href="([^"]+)"~', editor_Utils::url2a($this->entity->getTarget()));

        // Flush response data
        $this->responseA []= ['updated' => $updated, 'isValidUrl' => $isValidUrl];
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
                'rex' => 'rfc5646'
            ]
        ]);

        // Get param shortcuts
        $target = $this->getParam('target');
        $attrId = $this->getParam('attrId');
        $termLang = $this->getParam('termLang');
        $mainLang = $this->getParam('mainLang');

        // Set attribute's target-prop
        $this->entity->setTarget($target);

        // Update attribute
        $data['updated'] = $this->entity->update([
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid,
        ]);

        // If given target is not empty
        if ($target) {

            // Detect level
            $level = $this->entity->getTermId() ? 'term' : 'entry';

            // Setup preferred languages array
            $prefLangA = [];
            if ($termLang) $prefLangA[] = $termLang;
            if ($mainLang) $prefLangA[] = $mainLang;
            if ($level == 'term' && !$prefLangA) $prefLangA []= $this->entity->getLanguage();
            $preLangA = array_unique($prefLangA);

            // Prepare first 2 arguments to be used for $this->_refTarget(&$refA, $refTargetIdA, $prefLangA) call
            $refA[$level][$attrId] = $this->entity->toArray();
            $refTargetIdA[$target] = [$level, $attrId];

            // Call $this->_refTarget() with that args
            editor_Models_Terminology_Models_AttributeModel::refTarget($refA, $refTargetIdA, $prefLangA, $level);

            // Append refTarget data to the response
            $data += $refA[$level][$attrId];
        }

        // Append response data
        $this->responseA []= $data;
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

        // If uploaded file is successfully copied into proper location
        if ($i->copyImage($_['figure']['tmp_name'], $this->entity->getCollectionId())) {

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
            $this->responseA []= ['src' => $i->getPublicPath(), 'updated' => $updated];

            // Else flush empty src
        } else $this->responseA []= ['src' => ''] + $_;

        // Return uploaded temporary file path
        return $_['figure']['tmp_name'];
    }

    /**
     * @param editor_Models_Terminology_Models_AttributeModel|null $drop
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Mismatch
     */
    public function attrupdateAction(editor_Models_Terminology_Models_AttributeModel $drop = null) {

        // Check request params and return an array, containing records
        // fetched from database by dataTypeId-param (and termId-param, if given)
        $_ = $this->_attrupdateCheck($drop ? false : true);

        // Default response data to be flushed in case of attribute change
        $data = ['success' => true, 'updated' => $this->_session->userName . ', ' . date('d.m.Y H:i:s')];

        // If attr was not yet changed after importing from tbx - append current value to response
        if (!$this->entity->getIsCreatedLocally()) $data['imported'] = $this->entity->getValue();

        // If $drop arg is given - use it's value
        $value = $drop ? $drop->getValue() : $this->getParam('value');

        // If $drop arg is given
        if ($drop) {

            // Update collection stats
            ZfExtended_Factory
                ::get('editor_Models_TermCollection_TermCollection')
                ->updateStats($drop->getCollectionId(), [
                    'termEntry' => 0,
                    'term' => 0,
                    'attribute' => -1
                ]);

            // Delete
            $drop->delete();
        }

        // If it's a processStatus-attribute
        if ($this->entity->getType() == 'processStatus' && !$this->entity->getIsDraft()) {

            // Check whether current user is allowed to change processStatus from it's current value to given value
            $this->_attrupdateCheckProcessStatusChangeIsAllowed($_);

            // Do process status change, incl. detaching proposal if need, etc
            $data += $_['termId']->doProcessStatusChange(
                $value,
                $this->_session->id,
                $this->_session->userName,
                $this->_session->userGuid,
                $this->entity
            );

            // Collect data to be merged into response, so client app will be able
            // to update (administrative|process)Status-icons within left and center panels
            $this->affectedA['icons'][$_['termId']->getId()]['processStatus'] = $value;

        // Else
        } else {

            // Update attribute value
            $this->entity->setValue($value);
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
        if (isset($_['termId']) && !$this->entity->getIsDraft())
            if ($status = $this->_updateTermStatus($_['termId'], $this->entity)) {
                $data['status'] = $status;
                $this->affectedA['icons'][$_['termId']->getId()]['status'] = $status['status'];
            }

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

        // Collect response data
        $this->responseA []= $data;
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

        /* @var $termNoteStatus editor_Models_Terminology_TermStatus */
        $termNoteStatus = ZfExtended_Factory::get('editor_Models_Terminology_TermStatus');

        $others = [];
        $status = $termNoteStatus->getStatusForUpdatedAttribute($attrM, $others);

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
     * @param bool $valueRequired
     * @return array
     * @throws ZfExtended_Mismatch
     */
    protected function _attrupdateCheck($valueRequired = true) {

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
                    'req' => $valueRequired,
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

            'collectionId' => $_['termId']->getCollectionId(),
            'termEntryId'  => $_['termId']->getTermEntryId(),
            'language'     => $l = $this->getParam('language') ?: null,
            'termId'       => $this->getParam('termId') ?: null,
            'termTbxId'    => $this->getParam('termId') ? $_['termId']->getTermTbxId() : null,
            'isCreatedLocally' => 1,
            'createdBy' => $this->_session->id,
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedBy' => $this->_session->id,
            'updatedAt' => date('Y-m-d H:i:s'),
            'termEntryGuid' => $_['termId']->getTermEntryGuid(),
            //'langSetGuid' => null,
            'termGuid' => $this->getParam('termId') ? $_['termId']->getGuid() : null,
            'guid' => ZfExtended_Utils::uuid(),
            'attrLang' => $l,
            //'dataType' => null
            'isDraft' => $this->batch ? 1 : 0
        ]);
    }
}
