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

namespace MittagQI\Translate5\LanguageResource\TaskTm\Repository;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\LanguageResource\TaskTm\TaskTmTaskAssociation;
use Zend_Db_Table_Row;
use ZfExtended_Factory;

class TaskTmRepository
{
    public function hasWritableOfType(string $taskGuid, string $serviceType): bool
    {
        $db = ZfExtended_Factory::get(TaskAssociation::class)->db;
        $languageResourceAssocTable = ZfExtended_Factory::get(TaskAssociation::class)->db->info($db::NAME);
        $languageResourceTable = ZfExtended_Factory::get(LanguageResource::class)->db->info($db::NAME);
        $s = $db->select()
            ->from([
                'lrt' => $languageResourceAssocTable,
            ], [
                'count' => 'COUNT(*)',
            ])
            ->setIntegrityCheck(false)
            ->joinInner([
                'lr' => $languageResourceTable,
            ], 'lrt.languageResourceId = lr.id', [])
            ->where('lrt.taskGuid = ?', $taskGuid)
            ->where('lr.serviceType = ?', $serviceType)
            ->where('lrt.segmentsUpdateable = ?', 1)
        ;

        /** @var null|object{count: int} $row */
        $row = $db->fetchRow($s);

        return 0 !== (int) $row?->count;
    }

    public function findOfTypeCreatedForTask(string $taskGuid, string $serviceType): ?LanguageResource
    {
        $languageResource = ZfExtended_Factory::get(LanguageResource::class);
        $db = $languageResource->db;
        $s = $db->select()
            ->from(
                [
                    'lr' => $db->info($db::NAME),
                ]
            )
            ->joinLeft(
                [
                    'ttmt' => ZfExtended_Factory::get(TaskTmTaskAssociation::class)->db->info($db::NAME),
                ],
                'ttmt.languageResourceId = lr.id',
                []
            )
            ->where('lr.serviceType = ?', $serviceType)
            ->where('ttmt.taskGuid = ?', $taskGuid)
        ;

        $row = $db->fetchRow($s);

        if (null === $row) {
            return null;
        }

        $languageResource->init(
            new Zend_Db_Table_Row(
                [
                    'table' => $db,
                    'data' => $row->toArray(),
                    'stored' => true,
                    'readOnly' => false,
                ]
            )
        );

        return $languageResource;
    }

    /**
     * @return iterable<LanguageResource>
     */
    public function getAllCreatedForTask(string $taskGuid): iterable
    {
        $db = ZfExtended_Factory::get(LanguageResource::class)->db;
        $s = $db->select()
            ->from(
                [
                    'lr' => $db->info($db::NAME),
                ]
            )
            ->joinLeft(
                [
                    'ttmt' => ZfExtended_Factory::get(TaskTmTaskAssociation::class)->db->info($db::NAME),
                ],
                'ttmt.languageResourceId = lr.id',
                []
            )
            ->where('ttmt.taskGuid = ?', $taskGuid)
        ;

        $rows = $db->fetchAll($s);

        $languageResource = ZfExtended_Factory::get(LanguageResource::class);

        foreach ($rows as $row) {
            $languageResource->init($row);

            yield clone $languageResource;
        }
    }

    /**
     * @return iterable<LanguageResource>
     */
    public function getOfTypeAssociatedToTask(
        string $taskGuid,
        string $serviceType,
        bool $onlyWritable = false
    ): iterable {
        $db = ZfExtended_Factory::get(LanguageResource::class)->db;
        $s = $db->select()
            ->from(
                [
                    'lr' => $db->info($db::NAME),
                ]
            )
            ->joinLeft(
                [
                    'ta' => ZfExtended_Factory::get(TaskAssociation::class)->db->info($db::NAME),
                ],
                'ta.languageResourceId = lr.id',
                []
            )
            ->joinLeft(
                [
                    'ttmt' => ZfExtended_Factory::get(TaskTmTaskAssociation::class)->db->info($db::NAME),
                ],
                'ttmt.languageResourceId = lr.id',
                []
            )
            ->where('ta.taskGuid = ?', $taskGuid)
            ->where('lr.serviceType = ?', $serviceType)
            ->where('ttmt.id IS NOT NULL')
        ;

        if ($onlyWritable) {
            $s->where('ta.segmentsUpdateable = ?', 1);
        }

        $rows = $db->fetchAll($s);

        $languageResource = ZfExtended_Factory::get(LanguageResource::class);

        foreach ($rows as $row) {
            $languageResource->init($row);

            yield clone $languageResource;
        }
    }

    public function getIdsOfTypeAssociatedToTask(string $taskGuid, string $serviceType): array
    {
        $db = ZfExtended_Factory::get(LanguageResource::class)->db;
        $s = $db->select()
            ->from(
                [
                    'lr' => $db->info($db::NAME),
                ],
                ['id']
            )
            ->joinLeft(
                [
                    'ta' => ZfExtended_Factory::get(TaskAssociation::class)->db->info($db::NAME),
                ],
                'ta.languageResourceId = lr.id',
                []
            )
            ->joinLeft(
                [
                    'ttmt' => ZfExtended_Factory::get(TaskTmTaskAssociation::class)->db->info($db::NAME),
                ],
                'ttmt.languageResourceId = lr.id',
                []
            )
            ->where('ta.taskGuid = ?', $taskGuid)
            ->where('lr.serviceType = ?', $serviceType)
            ->where('ttmt.id IS NOT NULL')
        ;

        $rows = $db->fetchAll($s)->toArray();

        return array_map('intval', array_column($rows, 'id'));
    }
}
