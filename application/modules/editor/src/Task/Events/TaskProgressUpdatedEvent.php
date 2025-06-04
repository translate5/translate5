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

namespace MittagQI\Translate5\Task\Events;

class TaskProgressUpdatedEvent
{
    /**
     * @param array<string, int> $userEditable
     * @param array<string, int> $userFinished
     */
    public function __construct(
        public readonly string $taskGuid,
        public readonly int $taskEditable,
        public readonly int $taskFinished,
        public readonly int|float $taskProgress,
        public readonly array|int|null $userEditable,
        public readonly array|int|null $userFinished,
        public readonly int|float|bool|null $userProgress,
    ) {
    }

    public static function fromArray(string $taskGuid, array $progress): self
    {
        return new self(
            $taskGuid,
            $progress['taskEditable'],
            $progress['taskFinished'],
            $progress['taskProgress'],
            $progress['userEditable'],
            $progress['userFinished'],
            $progress['userProgress'] ?? null,
        );
    }

    /**
     * @return array{
     *     taskEditable: int,
     *     taskFinished: int,
     *     taskProgress: int,
     *     userEditable: array<string, int>,
     *     userFinished: array<string, int>,
     *     userProgress: int|float|bool|null
     * }
     */
    public function getProgress(): array
    {
        return [
            'taskEditable' => $this->taskEditable,
            'taskFinished' => $this->taskFinished,
            'taskProgress' => $this->taskProgress,
            'userEditable' => $this->userEditable,
            'userFinished' => $this->userFinished,
            'userProgress' => $this->userProgress,
        ];
    }
}
