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
class editor_AttributedatatypeController extends ZfExtended_RestController
{
    /**
     * Use termportal trait
     */
    use editor_Controllers_Traits_TermportalTrait;

    /**
     * @var string
     */
    protected $entityClass = 'editor_Models_Terminology_Models_AttributeDataType';

    /**
     * @var editor_Models_Terminology_Models_AttributeDataType
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
    }

    /**
     * Get list of attibute datatypes
     */
    public function indexAction()
    {
        // Get ids of collections accessible to current user
        $collectionIds = ZfExtended_Factory
            ::get('editor_Models_TermCollection_TermCollection')
            ->getCollectionForAuthenticatedUser();

        if(empty($collectionIds)) {
            throw new ZfExtended_NotFoundException('No collections found for current user!');
        }

        // Get possible attribs as dataTypeId => info pairs
        $attribs = ZfExtended_Factory
            ::get('editor_Models_Terminology_Models_AttributeDataType')
            ->getLocalized($this->user()->getLocale(), $collectionIds);

        if(empty($attribs)) {
            throw new ZfExtended_NotFoundException('No attributes found!');
        }

        // Flush into response
        $this->view->assign($attribs);
    }

    /**
     *
     */
    public function getAction() {
        throw new BadMethodCallException();
    }

    /**
     * Delete attribute
     *
     * @throws ZfExtended_Mismatch
     */
    public function deleteAction() {
        throw new BadMethodCallException();
    }

    /**
     * Update attribute
     *
     * @throws ZfExtended_Mismatch
     */
    public function putAction() {

        // Validate params and load entity
        $this->jcheck([
            'dataTypeId' => [
                'req' => true,
                'rex' => 'int11',
                'key' => $this->entity
            ],
            'locale' => [
                'req' => true,
                'fis' => 'en,de'
            ],
            'label' => [
                'rex' => 'varchar255'
            ]
        ]);

        // Shortcuts
        $locale = $this->getParam('locale');
        $custom = json_decode($this->entity->getL10nCustom(), true);
        $customLabel  = $this->getParam('label');
        $systemLabel = json_decode($this->entity->getL10nSystem())->$locale;

        // Set label for current locale
        $custom[$locale] = $customLabel != $systemLabel ? $customLabel : '';

        // Decode back to json
        $this->entity->setL10nCustom(json_encode($custom));

        // Save
        $this->entity->save();

        // Flush label
        $this->view->assign(['label' => $customLabel ?: $systemLabel ?: $this->entity->getType()]);
    }
}
