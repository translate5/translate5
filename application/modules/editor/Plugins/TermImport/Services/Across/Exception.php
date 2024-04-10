<?php

namespace MittagQI\Translate5\Plugins\TermImport\Services\Across;

/**
 * Special AcrossSoapConnector Exception extends the normal Exception
 */
class Exception extends \ZfExtended_ErrorCodeException
{
    /**
     * @var string
     */
    protected $domain = 'plugin.termimport';

    protected static $localErrorCodes = [
        'E1455' => 'Across TBX Export: Can not wait until job "{job}" is finished',
        'E1456' => 'Across TBX Export: Error on connecting to Across under "{url}"',
        'E1457' => 'Across TBX Export: Can not create Across security token',
        'E1458' => 'Across TBX Export: Error on communication with Across',
        'E1459' => 'Across TBX Export: Can not create temporary filestream',
        'E1460' => 'Across TBX Export: Can not read from file with fileguid {fileGuid}',
    ];
}
