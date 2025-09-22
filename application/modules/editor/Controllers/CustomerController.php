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

use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\Customer\ActionAssert\CustomerActionPermissionAssert;
use MittagQI\Translate5\Customer\CustomerService;
use MittagQI\Translate5\Repository\UserRepository;
use MittagQI\Translate5\Test\Enums\TestUser;

class Editor_CustomerController extends ZfExtended_RestController
{
    protected $entityClass = editor_Models_Customer_Customer::class;

    /**
     * @var editor_Models_Customer_Customer
     */
    protected $entity;

    /**
     * The download-actions need to be csrf unprotected!
     */
    protected array $_unprotectedActions = ['exportresource'];

    private CustomerActionPermissionAssert $permissionAssert;

    public function init()
    {
        parent::init();
        //add context of valid export formats:
        //resourceLogExport
        $this->_helper->getHelper('contextSwitch')->addContext('resourceLogExport', [
            'headers' => [
                'Content-Type' => 'application/zip',
            ],
        ])->addActionContext('exportresource', 'resourceLogExport')->initContext();

        $this->permissionAssert = CustomerActionPermissionAssert::create();
    }

    public function indexAction()
    {
        $userRepository = new UserRepository();
        $authUser = $userRepository->get(ZfExtended_Authentication::getInstance()->getUserId());

        $customerModel = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);
        $rows = $this->entity->loadAll();
        $context = new PermissionAssertContext($authUser);

        foreach ($rows as $key => $row) {
            $customerModel->init(
                new Zend_Db_Table_Row(
                    [
                        'table' => $customerModel->db,
                        'data' => $row,
                        'stored' => true,
                        'readOnly' => true,
                    ]
                )
            );

            try {
                $this->permissionAssert->assertGranted(CustomerAction::Read, $customerModel, $context);
            } catch (PermissionExceptionInterface) {
                unset($rows[$key]);

                continue;
            }
        }

