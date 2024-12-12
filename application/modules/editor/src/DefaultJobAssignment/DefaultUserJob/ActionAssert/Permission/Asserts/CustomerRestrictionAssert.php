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

declare(strict_types=1);

namespace MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\ActionAssert\Permission\Asserts;

use BackedEnum;
use editor_Models_UserAssocDefault as DefaultUserJob;
use MittagQI\Translate5\ActionAssert\Permission\ActionPermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\Customer\ActionAssert\CustomerAction;
use MittagQI\Translate5\Customer\ActionAssert\CustomerActionPermissionAssert;
use MittagQI\Translate5\Customer\Exception\InexistentCustomerException;
use MittagQI\Translate5\DefaultJobAssignment\DefaultJobAction;
use MittagQI\Translate5\DefaultJobAssignment\DefaultUserJob\ActionAssert\Permission\Exception\NoAccessToDefaultUserJobException;
use MittagQI\Translate5\Repository\CustomerRepository;

/**
 * @implements PermissionAssertInterface<DefaultJobAction, DefaultUserJob>
 */
class CustomerRestrictionAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly ActionPermissionAssertInterface $customerPermissionAssert,
        private readonly CustomerRepository $customerRepository,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(
            CustomerActionPermissionAssert::create(),
            CustomerRepository::create(),
        );
    }

    public function supports(BackedEnum $action): bool
    {
        return true;
    }

    public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
    {
        if ($object->getUserGuid() === $context->actor->getUserGuid() && DefaultJobAction::Read === $action) {
            return;
        }

        try {
            $customer = $this->customerRepository->get((int) $object->getCustomerId());

            $this->customerPermissionAssert->assertGranted(CustomerAction::DefaultJob, $customer, $context);
        } catch (PermissionExceptionInterface|InexistentCustomerException) {
            throw new NoAccessToDefaultUserJobException($object);
        }
    }
}
