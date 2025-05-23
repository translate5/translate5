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

use editor_Models_LanguageResources_LanguageResource as LanguageResource;

/**
 * Evaluates to a ZfExtended_BadGateway exception!
 */
class editor_Services_Connector_Exception extends ZfExtended_BadGatewayErrorCode
{
    public const DOMAIN = 'editor.languageresource.service.connector';

    /**
     * @var string
     */
    protected $domain = self::DOMAIN;

    protected static $localErrorCodes = [
        'E1282' => 'Language resource communication error.',
        'E1288' => 'The language code [{languageCode}] from resource [{resourceName}] is not valid or does not exist in the translate5 language code collection.',
        'E1311' => 'Could not connect to {service}: server not reachable',
        'E1312' => 'Could not connect to {service}: timeout on connection to server',
        'E1313' => 'The queried {service} returns an error: {error}',
        'E1370' => 'Empty response from {service}',
        'E1315' => 'JSON decode error: {errorMsg}',
        'E1485' => '{service} use not authorized',
        'E1486' => '{service} endpoint not found',
        'E1512' => 'The TM is in {status} status at the moment. Please try again later.',
        'E1536' => 'Request to service {service}: Parameter {paramname} missing.',
        'E1537' => 'Request to service {service}: Invalid response.',
        'E1564' => 't5memory: Language Resource [{name}] has no writable memory in list',
        'E1565' => 'Invalid offset provided. Should be: tmId:offset',
        'E1586' => 'Segment appeared to be not saved to TM properly',
    ];

    protected function setDuplication()
    {
        parent::setDuplication();
        ZfExtended_Logger::addDuplicatesByMessage('E1311', 'E1312', 'E1370', 'E1485', 'E1486', 'E1512');
    }

    public static function fromApiRequestError(
        mixed $error,
        string $service,
        ?LanguageResource $languageResource,
        ?string $tmName = null,
        ?Throwable $previous = null,
    ): self {
        $ecode = 'E1313';
        $data = [
            'service' => $service,
            'languageResource' => $languageResource ?? '',
            'tmName' => $tmName,
            'error' => $error,
        ];
        if (strpos($error->error ?? '', 'needs to be organized') !== false) {
            $ecode = 'E1314';
            $data['tm'] = $languageResource?->getName();
        }

        if (strpos($error->error ?? '', 'too many open translation memory databases') !== false) {
            $ecode = 'E1333';
        }

        /* @phpstan-ignore-next-line */
        return new self($ecode, $data, $previous);
    }
}
