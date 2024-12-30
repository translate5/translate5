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

use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\Customer\ActionAssert\CustomerActionPermissionAssert;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\UserRepository;

/**
 * Controller for the LanguageResources Associations
 */
class editor_LanguageresourcecustomerassocController extends ZfExtended_RestController
{
    use editor_Controllers_Traits_ControllerTrait;

    protected $entityClass = 'editor_Models_LanguageResources_CustomerAssoc';

    /**
     * @var editor_Models_LanguageResources_CustomerAssoc
     */
    protected $entity;

    /**
     * ignoring ID field for POST Requests
     * @var array
     */
    protected $postBlacklist = ['id'];

    private CustomerActionPermissionAssert $permissionAssert;

    private UserRepository $userRepository;

    private CustomerRepository $customerRepository;

    public function init()
    {
        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1050' => 'Referenced language resource not found.',
            'E1051' => 'Cannot remove language resource from task since task is used at the moment.',
        ], 'editor.languageresource.taskassoc');
        parent::init();

        $this->permissionAssert = CustomerActionPermissionAssert::create();
        $this->userRepository = new UserRepository();
        $this->customerRepository = new CustomerRepository();
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction()
    {
        $customerId = (int) $this->getParam('customerId');

        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserId());
        $context = new PermissionAssertContext($authUser);

        $customer = $this->customerRepository->get($customerId);

        $this->permissionAssert->assertGranted(CustomerAction::Read, $customer, $context);

        $this->view->rows = $this->entity->loadByCustomerId($customerId);
        $this->view->total = count($this->view->rows);
    }

    /**
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function postAction()
    {
        $customerId = (int) $this->getParam('customerId');
        $authUser = $this->userRepository->get(ZfExtended_Authentication::getInstance()->getUserId());
        $context = new PermissionAssertContext($authUser);

        $customer = $this->customerRepository->get($customerId);

        $this->permissionAssert->assertGranted(CustomerAction::Update, $customer, $context);

        $resourceId = (int) $this->getParam('languageResourceId');

        // Check customer access restriction
        $customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);
        $customer->load($customerId);
        $customer->checkClientRestriction();

        // Load assoc record, if exists for given $customerId and $resourceId
        $this->entity->loadRowByCustomerIdAndResourceId($customerId, $resourceId);

        // If not exists - init new
        if (! $this->entity->getId()) {
            $this->entity->init([
                'customerId' => $customerId,
                'languageResourceId' => $resourceId,
            ]);
        }

        // Setup penalty
        foreach (['penaltyGeneral', 'penaltySublang'] as $param) {
            if ($this->hasParam($param)) {
                $this->entity->{'set' . ucfirst($param)}(
                    $this->getParam($param)
                );
            }
        }

        // Save
        $this->entity->save();

        // Append assoc id to response
        $this->view->assocId = $this->entity->getId();
    }
}
