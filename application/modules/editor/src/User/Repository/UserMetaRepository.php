<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\User\Repository;

use editor_Models_UserPreselection;
use ZfExtended_Authentication;
use ZfExtended_AuthenticationInterface;
use ZfExtended_Factory;

class UserMetaRepository
{
    public function __construct(
        private readonly editor_Models_UserPreselection $userPreselection,
        private readonly ZfExtended_AuthenticationInterface $authentication,
    ) {
    }

    public static function create(): self
    {
        return new self(
            ZfExtended_Factory::get(editor_Models_UserPreselection::class),
            ZfExtended_Authentication::getInstance(),
        );
    }

    /**
     * Save the default languages for file translation for the current user.
     * When the record for the user exists, it will be updated with the new values.
     *
     * @param int|null $source Source language ID (null to keep existing)
     * @param int|null $target Target language ID for single-language mode (null to keep existing)
     * @param int|null $customerId Customer ID (null to keep existing)
     * @param array|null $multiTargetSelections Array of "resourceId|langCode" values for multi-language mode (null to keep existing)
     */
    public function saveFileDefaultLanguages(
        ?int $source,
        ?int $target,
        ?int $customerId,
        ?array $multiTargetSelections = null,
    ): void {
        $userId = $this->authentication->getUserId();

        $this->userPreselection->loadOrSet($userId);

        if ($source !== null) {
            $this->userPreselection->setSourceLangFileDefault($source);
        }
        if ($target !== null) {
            $this->userPreselection->setTargetLangFileDefault($target);
        }
        if ($multiTargetSelections !== null) {
            $this->userPreselection->setTargetLangFileDefaultMulti(json_encode($multiTargetSelections));
        }
        if ($customerId !== null) {
            $this->userPreselection->setFileCustomerDefault($customerId);
        }

        $this->userPreselection->save();
    }
}
