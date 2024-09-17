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

namespace MittagQI\Translate5\PooledService;

/**
 * Interface to be implemented if a service is - or wants to be - a pooled service
 */
interface PooledServiceInterface
{
    /**
     * Retrieves the URLs of one of our Pools
     * If the pool is not load-balanced, The URLs will be filtered for services that are marked as DOWN
     * via the global services mem-cache
     */
    public function getPooledServiceUrls(string $pool): array;

    /**
     * Retrieves a random url out of one of our Pools
     * If the pool is not load-balanced, The URL will be filtered for services that are marked as DOWN
     * via the global services mem-cache
     */
    public function getPooledServiceUrl(string $pool): ?string;

    /**
     * Special API for pooled services with a single URL for one pool:
     * This also is expected to represent a load-balancing and for a single URL maybe multiple workers are queued
     */
    public function isPoolLoadBalanced(string $pool): bool;

    /**
     * To enable a transition from pooled services (providing a t5-based load-balancing) to docker services which include load-balancing,
     * a pooled-service with just one default-url configured will count as non-pooled service.
     * This will lead to parallel workers up to IPs behind the single URL
     * Note: also pooled services can have this kind of load-balancing when only a single URL is defined for a pool
     */
    public function isPooled(): bool;

    /**
     * Used to check the validity of a pool-name
     */
    public function isValidPool(string $pool): bool;

    public function getServiceUrl(): ?string;

    public function getServiceUrls(): array;

    public function setServiceUrlDown(string $serviceUrl): bool;

    public function getServiceId(): string;

    public function getNumIpsForUrl(string $serviceUrl): int;
}
