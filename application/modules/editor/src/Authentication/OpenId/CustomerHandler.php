<?php

namespace MittagQI\Translate5\Authentication\OpenId;

use editor_Models_Customer_Customer;
use ReflectionException;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use Zend_Registry;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;

/**
 * This class handles the customer creation for the openid login. This class assumes that claimsCustomer field from the
 * sso provider do exist and is not empty.
 */
class CustomerHandler
{
    public function __construct(
        private string $claimsCustomerNumber
    ) {
    }

    /**
     * Creates a customer if it does not exist and returns the id of the customer
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey|Zend_Exception
     */
    public function handleCustomer(): int
    {
        $customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);

        try {
            $customer->loadByNumber($this->claimsCustomerNumber);
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            $customer->init([]);
            $customer->setNumber($this->claimsCustomerNumber);
            $customer->setName($this->claimsCustomerNumber);
            $customer->save();
            $log = Zend_Registry::get('logger')->cloneMe('core.openidconnect');
            $log->info('E1030', 'OpenID connect: Created customer {customer}', [
                'customer' => $this->claimsCustomerNumber,
            ]);
        }

        return $customer->getId();
    }
}
