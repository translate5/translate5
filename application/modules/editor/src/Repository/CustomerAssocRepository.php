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

namespace MittagQI\Translate5\Repository;

use editor_Models_LanguageResources_CustomerAssoc as CustomerAssoc;
use Zend_Db_Table_Row;
use ZfExtended_Models_Entity_NotFoundException;

class CustomerAssocRepository
{
    public function delete(CustomerAssoc $assoc): void
    {
        $assoc->delete();
    }

    public function save(CustomerAssoc $assoc): void
    {
        $assoc->save();
    }

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
        $customerAssoc = new CustomerAssoc();

        $select = $customerAssoc->db
            ->select()
            ->where('languageResourceId = ?', $languageResourceId)
            ->where('customerId = ?', $customerId)
        ;

        $row = $customerAssoc->db->fetchRow($select);

        if (null === $row) {
            return null;
        }

        $customerAssoc->init($row);

        return $customerAssoc;
    }

    /**
     * @return iterable<CustomerAssoc>
     */
    public function getByLanguageResource(int $languageResourceId): iterable
    {
        $customerAssoc = new CustomerAssoc();

        foreach ($customerAssoc->loadByLanguageResourceId($languageResourceId) as $row) {
            $customerAssoc->init(
                new Zend_Db_Table_Row(
                    [
                        'table' => $customerAssoc->db,
                        'data' => $row,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            yield clone $customerAssoc;
        }
    }

    /**
     * @return iterable<CustomerAssoc>
     */
    public function getByCustomer(int $customerId): iterable
    {
        $customerAssoc = new CustomerAssoc();
        $s = $customerAssoc->db->select();
        $s->where('customerId = ?', $customerId);

        foreach ($customerAssoc->db->fetchAll($s)->toArray() as $row) {
            $customerAssoc->init(
                new Zend_Db_Table_Row(
                    [
                        'table' => $customerAssoc->db,
                        'data' => $row,
                        'stored' => true,
                        'readOnly' => false,
                    ]
                )
            );

            yield clone $customerAssoc;
        }
    }
}
