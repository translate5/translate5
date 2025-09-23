<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2024 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\T5Memory\Api\Response;

final class MatchDTO
{
    public function __construct(
        public readonly string $source,
        public readonly string $target,
        public readonly int $segmentId,
        public readonly string $customId,
        public readonly string $documentName,
        public readonly string $sourceLang,
        public readonly string $targetLang,
        public readonly string $type,
        public readonly string $author,
        public readonly string $timestamp,
        public readonly string $markupTable,
        public readonly string $context,
        public readonly string $additionalInfo,
        public readonly string $internalKey,
        public readonly string $matchType,
        public readonly int $matchRate,
        public readonly int $fuzzyWords,
        public readonly int $fuzzyDiffs,
        public readonly bool $guessed = false,
        public readonly bool $possiblyNotOptimal = false,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            source: $data['source'] ?? '',
            target: $data['target'] ?? '',
            segmentId: (int) ($data['segmentId'] ?? 0),
            customId: $data['customId'] ?? '',
            documentName: $data['documentName'] ?? '',
            sourceLang: $data['sourceLang'] ?? '',
            targetLang: $data['targetLang'] ?? '',
            type: $data['type'] ?? '',
            author: $data['author'] ?? '',
            timestamp: $data['timestamp'] ?? '',
            markupTable: $data['markupTable'] ?? '',
            context: $data['context'] ?? '',
            additionalInfo: $data['additionalInfo'] ?? '',
            internalKey: $data['internalKey'] ?? '',
            matchType: $data['matchType'] ?? '',
            matchRate: (int) ($data['matchRate'] ?? 0),
            fuzzyWords: (int) ($data['fuzzyWords'] ?? 0),
            fuzzyDiffs: (int) ($data['fuzzyDiffs'] ?? 0),
        );
    }

    public function makeGuessed(): self
    {
        return new self(
            source: $this->source,
            target: $this->target,
            segmentId: $this->segmentId,
            customId: $this->customId,
            documentName: $this->documentName,
            sourceLang: $this->sourceLang,
            targetLang: $this->targetLang,
            type: $this->type,
            author: $this->author,
            timestamp: $this->timestamp,
            markupTable: $this->markupTable,
            context: $this->context,
            additionalInfo: $this->additionalInfo,
            internalKey: $this->internalKey,
            matchType: $this->matchType,
            matchRate: $this->matchRate,
            fuzzyWords: $this->fuzzyWords,
            fuzzyDiffs: $this->fuzzyDiffs,
            guessed: true,
        );
    }

    public function makeNotOptimal(): self
    {
        return new self(
            source: $this->source,
            target: $this->target,
            segmentId: $this->segmentId,
            customId: $this->customId,
            documentName: $this->documentName,
            sourceLang: $this->sourceLang,
            targetLang: $this->targetLang,
            type: $this->type,
            author: $this->author,
            timestamp: $this->timestamp,
            markupTable: $this->markupTable,
            context: $this->context,
            additionalInfo: $this->additionalInfo,
            internalKey: $this->internalKey,
            matchType: $this->matchType,
            matchRate: $this->matchRate,
            fuzzyWords: $this->fuzzyWords,
            fuzzyDiffs: $this->fuzzyDiffs,
            guessed: $this->guessed,
            possiblyNotOptimal: true,
        );
    }
}
