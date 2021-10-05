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
     */
    public function postAction() {

        // Get request params
        $params = $this->getRequest()->getParams();

        // Validate params
        $_ = editor_Utils::jcheck([
            'termEntryId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => 'terms_term_entry'
            ],
            'mode' => [
                // 'req' => false
                'fis' => 'xref,ref,figure'
            ],
        ], $params);

        // If no or only certain collections are accessible - validate collection accessibility
        if ($this->collectionIds !== true) editor_Utils::jcheck([
            'collectionId' => [
                'fis' => $this->collectionIds ?: 'invalid'
            ],
        ], $_['termEntryId']);

        // Call the appropriate method depend on attr's `elementName` or `type` prop
        settype($params['mode'], 'string');
        if ($params['mode'] == 'xref') $this->xrefcreateAction($_);
        else if ($params['mode'] == 'ref') $this->refcreateAction($_);
        else if ($params['mode'] == 'figure') $this->figurecreateAction($_);
        else $this->attrcreateAction($_);

        // Update
        ZfExtended_Factory
            ::get('editor_Models_TermCollection_TermCollection')
            ->updateStats($_['termEntryId']['collectionId'], [
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

        // Get request params
        $params = $this->getRequest()->getParams();

        // Validate params
        $_ = editor_Utils::jcheck([
            'attrId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => 'terms_attributes'
            ],
        ], $params);

        // If no or only certain collections are accessible - validate collection accessibility
        if ($this->collectionIds !== true) editor_Utils::jcheck([
            'collectionId' => [
                'fis' => $this->collectionIds ?: 'invalid'
            ],
        ], $_['attrId']);

        // Call the appropriate method depend on attr's `elementName` or `type` prop
        if ($_['attrId']['elementName'] == 'xref') $this->xrefupdateAction($_);
        else if ($_['attrId']['elementName'] == 'ref') $this->refupdateAction($_);
        else if ($_['attrId']['type'] == 'figure') $this->figureupdateAction($_);
        else $this->attrupdateAction($_);
    }

    /**
     * Delete attribute
     *
     * @throws ZfExtended_Mismatch
     */
    public function deleteAction() {

        // Get request params
        $params = $this->getRequest()->getParams();

        // Validate params
        $_ = editor_Utils::jcheck([
            'attrId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => 'terms_attributes'
            ]
        ], $params);

        // If no or only certain collections are accessible - validate collection accessibility
        if ($this->collectionIds !== true) editor_Utils::jcheck([
            'collectionId' => [
                'fis' => $this->collectionIds ?: 'invalid'
            ],
        ], $_['attrId']);

        // If it's a processStatus- or administrativeStatus-attribute - do nothing
        if ($_['attrId']['type'] == 'processStatus' || $_['attrId']['type'] == 'administrativeStatus') return;

        // Create `terms_attributes` model instance
        $a = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        $a->load($params['attrId']);
        $updated = $a->delete($misc = [
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid,
        ]);

        // Flush response data
        $this->view->assign(['updated' => $updated]);

        // Update
        ZfExtended_Factory
            ::get('editor_Models_TermCollection_TermCollection')
            ->updateStats($_['attrId']['collectionId'], [
                'termEntry' => 0,
                'term' => 0,
                'attribute' => -1
            ]);
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Mismatch
     */
    public function xrefcreateAction($_) {

        // Get request params
        $params = $this->getRequest()->getParams();

        // Validate params
        editor_Utils::jcheck([
            'language' => [
                'req' => $params['level'] == 'language',
                'rex' => 'rfc5646'
            ],
            'level' => [
                'req' => true,
                'fis' => 'entry,language,term'
            ],
            'type' => [
                'req' => true,
                'fis' => 'xGraphic,externalCrossReference'
            ],
        ], $params);

        // Create `terms_attributes` model instance
        /** @var editor_Models_Terminology_Models_AttributeModel $a */
        $a = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');

        // Apply data
        $a->init([
            'collectionId' => $_['termEntryId']['collectionId'],
            'termEntryId' => $params['termEntryId'],
            'language' => $params['level'] == 'language' ? $params['language'] : null,

            // No need for xrefs
            //'termId' => ,

            'dataTypeId' => editor_Utils::db()->query(
                'SELECT `id` FROM `terms_attributes_datatype` WHERE `type` = ?', $params['type']
            )->fetchColumn(),
            'type' => $params['type'],

            // Below 2 will be set by xrefupdateAction()
            //'value' => ,
            //'target' => ,

            'isCreatedLocally' => 1,
            'createdBy' => $this->_session->id,
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedBy' => $this->_session->id,
            'updatedAt' => date('Y-m-d H:i:s'),
            'termEntryGuid' => $_['termEntryId']['entryGuid'],
            //'langSetGuid' => null,
            //'termGuid' => null,
            'guid' => ZfExtended_Utils::uuid(),
            'elementName' => 'xref',
            'attrLang' => $params['language'],
            //'dataType' => null
        ]);

        // Save attr and affect transacgrp-records
        $updated = $a->insert($misc = [
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid,
        ]);

        // Prepare inserted data to be flushed into response json
        $inserted = [
            'id' => $a->getId(),
            'value' => '',
            'target' => '',
            'isValidUrl' => false,
            'created' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($a->getCreatedAt())),
            'updated' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($a->getUpdatedAt())),
        ];

        // Flush response data
        $this->view->assign(['inserted' => $inserted, 'updated' => $updated]);
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Mismatch
     */
    public function refcreateAction($_) {

        // Get request params
        $params = $this->getRequest()->getParams();

        // Validate params
        $_ += editor_Utils::jcheck([
            'level' => [
                'req' => true,
                'fis' => 'entry,term'
            ],
            'termId' => [
                'req' => $params['level'] == 'term',
                'rex' => 'int11',
                'key' => 'terms_term'
            ],
        ], $params);

        // Create `terms_attributes` model instance
        $a = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');

        // Apply data
        $a->init([
            'collectionId' => $_['termEntryId']['collectionId'],
            'termEntryId' => $params['termEntryId'],
            'language' => $params['level'] == 'term' ? $_['termId']['language'] : null,
            'termId' => $params['level'] == 'term' ? $_['termId']['id'] : null,
            'termTbxId' => $params['level'] == 'term' ? $_['termId']['termTbxId'] : null,

            'dataTypeId' => editor_Utils::db()->query(
                'SELECT `id` FROM `terms_attributes_datatype` WHERE `type` = "crossReference"'
            )->fetchColumn(),
            'type' => 'crossReference',

            // Below 2 will be set by refupdateAction()
            //'value' => ,
            //'target' => ,

            'isCreatedLocally' => 1,
            'createdBy' => $this->_session->id,
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedBy' => $this->_session->id,
            'updatedAt' => date('Y-m-d H:i:s'),
            'termEntryGuid' => $_['termEntryId']['entryGuid'],
            //'langSetGuid' => null,
            'termGuid' => $params['level'] == 'term' ? $_['termId']['guid'] : null,
            'guid' => ZfExtended_Utils::uuid(),
            'elementName' => 'ref',
            'attrLang' => $params['level'] == 'term' ? $_['termId']['language']: null,
            //'dataType' => null
        ]);

        // Save attr and affect transacgrp-records
        $updated = $a->insert($misc = [
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid,
        ]);

        // Prepare inserted data to be flushed into response json
        $inserted = [
            'id' => $a->getId(),
            'target' => '',
            'value' => '',
            'language' => '',
            'created' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($a->getCreatedAt())),
            'updated' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($a->getUpdatedAt())),
        ];

        // Flush response data
        $this->view->assign(['inserted' => $inserted, 'updated' => $updated]);
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Mismatch
     */
    public function figurecreateAction($_) {

        // Get request params
        $params = $this->getRequest()->getParams();

        // Validate params
        editor_Utils::jcheck([
            'language' => [
                'req' => $params['level'] == 'language',
                'rex' => 'rfc5646'
            ],
            'level' => [
                'req' => true,
                'fis' => 'entry,language'
            ],
        ], $params);

        // Create `terms_attributes` model instance
        $a = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');

        // Apply data
        $a->init([
            'collectionId' => $_['termEntryId']['collectionId'],
            'termEntryId' => $params['termEntryId'],
            'language' => $params['level'] == 'language' ? $params['language'] : null,
            // 'termId' => ,
            'dataTypeId' => $dataTypeId = editor_Utils::db()->query(
                'SELECT `id` FROM `terms_attributes_datatype` WHERE `type` = "figure"'
            )->fetchColumn(),
            'type' => 'figure',
            'value' => 'Image',
            'target' => ZfExtended_Utils::uuid(),
            'isCreatedLocally' => 1,
            'createdBy' => $this->_session->id,
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedBy' => $this->_session->id,
            'updatedAt' => date('Y-m-d H:i:s'),
            'termEntryGuid' => $_['termEntryId']['entryGuid'],
            //'langSetGuid' => null,
            //'termGuid' => $params['level'] == 'term' ? $_['termId']['guid'] : null,
            'guid' => ZfExtended_Utils::uuid(),
            'elementName' => 'descrip',
            'attrLang' => $params['level'] == 'language' ? $params['language'] : null,
            //'dataType' => null
        ]);

        // Save attr and affect transacgrp-records
        $updated = $a->insert($misc = [
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid,
        ]);

        // Prepare inserted data to be flushed into response json
        $inserted = [
            'id' => $a->getId(),
            'elementName' => $a->getElementName(),
            'target' => $a->getTarget(),
            'value' => $a->getValue(),
            'type' => $a->getType(),
            'src' => '',
            'language' => $a->getLanguage(),
            'dataTypeId' => $a->getDataTypeId(),
            'created' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($a->getCreatedAt())),
            'updated' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($a->getUpdatedAt())),
        ];

        // Flush response data
        $this->view->assign(['inserted' => $inserted, 'updated' => $updated]);
    }

    /**
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Mismatch
     */
    public function attrcreateAction($_) {

        // Get request params
        $params = $this->getRequest()->getParams();

        // Validate dataTypeId first
        $_ += editor_Utils::jcheck([
            'dataTypeId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => 'terms_attributes_datatype'
            ],
        ], $params);

        // Validate others params
        $_ += editor_Utils::jcheck([
            'language' => [
                'req' => $params['level'] != 'entry',
                'rex' => 'rfc5646'
            ],
            'level' => [
                'req' => true,
                'fis' => $_['dataTypeId']['level']
            ],
            'termId' => [
                'req' => $params['level'] == 'term',
                'rex' => 'int11',
                'key' => 'terms_term'
            ],
        ], $params);

        // Prevent creating attribute that is:
        // 1. Having type, that is handled by dedicated *createAction()
        // 2. Having label, that is not actually used as an attribute
        editor_Utils::jcheck([
            'type' => [
                'dis' => 'figure,externalCrossReference,crossReference,xGraphic'
            ],
            'label' => [
                'dis' => 'date,langSet,term'
            ],
        ], $_['dataTypeId']);

        // Setup WHERE clauses for entry-, language- and term-level attributes
        $levelWHERE = [
            'entry'    => '`termEntryId` = :termEntryId AND ISNULL(`language`) AND ISNULL(`termId`)',
            'language' => '`termEntryId` = :termEntryId AND `language` = :language AND ISNULL(`termId`)',
            'term'     => '`termId` = :termId'
        ];

        // Params for binding to the existing attribute-fetching query
        $bind = [
            'entry'    => [':termEntryId' => $params['termEntryId']],
            'language' => [':termEntryId' => $params['termEntryId'], ':language' => $params['language']],
            'term'     => [':termId' => $params['termId']]
        ];

        // Prevent creating attribute with a dataTypeId, that one of already existing attributes has
        editor_Utils::jcheck([
            'dataTypeId' => [
                'dis' => editor_Utils::db()->query('
                    SELECT `dataTypeId` 
                    FROM `terms_attributes`
                    WHERE ' . $levelWHERE[$params['level']]
                    , $bind[$params['level']])->fetchAll(PDO::FETCH_COLUMN)
            ]
        ], $params);

        // If attribute we're going to add is not a part of TBX basic standard
        if (!$_['dataTypeId']['isTbxBasic']) editor_Utils::jcheck([
            'collectionId' => [
                'fis' => editor_Utils::db()->query('
                    SELECT `collectionId` 
                    FROM `terms_collection_attribute_datatype` 
                    WHERE `dataTypeId` = ?'
                    , $params['dataTypeId'])->fetchAll(PDO::FETCH_COLUMN)
            ]
        ], $_['termEntryId']);

        // Create `terms_attributes` model instance
        /* @var $a editor_Models_Terminology_Models_AttributeModel */
        $a = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');

        // Apply data
        $a->init([
            'collectionId' => $_['termEntryId']['collectionId'],
            'termEntryId' => $params['termEntryId'],
            'language' => $params['level'] != 'entry' ? $params['language'] : null,
            'termId' => $params['level'] == 'term' ? $_['termId']['id'] : null,
            'termTbxId' => $params['level'] == 'term' ? $_['termId']['termTbxId'] : null,

            'dataTypeId' => $params['dataTypeId'],
            'type' => $_['dataTypeId']['type'],
            'value' => $_['dataTypeId']['dataType'] == 'picklist' ? explode(',', $_['dataTypeId']['picklistValues'])[0] : null,

            // This wont be set for ordinary attributes
            //'target' => ,

            'isCreatedLocally' => 1,
            'createdBy' => $this->_session->id,
            'createdAt' => date('Y-m-d H:i:s'),
            'updatedBy' => $this->_session->id,
            'updatedAt' => date('Y-m-d H:i:s'),
            'termEntryGuid' => $_['termEntryId']['entryGuid'],
            //'langSetGuid' => null,
            'termGuid' => $params['level'] == 'term' ? $_['termId']['guid'] : null,
            'guid' => ZfExtended_Utils::uuid(),
            'elementName' => $_['dataTypeId']['label'],
            'attrLang' => $params['level'] != 'entry' ? $params['language'] : null,
            //'dataType' => null
        ]);

        // Save attr and affect transacgrp-records
        $updated = $a->insert($misc = [
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid
        ]);

        // Prepare inserted data to be flushed into response json
        $inserted = [
            'id' => $a->getId(),
            'target' => '',
            'value' => $a->getValue(),
            'type' => $a->getType(),
            'language' => $a->getLanguage(),
            'dataTypeId' => $a->getDataTypeId(),
            'created' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($a->getCreatedAt())),
            'updated' => $misc['userName'] . ', ' . date('d.m.Y H:i:s', strtotime($a->getUpdatedAt())),
        ];

        // Response data
        $data = ['inserted' => $inserted, 'updated' => $updated];

        // Get the term (if termId exists only)
        /** @var editor_Models_Terminology_Models_TermModel $t */
        if (!empty($params['termId'])) {
            $t = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
            $t->load($params['termId']);

            // If term status changed - append new value to json
            if ($status = $this->_updateTermStatus($t, $a))
                $data['status'] = $status;
        }

        // Flush response data
        $this->view->assign($data);
    }


    /**
     * @throws ZfExtended_Mismatch
     */
    public function xrefupdateAction($_) {

        // Get request params
        $params = $this->getRequest()->getParams();

        // Validate params
        editor_Utils::jcheck([
            'dataIndex' => [
                'req' => true,
                'fis' => 'value,target'
            ],
            'value' => [
                'rex' => 'varchar255'
            ]
        ], $params);

        // Create `terms_attributes` model instance
        /** @var editor_Models_Terminology_Models_AttributeModel $a */
        $a = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        $a->load($params['attrId']);
        $a->{'set' . ucfirst($params['dataIndex'])}($params['value']);
        $updated = $a->update($misc = [
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid,
        ]);

        // Setup $isValidUrl flag indicating whether `target`-prop contains a valid url
        $isValidUrl = preg_match('~ href="([^"]+)"~', editor_Utils::url2a($a->getTarget()));

        // Flush response data
        $this->view->assign(['updated' => $updated, 'isValidUrl' => $isValidUrl]);
    }

    /**
     * @throws ZfExtended_Mismatch
     */
    public function refupdateAction($_) {

        // Get request params
        $params = $this->getRequest()->getParams();

        // Validate params
        editor_Utils::jcheck([
            'target' => [
                'rex' => 'xmlid',
            ],
            'termLang,mainLang' => [
                'req' => true,
                'rex' => 'rfc5646'
            ]
        ], $params);

        // Create `terms_attributes` model instance
        /** @var editor_Models_Terminology_Models_AttributeModel $a */
        $a = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');
        $a->load($params['attrId']);
        $a->setTarget($params['target']);
        $data['updated'] = $a->update($misc = [
            'userName' => $this->_session->userName,
            'userGuid' => $this->_session->userGuid,
        ]);

        // If given target is not empty
        if ($params['target']) {

            // Detect level
            $level = $_['attrId']['termId'] ? 'term' : 'entry';

            // Prepare first 2 arguments to be used for $this->_refTarget(&$refA, $refTargetIdA, $prefLangA) call
            $refA[$level][$params['attrId']] = $_['attrId'];
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
    public function figureupdateAction($_) {

        // Get request params
        $params = $this->getRequest()->getParams();

        // Validate params
        $_ += editor_Utils::jcheck([
            'level' => [
                'req' => true,
                'fis' => 'entry,language'
            ],
            'figure' => [
                'req' => true,
                'ext' => '~^(gif|png|jpe?g)$~'
            ]
        ], $params);

        // Create `terms_images` model instance
        /** @var $i editor_Models_Terminology_Models_ImagesModel */
        $i = ZfExtended_Factory::get('editor_Models_Terminology_Models_ImagesModel');

        // Apply data
        $i->init([
            'targetId' => $_['attrId']['target'],
            'name' => $_['figure']['name'],
            'uniqueName' => ZfExtended_Utils::uuid() . $_['figure']['.ext'],
            'encoding' => 'hex',
            'format' => $_['figure']['type'],
            'collectionId' => $_['attrId']['collectionId']
        ]);

        // If uploaded file is successfully moved into proper location
        if ($i->moveImage($_['figure']['tmp_name'], $_['attrId']['collectionId'])) {

            // Save `terms_images` record
            $i->save();

            // Update `date` and `transacNote` of 'modification'-records
            // for all levels starting from term-level and up to top
            $updated = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
                ->affectLevels(
                    $this->_session->userName,
                    $this->_session->userGuid,
                    $_['attrId']['termEntryId'],
                    $_['attrId']['language']
                );

            // Flush response data
            $this->view->assign(['src' => $i->getPublicPath(), 'updated' => $updated]);

            // Else flush empty src
        } else $this->view->assign(['src' => ''] + $_);
    }

    /**
     * @throws ZfExtended_Mismatch
     */
    public function attrupdateAction($_) {

        // Get request params
        $params = $this->getRequest()->getParams();

        // Validate params
        $_ += editor_Utils::jcheck([
            'level' => [
                'req' => true,
                'fis' => 'entry,language,term'
            ],
            'termId' => [
                'rex' => 'int11',
                'key' => 'terms_term'
            ]
        ], $params);

        // Get attribute meta
        $_ += editor_Utils::jcheck([
            'dataTypeId' => [
                'key' => 'terms_attributes_datatype'
            ]
        ], $_['attrId']);

        // If attribute is a picklist - make sure given value is in the list of allowed values
        if ($_['dataTypeId']['dataType'] == 'picklist')
            editor_Utils::jcheck([
                'value' => [
                    'req' => true,
                    'fis' => $_['dataTypeId']['picklistValues']
                ]
            ], $params);

        // Default response data to be flushed in case of attribute change
        $data = ['success' => true, 'updated' => $this->_session->userName . ', ' . date('d.m.Y H:i:s')];

        // If attr was not yet changed after importing from tbx - append current value to response
        if (!$_['attrId']['isCreatedLocally']) $data['imported'] = $_['attrId']['value'];

        /* @var $attrM editor_Models_Terminology_Models_AttributeModel */
        $attrM = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');

        // Get the term (if termId exists only)
        /** @var editor_Models_Terminology_Models_TermModel $t */
        $t = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
        if(!empty($params['termId'])) {
            $t->load($params['termId']);
        }

        // If it's a processStatus-attribute
        if ($_['attrId']['type'] == 'processStatus') {
            // Get current value of processStatus attribute, that should be involved in validation
            $current = $t->getProposal() ? 'unprocessed' : $_['attrId']['value'];

            // Define which old values can be changed to which new values
            $allow = false; $allowByRole = [
                'termSearch' => false, // no change allowed
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
                if ($role[$i])
                    $allow = is_bool($info) || is_bool($allow)
                        ? $info
                        : $info + $allow;

            // Prepare list of allowed values
            $allowed = []; foreach(explode(',', $_['dataTypeId']['picklistValues']) as $possible)
                if ($allow === true || (is_array($allow[$current]) && $allow[$current][$possible]))
                    $allowed []= $possible;

            // Make sure only allowed values can be set as new value of processStatus attribute
            editor_Utils::jcheck([
                'value' => [
                    'fis' => implode(',', $allowed ?: ['wontpass'])
                ]
            ], $params);

            // If term, that we're going to change processStatus for - has a proposal
            if ($t->getProposal()) {

                // If new processStatus is rejected, provisionallyProcessed or finalized
                if ($params['value'] != 'unprocessed') {

                    // Move existing term's proposal to the new term, with attributes replicated
                    $p = ZfExtended_Factory::get('editor_Models_Terminology_Models_TermModel');
                    $init = $t->toArray(); unset($init['id'], $init['proposal']);
                    $init['processStatus'] = $params['value'];
                    $init['term'] = $t->getProposal();
                    $init['guid'] = ZfExtended_Utils::uuid();
                    $init['termTbxId'] = 'id' . ZfExtended_Utils::uuid();
                    $init['updatedBy'] = $this->_session->id;
                    $p->init($init);
                    $p->insert([
                        'userName' => $this->_session->userName,
                        'userGuid' => $this->_session->userGuid,
                        'copyAttrsFromTermId' => $t->getId()
                    ]);

                    // Make sure newly created term's data to be flushed within json response,
                    // so it'll be possible to add record into siblings-panel grid's store
                    $data['inserted'] = [
                        'id' => $p->getId(),
                        'tbx' => $p->getTermTbxId(),
                        'languageId' => $p->getLanguageId(),
                        'language' => $p->getLanguage(),
                        'term' => $p->getTerm(),
                        'proposal' => $p->getProposal(),
                        'status' => $p->getStatus(),
                        'processStatus' => $p->getProcessStatus(),
                        'termEntryTbxId' => $p->getTermEntryTbxId(),

                        // For store's new record, images array will be copied from source record
                        'images' => [],
                    ];

                    // If processStatus is 'rejected', it means that proposal for existing term was rejected,
                    // so that we spoof $params['value'] with processStatus of existing term,
                    // as it will be flushed within json response
                    if ($params['value'] == 'rejected') $params['value'] = $t->getProcessStatus();

                    // Else if existing term's proposal is accepted, e.g. is 'provisionallyProcessed'
                    // or 'finalized' - then, for existing term, setup `processStatus` = 'rejected'
                    // Also, spoof $params['value'] for it to be 'rejected', as it will be flushed within json response
                    else $t->setProcessStatus($params['value'] = 'rejected');

                    // Remove proposal from existing term
                    $t->setProposal('');
                    $t->setUpdatedBy($this->_session->id);
                    $__ = $t->update(['updateProcessStatusAttr' => $params['attrId']]);

                    // If value returned by the above call is an array containing 'normativeAuthorization' key
                    // it means that term processStatus was changed to 'rejected', so 'normativeAuthorization'
                    // attribute was set to 'deprecatedTerm', and in case if there was no such attribute previously
                    // we need to pass attr info to client side for attr-field to be added into the attr-panel
                    if ($naa = $__['normativeAuthorization'])
                        $data['normativeAuthorization'] = [
                            'id' => $naa['id'],
                            'value' => $naa['value'],
                            'type' => $naa['type'],
                            'dataTypeId' => $naa['dataTypeId'],
                            'created' => $this->_session->userName . ', ' . date('d.m.Y H:i:s', strtotime($naa['createdAt'])),
                            'updated' => $this->_session->userName . ', ' . date('d.m.Y H:i:s', strtotime($naa['updatedAt'])),
                        ];

                    // Update
                    ZfExtended_Factory
                        ::get('editor_Models_TermCollection_TermCollection')
                        ->updateStats($_['termId']['collectionId'], ['termEntry' => 0, 'term' => 1]);

                // Else do nothing
                } else {

                }

            // Else
            } else {

                // Update `processStatus` on `terms_term`-record
                $t->setProcessStatus($params['value']);
                $t->setUpdatedBy($this->_session->id);
                $__ = $t->update(['updateProcessStatusAttr' => $params['attrId']]);

                // If value returned by the above call is an array containing 'normativeAuthorization' key
                // it means that term processStatus was changed to 'rejected', so 'normativeAuthorization'
                // attribute was set to 'deprecatedTerm', and in case if there was no such attribute previously
                // we need to pass attr info to client side for attr-field to be added into the attr-panel
                if ($naa = $__['normativeAuthorization']) {
                    $data['normativeAuthorization'] = [
                        'id' => $naa['id'],
                        'value' => $naa['value'],
                        'type' => $naa['type'],
                        'dataTypeId' => $naa['dataTypeId'],
                        'created' => $this->_session->userName . ', ' . date('d.m.Y H:i:s', strtotime($naa['createdAt'])),
                        'updated' => $this->_session->userName . ', ' . date('d.m.Y H:i:s', strtotime($naa['updatedAt'])),
                    ];

                    // Increment collection stats 'attribute'-prop only
                    ZfExtended_Factory
                        ::get('editor_Models_TermCollection_TermCollection')
                        ->updateStats($_['termId']['collectionId'], [
                            'termEntry' => 0,
                            'term' => 0,
                            'attribute' => 1
                        ]);
                }
            }

            // Append processStatus to response data
            $data['processStatus'] = [
                'id' => $_['attrId']['id'],
                'value' => $params['value'],
                'type' => 'processStatus',
                'dataTypeId' => $_['attrId']['dataTypeId'],
                'created' => $this->_session->userName . ', ' . date('d.m.Y H:i:s', strtotime($_['attrId']['createdAt'])),
                'updated' => $this->_session->userName . ', ' . date('d.m.Y H:i:s'),
            ];

        // Else
        } else {

            // Update attribute value
            $attrR = $attrM->load($params['attrId']);
            $attrR->setFromArray(['value' => $params['value'], 'updatedBy' => $this->_session->id, 'isCreatedLocally' => 1]);
            $attrM->update();
        }

        // The term status is updated in in anycase (due implicit normativeAuthorization changes above),
        // not only if a attribute is changed mapped to the term status
        if (isset($params['termId']))
            if ($status = $this->_updateTermStatus($t, $attrM))
                $data['status'] = $status;

        // Update `date` and `transacNote` of 'modification'-records
        // for all levels starting from term-level and up to top
        $data['updated'] = ZfExtended_Factory::get('editor_Models_Terminology_Models_TransacgrpModel')
            ->affectLevels(
                $this->_session->userName,
                $this->_session->userGuid,
                $_['attrId']['termEntryId'],
                $_['attrId']['language'],
                $_['attrId']['termId']
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
}
