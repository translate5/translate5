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

namespace MittagQI\Translate5\T5Memory\Api\Exception;

use Exception;
use MittagQI\Translate5\T5Memory\Api\Contract\ResponseExceptionInterface;

class InvalidResponseStructureException extends Exception implements ResponseExceptionInterface
{
    public static function invalidBody(string $expectedFieldPath, string $responseBody): self
    {
        return new self(
            sprintf('Element "%s" not found in response body:%s%s', $expectedFieldPath, PHP_EOL, $responseBody),
        );
    }

    public static function invalidHeader(string $name, string $value): self
    {
        return new self(
            sprintf('Header "%s" has invalid value: %s', $name, $value),
        );
    }
}
