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

namespace MittagQI\Translate5\T5Memory;

use MittagQI\Translate5\T5Memory\Enum\WaitCallState;
use Zend_Config;
use Zend_Registry;

class RetryService
{
    public function __construct(
        private readonly Zend_Config $config,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Registry::get('config'),
        );
    }

    /**
     * @template T
     * @param callable(): array{WaitCallState, T} $callback
     * @return T|null
     */
    public function callAwaiting(callable $callback, bool $canWaitLong = false)
    {
        $elapsedTime = 0;
        $maxWaitingTime = $this->getMaxWaitingTimeSeconds($canWaitLong);
        $state = WaitCallState::Retry;
        $result = null;

        while ($elapsedTime < $maxWaitingTime && $state === WaitCallState::Retry) {
            sleep($this->getRetryDelaySeconds());
            $elapsedTime += $this->getRetryDelaySeconds();

            [$state, $result] = $callback();
        }

        return $result;
    }

    public function getMaxRequestRetries(): int
    {
        return (int) $this->config->runtimeOptions->LanguageResources->t5memory->requestMaxRetries;
    }

    public function getRetryDelaySeconds(): int
    {
        return (int) $this->config->runtimeOptions->LanguageResources->t5memory->requestRetryDelaySeconds;
    }

    public function getMaxWaitingTimeSeconds(bool $canWaitLong = false): int
    {
        if ($canWaitLong || $this->canWaitLongTaskFinish()) {
            // 1 hour max waiting time
            return 3600;
        }

        return $this->getRetryDelaySeconds() * $this->getMaxRequestRetries();
    }

    /**
     * Shows if connector can wait for a long-running task (e.g. reorganize, import, export) to finish before do
     * fuzzy requests or other operations.
     */
    public function canWaitLongTaskFinish(): bool
    {
        // This should be moved to config, but this requires refactoring of the connectors
        // and introducing a connector factory instead of manager as it is implemented at the moment
        return defined('ZFEXTENDED_IS_WORKER_THREAD');
    }
}
