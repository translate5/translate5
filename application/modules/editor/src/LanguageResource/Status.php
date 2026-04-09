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

namespace MittagQI\Translate5\LanguageResource;

use Zend_Exception;
use ZfExtended_Zendoverwrites_Translate;

class Status
{
    public const NOTCHECKED = 'notchecked';

    public const ERROR = 'error';

    public const AVAILABLE = 'available';

    public const UNKNOWN = 'unknown';

    public const NOCONNECTION = 'noconnection';

    public const NOVALIDLICENSE = 'novalidlicense';

    public const NOT_LOADED = 'notloaded';

    public const QUOTA_EXCEEDED = 'quotaexceeded';

    public const REORGANIZE_IN_PROGRESS = 'reorganize';

    public const REORGANIZE_FAILED = 'reorganize failed';

    public const TUNING_IN_PROGRESS = 'tuninginprogress';

    public const IMPORT = 'import';

    public const WAITING_FOR_LOADING = 'waitingForLoading';

    public const LOADING = 'loading';

    public const FAILED_TO_OPEN = 'failedToOpen';

    public const CONVERTING = 'converting';

    /**
     * Retrieve the linguistic equivalent of the status values above
     * @throws Zend_Exception
     */
    public static function statusInfo(string $status): string
    {
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();

        return match ($status) {
            self::NOTCHECKED => $translate->_('Not checked'),
            self::ERROR => $translate->_('Error'),
            self::AVAILABLE => $translate->_('available'),
            self::NOCONNECTION => $translate->_('No connection!'),
            self::NOVALIDLICENSE => $translate->_('No valid license.'),
            self::QUOTA_EXCEEDED => $translate->_('Quota exceeded'),
            self::REORGANIZE_IN_PROGRESS => $translate->_('Reorganization in progress'),
            self::REORGANIZE_FAILED => $translate->_('Reorganization failed'),
            self::TUNING_IN_PROGRESS => $translate->_('Training in progress'),
            self::WAITING_FOR_LOADING => $translate->_('Wait until loading is complete'),
            self::LOADING => $translate->_('Loading'),
            self::FAILED_TO_OPEN => $translate->_('Failed to load'),
            self::CONVERTING => $translate->_('Converting'),
            default => $translate->_('unknown'),
        };
    }
}
