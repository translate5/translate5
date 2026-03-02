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

namespace MittagQI\Translate5\Tag;

final class ReplacedRenderingConfig
{
    public string $singleTagPlaceholder;

    public string $openerTagPlaceholder;

    public string $closerTagPlaceholder;

    public string $numberPlaceholder;

    public string $placeablePlaceholder;

    public string $specialcharPlaceholder;

    public string $whitespacePlaceholder;

    public function __construct(
        public readonly bool $isForSource,
        public readonly bool $placeablesAsPlaceholder = false,
        public readonly bool $numbersAsPlaceholder = false,
        public readonly bool $specialcharsAsPlaceholder = false,
        public readonly bool $whitespaceAsPlaceholder = false,
        public readonly bool $singleTagsAsPlaceholder = false,
        public readonly bool $pairedTagsAsPlaceholder = false,
        string $placeholder = '',
    ) {
        $this->placeablePlaceholder = $placeholder;
        $this->numberPlaceholder = $placeholder;
        $this->specialcharPlaceholder = $placeholder;
        $this->whitespacePlaceholder = $placeholder;
        $this->singleTagPlaceholder = $placeholder;
        $this->openerTagPlaceholder = $placeholder;
        $this->closerTagPlaceholder = $placeholder;
    }
}
