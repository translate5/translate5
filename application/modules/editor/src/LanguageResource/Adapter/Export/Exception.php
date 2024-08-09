<?php

namespace MittagQI\Translate5\LanguageResource\Adapter\Export;

class Exception extends \ZfExtended_ErrorCodeException
{
    /**
     * @var string
     */
    protected $domain = 'editor.languageResource.tm.export';

    protected static array $localErrorCodes = [
        'E1607' => 'TM Export: General problem with TM export. Check the error log for more info.',
        'E1608' => 'TM Export: Could not start export worker',
    ];
}
