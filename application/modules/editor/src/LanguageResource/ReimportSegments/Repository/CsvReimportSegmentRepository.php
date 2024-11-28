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

namespace MittagQI\Translate5\LanguageResource\ReimportSegments\Repository;

use MittagQI\Translate5\LanguageResource\Adapter\UpdateSegmentDTO;

class CsvReimportSegmentRepository implements ReimportSegmentRepositoryInterface
{
    public function save(string $runId, UpdateSegmentDTO $dto): void
    {
        $filename = $this->getFileName($runId, $dto->taskGuid);
        file_put_contents($filename, $this->toCsvString($dto) . PHP_EOL, FILE_APPEND);
    }

    public function getByTask(string $runId, string $taskGuid): iterable
    {
        $filename = $this->getFileName($runId, $taskGuid);

        $file = fopen($filename, 'r');

        while (($line = fgets($file)) !== false) {
            yield $this->fromCsvString($line);
        }

        fclose($file);
    }

    public function cleanByTask(string $runId, string $taskGuid): void
    {
        $filename = $this->getFileName($runId, $taskGuid);
        unlink($filename);
    }

    private function getFileName(string $runId, string $taskGuid): string
    {
        $dir = APPLICATION_DATA . '/ReimportSegments/';

        if (! is_dir($dir)) {
            $oldMask = umask(0);
            mkdir($dir, recursive: true);
            umask($oldMask);
        }

        $fileName = $dir . $runId . '_' . trim($taskGuid, '{}') . '.csv';

        if (! file_exists($fileName)) {
            touch($fileName);
        }

        return $fileName;
    }

    private function toCsvString(UpdateSegmentDTO $dto): string
    {
        return implode(',', [
            $dto->taskGuid,
            $dto->segmentId,
            $this->escape($dto->source),
            $this->escape($dto->target),
            $dto->fileName,
            $dto->timestamp,
            $dto->userName,
            $this->escape($dto->context),
        ]);
    }

    private function fromCsvString(string $csvString): UpdateSegmentDTO
    {
        $data = str_getcsv($csvString);

        return new UpdateSegmentDTO(
            taskGuid: $data[0],
            segmentId: (int) $data[1],
            source: $this->unescape($data[2]),
            target: $this->unescape($data[3]),
            fileName: $data[4],
            timestamp: $data[5],
            userName: $data[6],
            context: $this->unescape($data[7]),
        );
    }

    private function escape(string $string): string
    {
        return str_replace(',', '\,', $string);
    }

    private function unescape(string $string): string
    {
        return str_replace('\,', ',', $string);
    }
}
