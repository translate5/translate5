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

namespace MittagQI\Translate5\T5Memory\DTO;

use MittagQI\Translate5\T5Memory\Enum\SearchMode;

final readonly class SearchDTO
{
    public string $source;

    public string $target;

    final public function __construct(
        string $source,
        public SearchMode $sourceMode,
        public bool $sourceCaseSensitive,
        string $target,
        public SearchMode $targetMode,
        public bool $targetCaseSensitive,
        public string $sourceLanguage,
        public string $targetLanguage,
        public string $author,
        public SearchMode $authorMode,
        public bool $authorCaseSensitive,
        public int $creationDateFrom,
        public int $creationDateTo,
        public string $additionalInfo,
        public SearchMode $additionalInfoMode,
        public bool $additionalInfoCaseSensitive,
        public string $document,
        public SearchMode $documentMode,
        public bool $documentCaseSensitive,
        public string $context,
        public SearchMode $contextMode,
        public bool $contextCaseSensitive,
        public bool $onlyCount,
    ) {
        $this->source = preg_replace('/&(?!#?[a-zA-Z0-9]+;)/', '&amp;', $source);
        $this->target = preg_replace('/&(?!#?[a-zA-Z0-9]+;)/', '&amp;', $target);
    }

    public static function fromArray(array $data): static
    {
        return new static(
            $data['source'],
            $data['sourceMode'],
            $data['caseSensitive'],
            $data['target'],
            $data['targetMode'],
            $data['caseSensitive'],
            $data['sourceLanguage'],
            $data['targetLanguage'],
            $data['author'],
            $data['authorMode'],
            $data['caseSensitive'],
            $data['creationDateFrom'],
            $data['creationDateTo'],
            $data['additionalInfo'],
            $data['additionalInfoMode'],
            $data['caseSensitive'],
            $data['document'],
            $data['documentMode'],
            $data['caseSensitive'],
            $data['context'],
            $data['contextMode'],
            $data['caseSensitive'],
            $data['onlyCount'],
        );
    }

    public static function searchExactSegment(
        string $source,
        string $target,
        string $author,
        string $document,
        string $context,
    ): static {
        return new static(
            source: $source,
            sourceMode: SearchMode::Exact,
            sourceCaseSensitive: true,
            target: $target,
            targetMode: SearchMode::Exact,
            targetCaseSensitive: true,
            sourceLanguage: '',
            targetLanguage: '',
            author: $author,
            authorMode: SearchMode::Exact,
            authorCaseSensitive: false,
            creationDateFrom: 0,
            creationDateTo: time() + 86400,
            additionalInfo: '',
            additionalInfoMode: SearchMode::Contains,
            additionalInfoCaseSensitive: false,
            document: $document,
            documentMode: SearchMode::Exact,
            documentCaseSensitive: true,
            context: $context,
            contextMode: SearchMode::Exact,
            contextCaseSensitive: true,
            onlyCount: false,
        );
    }
}
