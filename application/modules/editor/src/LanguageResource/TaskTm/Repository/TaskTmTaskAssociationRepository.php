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

use MittagQI\Translate5\LanguageResource\TaskTm\TaskTmTaskAssociation;
use Zend_Db_Table_Row;
use ZfExtended_Factory;

class TaskTmTaskAssociationRepository
{
    public function save(TaskTmTaskAssociation $taskAssociation): void
    {
        $taskAssociation->save();
    }

    public function delete(TaskTmTaskAssociation $taskAssociation): void
    {
        $taskAssociation->delete();
    }

    public function deleteByTaskGuidAndTm(string $taskGuid, $tmId): void
    {
        $db = ZfExtended_Factory::get(TaskTmTaskAssociation::class)->db;
        $db->getAdapter()->query(
            'DELETE FROM ' . $db->info($db::NAME) . ' WHERE taskGuid = ? AND languageResourceId = ?',
            [$taskGuid, $tmId]
        );
    }

    public function deleteByTaskGuid(string $taskGuid): void
    {
        $db = ZfExtended_Factory::get(TaskTmTaskAssociation::class)->db;
        $db->getAdapter()->query('DELETE FROM ' . $db->info($db::NAME) . ' WHERE taskGuid = ?', $taskGuid);
    }

    public function findByTaskAndTm(int $taskId, int $tmId): ?TaskTmTaskAssociation
    {
        $taskTmTaskAssociation = ZfExtended_Factory::get(TaskTmTaskAssociation::class);
        $db = $taskTmTaskAssociation->db;
        $s = $db->select()
            ->from($db->info($db::NAME))
            ->where('taskId = ?', $taskId)
            ->where('languageResourceId = ?', $tmId);

        $row = $db->fetchRow($s);

        if (null === $row) {
            return null;
        }

        $taskTmTaskAssociation->init(
            new Zend_Db_Table_Row(
                [
                    'table' => $db,
                    'data' => $row->toArray(),
                    'stored' => true,
                    'readOnly' => false,
                ]
            )
        );

        return $taskTmTaskAssociation;
    }
}
