<?php

declare(strict_types=1);

namespace MittagQI\Translate5\LanguageResource\CrossSynchronization;

interface SynchronizableIntegration
{
    public function getName();

    public function getSynchronisationService(): SynchronisationInterface;
}
