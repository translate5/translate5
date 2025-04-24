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

use editor_Models_Db_LanguageResources_LanguageResource;
use editor_Models_Db_Task;
use editor_Models_Task as Task;
use MittagQI\Translate5\LanguageResource\Db\TaskAssociation as TaskAssociationDb;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\LanguageResource\TaskTm\Db\TaskTmTaskAssociation as TaskTmTaskAssociationDb;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;

class LanguageResourceTaskAssocRepository
{
    private const MATCH_ANALYSIS_STATUS = 'matchanalysis';

    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public static function create(): self
    {
        return new self(Zend_Db_Table::getDefaultAdapter());
    }

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
        $s = $this->db->select()
            ->from(
                [
                    'taskAssoc' => TaskAssociationDb::TABLE_NAME,
                ]
            )
            ->join(
                [
                    'languageResource' => editor_Models_Db_LanguageResources_LanguageResource::TABLE_NAME,
                ],
                'taskAssoc.languageResourceId = ' . 'languageResource.id',
                []
            )
            ->joinLeft(
                [
                    'ttm1' => TaskTmTaskAssociationDb::TABLE,
                ],
                'taskAssoc.languageResourceId = ttm1.languageResourceId',
                'IF(ISNULL(ttm1.id), 0, 1) AS isTaskTm'
            )
            ->joinLeft(
                [
                    'ttm2' => TaskTmTaskAssociationDb::TABLE,
                ],
                'taskAssoc.taskGuid = ttm2.taskGuid AND taskAssoc.languageResourceId = ttm2.languageResourceId',
                'IF(ISNULL(ttm2.id), 0, 1) AS isOriginalTaskTm'
            )
            ->where('taskAssoc.taskGuid = ?', $taskGuid);

        return (array) $this->db->fetchAll($s);
    }

    public function hasImportingAssociatedTasks(int $languageResourceId): bool
    {
        $s = $this->db->select()
            ->from(
                [
                    'assocs' => TaskAssociationDb::TABLE_NAME,
                ],
                [
                    'COUNT(*)',
                ]
            )
            ->join(
                [
                    'task' => editor_Models_Db_Task::TABLE_NAME,
                ],
                'assocs.taskGuid = task.taskGuid',
                []
            )
            ->where('assocs.languageResourceId = ?', $languageResourceId)
            ->where('task.state = ?', Task::STATE_IMPORT)
            ->group('assocs.id');

        return (int) $this->db->fetchOne($s) > 0;
    }

    public function hasAssociatedTasksInMatchAnalysisState(int $languageResourceId): bool
    {
        $s = $this->db->select()
            ->from(
                [
                    'assocs' => TaskAssociationDb::TABLE_NAME,
                ],
                [
                    'COUNT(*)',
                ]
            )
            ->join(
                [
                    'task' => editor_Models_Db_Task::TABLE_NAME,
                ],
                'assocs.taskGuid = task.taskGuid',
                []
            )
            ->where('assocs.languageResourceId = ?', $languageResourceId)
            ->where('task.state = ?', self::MATCH_ANALYSIS_STATUS)
            ->group('assocs.id');

        return (int) $this->db->fetchOne($s) > 0;
    }
}
