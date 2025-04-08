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

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RetryClient implements ClientInterface
{
    private const RETRY_DELAY_SECONDS = 5;

    private const MAX_ELAPSED_TIME = 60;

    public function __construct(
        private readonly ClientInterface $client,
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

                if (
                    str_contains($e->getMessage(), 'cURL error 6')
                    || str_contains($e->getMessage(), 'cURL error 7')
                    || str_contains($e->getMessage(), 'cURL error 56')
                ) {
                    sleep(self::RETRY_DELAY_SECONDS);
                    $timeElapsed += self::RETRY_DELAY_SECONDS;

                    continue;
                }

                throw $e;
            }
        }
    }
}
