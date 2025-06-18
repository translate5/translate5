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

use MittagQI\Translate5\HTTP\ClientFactory;
use MittagQI\Translate5\T5Memory\Api\Contract\FetchesStatusInterface;
use MittagQI\Translate5\T5Memory\Api\Contract\HasVersionInterface;
use MittagQI\Translate5\T5Memory\Api\Contract\ResponseExceptionInterface;
use MittagQI\Translate5\T5Memory\Api\Contract\SavesTmsInterface;
use MittagQI\Translate5\T5Memory\Api\Request\ResourcesRequest;
use MittagQI\Translate5\T5Memory\Api\Request\SaveTmsRequest;
use MittagQI\Translate5\T5Memory\Api\Request\StatusRequest;
use MittagQI\Translate5\T5Memory\Api\Response\ImportStatusResponse;
use MittagQI\Translate5\T5Memory\Api\Response\ResourcesResponse;
use MittagQI\Translate5\T5Memory\Api\Response\Response;
use MittagQI\Translate5\T5Memory\Api\Response\StatusResponse;
use Psr\Http\Client\ClientInterface;

class ConstantApi implements HasVersionInterface, FetchesStatusInterface, SavesTmsInterface
{
    /**
     * @var string[]
     */
    private array $versions = [];

    public function __construct(
        private ClientInterface $client,
    ) {
    }

    public static function create(): self
    {
        $factory = ClientFactory::create();
        $httpClient = new RetryClient($factory->createClient([]));

        return new self(
            $httpClient,
        );
    }

    public function ping(string $baseUrl): bool
    {
        $response = $this->client->sendRequest(new ResourcesRequest($baseUrl));

        return $response->getStatusCode() === 200;
    }

    public function version(string $baseUrl, bool $suppressExceptions = true): string
    {
        if (isset($this->versions[$baseUrl])) {
            return $this->versions[$baseUrl];
        }

        $response = $this->client->sendRequest(new ResourcesRequest($baseUrl));

        try {
            $this->versions[$baseUrl] = ResourcesResponse::fromResponse($response)->version;

            return $this->versions[$baseUrl];
        } catch (ResponseExceptionInterface $exception) {
            if ($suppressExceptions) {
                return self::FALLBACK_VERSION;
            }

            throw $exception;
        }
    }

    public function getStatus(string $baseUrl, string $tmName): StatusResponse
    {
        $response = $this->client->sendRequest(new StatusRequest($baseUrl, $tmName));

        return StatusResponse::fromResponse($response);
    }

    public function getImportStatus(string $baseUrl, string $tmName): ImportStatusResponse
    {
        $response = $this->client->sendRequest(new StatusRequest($baseUrl, $tmName));

        return ImportStatusResponse::fromResponse($response);
    }

    public function saveTms(string $baseUrl): Response
    {
        $response = $this->client->sendRequest(new SaveTmsRequest($baseUrl));

        return Response::fromResponse($response);
    }
}
