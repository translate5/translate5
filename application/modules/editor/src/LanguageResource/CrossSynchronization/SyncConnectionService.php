<?php

declare(strict_types=1);

namespace MittagQI\Translate5\LanguageResource\CrossSynchronization;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use Generator;

interface SyncConnectionService
{
    public function getName();

    /**
     * @return SynchronizationType[]
     */
    public function syncSourceOf(): array;

    /**
     * @return SynchronizationType[]
     */
    public function syncTargetFor(): array;

    /**
     * Determines if the service allows multiple sources for synchronization
     */
    public function isOneToOne(): bool;

    /**
     * @param int|null $customerId Use null if the synchronization is not customer specific
     * @return iterable<array{source: string, target: string}>
     */
    public function getSyncData(
        LanguageResource $languageResource,
        LanguagePair $languagePair,
        ?int $customerId,
        SynchronizationType $synchronizationType
    ): Generator;
}