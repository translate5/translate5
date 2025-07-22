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

namespace MittagQI\Translate5\LanguageResource;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Models_Task as Task;
use Zend_Exception;
use Zend_Registry;

class QueryDurationLogger
{
    /**
     * @var self[]
     */
    private static array $allLoggers = [];

    private float $start = 0;

    private float $sum = 0;

    private float $sumFromCache = 0;

    private int $queryCount = 0;

    private int $queryCountFromCache = 0;

    public function __construct(
        private readonly LanguageResource $languageResource
    ) {
        self::$allLoggers[] = $this;
    }

    /**
     * @phpstan-param array{
     *      task: Task,
     *      workerId: int,
     *  }&array<string, mixed> $extraData at least this fields for optimal logging
     * @throws Zend_Exception
     */
    public static function logFromWorker(string $message, array $extraData): void
    {
        $logger = Zend_Registry::get('logger')->cloneMe('editor.languageresource.durations');
        foreach (self::$allLoggers as $queryDurationLog) {
            if ($queryDurationLog->queryCount > 0 || $queryDurationLog->queryCountFromCache > 0) {
                $logger->info(
                    'E1732',
                    $message,
                    array_merge($queryDurationLog->getLogData(), $extraData)
                );
            }
        }
    }

    public function startQuery(): void
    {
        $this->start = microtime(true);
    }

    public function stopQuery(bool $fromCache = false): void
    {
        $duration = microtime(true) - $this->start;
        if ($fromCache) {
            $this->queryCountFromCache++;
            $this->sumFromCache += $duration;
        } else {
            $this->queryCount++;
            $this->sum += $duration;
        }
    }

    /**
     * @return array{
     *     id: int,
     *     resource: string,
     *     queryCount: int,
     *     queryCountFromCache: int,
     *     sumFromCache: float,
     *     sum: float,
     * }
     */
    public function getLogData(): array
    {
        return [
            'id' => (int) $this->languageResource->getId(),
            'resource' => $this->languageResource->getResourceId(),
            'queryCount' => $this->queryCount,
            'queryCountFromCache' => $this->queryCountFromCache,
            'sumFromCache' => round($this->sumFromCache, 4),
            'sum' => round($this->sum, 4),
        ];
    }
}
