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

namespace MittagQI\Translate5\T5Memory\Api\V5\Request;

use GuzzleHttp\Psr7\Request;

class CreateTmRequest extends Request
{
    public function __construct(
        string $baseUrl,
        string $tmName,
        string $sourceLang,
        string $tmxData,
    ) {
        $data = base64_encode($tmxData);

        parent::__construct(
            'POST',
            rtrim($baseUrl, '/'),
            [
                'Accept-charset' => 'UTF-8',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            json_encode([
                'name' => $tmName,
                'sourceLang' => $sourceLang,
                'data' => $data,
            ], JSON_PRETTY_PRINT)
        );
    }
}
