<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory\Api\Response;

use GuzzleHttp\Utils;
use InvalidArgumentException;
use MittagQI\Translate5\T5Memory\Api\Exception\CorruptResponseBodyException;
use MittagQI\Translate5\T5Memory\Api\Exception\InvalidJsonInResponseBodyException;
use MittagQI\Translate5\T5Memory\Api\Exception\InvalidResponseStructureException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ResourcesResponse
{
    public function __construct(public readonly string $version)
    {
    }

    /**
     * @throws CorruptResponseBodyException
     * @throws InvalidJsonInResponseBodyException
     * @throws InvalidResponseStructureException
     */
    public static function fromResponse(ResponseInterface $response): self
    {
        try {
            $content = $response->getBody()->getContents();
            $body = Utils::jsonDecode($content, true);

            if (! isset($body['Version'])) {
                throw InvalidResponseStructureException::invalidBody('Version', $content);
            }

            return new self($body['Version']);
        } catch (RuntimeException $e) {
            throw new CorruptResponseBodyException($e);
        } catch (InvalidArgumentException $e) {
            throw new InvalidJsonInResponseBodyException($e);
        }
    }
}