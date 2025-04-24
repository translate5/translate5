<?php

namespace MittagQI\Translate5\Segment\TagRepair\Xliff;

use ZfExtended_ErrorCodeException;

class TagConverterException extends ZfExtended_ErrorCodeException
{
    /**
     * @var string
     */
    protected $domain = 'core.languageresource.tagrepair';

    protected static $localErrorCodes = [
        'E1710' => 'Error on converting service results tags or invalide tag structure.',
        'E1711' => 'Error during regex replacement for service format conversion.',
        'E1712' => 'Error on converting service string to xliff format.',
    ];
}
