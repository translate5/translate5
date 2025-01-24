<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\TMMaintenance\DTO;

use Zend_Controller_Request_Abstract as Request;

class GetListDTO
{
    final public function __construct(
        public readonly int $tmId,
        public readonly int $limit,
        public readonly string $offset,
        public readonly string $source,
        public readonly string $sourceMode,
        public readonly string $target,
        public readonly string $targetMode,
        public readonly string $sourceLanguage,
        public readonly string $targetLanguage,
        public readonly string $author,
        public readonly string $authorMode,
        public readonly string $creationDateFrom,
        public readonly string $creationDateTo,
        public readonly string $additionalInfo,
        public readonly string $additionalInfoMode,
        public readonly string $document,
        public readonly string $documentMode,
        public readonly string $context,
        public readonly string $contextMode,
        public readonly bool $onlyCount,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        $data = json_decode($request->getParam('data'), true, JSON_THROW_ON_ERROR);

        return new static(
            (int) $data['tm'],
            (int) $request->getParam('limit'),
            isset($data['offset']) ? (string) $data['offset'] : '',
            (string) $data['source'],
            (string) $data['sourceMode'],
            (string) $data['target'],
            (string) $data['targetMode'],
            (string) $data['sourceLanguage'],
            (string) $data['targetLanguage'],
            (string) $data['author'],
            (string) $data['authorMode'],
            (string) $data['creationDateFrom'],
            (string) $data['creationDateTo'],
            isset($data['additionalInfo']) ? (string) $data['additionalInfo'] : '',
            isset($data['additionalInfoMode']) ? (string) $data['additionalInfoMode'] : '',
            (string) $data['document'],
            (string) $data['documentMode'],
            isset($data['context']) ? (string) $data['context'] : '',
            isset($data['contextMode']) ? (string) $data['contextMode'] : '',
            (bool) ($data['onlyCount'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'tmId' => $this->tmId,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'source' => $this->source,
            'sourceMode' => $this->sourceMode,
            'target' => $this->target,
            'targetMode' => $this->targetMode,
            'sourceLanguage' => $this->sourceLanguage,
            'targetLanguage' => $this->targetLanguage,
            'author' => $this->author,
            'authorMode' => $this->authorMode,
            'creationDateFrom' => $this->creationDateFrom,
            'creationDateTo' => $this->creationDateTo,
            'additionalInfo' => $this->additionalInfo,
            'additionalInfoMode' => $this->additionalInfoMode,
            'document' => $this->document,
            'documentMode' => $this->documentMode,
            'context' => $this->context,
            'contextMode' => $this->contextMode,
            'onlyCount' => $this->onlyCount,
        ];
    }
}
