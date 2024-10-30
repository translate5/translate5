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

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\LanguageResource\TaskTm\TaskTmTaskAssociation;
use ZfExtended_Factory;

class LanguageResourceTaskAssocRepository
{
    /**
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws \Zend_Db_Statement_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     */
    public function save(TaskAssociation $taskAssociation): void
    {
        $taskAssociation->save();
    }

    public function getAllByTaskGuid(string $taskGuid): array
    {
        $db = ZfExtended_Factory::get(TaskAssociation::class)->db;
        $languageResource = ZfExtended_Factory::get(LanguageResource::class)->db->info($db::NAME);
        $taskTmTable = ZfExtended_Factory::get(TaskTmTaskAssociation::class)->db->info($db::NAME);

        $s = $db->select()
            ->from(
                [
                    'taskAssoc' => $db->info($db::NAME),
                ]
            )
            ->setIntegrityCheck(false)
            ->join(
                [
                    'languageResource' => $languageResource,
                ],
                'taskAssoc.languageResourceId = ' . 'languageResource.id',
                []
            )
            ->joinLeft(
                [
                    'ttm1' => $taskTmTable,
                ],
                'taskAssoc.languageResourceId = ttm1.languageResourceId',
                'IF(ISNULL(ttm1.id), 0, 1) AS isTaskTm'
            )
            ->joinLeft(
                [
                    'ttm2' => $taskTmTable,
                ],
                'taskAssoc.taskGuid = ttm2.taskGuid AND taskAssoc.languageResourceId = ttm2.languageResourceId',
                'IF(ISNULL(ttm2.id), 0, 1) AS isOriginalTaskTm'
            )
            ->where('taskAssoc.taskGuid = ?', $taskGuid);

        return $db->fetchAll($s)->toArray();
    }
}
