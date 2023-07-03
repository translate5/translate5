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

use Zend_Exception;
use Zend_Registry;
use ZfExtended_Debug;
use ZfExtended_Exception;
use ZfExtended_Factory;
use editor_Models_Task_AbstractWorker;
use MittagQI\Translate5\Service\DockerServiceAbstract;

/**
 * Extends the import worker to work with pooled services tailored for processing segments
 * The Worker will be instantiated as many times as the service has max parallel configured or a single service-url has IPs or as many url's are configured for the pool
 * Then each of these workers will process the segments in a loop until no unprocessed segments are available anymore
 * The worker can operate with non-pooled services or pooled workers, that are configured as non-pooled (see MittagQI\Translate5\PooledService\ServiceAbstract::isPooled) as well
 * Then the amount of workers to queue is evaluated on the fly by getting the IP-Adresses the configured host has
 * the URL of the service is not the slot anymore but a seperate worker-param "serviceUrl"
 */
abstract class Worker extends editor_Models_Task_AbstractWorker
{
    /**
     * @var string
     */
    protected string $resourcePool = 'import';

    /**
     * Pooled (or "Pseudo-Pooled" = multiple IPs) Service Workers can run in Parallel!
     * @var bool
     */
    protected $onlyOncePerTask = false;

    /**
     * Temporary flag for queueing phase to prevent queueing multiple workers
     * @var bool
     */
    protected $isSingleThreaded = false;

    /**
     * @var int
     */
    protected int $maxParallel = -1;

    /**
     * @var array
     */
    protected array $slots;

    /**
     * @var bool
     */
    protected bool $isPooled;

    /**
     * @var bool
     */
    protected int $workerIndex = 0;

    /**
     * @var string
     */
    protected string $serviceUrl;

    /**
     * @var array
     */
    protected array $calculatedSlot;

    /**
     * @var DockerServiceAbstract|ServiceAbstract
     */
    protected DockerServiceAbstract|ServiceAbstract $service;

    /**
     * @var bool
     */
    protected bool $doDebug = false;

    public function __construct()
    {
        parent::__construct();
        $this->service = $this->createService();
        // pooled services need to have their pool respected
        $this->isPooled = $this->service->isPooled();
        // debugging generally is tailored to what workers are working when
        $this->doDebug = ZfExtended_Debug::hasLevel('core', 'ServiceWorkers');
    }

    /**
     * Must be implemented to create the service
     * This function is also used in a static context and must not use internal dependencis
     * @return DockerServiceAbstract|ServiceAbstract
     */
    abstract protected function createService(): DockerServiceAbstract|ServiceAbstract;

    /**
     * Must be implemented to create the no services available exception
     * @throws ZfExtended_Exception
     */
    abstract protected function raiseNoAvailableResourceException();

    /**
     * Sets the slot we need for queueing. Only for use when queuing parallel workers
     * @param array $slot
     */
    protected function setCalculatedSlot(array $slot)
    {
        $this->calculatedSlot = $slot;
    }

    /**
     * Sets our amount of parallel workers. Only for use when queuing parallel workers
     * @param int $maxParallel
     */
    protected function setMaxParallel(int $maxParallel)
    {
        $this->maxParallel = $maxParallel;
    }

    /**
     * Sets the url we need for processing
     * @param string $serviceUrl
     */
    protected function setServiceUrl(string $serviceUrl)
    {
        $this->serviceUrl = $serviceUrl;
    }

    /**
     * @return int
     */
    protected function getMaxParallelProcesses(): int
    {
        $this->initSlots();
        return $this->maxParallel;
    }

