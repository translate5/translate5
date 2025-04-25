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

use Http\Client\Exception\HttpException;
use JsonException;
use PharIo\Version\Version;
use PharIo\Version\VersionConstraint;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractVersionedApi
{
    abstract protected static function supportedVersion(): VersionConstraint;

    public static function isVersionSupported(string $version): bool
    {
        return static::supportedVersion()->complies(new Version($version));
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

                    $lastStatusInfo = $content->ErrorMsg
                        ?? $content->importErrorMsg
                        ?? $content->reorganizeErrorMsg
                        ?? null;

                    if (null !== $lastStatusInfo) {
                        $error = $lastStatusInfo;
                    }
                } catch (JsonException $e) {
                    $error = $e->getMessage();
                }
            }

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
}
