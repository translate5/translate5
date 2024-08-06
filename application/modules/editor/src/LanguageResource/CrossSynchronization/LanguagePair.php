<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of ZfExtended library

 Copyright (c) 2013 - 2021 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU LESSER GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file lgpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU LESSER GENERAL PUBLIC LICENSE version 3.0 requirements will be met:
https://www.gnu.org/licenses/lgpl-3.0.txt

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU LESSER GENERAL PUBLIC LICENSE version 3
             https://www.gnu.org/licenses/lgpl-3.0.txt

END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\LanguageResource\CrossSynchronization;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use InvalidArgumentException;

class LanguagePair
{
    public function __construct(
        public readonly int $sourceId,
        public readonly int $targetId,
        public readonly string $sourceCode,
        public readonly string $targetCode,
    ) {
    }

    public static function fromLanguageResource(LanguageResource $languageResource): self
    {
        if (is_array($languageResource->getSourceLang()) || is_array($languageResource->getTargetLang())) {
            throw new InvalidArgumentException('LanguageResource must have a single source and target language');
        }

        return new self(
            (int) $languageResource->getSourceLang(),
            (int) $languageResource->getTargetLang(),
            $languageResource->getSourceLangCode(),
            $languageResource->getTargetLangCode(),
        );
    }
}
