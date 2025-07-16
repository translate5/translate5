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
use MittagQI\Translate5\T5Memory\Api\Response\Response;
use MittagQI\Translate5\T5Memory\Api\V6\Request\CreateEmptyTmRequest;
use MittagQI\Translate5\T5Memory\Api\V6\Request\CreateTmRequest;
use MittagQI\Translate5\T5Memory\Api\V6\Request\DownloadTmRequest;
use MittagQI\Translate5\T5Memory\Api\V6\Request\DownloadTmxChunkRequest;
use MittagQI\Translate5\T5Memory\Api\V6\Request\FlushTmRequest;
use MittagQI\Translate5\T5Memory\Api\V6\Request\ImportTmxRequest;
use MittagQI\Translate5\T5Memory\Api\V6\Request\ReorganizeRequest;
use MittagQI\Translate5\T5Memory\Api\V6\Response\CreateTmResponse;
use MittagQI\Translate5\T5Memory\Api\V6\Response\DownloadTmResponse;
use MittagQI\Translate5\T5Memory\Api\V6\Response\DownloadTmxChunkResponse;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use PharIo\Version\VersionConstraint;
use PharIo\Version\VersionConstraintParser;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class VersionedApi extends AbstractVersionedApi
{
    public const VERSION = '^0.6 || ^0.7';

    public function __construct(
        private readonly ClientInterface $client,
    ) {
    }

    /**
     * @throws ClientExceptionInterface
     * @throws HttpException
     */
    public function reorganizeTm(string $baseUrl, string $tmName): void
    {
        $request = new ReorganizeRequest($baseUrl, $tmName);
        $response = $this->client->sendRequest($request);

        $this->throwExceptionOnNotSuccessfulResponse($response, $request);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws HttpException
     */
    public function importTmx(
        string $baseUrl,
        string $tmName,
        string $filePath,
        StripFramingTags $stripFramingTags,
    ): Response {
        $stream = $this->getStreamFromFile($filePath);
        $filename = basename($filePath);

        $request = new ImportTmxRequest(
            $baseUrl,
            $tmName,
            $filename,
            $stream,
            $stripFramingTags
        );

        $response = $this->client->sendRequest($request);

        fclose($stream);

        $this->throwExceptionOnNotSuccessfulResponse($response, $request);

        return Response::fromResponse($response);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws HttpException
     */
    public function flush(string $baseUrl, string $tmName): Response
    {
        $request = new FlushTmRequest($baseUrl, $tmName);
        $response = $this->client->sendRequest($request);

        $this->throwExceptionOnNotSuccessfulResponse($response, $request);

        return Response::fromResponse($response);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws HttpException
     */
    public function createEmptyTm(string $baseUrl, string $tmName, string $sourceLang): CreateTmResponse
    {
        $request = new CreateEmptyTmRequest($baseUrl, $this->sanitizeTmName($tmName), $sourceLang);
        $response = $this->client->sendRequest($request);

        $this->throwExceptionOnNotSuccessfulResponse($response, $request);

        return CreateTmResponse::fromResponse($response);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws HttpException
     */
    public function createTm(
        string $baseUrl,
        string $tmName,
        string $sourceLang,
        string $filePath,
        StripFramingTags $stripFramingTags,
    ): CreateTmResponse {
        $stream = $this->getStreamFromFile($filePath);

        $request = new CreateTmRequest(
            $baseUrl,
            $this->sanitizeTmName($tmName),
            $sourceLang,
            $stream,
            $stripFramingTags,
        );

        $response = $this->client->sendRequest($request);

        fclose($stream);

        $this->throwExceptionOnNotSuccessfulResponse($response, $request);

        return CreateTmResponse::fromResponse($response);
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
        $timeElapsed = 0;

        while (true) {
            $request = new DownloadTmxChunkRequest($baseUrl, $tmName, $chunkSize, $startFromInternalKey);
            $response = $this->client->sendRequest($request);

            // Retry on timeout error if not the last retry
            if ($this->isTimeoutErrorOccurred($response) && $timeElapsed < $this->getMaxWaitingTimeForALockSeconds()) {
                sleep($this->getRetryDelaySeconds());
                $timeElapsed += $this->getRetryDelaySeconds();

                continue;
            }

            $this->throwExceptionOnNotSuccessfulResponse($response, $request);

            return DownloadTmxChunkResponse::fromResponse($response, $startFromInternalKey);
        }
    }

    /**
     * @throws RuntimeException
     * @return resource
     */
    private function getStreamFromFile(string $filePath)
    {
        $stream = fopen($filePath, 'r');

        if (false === $stream) {
            throw new RuntimeException('Could not open file: ' . $filePath);
        }

        $bom = fread($stream, 2);

        rewind($stream);

        // Check for BOM indicating UTF-16 BE or LE
        if ($bom === "\xFE\xFF" || $bom === "\xFF\xFE") {
            $tmpFile = $filePath . bin2hex(random_bytes(2));
            $outputHandle = fopen($tmpFile, 'w');

            $from = $bom === "\xFE\xFF" ? 'UTF-16' : 'UTF-16LE';

            while (! feof($stream)) {
                $chunk = fread($stream, 4096); // Read in chunks
                if ($chunk === false) {
                    break;
                }

                // Convert the chunk from UTF-16 to UTF-8
                $utf8Chunk = mb_convert_encoding($chunk, 'UTF-8', $from);
                fwrite($outputHandle, $utf8Chunk);
            }

            fclose($stream);
            unlink($filePath);
            fclose($outputHandle);

            rename($tmpFile, $filePath);

            $stream = fopen($filePath, 'r');
        }

        return $stream;
    }

    protected static function supportedVersion(): VersionConstraint
    {
        return (new VersionConstraintParser())->parse(self::VERSION);
    }

    private function getMaxWaitingTimeForALockSeconds(): int
    {
        // 1 hour max waiting time
        return 3600;
    }

    private function getRetryDelaySeconds(): int
    {
        return 2;
    }
}
