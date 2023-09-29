<?php

declare(strict_types=1);

namespace MittagQI\Translate5\LanguageResource;

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
    public const TUNING_IN_PROGRESS = 'tuning_in_progress';
    public const IMPORT = 'import';
}
