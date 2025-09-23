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

namespace MittagQI\Translate5\T5Memory\Api;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Pool;
use MittagQI\Translate5\T5Memory\Api\Contract\PoolAsyncClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class RetryClient implements ClientInterface, PoolAsyncClientInterface
{
    private const RETRY_DELAY_SECONDS = 5;

    private const MAX_ELAPSED_TIME = 60;

    public function __construct(
        private readonly ClientInterface & \GuzzleHttp\ClientInterface $client,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $timeElapsed = 0;
        while (true) {
            try {
                return $this->client->sendRequest($request);
            } catch (ClientExceptionInterface $e) {
                if ($timeElapsed >= self::MAX_ELAPSED_TIME) {
                    throw $e;
                }

                if ($this->isRetryable($e)) {
                    sleep(self::RETRY_DELAY_SECONDS);
                    $timeElapsed += self::RETRY_DELAY_SECONDS;

                    continue;
                }

                throw $e;
            }
        }
    }

    public function poolAsync(
        array $requests,
        int $concurrency = 10,
        array $perRequestOptions = []
    ): array {
        $responses = [];
        $finalFailures = [];

        $pending = [];
        foreach ($requests as $i => $req) {
            $pending[$i] = $req;
        }

        $timeElapsed = 0;
        // Main retry loop
        while (! empty($pending)) {
            // Build a fixed snapshot for this attempt
            $batch = $pending;
            $rejections = []; // temp rejected reasons this round

            $pool = new Pool(
                $this->client,
                (function () use ($batch, $perRequestOptions) {
                    foreach ($batch as $idx => $req) {
                        // IMPORTANT for requests with bodies (POST/PUT/PATCH):
                        // Ensure body is at position 0 before re-sending.
                        $body = $req->getBody();
                        if ($body->isSeekable()) {
                            $body->rewind();
                        }

                        $opts = $perRequestOptions[$idx] ?? [];
                        yield function () use ($req, $opts) {
                            return $this->client->sendAsync($req, $opts);
                        };
                    }
                })(),
                [
                    'concurrency' => $concurrency,
                    'fulfilled' => function (ResponseInterface $res, int $iterIndex) use ($batch, &$responses) {
                        // Map iterator order back to original index
                        $origIndex = array_keys($batch)[$iterIndex];
                        $responses[$origIndex] = $res;
                    },
                    'rejected' => function (Throwable $reason, int $iterIndex) use ($batch, &$rejections) {
                        $origIndex = array_keys($batch)[$iterIndex];
                        $rejections[$origIndex] = $reason;
                    },
                ]
            );

            $pool->promise()->wait();

            // Clear pending, refill with retryable failures; move non-retryables to finalFailures
            $pending = [];

            foreach ($batch as $idx => $req) {
                if (isset($responses[$idx])) {
                    continue; // succeeded this round
                }

                $reason = $rejections[$idx] ?? new \RuntimeException('Unknown rejection');

                $retryable = $this->isRetryable($reason);

                if ($retryable && $timeElapsed < self::MAX_ELAPSED_TIME) {
                    // Requeue with incremented attempt
                    $pending[$idx] = $req;
                } else {
                    // Give up (non-retryable or out of attempts)
                    $finalFailures[$idx] = $reason;
                }
            }

            // Backoff before next round if we still have pending
            if (! empty($pending)) {
                sleep(self::RETRY_DELAY_SECONDS);
                $timeElapsed += self::RETRY_DELAY_SECONDS;
            }
        }

        return [
            'responses' => $responses,
            'failures' => $finalFailures,
        ];
    }

    private function isRetryable(Throwable $e): bool
    {
        // Network / connect / timeout
        if ($e instanceof ConnectException) {
            return true;
        }

        if (! $e instanceof ClientExceptionInterface) {
            return false;
        }

        return str_contains($e->getMessage(), 'cURL error 6') // Could not resolve host
            || str_contains($e->getMessage(), 'cURL error 7') // Failed to connect to host
            || str_contains($e->getMessage(), 'cURL error 56') // Recv failure
        ;
    }
}
