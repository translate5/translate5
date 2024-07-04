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

namespace MittagQI\Translate5\LanguageResource\CustomerAssoc;

use editor_Models_LanguageResources_CustomerAssoc as CustomerAssoc;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class CustomerAssocRepository
{
    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function getByLanguageResourceAndCustomer(int $languageResourceId, int $customerId): CustomerAssoc
    {
        $customerAssoc = $this->findByLanguageResourceAndCustomer($languageResourceId, $customerId);

        if (null === $customerAssoc) {
            throw new ZfExtended_Models_Entity_NotFoundException(
                sprintf(
                    'LanguageResourceCustomerAssoc with `languageResourceId` = %s and `customerId` = %s not found',
                    $languageResourceId,
                    $customerId
                )
            );
        }

        return $customerAssoc;
    }

    public function findByLanguageResourceAndCustomer(int $languageResourceId, int $customerId): ?CustomerAssoc
    {
        $customerAssoc = ZfExtended_Factory::get(CustomerAssoc::class);

        $select = $customerAssoc->db
            ->select()
            ->where('languageResourceId = ?', $languageResourceId)
            ->where('customerId = ?', $customerId)
        ;

        $row = $customerAssoc->db->fetchRow($select);

        if (null === $row) {
            return null;
        }

        $customerAssoc->hydrate($row);

        return $customerAssoc;
    }

    /**
     * @return iterable<CustomerAssoc>
     */
    public function getByLanguageResource(int $languageResourceId): iterable
    {
        $customerAssoc = ZfExtended_Factory::get(CustomerAssoc::class);

        foreach ($customerAssoc->loadByLanguageResourceId($languageResourceId) as $row) {
            $customerAssoc->hydrate($row);

            yield clone $customerAssoc;
        }
    }
}
