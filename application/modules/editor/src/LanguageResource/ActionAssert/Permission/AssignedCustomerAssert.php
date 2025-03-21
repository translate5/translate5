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

namespace MittagQI\Translate5\LanguageResource\ActionAssert\Permission;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\PermissionAssertContext;
use MittagQI\Translate5\LanguageResource\ActionAssert\LanguageResourceAction;
use MittagQI\Translate5\LanguageResource\Exception\NoAccessToLanguageResourceException;
use MittagQI\Translate5\Repository\LanguageResourceRepository;

/**
 * @implements PermissionAssertInterface<LanguageResourceAction, LanguageResource>
 */
final class AssignedCustomerAssert implements PermissionAssertInterface
{
    public function __construct(
        private readonly LanguageResourceRepository $languageResourceRepository
    ) {
    }

    public static function create(): self
    {
        return new self(
            new LanguageResourceRepository(),
        );
    }

    public function supports(\BackedEnum $action): bool
    {
        return true;
    }

    /**
     * @throws NoAccessToLanguageResourceException
     */
    public function assertGranted(\BackedEnum $action, object $object, PermissionAssertContext $context): void
    {
        if (! $context->actor->isClientRestricted()) {
            return;
        }

        $customerIs = $context->actor->getCustomersArray();

        if (! $this->languageResourceRepository->isAssociatedWithOneOfCustomers((int) $object->getId(), ...$customerIs)) {
            throw new NoAccessToLanguageResourceException((int) $object->getId());
        }
    }
}