    /**
     * @throws ZfExtended_Exception
     * @throws Zend_Exception
     */
    protected function initSlots()
    {
        if ($this->maxParallel < 0) {
            $serviceId = $this->service->getServiceId();
            $serviceUrls = [];
            // pooled workers will be limited by the pool-size ... if onlyOncePerTask is set to true we obviously can not run in parallel
            if ($this->isPooled && !$this->onlyOncePerTask && !$this->isSingleThreaded) {
                $serviceUrls = $this->service->getPooledServiceUrls($this->resourcePool);
                if (empty($serviceUrls) && $this->resourcePool != 'default') {
                    $serviceUrls = $this->service->getPooledServiceUrls('default');
                    $this->resourcePool = 'default';
                }
                $this->maxParallel = count($serviceUrls);
            } else {
                $serviceUrl = $this->service->getServiceUrl();
                if ($serviceUrl === null) {
                    // no service url available: no workers will be queued
                    $this->maxParallel = 0;
                } else if ($this->onlyOncePerTask || $this->isSingleThreaded) {
                    // if we are limited to one per task we can just queue a single worker
                    $serviceUrls = [ $serviceUrl ];
                    $this->maxParallel = 1;
                } else {
                    // non-pooled services are expected to have inbuilt load-balancing and we evaluate the number of instances by the IP's that exist behind the configured URL
                    $host = rtrim(parse_url($serviceUrl, PHP_URL_HOST), '.') . '.';
                    $hosts = gethostbynamel($host);
                    $this->maxParallel = (empty($hosts)) ? 1 : count($hosts); // QUIRK: for now, we set maxParallel to 1 if gethostbynamel() does not detect anything ... TODO FIXME: Throw Exception ?
                    $serviceUrls = ($this->maxParallel < 1) ? [] : [$serviceUrl];
                }
            }
            // limit max parallel to the top ... this gives the chance to limit all service workers via DB
            $config = Zend_Registry::get('config');
            $this->maxParallel = min($this->maxParallel, $config->runtimeOptions->worker->maxParallelWorkers);
            // generate the data to queue the workers
            $this->slots = [];
            $numUrls = count($serviceUrls);
            if ($this->maxParallel > 0) {
                for ($i = 0; $i < $this->maxParallel; $i++) {
                    $this->slots[] = [
                        'resource' => $serviceId . ucfirst($this->resourcePool), // the resource-name for the worker model
                        'slot' => $serviceId . '_' . $i,                         // the slot that represents a "virtualized" url and not the real URL anymore as with other workers
                        'url' => ($i < $numUrls) ? $serviceUrls[$i] : (($numUrls > 1) ? $serviceUrls[random_int(0, $numUrls - 1)] : $serviceUrls[0]) // the actual URL (saved in the worker-params)
                    ];
                }
            }
            if ($this->doDebug) {
                error_log('PooledService Worker::initSlots(): number of Workers: ' . $this->maxParallel . ' / slots: ' . print_r($this->slots, true));
            }
        }
    }

    /**
     * Overwritten to return precalculated data
     * @return array('resource' => ResurceName, 'slot' => SlotName);
     */
    protected function calculateSlot(): array
    {
        return $this->calculatedSlot;
    }

    /**
     * HINT: this function will not check the serviceUrl Param as it is set programmatically in the queue-method
     * @param array $parameters
     * @return bool
     */
    protected function validateParameters($parameters = [])
    {
        if ($this->isPooled && array_key_exists('resourcePool', $parameters) && $this->service->isValidPool($parameters['resourcePool'])) {
            $this->resourcePool = $parameters['resourcePool'];
        }
        if (array_key_exists('workerIndex', $parameters)) {
            $this->workerIndex = (int) $parameters['workerIndex'];
        }
        // we cannot check this param as it is not present in the initial init but will be added programmatically
        if (array_key_exists('serviceUrl', $parameters)) {
            $this->setServiceUrl($parameters['serviceUrl']);
        }
        return true;
    }

    /**
     * When called in the queueing phase only one worker will be queued
     */
    public function setSingleThreaded()
    {
        $this->isSingleThreaded = true;
    }

    /**
     * Queueing a pooled service worker usually queues several workers at once
     * UGLY: The param $startNext has a ambigous meaning here: more like "startAsManyAsThereAreFreeResources"
     * This is defined by the
     * @param int $parentId
     * @param null $state
     * @param bool $startNext
     * @return int
     * @throws ZfExtended_Exception
     */
    public function queue($parentId = 0, $state = null, $startNext = true): int
    {
        if ($startNext) {

            $this->checkIsInitCalled();
            $this->initSlots();
            if (count($this->slots) === 0) {
                $this->raiseNoAvailableResourceException();
            }

            $params = $this->workerModel->getParameters();
            $params['workerIndex'] = 0;
            $myselfQueued = false;

            foreach ($this->slots as $slot) {

                // crucial: we have to add the service-URL as worker-model parameter
                $params['serviceUrl'] = $slot['url'];

                if ($myselfQueued) {

                    // queue more workers of our type
                    $worker = ZfExtended_Factory::get(static::class);
                    $worker->init($this->workerModel->getTaskGuid(), $params);
                    $worker->setCalculatedSlot($slot);
                    $worker->setMaxParallel($this->maxParallel);
                    $worker->queue($parentId, $state, false);

                } else {

                    // queue ourself
                    $this->workerModel->setParameters($params);
                    $this->setCalculatedSlot($slot);
                    parent::queue($parentId, $state, false);
                    $myselfQueued = true;

                    if ($this->doDebug) {
                        error_log('Queued PooledService Worker: ' . get_class($this) . ', primary');
                    }
                }
                $params['workerIndex']++;
            }
            $this->wakeUpAndStartNextWorkers();
            $this->emulateBlocking();

            return (int) $this->workerModel->getId();
        }

        if ($this->doDebug) {
            error_log('Queued PooledService Worker: ' . get_class($this) . ', secondary');
        }

        return parent::queue($parentId, $state, false);
    }
}
