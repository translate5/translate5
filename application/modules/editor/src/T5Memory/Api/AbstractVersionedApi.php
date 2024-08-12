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
