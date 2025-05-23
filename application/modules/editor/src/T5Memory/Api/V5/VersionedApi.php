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

namespace MittagQI\Translate5\T5Memory\Api\V5;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Http\Client\Exception\HttpException;
use MittagQI\Translate5\T5Memory\Api\AbstractVersionedApi;
use MittagQI\Translate5\T5Memory\Api\Exception\InvalidResponseStructureException;
use MittagQI\Translate5\T5Memory\Api\Response\MutationResponse;
use MittagQI\Translate5\T5Memory\Api\V5\Request\CreateEmptyTmRequest;
use MittagQI\Translate5\T5Memory\Api\V5\Request\CreateTmRequest;
use MittagQI\Translate5\T5Memory\Api\V5\Request\DownloadTmRequest;
use MittagQI\Translate5\T5Memory\Api\V5\Request\DownloadTmxRequest;
use MittagQI\Translate5\T5Memory\Api\V5\Request\ImportRequest;
use MittagQI\Translate5\T5Memory\Api\V5\Request\ReorganizeRequest;
use MittagQI\Translate5\T5Memory\Api\V5\Response\CreateTmResponse;
use MittagQI\Translate5\T5Memory\Api\V5\Response\DownloadTmResponse;
use MittagQI\Translate5\T5Memory\Api\V5\Response\DownloadTmxResponse;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;
use PharIo\Version\VersionConstraint;
use PharIo\Version\VersionConstraintParser;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamInterface;

class VersionedApi extends AbstractVersionedApi
{
    public const VERSION = '^0.4 || ^0.5';

    public function __construct(
        private ClientInterface $client
    ) {
    }

    /**
     * @throws ClientExceptionInterface
     * @throws HttpException
     */
    public function import(
        string $baseUrl,
        string $tmName,
        string $tmxData,
        StripFramingTags $stripFramingTags,
    ): MutationResponse {
        $request = new ImportRequest($baseUrl, $tmName, $tmxData, $stripFramingTags);

        if ($this->client instanceof Client) {
            $response = $this->client->send($request, [
                RequestOptions::TIMEOUT => 1200,
            ]);
        } else {
            $response = $this->client->sendRequest($request);
        }

        return MutationResponse::fromResponse($response);
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
    public function createTm(string $baseUrl, string $tmName, string $sourceLang, string $tmxData): CreateTmResponse
    {
        $request = new CreateTmRequest($baseUrl, $this->sanitizeTmName($tmName), $sourceLang, $tmxData);
        $response = $this->client->sendRequest($request);

        $this->throwExceptionOnNotSuccessfulResponse($response, $request);

        return CreateTmResponse::fromResponse($response);
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
     * Stream has whole TMX.
     *
     * @throws ClientExceptionInterface
     * @throws InvalidResponseStructureException
     */
    public function getTmx(string $baseUrl, string $tmName): StreamInterface
    {
        $request = new DownloadTmxRequest($baseUrl, $tmName);
        $response = $this->client->sendRequest($request);

        $this->throwExceptionOnNotSuccessfulResponse($response, $request);

        return DownloadTmxResponse::fromResponse($response)->tmx;
    }

    /**
     * Stream has whole TM.
     *
     * @throws ClientExceptionInterface
     * @throws HttpException
     */
    public function getTm(string $baseUrl, string $tmName): StreamInterface
    {
        $request = new DownloadTmRequest($baseUrl, $tmName);
        $response = $this->client->sendRequest($request);

        $this->throwExceptionOnNotSuccessfulResponse($response, $request);

        return DownloadTmResponse::fromResponse($response)->tm;
    }

    protected static function supportedVersion(): VersionConstraint
    {
        return (new VersionConstraintParser())->parse(self::VERSION);
    }
}
