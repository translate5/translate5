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

namespace MittagQI\Translate5\ActionAssert\Permission;

use BackedEnum;
use MittagQI\Translate5\ActionAssert\Permission\Asserts\PermissionAssertInterface;
use MittagQI\Translate5\ActionAssert\Permission\Exception\PermissionExceptionInterface;

/**
 * @template A of BackedEnum
 * @template T of object
 * @implements ActionPermissionAssertInterface<A, T>
 */
abstract class ActionPermissionAssert implements ActionPermissionAssertInterface
{
    /**
     * @param PermissionAssertInterface[] $asserts
     */
    public function __construct(
        private readonly iterable $asserts,
    ) {
    }

    /**
     * @param A $action
     */
    public function assertGranted(BackedEnum $action, object $object, PermissionAssertContext $context): void
    {
        $atLeastOneAssertionMade = false;

        foreach ($this->asserts as $assert) {
            if (! $assert->supports($action)) {
                continue;
            }

            $atLeastOneAssertionMade = true;

            $assert->assertGranted($action, $object, $context);
        }

        if (! $atLeastOneAssertionMade) {
            throw new \RuntimeException('No assertion made for action ' . $action->value);
        }
    }

    /**
     * @param A $action
     */
    public function isGranted(BackedEnum $action, object $object, PermissionAssertContext $context): bool
    {
        try {
            $this->assertGranted($action, $object, $context);

            return true;
        } catch (PermissionExceptionInterface) {
            return false;
        }
    }
}
