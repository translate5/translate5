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

use JsonException;
use MittagQI\Translate5\T5Memory\Api\Contract\OverflowErrorInterface;
use MittagQI\Translate5\T5Memory\Enum\ImportStatusEnum;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class ImportStatusResponse extends AbstractResponse implements OverflowErrorInterface
{
    use OverflowErrorTrait;

    public function __construct(
        array $body,
        ?string $errorMessage,
        int $statusCode,
        public readonly ImportStatusEnum $status,
    ) {
        parent::__construct($body, $errorMessage, $statusCode);
    }

    public function successful(): bool
    {
        return $this->statusCode === 200 && $this->status !== ImportStatusEnum::Error;
    }

    public static function fromResponse(PsrResponseInterface $response): self
    {
        $content = $response->getBody()->getContents();

        try {
            $body = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return new self(
                [],
                'Invalid JSON response: ' . $content,
                $response->getStatusCode(),
                ImportStatusEnum::Error,
            );
        }

        [$status, $errorStatusMessage] = self::processStatus($body);

        if (500 === $response->getStatusCode()) {
            $errorStatusMessage = $body['ErrorMsg']
                ?? $body['importErrorMsg']
                ?? $body['reorganizeErrorMsg']
                ?? 'Unknown error';
        }

        return new self(
            $body,
            $errorStatusMessage,
            $response->getStatusCode(),
            $status,
        );
    }

    private static function processStatus(array $body): array
    {
        $status = $body['status'] ?? '';
        $tmxImportStatus = $body['tmxImportStatus'] ?? '';
        $errorStatusMessage = null;

        switch ($status) {
            // TM not found at all
            case 'waiting for loading':
            case 'reorganize running':
            case 'loading':
                // TM exists on a disk, but not loaded into memory
            case 'available':
                // we assume that the t5memory was restarted
                $result = ImportStatusEnum::Terminated;

                break;

                // TM exists and is loaded into memory
            case 'open':
                switch ($tmxImportStatus) {
                    case '':
                        // we expect tmxImportStatus to be present in response
                        // if it is empty, we assume that the t5memory was restarted
                        $result = ImportStatusEnum::Terminated;

                        break;

                    case 'available':
                        if (isset($body['importTime']) && $body['importTime'] === 'not finished') {
                            $result = ImportStatusEnum::Importing;

                            break;
                        }

                        $result = ImportStatusEnum::Done;

                        break;

                    case 'import':
                        $errorStatusMessage = 'TMX wird importiert, TM kann trotzdem benutzt werden';
                        $result = ImportStatusEnum::Importing;

                        break;

                    case 'error':
                    case 'failed':
                        $errorStatusMessage = $body['ErrorMsg']
                            ?? $body['importErrorMsg']
                            ?? $body['reorganizeErrorMsg']
                            ?? 'Unknown error';
                        $result = ImportStatusEnum::Error;

                        break;

                    default:
                        $result = ImportStatusEnum::Error;

                        break;
                }

                break;

            case 'import running':
                $result = ImportStatusEnum::Importing;

                break;

            default:
                $result = ImportStatusEnum::Error;

                break;
        }

        return [$result, $errorStatusMessage];
    }
}
