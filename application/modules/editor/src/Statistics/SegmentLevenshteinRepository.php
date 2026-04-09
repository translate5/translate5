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

namespace MittagQI\Translate5\Statistics;

use MittagQI\Translate5\Statistics\Dto\LevenshteinHistoryDTO;
use MittagQI\Translate5\Statistics\Dto\SegmentLevenshteinDTO;
use Zend_Db_Adapter_Abstract;
use Zend_Db_Table;

class SegmentLevenshteinRepository
{
    public const string TABLE_NAME = 'LEK_segment_statistics';

    public function __construct(
        private readonly Zend_Db_Adapter_Abstract $db,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Db_Table::getDefaultAdapter(),
        );
    }

    public function insertBatch(string $taskGuid, array $data): void
    {
        // ATTENTION: since stat data are pure integers we do not bind them.
        // That in combination with buffering (outside) increases insert speed enormously!
        $sql = 'INSERT IGNORE INTO ' . self::TABLE_NAME . '
            (taskGuid, segmentId, historyId, levenshteinOriginal, levenshteinPrevious, segmentlengthPrevious)
            VALUES ' . join(', ', array_map(function (LevenshteinHistoryDTO $levenshteinHistoryDTO) {
            return '(:taskGuid, ' . $levenshteinHistoryDTO->segmentId . ', ' .
                ($levenshteinHistoryDTO->historyId ?? 0) . ', ' .
            $levenshteinHistoryDTO->levenshteinOriginal . ', ' .
            $levenshteinHistoryDTO->levenshteinPrevious . ', ' .
            $levenshteinHistoryDTO->segmentlengthPrevious . ')';
        }, $data));
        $this->db->query($sql, [
            'taskGuid' => $taskGuid,
        ]);
    }

    public function upsert(SegmentLevenshteinDTO $dto): void
    {
        $sql = 'INSERT INTO ' . self::TABLE_NAME . '
            (taskGuid, segmentId, historyId, levenshteinOriginal, levenshteinPrevious, segmentlengthPrevious)
            VALUES (:taskGuid, :segmentId, :historyId, :levenshteinOriginal,
            :levenshteinPrevious, :segmentlengthPrevious)
            ON DUPLICATE KEY UPDATE
                levenshteinOriginal = VALUES(levenshteinOriginal),
                levenshteinPrevious = VALUES(levenshteinPrevious),
                segmentlengthPrevious = VALUES(segmentlengthPrevious)';

        $this->db->query($sql, [
            'taskGuid' => $dto->taskGuid,
            'segmentId' => $dto->segmentId,
            'historyId' => $dto->historyId,
            'levenshteinOriginal' => $dto->levenshteinOriginal,
            'levenshteinPrevious' => $dto->levenshteinPrevious,
            'segmentlengthPrevious' => $dto->segmentlengthPrevious,
        ]);
    }

    public function removeByTaskGuid(string $taskGuid): void
    {
        $this->db->query(
            'DELETE FROM ' . self::TABLE_NAME . ' WHERE taskGuid = :taskGuid',
            [
                'taskGuid' => $taskGuid,
            ]
        );
    }

    public function getCurrentBySegmentId(int $segmentId): ?SegmentLevenshteinDTO
    {
        $row = $this->db->fetchRow(
            'SELECT
                taskGuid,
                segmentId,
                historyId,
                levenshteinOriginal,
                levenshteinPrevious,
                segmentlengthPrevious
             FROM ' . self::TABLE_NAME . '
             WHERE segmentId = :segmentId AND historyId = 0
             LIMIT 1',
            [
                'segmentId' => $segmentId,
            ]
        );

        if (empty($row)) {
            return null;
        }

        return $this->fromDbRow($row);
    }

    /**
     * @return SegmentLevenshteinDTO[]
     */
    public function getBySegmentId(int $segmentId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT
                taskGuid,
                segmentId,
                historyId,
                levenshteinOriginal,
                levenshteinPrevious,
                segmentlengthPrevious
             FROM ' . self::TABLE_NAME . '
             WHERE segmentId = :segmentId',
            [
                'segmentId' => $segmentId,
            ]
        );

        if (empty($rows)) {
            return [];
        }

        return array_map($this->fromDbRow(...), $rows);
    }

    /**
     * @param array{
     *   taskGuid:string,
     *   segmentId:int|string,
     *   historyId:int|string,
     *   levenshteinOriginal:int|string,
     *   levenshteinPrevious:int|string,
     *   segmentlengthPrevious:int|string
     * } $row
     */
    private function fromDbRow(array $row): SegmentLevenshteinDTO
    {
        return new SegmentLevenshteinDTO(
            (string) $row['taskGuid'],
            (int) $row['segmentId'],
            (int) $row['historyId'],
            (int) $row['levenshteinOriginal'],
            (int) $row['levenshteinPrevious'],
            (int) $row['segmentlengthPrevious'],
        );
    }
}