        $this->view->rows = array_values($rows);
        $this->view->total = count($rows);
    }

    public function postAction()
    {
        try {
            parent::postAction();
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            $this->handleDuplicate($e);
        }
    }

    public function putAction()
    {
        try {
            parent::putAction();
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e) {
            $this->handleDuplicate($e);
        }
    }

    public function deleteAction()
    {
        ZfExtended_Models_Entity_Conflict::addCodes([
            'E1745' => 'The default client can not be deleted',
            'E1047' => 'A client cannot be deleted as long as tasks are assigned to this client.',
        ], 'editor.customer');

        try {
            $this->entityLoad();
            $this->processClientReferenceVersion();

            if ($this->entity->isDefaultCustomer()) {
                throw new ZfExtended_Models_Entity_Conflict('E1745');
            }

            CustomerService::create()->delete($this->entity);
        } catch (ZfExtended_Models_Entity_Exceptions_IntegrityConstraint) {
            throw new ZfExtended_Models_Entity_Conflict('E1047');
        }
    }

    /***
     * Export language resources usage as excel document
     */
    public function exportresourceAction()
    {
        $customerId = $this->getRequest()->getParam('customerId', null);

        $context = $this->_helper->getHelper('contextSwitch')->getCurrentContext();
        //if json is requested, return only the data
        if ($context == 'json') {
            //INFO: this is currently only available for api testing
            $this->setupTextExportResourcesLogData($customerId);

            return;
        }
        $export = ZfExtended_Factory::get('editor_Models_LanguageResources_UsageExporter');
        /* @var $export editor_Models_LanguageResources_UsageExporter */
        $taskType = $this->getRequest()->getParam('taskType', null);
        if (! empty($taskType)) {
            $taskType = explode(',', $taskType);
            $export->setDocumentTaskType($taskType);
        }
        if ($export->excel($customerId)) {
            $t = ZfExtended_Zendoverwrites_Translate::getInstance();
            $this->view->result = $t->_("Es wurden keine Ergebnisse gefunden");
        }
    }

    public function copyOperation()
    {
        $copy = ZfExtended_Factory::get('editor_Models_Customer_CopyCustomer');
        /** @var editor_Models_Customer_CopyCustomer $copy */

        $source = $this->getParam('copyDefaultAssignmentsCustomer', false);
        if (! empty($source)) {
            $copy->copyUserAssoc($source, $this->entity->getId());
        }

        $source = $this->getParam('copyConfigCustomer', false);
        if (! empty($source)) {
            $copy->copyConfig($source, $this->entity->getId());
        }
    }

    /**
     * @throws Zend_Exception
     */
    protected function decodePutData()
    {
        parent::decodePutData();
        $this->handleDomainField();
        $this->handleDefaultOpenIdLableText();
    }

    /**
     * Handle the domain field from the post/put request data.
     */
    protected function handleDomainField(): void
    {
        if (! isset($this->data->domain)) {
            return;
        }
        //because it is uniqe key, do not allow empty value
        if (empty($this->data->domain)) {
            $this->data->domain = null;

            return;
        }
        //add always / at the end of the url
        if (! str_ends_with($this->data->domain, '/')) {
            $this->data->domain .= '/';
        }

        //remove always the protocol if it is provided by the api or frontend
        $disallowed = ['http://', 'https://'];
        foreach ($disallowed as $d) {
            if (str_starts_with($this->data->domain, $d)) {
                $this->data->domain = str_replace($d, '', $this->data->domain);
            }
        }
    }

    /**
     * Set default text for the "login with SSO" button
     * @throws Zend_Exception
     */
    protected function handleDefaultOpenIdLableText(): void
    {
        $openIdRedirectCheckbox = $this->data->openIdRedirectCheckbox ?? null;
        if (is_null($openIdRedirectCheckbox) || (bool) $openIdRedirectCheckbox === true) {
            return;
        }
        if (empty($this->data->openIdRedirectLabel)) {
            $t = ZfExtended_Zendoverwrites_Translate::getInstance();
            $this->data->openIdRedirectLabel = $t->_('Single Sign-on');
        }
    }

    /**
     * Protect the default customer from being edited or deleted.
     * @throws Zend_Exception
     */
    protected function entityLoad(): void
    {
        parent::entityLoad();
        if ($this->isModificationRequest() && $this->entity->isDefaultCustomer()) {
            $this->decodePutData();
            $this->preventDefaultCustomerModification();
        }
    }

    /**
     * Internal handler for duplicated entity message
     * @param Zend_Db_Statement_Exception $e
     * @throws Zend_Db_Statement_Exception
     */
    protected function handleDuplicate(ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey $e)
    {
        if ($e->isInMessage('domain_UNIQUE')) {
            ZfExtended_UnprocessableEntity::addCodes([
                'E1104' => 'This domain is already in use.',
            ], 'editor.customer');

            throw ZfExtended_UnprocessableEntity::createResponse('E1104', [
                'domain' => [
                    'duplicateDomain' => 'Diese Domain wird bereits verwendet.',
                ],
            ]);
        }

        ZfExtended_UnprocessableEntity::addCodes([
            'E1063' => 'The given client-number is already in use.',
        ], 'editor.customer');

        throw ZfExtended_UnprocessableEntity::createResponse('E1063', [
            'number' => [
                'duplicateClientNumber' => 'Diese Kundennummer wird bereits verwendet.',
            ],
        ]);
    }

    /***
     * Set the resources log data for the current export request. If the request is from non test user, this will throw
     * an exception.
     * @param int $customerId
     */
    protected function setupTextExportResourcesLogData(int $customerId = null)
    {
        $allowed = [TestUser::TestManager->value, TestUser::TestApiUser->value];
        if (! in_array(ZfExtended_Authentication::getInstance()->getLogin(), $allowed)) {
            throw new ZfExtended_Models_Entity_NoAccessException('The current user is not alowed to use the resources log export data.');
        }
        $export = ZfExtended_Factory::get('editor_Models_LanguageResources_UsageExporter');
        /* @var $export editor_Models_LanguageResources_UsageExporter */
        $taskType = $this->getRequest()->getParam('taskType', null);
        if (! empty($taskType)) {
            $taskType = explode(',', $taskType);
            $export->setDocumentTaskType($taskType);
        }
        $this->view->rows = $export->getExportRawDataTests($customerId);
    }

    /**
     * It is modification request if it is PUT or DELETE request
     */
    protected function isModificationRequest(): bool
    {
        return $this->_request->isPut() || $this->_request->isDelete();
    }

    /**
     * Check if field modification is allowed for the default customer
     *
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    protected function preventDefaultCustomerModification(): void
    {
        $blacklistedPutDefaultCustomerFields = [
            'number',
            'name',
        ];

        foreach ($blacklistedPutDefaultCustomerFields as $field) {
            if (isset($this->data->$field)) {
                throw new ZfExtended_Models_Entity_NoAccessException(
                    'The ' . $field . ' of the default client can not be edited.'
                );
            }
        }
    }
}
