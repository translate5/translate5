<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api;

use Http\Client\Exception\HttpException;
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
        RequestInterface $request
    ): void {
        if ($response->getStatusCode() !== 200) {
            throw new HttpException($response->getReasonPhrase(), $request, $response);
        }
    }
}
