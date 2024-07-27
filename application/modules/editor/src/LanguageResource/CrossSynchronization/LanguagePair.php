<?php

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
