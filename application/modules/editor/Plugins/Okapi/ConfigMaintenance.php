<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\Okapi;

use editor_Models_Config;
use editor_Models_Customer_CustomerConfig;
use editor_Models_TaskConfig;
use Zend_Db_Statement_Exception;
use ZfExtended_Factory as Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;

/**
 * Interface to maintain the Okapi server configurations
 */
class ConfigMaintenance
{

    const CONFIG_SERVER_USED = 'runtimeOptions.plugins.Okapi.serverUsed';
    const CONFIG_SERVER = 'runtimeOptions.plugins.Okapi.server';
    private editor_Models_Config $config;

    public function __construct()
    {
        $this->config = Factory::get(editor_Models_Config::class);
    }

    /**
     * adds a new server, replace url if name exists already
     * @param string $url
     * @param string $name
     * @return string|null
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function addServer(string $url, string $name): ?string
    {
        $oldValue = null;
        $this->config->loadByName(self::CONFIG_SERVER);
        $servers = $this->fromJson($this->config->getValue());
        if (!empty($servers[$name])) {
            $oldValue = $servers[$name];
        }
        $servers[$name] = $url;
        $this->config->setValue($this->toJson($servers));
        $this->config->save();
        $this->updateServerUsedDefaults($servers);
        return $oldValue;
    }

    private function fromJson(string $json): ?array
    {
        return json_decode($json, true);
    }

    private function toJson(array $servers): string
    {
        return json_encode($servers);
    }

    /**
     * Update server used config defaults when new server is added (runtimeOptions.plugins.Okapi.server)
     * @param array $serverList
     * @return void
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function updateServerUsedDefaults(array $serverList): void
    {
        if (empty($serverList)) {
            return;
        }
        $defaults = implode(',', array_keys($serverList));

        $this->config->loadByName(self::CONFIG_SERVER_USED);
        $this->config->setDefaults($defaults);
        $this->config->save();
    }

    /**
     * Update server used config defaults when new server is added (runtimeOptions.plugins.Okapi.server)
     * @param string $name
     * @return void
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function updateServerUsed(string $name): void
    {
        $this->config->loadByName(self::CONFIG_SERVER_USED);
        $this->config->setValue($name);
        $this->config->save();
    }

    /**
     * returns the available defaults (used for config dropdowns)
     * @param string|null $selected receives the currently configured (selected) servername
     * @return array
     */
    public function getServerUsedDefaults(?string &$selected): array
    {
        if ($this->config->getName() !== self::CONFIG_SERVER_USED) {
            $this->config->loadByName(self::CONFIG_SERVER_USED);
        }
        $defaults = explode(',', $this->config->getDefaults());
        $selected = $this->config->getValue();
        return $defaults;
    }

    /**
     * Remove non-existing server values from client overwrites and update default config to a valid one
     * @param array $serverList
     * @return void
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function cleanUpNotUsed(array $serverList): void
    {
        $config = Factory::get(editor_Models_Customer_CustomerConfig::class);
        $db = $config->db;

        $where = ['name = ? ' => self::CONFIG_SERVER_USED];

        $serverList = array_keys($serverList);

        if (!empty($serverList)) {
            $where['value NOT IN (?)'] = $serverList;
        }
        // remove all serverUsed configs with non-existing server values
        $db->delete($where);

        $this->config->loadByName(self::CONFIG_SERVER_USED);

        // if the current default config on instance level is not in the server list, we reset it to the last one
        if (!in_array($this->config->getValue(), $serverList)) {
            $this->config->setValue(end($serverList));
            $this->config->save();
        }
    }

    /***
     * Check if the removed config is used from the tasks. If yes, this action is not allowed. We can not remove
     * used config name/server.
     * @param array $serverList
     * @return int
     */
    public function countTaskUsageSum(array $serverList): int
    {
        if (empty($serverList)) {
            return 0;
        }

        $usageForAllInstances = $this->countTaskUsage();

        $foundUsages = array_intersect_key($usageForAllInstances, $serverList);
        return array_sum($foundUsages);
    }

    /***
     * Check if the removed config is used from the tasks. If yes, this action is not allowed. We can not remove
     * used config name/server.
     * @return array
     */
    public function countTaskUsage(): array
    {
        $config = Factory::get(editor_Models_TaskConfig::class);
        $db = $config->db;

        $s = $db->select()
            ->from($db, ['cnt' => 'count(*)', 'serverName' => 'value'])
            ->where('name = ?', self::CONFIG_SERVER_USED)
            ->group('value');

        // if result has values this means the removed config is used for one of the existing tasks
        $result = $db->getAdapter()->fetchAll($s);
        return array_column($result, 'cnt', 'serverName');
    }

    public function serverListFromJson(string $json): array
    {
        $serverList = $this->fromJson($json);
        if (is_null($serverList)) {
            //FIXME Logging / Exception?
            return [];
        }
        return $serverList;
    }

    /**
     * returns a summarized usage for each configured server
     * @return array
     */
    public function getSummary(): array
    {
        $this->config->loadByName(self::CONFIG_SERVER);
        $servers = $this->fromJson($this->config->getValue());
        $servers = array_map(function ($url) {
            return [
                'url' => $url,
                'customerIdUsage' => [],
                'taskUsageCount' => 0,
            ];
        }, $servers ?? []);

        $customerConfig = Factory::get(editor_Models_Customer_CustomerConfig::class);
        $s = $customerConfig->db->select()
            ->where('name = ?', self::CONFIG_SERVER_USED);

        // if result has values this means the removed config is used for one of the existing tasks
        $customerConfig = $customerConfig->db->getAdapter()->fetchAll($s);
        foreach ($customerConfig as $perCustomer) {
            $servers[$perCustomer['value']]['customerIdUsage'][] = $perCustomer['customerId'];
        }

        $taskUsage = $this->countTaskUsage();

        foreach ($taskUsage as $serverName => $usage) {
            $servers[$serverName]['taskUsageCount'] = $usage;
        }
        return $servers;
    }

    /**
     * Sets the given list of names and URLs as Okapi servers
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     */
    public function setServerList(array $serverList): void
    {
        $this->config->loadByName(self::CONFIG_SERVER);
        $this->config->setValue($this->toJson($serverList));
        $this->config->save();
        $this->updateServerUsedDefaults($serverList);
    }

    /**
     * @return array
     */
    public function getServerList(): array
    {
        $this->config->loadByName(self::CONFIG_SERVER);
        return $this->fromJson($this->config->getValue());
    }
}