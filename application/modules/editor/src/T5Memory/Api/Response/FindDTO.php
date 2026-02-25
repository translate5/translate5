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

final readonly class FindDTO
{
    public function __construct(
        public string $source,
        public string $target,
        public int $segmentId,
        public string $customId,
        public string $documentName,
        public string $sourceLang,
        public string $targetLang,
        public string $type,
        public string $author,
        public string $timestamp,
        public string $markupTable,
        public string $context,
        public string $additionalInfo,
        public string $internalKey,
        public ?int $partId = null,
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
        );
    }

    public function withPartId(int $id): self
    {
        return new self(
            source: $this->source,
            target: $this->target,
            segmentId: $id,
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
            partId: $id,
        );
    }
}
