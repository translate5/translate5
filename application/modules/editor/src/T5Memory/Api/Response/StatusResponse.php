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
use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;
use MittagQI\Translate5\T5Memory\Api\Contract\OverflowErrorInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class StatusResponse extends AbstractResponse implements OverflowErrorInterface
{
    use OverflowErrorTrait;

    public function __construct(
        array $body,
        ?string $errorMessage,
        int $statusCode,
        public readonly string $status,
    ) {
        parent::__construct($body, $errorMessage, $statusCode);
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
                LanguageResourceStatus::ERROR,
            );
        }

        $errorStatusMessage = null;

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
            self::processStatus($body),
        );
    }

    public function successful(): bool
    {
        return $this->statusCode === 200 && $this->status !== LanguageResourceStatus::ERROR;
    }

    private static function processStatus(array $body): string
    {
        $status = $body['status'] ?? '';
        $tmxImportStatus = $body['tmxImportStatus'] ?? '';
        $reorganizeStatus = $body['reorganizeStatus'] ?? '';

        switch ($status) {
            // TM not found at all
            case 'not found':
                // We have no status 'not found' at the moment, so we use 'error' instead
                return LanguageResourceStatus::ERROR;

                // TM exists on a disk, but not loaded into memory
            case 'available':
                // TODO change this to STATUS_NOT_LOADED after discussed with the team
                //                return self::STATUS_NOT_LOADED;
                return LanguageResourceStatus::AVAILABLE;

                // TM exists and is loaded into memory
            case 'open':
                {
                    switch ($reorganizeStatus) {
                        case 'reorganize':
                            return LanguageResourceStatus::REORGANIZE_IN_PROGRESS;

                        default:
                            break;
                    }

                    switch ($tmxImportStatus) {
                        case '':
                            return LanguageResourceStatus::AVAILABLE;

                        case 'available':
                            if (isset($body['importTime']) && $body['importTime'] === 'not finished') {
                                return LanguageResourceStatus::IMPORT;
                            }

                            return LanguageResourceStatus::AVAILABLE;

                        case 'import':
                            return LanguageResourceStatus::IMPORT;

                        default:
                            break;
                    }

                    return LanguageResourceStatus::AVAILABLE;
                }

            case 'reorganize running':
                return LanguageResourceStatus::REORGANIZE_IN_PROGRESS;

            case 'import running':
                return LanguageResourceStatus::IMPORT;

            case 'waiting for loading':
                return LanguageResourceStatus::WAITING_FOR_LOADING;

            case 'loading':
                return LanguageResourceStatus::LOADING;

            case 'failed to open':
                return LanguageResourceStatus::FAILED_TO_OPEN;

            default:
                return LanguageResourceStatus::UNKNOWN;
        }
    }
}
