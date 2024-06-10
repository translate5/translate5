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

namespace MittagQI\Translate5\T5Memory\DTO;

class SearchDTO
{
    final public function __construct(
        public readonly string $source,
        public readonly string $sourceMode,
        public readonly string $target,
        public readonly string $targetMode,
        public readonly string $author,
        public readonly string $authorMode,
        public readonly int $creationDateFrom,
        public readonly int $creationDateTo,
        public readonly string $additionalInfo,
        public readonly string $additionalInfoMode,
        public readonly string $document,
        public readonly string $documentMode,
        public readonly string $context,
        public readonly string $contextMode,
    ) {
    }

    public static function fromArray(array $data): static
    {
        return new static(
            $data['source'],
            $data['sourceMode'],
            $data['target'],
            $data['targetMode'],
            $data['author'],
            $data['authorMode'],
            $data['creationDateFrom'],
            $data['creationDateTo'],
            $data['additionalInfo'],
            $data['additionalInfoMode'],
            $data['document'],
            $data['documentMode'],
            $data['context'],
            $data['contextMode'],
        );
    }
}
