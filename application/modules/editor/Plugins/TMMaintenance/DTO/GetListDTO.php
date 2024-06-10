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
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            (int) $request->getParam('tm'),
            (int) $request->getParam('limit'),
            (string) $request->getParam('offset'),
            (string) $request->getParam('source'),
            (string) $request->getParam('sourceMode'),
            (string) $request->getParam('target'),
            (string) $request->getParam('targetMode'),
            (string) $request->getParam('author'),
            (string) $request->getParam('authorMode'),
            (string) $request->getParam('creationDateFrom'),
            (string) $request->getParam('creationDateTo'),
            (string) $request->getParam('additionalInfo'),
            (string) $request->getParam('additionalInfoMode'),
            (string) $request->getParam('document'),
            (string) $request->getParam('documentMode'),
            (string) $request->getParam('context'),
            (string) $request->getParam('contextMode'),
        );
    }

    public function toArray()
    {
        return [
            'tmId' => $this->tmId,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'source' => $this->source,
            'sourceMode' => $this->sourceMode,
            'target' => $this->target,
            'targetMode' => $this->targetMode,
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
        ];
    }
}
