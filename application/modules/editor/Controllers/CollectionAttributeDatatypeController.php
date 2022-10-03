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
class editor_CollectionAttributeDatatypeController extends ZfExtended_RestController
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
     * @throws Zend_Session_Exception
     */
    public function init() {

        // Call parent
        parent::init();

        // If request contains json-encoded 'data'-param, decode it and append to request params
        $this->handleData();

        // Pick session
        $this->_session = (new Zend_Session_Namespace('user'))->data;
    }

    /**
     *
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

        // Get [id => dataTypeId] pairs for each record, representing availability
        // of a certain attribute datatype in certain TermCollection
        $dataTypeIdA = $this->entity->db->getAdapter()->query('
            SELECT `id`, `dataTypeId` FROM `terms_collection_attribute_datatype` WHERE `collectionId` = ?'
            , $_['collectionId']['id'])->fetchAll(PDO::FETCH_KEY_PAIR);

        // Flush those pairs
        $this->view->assign([
            'success' => true,
            'dataTypeIdA' => $dataTypeIdA,
        ]);
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

        // Validate collectionId-param and dataTypeId-param
        $_ = $this->jcheck([
            'collectionId' => [
                'req' => true,                                                      // required
                'rex' => 'int11',                                                   // regular expression preset key or raw expression
                'key' => 'LEK_languageresources',                                   // points to existing record in a given db table
            ],
            'dataTypeId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => 'terms_attributes_datatype'
            ]
        ]);

        // Init new record
        $this->entity->init([
            'collectionId' => $_['collectionId']['id'],
            'dataTypeId'   => $_['dataTypeId']['id'],
        ]);

        // Save
        $this->entity->save();

        // Flush success
        $this->jflush(true, ['mappingId' => $this->entity->getId()]);
    }

    /**
     *
     */
    public function putAction() {
        throw new BadMethodCallException();
    }

    /**
     * Delete attribute
     *
     * @throws ZfExtended_Mismatch
     */
    public function deleteAction() {

        // Validate mappingId-param
        $this->jcheck([
            'mappingId' => [
                'req' => true,                                                      // required
                'rex' => 'int11',                                                   // regular expression preset key or raw expression
                'key' => $this->entity,
            ],
        ]);

        // Delete mapping
        $this->entity->delete();

        // Flush response
        $this->jflush(true, ['mappingId' => 0]);
    }
}
