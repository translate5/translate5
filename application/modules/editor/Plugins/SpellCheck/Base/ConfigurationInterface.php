<?php

declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\SpellCheck\Base;

/**
 * Separate holder of certain configurations to accompany Worker\Import
 */
interface ConfigurationInterface
{
    /**
     * Get logger domain
     *
     * @param string $processingType
     *
     * @return string
     */
    public static function getLoggerDomain(string $processingType): string;

    /**
     * Get array of available resource slots for the given $resourcePool
     *
     * @param string $resourcePool
     *
     * @return array
     */
    public function getAvailableResourceSlots(string $resourcePool): array;

    /**
     * Append slot (URL) to the list of down slots to be able to skip it further
     *
     * @param string $url
     */
    public function disableResourceSlot(string $url): void;

    /**
     * Shows how much segments can be processed per one worker call
     *
     * @return int
     */
    public function getSegmentsPerCallAmount(): int;
}
