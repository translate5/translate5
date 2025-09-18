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

namespace MittagQI\Translate5\T5Memory\Api;

use GuzzleHttp\Exception\ConnectException;
use Http\Client\Exception\HttpException;
use JsonException;
use MittagQI\Translate5\HTTP\ClientFactory;
use MittagQI\Translate5\Integration\SegmentUpdate\UpdateSegmentDTO;
use MittagQI\Translate5\T5Memory\Api\Contract\DeletesMemoryInterface;
use MittagQI\Translate5\T5Memory\Api\Contract\FetchesStatusInterface;
use MittagQI\Translate5\T5Memory\Api\Contract\HasVersionInterface;
use MittagQI\Translate5\T5Memory\Api\Contract\ProvidesMemoryListInterface;
use MittagQI\Translate5\T5Memory\Api\Contract\ResponseExceptionInterface;
use MittagQI\Translate5\T5Memory\Api\Contract\SavesTmsInterface;
use MittagQI\Translate5\T5Memory\Api\Exception\InvalidResponseStructureException;
use MittagQI\Translate5\T5Memory\Api\Exception\SegmentTooLongException;
use MittagQI\Translate5\T5Memory\Api\Exception\UnsuccessfulRequestException;
use MittagQI\Translate5\T5Memory\Api\Request\CreateEmptyTmRequest;
use MittagQI\Translate5\T5Memory\Api\Request\CreateTmRequest;
use MittagQI\Translate5\T5Memory\Api\Request\DeleteTmRequest;
use MittagQI\Translate5\T5Memory\Api\Request\DownloadTmRequest;
use MittagQI\Translate5\T5Memory\Api\Request\DownloadTmxChunkRequest;
use MittagQI\Translate5\T5Memory\Api\Request\FlushTmRequest;
use MittagQI\Translate5\T5Memory\Api\Request\GetEntryRequest;
use MittagQI\Translate5\T5Memory\Api\Request\ImportTmxRequest;
use MittagQI\Translate5\T5Memory\Api\Request\MemoryListRequest;
use MittagQI\Translate5\T5Memory\Api\Request\ReorganizeRequest;
use MittagQI\Translate5\T5Memory\Api\Request\ResourcesRequest;
use MittagQI\Translate5\T5Memory\Api\Request\SaveTmsRequest;
use MittagQI\Translate5\T5Memory\Api\Request\StatusRequest;
use MittagQI\Translate5\T5Memory\Api\Request\UpdateRequest;
use MittagQI\Translate5\T5Memory\Api\Response\CreateTmResponse;
use MittagQI\Translate5\T5Memory\Api\Response\DownloadTmResponse;
use MittagQI\Translate5\T5Memory\Api\Response\DownloadTmxChunkResponse;
use MittagQI\Translate5\T5Memory\Api\Response\GetEntryResponse;
use MittagQI\Translate5\T5Memory\Api\Response\ImportStatusResponse;
use MittagQI\Translate5\T5Memory\Api\Response\MemoryListResponse;
use MittagQI\Translate5\T5Memory\Api\Response\ResourcesResponse;
use MittagQI\Translate5\T5Memory\Api\Response\Response;
use MittagQI\Translate5\T5Memory\Api\Response\StatusResponse;
use MittagQI\Translate5\T5Memory\Api\Response\UpdateResponse;
use MittagQI\Translate5\T5Memory\DTO\ImportOptions;
use MittagQI\Translate5\T5Memory\DTO\ReorganizeOptions;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class T5MemoryApi implements HasVersionInterface, FetchesStatusInterface, SavesTmsInterface, ProvidesMemoryListInterface, DeletesMemoryInterface
{
    /**
     * @var string[]
     */
    private array $versions = [];

    public function __construct(
        private readonly ClientInterface $client,
        private readonly SegmentLengthValidator $segmentLengthValidator,
    ) {
    }

    public static function create(): self
    {
        $factory = ClientFactory::create();
        $httpClient = new RetryClient($factory->createClient([]));

        return new self(
            $httpClient,
            SegmentLengthValidator::create(),
        );
    }

    public function ping(string $baseUrl): bool
    {
        try {
            $response = $this->client->sendRequest(new ResourcesRequest($baseUrl));
        } catch (ConnectException) {
            return false;
        }

        return $response->getStatusCode() === 200;
    }

    public function version(string $baseUrl): string
    {
        if (isset($this->versions[$baseUrl])) {
            return $this->versions[$baseUrl];
        }

        $response = $this->client->sendRequest(new ResourcesRequest($baseUrl));

        try {
            $this->versions[$baseUrl] = ResourcesResponse::fromResponse($response)->version;

            return $this->versions[$baseUrl];
        } catch (ResponseExceptionInterface $exception) {
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

    public function getMemories(string $baseUrl): MemoryListResponse
    {
        $response = $this->client->sendRequest(new MemoryListRequest($baseUrl));

        return MemoryListResponse::fromResponse($response);
    }

    public function deleteTm(string $baseUrl, string $tmName): void
    {
        $response = $this->client->sendRequest(new DeleteTmRequest($baseUrl, $tmName));

        if (200 === $response->getStatusCode()) {
            return;
        }

        $content = $response->getBody()->getContents();

        if (500 === $response->getStatusCode() && str_contains($content, 'not found(error 48)')) {
            // This is a known error when trying to delete a TM that does not exist.
            return;
        }

        $response->getBody()->rewind();

        throw new UnsuccessfulRequestException($response);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws HttpException
     */
    public function reorganizeTm(string $baseUrl, string $tmName, ReorganizeOptions $reorganizeOptions): void
    {
        $request = new ReorganizeRequest($baseUrl, $tmName, $reorganizeOptions->saveDifferentTargetsForSameSource);
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
        ImportOptions $importOptions,
    ): Response {
        $stream = $this->getStreamFromFile($filePath);
        $filename = basename($filePath);

        $request = new ImportTmxRequest(
            $baseUrl,
            $tmName,
            $filename,
            $stream,
            $importOptions->stripFramingTags,
            $importOptions->saveDifferentTargetsForSameSource
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
     * @throws SegmentTooLongException
     */
    public function update(
        string $baseUrl,
        string $tmName,
        UpdateSegmentDTO $dto,
        string $sourceLang,
        string $targetLang,
        bool $saveDifferentTargetsForSameSource,
        bool $save2disk = true,
    ): UpdateResponse {
        $this->segmentLengthValidator->validate($dto->source);
        $this->segmentLengthValidator->validate($dto->target);

        $request = new UpdateRequest(
            $baseUrl,
            $tmName,
            $dto,
            $sourceLang,
            $targetLang,
            $saveDifferentTargetsForSameSource,
            $save2disk,
        );
        $response = $this->sendRequest($request);

        return UpdateResponse::fromResponse($response);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function getEntry(
        string $baseUrl,
        string $tmName,
        string $internalKey,
    ): GetEntryResponse {
        $parts = explode(':', $internalKey);
        $request = new GetEntryRequest(
            $baseUrl,
            $tmName,
            $parts[0],
            $parts[1] ?? 0,
        );
        $response = $this->sendRequest($request);

        return GetEntryResponse::fromResponse($response);
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

    /**
     * @throws HttpException
     */
    protected function throwExceptionOnNotSuccessfulResponse(
        ResponseInterface $response,
        RequestInterface $request,
    ): void {
        if ($response->getStatusCode() !== 200) {
            $body = (string) $response->getBody();
            $error = $response->getReasonPhrase();

            if (! empty($body)) {
                try {
                    $content = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

                    $lastStatusInfo = $content['ErrorMsg']
                        ?? $content['importErrorMsg']
                        ?? $content['reorganizeErrorMsg']
                        ?? null;

                    if (null !== $lastStatusInfo) {
                        $error = $lastStatusInfo;
                    }
                } catch (JsonException $e) {
                    $error = $e->getMessage();
                }
            }

            $response->getBody()->rewind();

            throw new HttpException($error, $request, $response);
        }
    }

    protected function tryParseResponseAsJson(ResponseInterface $response): array
    {
        try {
            return json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
    }

    protected function isTimeoutErrorOccurred(ResponseInterface $response): bool
    {
        if ($response->getStatusCode() === 200) {
            return false;
        }

        $data = $this->tryParseResponseAsJson($response);

        return 506 === (int) ($data['ReturnValue'] ?? 0);
    }

    protected function sanitizeTmName(string $tmName): string
    {
        return str_replace('+', '-plus-', $tmName);
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

    /**
     * @throws ClientExceptionInterface
     */
    private function sendRequest(RequestInterface $request): ResponseInterface
    {
        $response = $this->client->sendRequest($request);

        $this->throwExceptionOnNotSuccessfulResponse($response, $request);

        return $response;
    }
}
