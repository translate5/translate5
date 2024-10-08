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

namespace MittagQI\Translate5\T5Memory\Api\V6\Response;

use MittagQI\Translate5\T5Memory\Api\Exception\CorruptResponseBodyException;
use MittagQI\Translate5\T5Memory\Api\Exception\InvalidResponseStructureException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class DownloadTmxChunkResponse
{
    public function __construct(
        public readonly ?string $nextInternalKey,
        public readonly StreamInterface $chunk
    ) {
    }

    /**
     * @throws CorruptResponseBodyException
     * @throws InvalidResponseStructureException
     */
    public static function fromResponse(ResponseInterface $response, ?string $startFromInternalKey): self
    {
        $nextInternalKeyHeader = $response->getHeader('NextInternalKey');
        $nextInternalKey = null;

        if (
            ! empty($nextInternalKeyHeader)
            && $nextInternalKeyHeader[0] !== $startFromInternalKey
            && '0:0' !== $nextInternalKeyHeader[0]
        ) {
            $nextInternalKey = $nextInternalKeyHeader[0];

            if (! preg_match('/\d+:\d+/', $nextInternalKey)) {
                throw InvalidResponseStructureException::invalidHeader('NextInternalKey', $nextInternalKey);
            }
        }

        return new self($nextInternalKey, $response->getBody());
    }
}
