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

namespace MittagQI\Translate5\LanguageResource\CrossSynchronization;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use ZfExtended_Factory;

class CrossSynchronizationConnectionRepository
{
    public function getAllConnectionsRenderData(?int $filterLanguageResourceId): array
    {
        $db = ZfExtended_Factory::get(CrossSynchronizationConnection::class)->db;
        $lrTable = ZfExtended_Factory::get(LanguageResource::class)->db->info($db::NAME);

        $select = $db->select()
            ->setIntegrityCheck(false)
            ->from(
                ['LanguageResourceSync' => $db->info($db::NAME)],
                ['id', 'sourceLanguageResourceId', 'targetLanguageResourceId']
            )
            ->join(
                [
                    'LanguageResourceSource' => $lrTable,
                ],
                'LanguageResourceSync.sourceLanguageResourceId = LanguageResourceSource.id',
                [
                    'LanguageResourceSource.serviceName as sourceServiceName',
                    'LanguageResourceSource.name as sourceName'
                ]
            )
            ->join(
                [
                    'LanguageResourceTarget' => $lrTable,
                ],
                'LanguageResourceSync.targetLanguageResourceId = LanguageResourceTarget.id',
                [
                    'LanguageResourceTarget.serviceName as targetServiceName',
                    'LanguageResourceTarget.name as targetName'
                ]
            );

        if ($filterLanguageResourceId) {
            $select
                ->where('LanguageResourceSync.sourceLanguageResourceId = ?', $filterLanguageResourceId)
                ->orWhere('LanguageResourceSync.targetLanguageResourceId = ?', $filterLanguageResourceId);
        }

        return $db->fetchAll($select)->toArray();
    }

    public function getAllConnections(?int $filterLanguageResourceId): iterable
    {
        $syncModel = ZfExtended_Factory::get(CrossSynchronizationConnection::class);
        $db = $syncModel->db;

        $select = $db->select()
            ->setIntegrityCheck(false)
            ->from(['LanguageResourceSync' => $db->info($db::NAME)]);

        if ($filterLanguageResourceId) {
            $select
                ->where('LanguageResourceSync.sourceLanguageResourceId = ?', $filterLanguageResourceId)
                ->orWhere('LanguageResourceSync.targetLanguageResourceId = ?', $filterLanguageResourceId);
        }

        foreach ($db->fetchAll($select)->toArray() as $row) {
            $syncModel->hydrate($row);

            yield clone $syncModel;
        }
    }
}