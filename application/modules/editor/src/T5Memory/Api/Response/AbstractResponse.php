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

namespace MittagQI\Translate5\T5Memory\Api\Response;

use MittagQI\Translate5\T5Memory\Api\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

abstract class AbstractResponse implements ResponseInterface
{
    private readonly int $code;

    public function __construct(
        private readonly array $body,
        private readonly ?string $errorMessage,
        protected readonly int $statusCode,
    ) {
        $this->code = (int) ($body['returnValue'] ?? $body['ReturnValue'] ?? 0);
    }

    abstract public static function fromResponse(PsrResponseInterface $response): AbstractResponse;

    public function getBody(): array
    {
        return $this->body;
    }

    public function successful(): bool
    {
        return $this->statusCode === 200;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function needsReorganizing(\Zend_Config $config): bool
    {
        if (str_contains($this->getErrorMessage() ?: '', 'Failed to load tm')) {
            return false;
        }

        $errorCodes = explode(
            ',',
            $config->runtimeOptions->LanguageResources->t5memory->reorganizeErrorCodes
        );

        return in_array($this->getCode(), $errorCodes);
    }

    protected static function getContent(PsrResponseInterface $response): string
    {
        if ($response->getBody()->isSeekable()) {
            $response->getBody()->rewind();
        }

        return $response->getBody()->getContents();
    }
}
