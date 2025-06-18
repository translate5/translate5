<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2025 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Service\Hotfolder;

use editor_Models_Customer_Customer as Customer;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\Filesystem\FilesystemFactory;
use MittagQI\Translate5\Repository\CustomerRepository;
use MittagQI\Translate5\Repository\UserRepository;
use Zend_Config;
use Zend_Registry;
use ZfExtended_ErrorCodeException;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User as User;

abstract class ProjectManagerProviderBase
{
    final public function __construct(
        private readonly UserRepository $userRepository,
        private readonly CustomerRepository $customerRepository,
        protected readonly Zend_Config $systemConfig,
    ) {
    }

    public static function create(): static
    {
        return new static(
            new UserRepository(),
            new CustomerRepository(),
            Zend_Registry::get('config'),
        );
    }

    public function getByGuid(string $guid): User
    {
        return $this->userRepository->getByGuid($guid);
    }

    public function getFallbackPm(string $key): User
    {
        if (FilesystemFactory::DEFAULT_HOST_LABEL === $key) {
            return $this->getDefaultPm();
        }

        $customer = $this->getCustomerByKey($key);

        return $this->findCustomerPm($customer) ?: $this->getDefaultPm();
    }

    public function getCustomerOrDefaultPm(Customer $customer): User
    {
        return $this->findCustomerPm($customer) ?: $this->getDefaultPm();
    }

    abstract protected function getDefaultPmConfigName(): string;

    abstract protected function getDefaultHostLabel(): string;

    abstract protected function getDefaultPmId(): ?string;

    abstract protected function createException(): ZfExtended_ErrorCodeException;

    private function getDefaultPm(): User
    {
        $defaultPmId = $this->getDefaultPmId();
        $exception = $this->createException();

        if (! is_numeric($defaultPmId)) {
            throw $exception;
        }

        try {
            return $this->userRepository->get((int) $defaultPmId);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw $exception;
        }
    }

    private function findCustomerPm(Customer $customer): ?User
    {
        $customerDefaultPm = $this->customerRepository->getConfigValue(
            (int) $customer->getId(),
            $this->getDefaultPmConfigName()
        );

        if (null === $customerDefaultPm) {
            return null;
        }

        try {
            return $this->userRepository->get((int) $customerDefaultPm);
        } catch (ZfExtended_Models_Entity_NotFoundException) {
            throw $this->createException();
        }
    }

    private function getCustomerByKey(string $filesystemKey): Customer
    {
        if ($this->getDefaultHostLabel() === $filesystemKey) {
            return $this->customerRepository->getDefaultCustomer();
        }

        return $this->customerRepository->get((int) base64_decode($filesystemKey));
    }
}
