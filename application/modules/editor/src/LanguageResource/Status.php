<?php

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
    public const REORGANIZE_IN_PROGRESS = 'reorganize_in_progress';
    public const REORGANIZE_FAILED = 'reorganize_failed';
    public const TUNING_IN_PROGRESS = 'tuninginprogress';
    public const IMPORT = 'import';


    /**
     * Retrieve the linguistic equivalent of the status values above
     * @param string $status
     * @return string
     * @throws Zend_Exception
     */
    public static function statusInfo(string $status): string
    {
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();
        return match ($status) {
            self::NOTCHECKED => $translate->_('Nicht geprüft'),
            self::ERROR => $translate->_('Fehler'),
            self::AVAILABLE => $translate->_('verfügbar'),
            self::NOCONNECTION => $translate->_('Keine Verbindung!'),
            self::NOVALIDLICENSE => $translate->_('Keine gültige Lizenz.'),
            self::QUOTA_EXCEEDED => $translate->_('Kontingent überschritten'),
            self::REORGANIZE_IN_PROGRESS => $translate->_('Wird reorganisiert'),
            self::REORGANIZE_FAILED => $translate->_('Reorganisation gescheitert'),
            self::TUNING_IN_PROGRESS => $translate->_('Wird trainiert'),
            default => $translate->_('unbekannt')
        };
    }
}
