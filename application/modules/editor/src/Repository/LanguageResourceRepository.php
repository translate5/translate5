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
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use Zend_Db_Table_Row;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;

class LanguageResourceRepository
{
    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function get(int $id): LanguageResource
    {
        $languageResource = ZfExtended_Factory::get(LanguageResource::class);
        $languageResource->load($id);

        return $languageResource;
    }

    public function save(LanguageResource $lr): void
    {
        $lr->save();
    }

    public function languageResourceWithServiceNameAndCustomerIdExists(string $serviceName, int ...$customerIds): bool
    {
        if (empty($customerIds)) {
            return false;
        }

        $languageResource = ZfExtended_Factory::get(LanguageResource::class);
        $db = $languageResource->db;

        $lrCustomerTable = ZfExtended_Factory::get(CustomerAssoc::class)->db->info($db::NAME);

        $select = $db->select()
            ->setIntegrityCheck(false)
            ->from(
                [
                    'LanguageResources' => $db->info($db::NAME),
                ],
            )
            ->join(
                [
                    'CustomerAssoc' => $lrCustomerTable,
                ],
                'CustomerAssoc.languageResourceId = LanguageResources.id',
                []
            )
            ->where('CustomerAssoc.customerId IN (?)', $customerIds)
            ->where('LanguageResources.serviceName = ?', $serviceName)
            ->limit(1);

        return ! empty($db->fetchRow($select)?->toArray());
    }

    public function findOneByServiceNameAndCustomerId(string $serviceName, int $customerId): ?LanguageResource
    {
        $languageResource = ZfExtended_Factory::get(LanguageResource::class);
        $db = $languageResource->db;

        $lrCustomerTable = ZfExtended_Factory::get(CustomerAssoc::class)->db->info($db::NAME);

        $select = $db->select()
            ->setIntegrityCheck(false)
            ->from(
                [
                    'LanguageResources' => $db->info($db::NAME),
                ],
            )
            ->join(
                [
                    'CustomerAssoc' => $lrCustomerTable,
                ],
                'CustomerAssoc.languageResourceId = LanguageResources.id',
                []
            )
            ->where('CustomerAssoc.customerId = ?', $customerId)
            ->where('LanguageResources.serviceName = ?', $serviceName);

        $row = $db->fetchRow($select);

        if (empty($row?->toArray())) {
            return null;
        }

        $languageResource->init(
            new Zend_Db_Table_Row(
                [
                    'table' => $db,
                    'data' => $row,
                    'stored' => true,
                    'readOnly' => false,
                ]
            )
        );

        return $languageResource;
    }
}
