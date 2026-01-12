<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\TMMaintenance\Exception;

use ZfExtended_ErrorCodeException;

class ErrorException extends ZfExtended_ErrorCodeException
{
    use \ZfExtended_ResponseExceptionTrait;

    protected static $localErrorCodes = [
        'E1314' => 'The queried T5Memory TM "{tm}" is corrupt and must be reorganized before usage!',
        'E1333' => 'The queried T5Memory server has to many open TMs!',
        'E1306' => 'Could not save segment to TM',
        'E1688' => 'Could not delete segment',
        'E1377' => 'Memory status: {status}. Please try again in a while.',
        'E1616' => 'T5Memory server version serving the selected memory is not supported',
        'E1611' => 't5memory: Requested segment not found. Probably it was deleted.',
        'E1612' => 't5memory: Found segment id differs from the requested one, probably it was deleted or edited meanwhile. Try to refresh your search.',
    ];

    protected $httpReturnCode = 422;
}
