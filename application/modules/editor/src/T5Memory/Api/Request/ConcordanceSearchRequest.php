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

namespace MittagQI\Translate5\T5Memory\Api\Request;

use GuzzleHttp\Psr7\Request;
use MittagQI\Translate5\T5Memory\DTO\SearchDTO;
use MittagQI\Translate5\T5Memory\Enum\SearchMode;

class ConcordanceSearchRequest extends Request
{
    private const DATE_FORMAT = 'Ymd\THis\Z';

    public function __construct(
        string $baseUrl,
        string $tmName,
        SearchDTO $dto,
        ?string $searchPosition = null,
        ?int $numResults = null,
    ) {
        $tmName = urlencode($tmName);

        parent::__construct(
            'POST',
            rtrim($baseUrl, '/') . "/$tmName/search",
            [
                'Accept-charset' => 'UTF-8',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            json_encode(
                [
                    'source' => $dto->source,
                    'sourceSearchMode' => $this->getSearchMode($dto->sourceMode, $dto->sourceCaseSensitive),
                    'target' => $dto->target,
                    'targetSearchMode' => $this->getSearchMode($dto->targetMode, $dto->targetCaseSensitive),
                    'sourceLang' => $dto->sourceLanguage,
                    'targetLang' => $dto->targetLanguage,
                    'document' => $dto->document,
                    'documentSearchMode' => $this->getSearchMode($dto->documentMode, $dto->documentCaseSensitive),
                    'author' => $dto->author,
                    'authorSearchMode' => $this->getSearchMode($dto->authorMode, $dto->authorCaseSensitive),
                    'addInfo' => $dto->additionalInfo,
                    'addInfoSearchMode' => $this->getSearchMode($dto->additionalInfoMode, $dto->additionalInfoCaseSensitive),
                    'context' => $dto->context,
                    'contextSearchMode' => $this->getSearchMode($dto->contextMode, $dto->contextCaseSensitive),
                    'timestampSpanStart' => gmdate(self::DATE_FORMAT, $dto->creationDateFrom),
                    'timestampSpanEnd' => gmdate(self::DATE_FORMAT, $dto->creationDateTo),
                    'onlyCountSegments' => $dto->onlyCount ? '1' : '0',
                    'searchPosition' => (string) $searchPosition,
                    'numResults' => $numResults,
                ],
                JSON_PRETTY_PRINT
            ),
        );
    }

    private function getSearchMode(SearchMode $mode, bool $caseSensitive): string
    {
        return $mode->value . ', ' . ($caseSensitive ? 'CASESENSETIVE' : 'CASEINSENSETIVE');
    }
}
