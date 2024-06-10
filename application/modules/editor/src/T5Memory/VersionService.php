<?php

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory;

use DateTime;
use DateTimeInterface;
use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use MittagQI\Translate5\T5Memory\Api\Contract\HasVersion;
use PharIo\Version\GreaterThanOrEqualToVersionConstraint;
use PharIo\Version\Version;

class VersionService
{
    private bool $isInternalFuzzy = false;

    public function __construct(private HasVersion $versionFetchingApi)
    {
    }

    public function isLRVersionGreaterThan(string $version, ?LanguageResource $languageResource): bool
    {
        $constraint = new GreaterThanOrEqualToVersionConstraint($version, new Version($version));

        return $constraint->complies(new Version($this->getT5MemoryVersion($languageResource)));
    }

    public function setInternalFuzzy(bool $isFuzzy): void
    {
        $this->isInternalFuzzy = $isFuzzy;
    }

    public function getT5MemoryVersion(?LanguageResource $languageResource): string
    {
        if (! $languageResource) {
            return HasVersion::FALLBACK_VERSION;
        }

        $version = $languageResource->getSpecificData('version', true);

        if (isset($version['version'], $version['lastSynced'])
            && null !== $version
            && (new DateTime($version['lastSynced']))->modify('+30 minutes') > new DateTime()
        ) {
            return $version['version'];
        }

        $version = $this->versionFetchingApi->version($languageResource->getResource()->getUrl());

        if (! $this->isInternalFuzzy) {
            // TODO In editor_Services_Manager::visitAllAssociatedTms language resource is initialized
            // without refreshing from DB, which leads th that here it is tried to be inserted as new one
            // so refreshing it here. Need to check if we can do this in editor_Services_Manager::visitAllAssociatedTms
            $languageResource->refresh();
            $languageResource->addSpecificData('version', [
                'version' => $version,
                'lastSynced' => date(DateTimeInterface::RFC3339),
            ]);
            $languageResource->save();
        }

        return $version;
    }
}