<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\T5Memory\Api\V6\Request;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use MittagQI\Translate5\T5Memory\Enum\StripFramingTags;

class ImportTmxRequest extends Request
{
    /**
     * @param resource $stream
     */
    public function __construct(
        string $baseUrl,
        string $tmName,
        string $filename,
        $stream,
        StripFramingTags $stripFramingTags
    ) {
        $tmName = urlencode($tmName);

        $multipart[] = [
            'name' => 'json_data',
            'contents' => json_encode(
                [
                    'framingTags' => $stripFramingTags->value,
                ],
                JSON_PRETTY_PRINT
            ),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];

        $multipart[] = [
            'name' => 'file',
            'contents' => $stream,
            'filename' => $filename,
        ];

        $body = new MultipartStream($multipart);

        parent::__construct(
            'POST',
            rtrim($baseUrl, '/') . "/$tmName/importtmx",
            [
                'Content-Type' => 'multipart/form-data; boundary=' . $body->getBoundary(),
            ],
            $body,
        );
    }
}
