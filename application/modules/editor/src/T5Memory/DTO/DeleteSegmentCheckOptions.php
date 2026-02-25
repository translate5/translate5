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

readonly class DeleteSegmentCheckOptions
{
    public function __construct(
        public bool $checkTarget,
        public bool $checkAuthor,
        public bool $checkDocumentName,
        public bool $checkContext,
        public bool $checkTimestamp,
    ) {
    }

    public static function exactCheck(): self
    {
        return new self(
            checkTarget: true,
            checkAuthor: true,
            checkDocumentName: true,
            checkContext: true,
            checkTimestamp: true,
        );
    }

    public static function sameSourceCheck(): self
    {
        return new self(
            checkTarget: false,
            checkAuthor: false,
            checkDocumentName: false,
            checkContext: false,
            checkTimestamp: false,
        );
    }

    public static function sameSourceAndTargetCheck(): self
    {
        return new self(
            checkTarget: true,
            checkAuthor: false,
            checkDocumentName: false,
            checkContext: false,
            checkTimestamp: false,
        );
    }
}
