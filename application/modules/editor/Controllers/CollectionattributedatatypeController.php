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
class editor_CollectionattributedatatypeController extends ZfExtended_RestController
{
    /**
     * Use termportal trait
     */
    use editor_Controllers_Traits_TermportalTrait;

    /**
     * @var string
     */
    protected $entityClass = 'editor_Models_Terminology_Models_CollectionAttributeDataType';

    /**
     * @var editor_Models_Terminology_Models_CollectionAttributeDataType
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
     * @throws ZfExtended_Mismatch
     * @throws Zend_Db_Statement_Exception
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
            //$this->isAllowed( 'editor_term', 'anyCollection') // use this instead of upper line when other branch is merged into develop
                ?: ZfExtended_Factory
                    ::get(editor_Models_TermCollection_TermCollection::class)
                    ->getAccessibleCollectionIds(editor_User::instance()->getModel());
    }

    /**
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Mismatch
     */
    public function indexAction()
    {
        // Validate collectionId-param
        $_ = $this->jcheck([
            'collectionId' => [
                'req' => true,                                                      // required
                'rex' => 'int11',                                                   // regular expression preset key or raw expression
                'key' => 'LEK_languageresources',                                   // points to existing record in a given db table
            ]
        ]);

        // If no or only certain collections are accessible - validate collection accessibility
        if ($this->collectionIds !== true) $this->jcheck([
            'collectionId' => [
                'fis' => $this->collectionIds ?: 'invalid' // FIND_IN_SET
            ],
        ]);

        // Get [dataTypeId => mappingInfo] pairs for each record, representing mapping
        // between certain attribute datatype and certain TermCollection
        $this->view->assign([
            'success' => true,
            'mappingA' => $this->entity->loadAllByCollectionId($_['collectionId']['id'])
        ]);
    }

    /**
     *
     */
    public function getAction() {
        throw new BadMethodCallException();
    }

    /**
     *
     */
    public function postAction() {
        throw new BadMethodCallException();
    }

    /**
     *
     */
    public function deleteAction() {
        throw new BadMethodCallException();
    }

    /**
     * Change mapping record
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Mismatch
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function putAction() {

        // Validate mappingId-param
        $this->jcheck([
            'mappingId' => [
                'req' => true,                                                      // required
                'rex' => 'int11',                                                   // regular expression preset key or raw expression
                'key' => $this->entity,
            ],
            'enabled' => [
                'req' => true,
                'rex' => 'bool'
            ]
        ]);

        // If no or only certain collections are accessible - validate collection accessibility
        if ($this->collectionIds !== true) $this->jcheck([
            'collectionId' => [
                'fis' => $this->collectionIds ?: 'invalid' // FIND_IN_SET
            ],
        ], $this->entity);

        // Load dataType-record
        $dataTypeR = $this->jcheck(['dataTypeId' => ['key' => 'terms_attributes_datatype']], $this->entity)['dataTypeId'];

        // Prevent disabling dataTypes having type = 'processStatus' or 'administrativeStatus'
        $this->jcheck([
            'type' => [
                'dis' => 'processStatus,administrativeStatus'
            ]
        ], $dataTypeR);

        // If datatype should be disabled
        if (!$enabled = $this->getParam('enabled')) {

            // Get attribute model
            $attrM = ZfExtended_Factory::get('editor_Models_Terminology_Models_AttributeModel');

            // Get quantity of attributes to be removed in case of datatype disability confirmation
            $qty = $attrM->qtyBy($this->entity->getCollectionId(), $this->entity->getDataTypeId());

            // Build disability confirmation msg
            $msg = sprintf(join('<br>', [
                'If you disable that datatype for this termcollection,',
                ' all %s attributes having that datatype in that',
                'termcollections will be also deleted. Continue?']), $qty);
        }

        // If user is going to disable datatype, proceed only after such operation is confirmed
        if ($enabled || $this->confirm($msg)) {

            // Update mapping's enabled-flag
            $this->entity->setEnabled($enabled);

            // Save mapping
            $this->entity->save();

            // If mapping's enabled-flag was set to false
            if (!$this->entity->getEnabled()) {

                // Delete all terms_attributes-records
                $attrM->deleteBy($this->entity->getCollectionId(), $this->entity->getDataTypeId());

                // It means we should set exists-flag to false as well,
                // as all attributes having dataTypeId and collectionId were deleted
                $this->entity->setExists(false);

                // Save mapping
                $this->entity->save();
            }

            // Flush response
            $this->jflush(true);
        }
    }
}
