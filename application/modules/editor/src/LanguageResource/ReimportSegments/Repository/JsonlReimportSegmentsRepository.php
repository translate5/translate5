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

use MittagQI\Translate5\LanguageResource\ReimportSegments\ReimportSegmentDTO;

class JsonlReimportSegmentsRepository implements ReimportSegmentRepositoryInterface
{
    public const DIRECTORY = 'ReimportSegments';

    public function save(string $runId, ReimportSegmentDTO $dto): void
    {
        $filename = $this->getFileName($runId, $dto->taskGuid);
        file_put_contents(
            $filename,
            json_encode($this->toArray($dto), JSON_THROW_ON_ERROR) . PHP_EOL,
            FILE_APPEND
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getByTask(string $runId, string $taskGuid): iterable
    {
        $filename = $this->getFileName($runId, $taskGuid);

        foreach (file($filename) as $line) {
            yield $this->fromArray(json_decode($line, true, 512, JSON_THROW_ON_ERROR));
        }
    }

    public function cleanByTask(string $runId, string $taskGuid): void
    {
        $filename = $this->getFileName($runId, $taskGuid);
        unlink($filename);
    }

    private function toArray(ReimportSegmentDTO $dto): array
    {
        return [
            $dto->taskGuid,
            $dto->segmentId,
            $dto->source,
            $dto->target,
            $dto->fileName,
            $dto->timestamp,
            $dto->userName,
            $dto->context,
        ];
    }

    private function fromArray(array $data): ReimportSegmentDTO
    {
        return new ReimportSegmentDTO(
            taskGuid: $data[0],
            segmentId: (int) $data[1],
            source: $data[2],
            target: $data[3],
            fileName: $data[4],
            timestamp: $data[5],
            userName: $data[6],
            context: $data[7],
        );
    }

    private function getFileName(string $runId, string $taskGuid)
    {
        $dir = $this->getDirectory();

        if (! is_dir($dir)) {
            $oldMask = umask(0);

            if (! mkdir($dir, recursive: true) && ! is_dir($dir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }

            umask($oldMask);
        }

        $fileName = $this->generateFileName($dir, $runId, $taskGuid, '.jsonl');

        if (! file_exists($fileName)) {
            touch($fileName);
        }

        return $fileName;
    }

    private function getDirectory(): string
    {
        return APPLICATION_DATA . '/' . self::DIRECTORY . '/';
    }

    private function generateFileName(string $dir, string $runId, string $taskGuid, string $extension): string
    {
        return $dir . $runId . '_' . trim($taskGuid, '{}') . $extension;
    }
}
