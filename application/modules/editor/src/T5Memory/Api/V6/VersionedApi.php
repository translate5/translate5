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

namespace MittagQI\Translate5\T5Memory\Api\V6;

use Http\Client\Exception\HttpException;
use MittagQI\Translate5\T5Memory\Api\AbstractVersionedApi;
use MittagQI\Translate5\T5Memory\Api\Exception\InvalidResponseStructureException;
use MittagQI\Translate5\T5Memory\Api\V6\Request\DownloadTmRequest;
use MittagQI\Translate5\T5Memory\Api\V6\Request\DownloadTmxChunkRequest;
use MittagQI\Translate5\T5Memory\Api\V6\Response\DownloadTmResponse;
use MittagQI\Translate5\T5Memory\Api\V6\Response\DownloadTmxChunkResponse;
use PharIo\Version\VersionConstraint;
use PharIo\Version\VersionConstraintParser;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;

class VersionedApi extends AbstractVersionedApi
{
    public const VERSION = '^0.6';

    public function __construct(
        private ClientInterface $client
    ) {
    }

    /**
     * Stream has whole TM.
     *
     * @throws ClientExceptionInterface
     * @throws HttpException
     */
    public function downloadTm(string $baseUrl, string $tmName): StreamInterface
    {
        $request = new DownloadTmRequest($baseUrl, $tmName);
        $response = $this->client->sendRequest($request);

        $this->throwExceptionOnNotSuccessfulResponse($response, $request);

        return DownloadTmResponse::fromResponse($response)->tm;
    }

    /**
     * Stream has the XML chunk of the TMX.
     *
     * @return iterable<StreamInterface>
     *
     * @throws ClientExceptionInterface
     * @throws InvalidResponseStructureException
     * @throws HttpException
     */
    public function downloadTmx(string $baseUrl, string $tmName, int $chunkSize): iterable
    {
        $chunk = $this->fetchChunk($baseUrl, $tmName, $chunkSize);

        yield $chunk->chunk;

        while ($chunk->nextInternalKey !== null) {
            $chunk = $this->fetchChunk($baseUrl, $tmName, $chunkSize, $chunk->nextInternalKey);

            yield $chunk->chunk;
        }
    }

    /**
     * @throws ClientExceptionInterface
     * @throws InvalidResponseStructureException
     * @throws HttpException
     */
    private function fetchChunk(
        string $baseUrl,
        string $tmName,
        int $chunkSize,
        ?string $startFromInternalKey = null,
    ): DownloadTmxChunkResponse {
        $request = new DownloadTmxChunkRequest($baseUrl, $tmName, $chunkSize, $startFromInternalKey);
        $response = $this->client->sendRequest($request);

        $this->throwExceptionOnNotSuccessfulResponse($response, $request);

        return DownloadTmxChunkResponse::fromResponse($response, $startFromInternalKey);
    }

    protected static function supportedVersion(): VersionConstraint
    {
        return (new VersionConstraintParser())->parse(self::VERSION);
    }
}
