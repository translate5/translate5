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

/**
 * SECTION TO INCLUDE PROGRAMMATIC LOCALIZATION
 * ============================================
 * $translate->_('config_overwrite_instance');
 * $translate->_('config_overwrite_customer');
 * $translate->_('config_overwrite_taskImport');
 * $translate->_('config_overwrite_task');
 * $translate->_('config_overwrite_user');
 * $translate->_('config_overwrite_system');
 */

use MittagQI\ZfExtended\Acl\ConfigRestrictionResource;

/**
 * TODO: config validator. It needs to check if the field is requeired or not
 */
class editor_Models_Config extends ZfExtended_Models_Config
{
    public const CONFIG_SOURCE_DB = "db";

    public const CONFIG_SOURCE_INI = "ini";

    public const CONFIG_SOURCE_SYSTEM = "system"; // the source is zf_configuration (loaded from database table) / this is same as source_db

    public const CONFIG_SOURCE_CUSTOMER = "customer"; // the source is customer specific config (loaded from database table)

    public const CONFIG_SOURCE_TASK = "task"; //the source is task specific config (loaded from database table)

    public const CONFIG_SOURCE_USER = "user"; //the source is user specific config (loaded from database table)

    /**
     * invisible in the Frontend
     */
    public const CONFIG_LEVEL_SYSTEM = 1;

    /**
     * editable in system config
     */
    public const CONFIG_LEVEL_INSTANCE = 2;

    /**
     * editable in system & customer config
     */
    public const CONFIG_LEVEL_CUSTOMER = 4;

    /**
     * editable in system & customer & task config, freezed thereafter (not editable anymore after import)
     */
    public const CONFIG_LEVEL_TASKIMPORT = 8;

    /**
     * editable in system & customer & task config, throughout the lifetime of a task
     */
    public const CONFIG_LEVEL_TASK = 16;

    /**
     * editable by the user in the UI, preset-able on the previous levels
     */
    public const CONFIG_LEVEL_USER = 32;

    private const CUSTOMER_CONFIG_LEVELS = [
        self::CONFIG_LEVEL_CUSTOMER,
        self::CONFIG_LEVEL_TASK,
        self::CONFIG_LEVEL_TASKIMPORT,
        self::CONFIG_LEVEL_USER,
    ];

    // system 1 (default), instance 2, customer 4, task 8 , user 16
    protected $configLabel = [
        self::CONFIG_LEVEL_SYSTEM => 'system',
        self::CONFIG_LEVEL_INSTANCE => 'instance',
        self::CONFIG_LEVEL_CUSTOMER => 'customer',
        self::CONFIG_LEVEL_TASKIMPORT => 'taskImport',
        self::CONFIG_LEVEL_TASK => 'task',
        self::CONFIG_LEVEL_USER => 'user',
    ];

    /**
     * Validate if the current user config load is for the current user
     * @throws editor_Models_ConfigException
     */
    public static function checkUserGuid(string $userGuid): void
    {
        if (ZfExtended_Authentication::getInstance()->getUserGuid() != $userGuid) {
            throw new editor_Models_ConfigException('E1299');
        }
    }

    /**
     * Load configs fron the database by given level
     * @param array $excludeType config types to be excluded, expect a typeClass is set!
     * @throws Zend_Exception
     */
    protected function loadByLevel(mixed $level, array $excludeType = [], bool $accessRestricted = false): array
    {
        if (! is_array($level)) {
            $level = [$level];
        }

        $s = $this->db->select()
            ->from(
                'Zf_configuration',
                ['Zf_configuration.*',
                    new Zend_Db_Expr($this->db->getAdapter()->quote(self::CONFIG_SOURCE_DB) . ' as origin')]
            )
            ->where('level & ? > 0', array_sum($level));

        if ($accessRestricted) {
            $user = ZfExtended_Authentication::getInstance()->getUser();
            $acl = ZfExtended_Acl::getInstance();
            $restrictionLevels = $acl->getRightsToRolesAndResource(
                $user->getRoles(),
                ConfigRestrictionResource::ID
            );
            $s->where('accessRestriction IN(?)', $restrictionLevels);
        }

        if (! empty($excludeType)) {
            $s->where('(type NOT IN(?)', $excludeType);
            $s->orWhere('typeClass IS NOT NULL)');
        }
        $pm = Zend_Registry::get('PluginManager');
        /* @var $pm ZfExtended_Plugin_Manager */

        //filter out all inactive plugins
        foreach ($pm->getInactivePluginsConfigPrefix() as $filter) {
            $s->where('lower(name) NOT LIKE ?', strtolower($filter) . '%');
        }
        $dbResults = $this->loadFilterdCustom($s);

        $dbResultsNamed = [];
        //merge the ini with zfconfig values
        $iniOptions = Zend_Registry::get('bootstrap')->getApplication()->getOptions();

        $typeManager = Zend_Registry::get('configTypeManager');
        /* @var $typeManager ZfExtended_DbConfig_Type_Manager */

        foreach ($dbResults as &$row) {
            $this->mergeWithIni($iniOptions, explode('.', $row['name']), $row);
            $type = $typeManager->getType($row['typeClass']);
            if ($type === null) {
                continue;
            }
            $row['typeClassGui'] = $type->getGuiViewCls(); //we can overwrite the typeClass here, since php class value is not usable in GUI
            $row['defaults'] = $type::getDefaultList($row['defaults']);
            $dbResultsNamed[$row['name']] = $row;
        }

        return $dbResultsNamed;
    }

    /***
     * Load all zf configuration values merged with the user config values and installation.ini vaues. The user config value will
     * override the zf confuguration/ini (default) values.
     * Config level and user role map:
     *
     *  CONFIG_LEVEL_SYSTEM=1;    //system configuration.
     *  CONFIG_LEVEL_INSTANCE=2;  //(zf_configuration properties) API and ADMIN user ↓
     *  CONFIG_LEVEL_CUSTOMER=4;  //customer configuration
     *  CONFIG_LEVEL_TASK=8;      //task configuration PM Users           ↓
     *  CONFIG_LEVEL_USER=16;     //user configuration. State fields and user custom configuration. ALL other Users   ↓
     *
     * @param string $nameFilter optional config name filter, applied with like (% must be provided in $nameFilter as desired)
     * @return array
     */
    public function loadAllMerged(string $nameFilter = null): array
    {
        $user = ZfExtended_Authentication::getInstance()->getUser();

        //get all application config level for the user
        $userLevelStrings = $user->getApplicationConfigLevel();

        $userLevelInt = array_sum(array_unique(array_map([$this, 'convertStringLevelToInt'], $userLevelStrings)));

        $s = $this->db->select()
            ->from('Zf_configuration')
            ->where('level & ? > 0', $userLevelInt);
        if (! empty($nameFilter)) {
            $s->where('name like ?', $nameFilter);
        }
        $dbResults = $this->loadFilterdCustom($s);
        $dbResultsNamed = $this->nameAsKey($dbResults);

        return array_values($this->mergeUserValues($user->getUserGuid(), $dbResultsNamed));
    }

    /**
     * overrides the DB config values from the user config
     * The result array keys are set from the config name.
     */
    public function mergeUserValues(string $userGuid, array $dbResults = [], bool $accessRestricted = false): array
    {
        if (empty($dbResults)) {
            $dbResults = $this->loadByLevel(self::CONFIG_LEVEL_USER, accessRestricted: $accessRestricted);
        }
        array_walk($dbResults, function (&$r) use ($userGuid) {
            $r['userGuid'] = $userGuid;
        });
        $s = $this->db->select()
            ->setIntegrityCheck(false)
            ->from('LEK_user_config')
            ->where('userGuid = ?', $userGuid);
        $userResults = $this->db->fetchAll($s)->toArray();

        return $this->mergeConfig($userResults, $dbResults, self::CONFIG_SOURCE_USER);
    }

    /**
     * Load all task specific config with customer specific base. The base customer is the task customer.
     * The result array keys are set from the config name.
     *
     * @throws ZfExtended_Models_Entity_NotFoundException|ReflectionException
     */
    public function mergeTaskValues(
        string $taskGuid,
        array $dbResults = [],
        bool $excludeMaps = true,
        bool $accessRestricted = false
    ): array {
        $task = ZfExtended_Factory::get(editor_Models_Task::class);
        $task->loadByTaskGuid($taskGuid);

        //when the task is not with state import or project, change for task config and task import config is allowed
        $isImportDisabled = ! in_array($task->getState(), [$task::STATE_IMPORT, $task::STATE_PROJECT]);

        //load the task customer config as config base for this task
        //on customer level, we can override task specific config. With this, those overrides will be loaded
        //and used as base value in task config window
        //configs with customer level will be marked as readonly on the frontend
        $customerBase = $this->mergeCustomerValues(
            (int) $task->getCustomerId(),
            $dbResults,
            self::CUSTOMER_CONFIG_LEVELS,
            $excludeMaps,
            $accessRestricted,
        );
        array_walk($customerBase, function (&$r) use ($taskGuid, $isImportDisabled, $task) {
            $r['taskGuid'] = $taskGuid;
            //it is readonly when the config is import config and the task is not in import state
            //or when the current config is customer level config
            //or when the task is ended already
            $r['isReadOnly'] = ($isImportDisabled && $r['level'] == self::CONFIG_LEVEL_TASKIMPORT)
                || $r['level'] == self::CONFIG_LEVEL_CUSTOMER
                || $task->getState() == $task::STATE_END;
        });
        $s = $this->db->select()
            ->setIntegrityCheck(false)
            ->from('LEK_task_config')
            ->where('taskGuid = ?', $taskGuid);
        //load all task specific configs
        $taskSpecificConfig = $this->db->fetchAll($s)->toArray();

        // update the configBase with $taskSpecificConfig values
        return $this->mergeConfig($taskSpecificConfig, $customerBase, self::CONFIG_SOURCE_TASK);
    }

    /***
     * Load all configs which can be override by customer.
     * You can also override task specific configs on customer level. Then the customer override value will be used
     * as base value when no taks override exist.
     * The result array keys are set from the config name.
     *
     * @param int $customerId
     * @param array $dbResults
     * @param array $level
     * @return array
     */
    public function mergeCustomerValues(
        int $customerId,
        array $dbResults = [],
        array $level = self::CUSTOMER_CONFIG_LEVELS,
        bool $excludeMaps = true,
        bool $accessRestricted = false,
    ): array {
        if (empty($dbResults)) {
            //include task levels so we can set the baase values for task config
            //do not load all map config types (usualy default state) since no config editor for the frontend
            //is available for now
            $dbResults = $this->loadByLevel(
                $level,
                $excludeMaps ? [ZfExtended_DbConfig_Type_CoreTypes::TYPE_MAP] : [],
                $accessRestricted
            );
        }
        array_walk($dbResults, function (&$r) use ($customerId) {
            $r['customerId'] = $customerId;
        });
        $s = $this->db->select()
            ->setIntegrityCheck(false)
            ->from('LEK_customer_config')
            ->where('customerId = ?', $customerId);
        $userResults = $this->db->fetchAll($s)->toArray();

        return $this->mergeConfig($userResults, $dbResults, self::CONFIG_SOURCE_CUSTOMER);
    }

    /***
     * Load all configs for which the current user is allowed to see.
     * The result array keys are set from the config name.
     *
     * @param array $dbResults
     * @return array
     * @throws ReflectionException
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    public function mergeInstanceValue(array $dbResults = [], bool $accessRestricted = false): array
    {
        if (empty($dbResults)) {
            $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
            $user->load(ZfExtended_Authentication::getInstance()->getUserId());
            //get all application config level for the user
            $levels = [];
            // important: the frontend shall just see levels above system level no matter what ACLs might exist
            foreach ($user->getApplicationConfigLevel() as $appConfigLevel) {
                $level = $this->convertStringLevelToInt($appConfigLevel);
                if ($level >= self::CONFIG_LEVEL_INSTANCE) {
                    $levels[] = $level;
                }
            }
            $levels = array_unique($levels);
            // do not load all map config types (usualy default state) since no config editor for the frontend
            // is available for all types
            $dbResults = $this->loadByLevel($levels, [ZfExtended_DbConfig_Type_CoreTypes::TYPE_MAP], $accessRestricted);
        }

        return $this->mergeConfig([], $dbResults, self::CONFIG_LEVEL_SYSTEM);
    }

    /***
     * Merge the input array into the result array. Values will be merged only if the config from
     * the input array exisit in the result array
     * @param array $input
     * @param array $result
     * @param string $configSource
     * @return array
     */
    protected function mergeConfig(array $input, array $result, string $configSource)
    {
        foreach ($input as $row) {
            if (! empty($result[$row['name']])) {
                $row['overwritten'] = $row['value'];
                $result[$row['name']]['overwritten'] = $result[$row['name']]['value'];
                $result[$row['name']]['value'] = $row['value'];
                $result[$row['name']]['origin'] = $configSource;
                $result[$row['name']]['defaults'] = $this->getDefaultsFromTypeClass(
                    $result[$row['name']]['defaults'],
                    $result[$row['name']]['typeClass']
                );
            }
        }

        return $result;
    }

    public function getDefaultsFromTypeClass(?string $defaults, ?string $typeClass): ?string
    {
        if (! empty($typeClass) && is_subclass_of($typeClass, ZfExtended_DbConfig_Type_Abstract::class)) {
            return $typeClass::getDefaultList($defaults);
        }

        return $defaults;
    }

    /**
     * returns true if installation.ini has a same named entry
     * @throws Zend_Exception
     */
    public function hasIniEntry(): bool
    {
        $iniOptions = Zend_Registry::get('bootstrap')->getApplication()->getOptions();
        $name = $this->getName();
        $name = explode('.', $name);
        foreach ($name as $key) {
            if (! array_key_exists($key, $iniOptions)) {
                return false;
            }
            $iniOptions = $iniOptions[$key];
        }

        return true;
    }

    /**
     * Returns the level integer value to a named level value
     */
    protected function convertStringLevelToInt(string $level): int
    {
        $const = 'self::CONFIG_LEVEL_' . strtoupper($level);
        if (defined($const)) {
            return constant($const);
        }

        return 0;
    }

    /**
     * Merges the ini config values into the DB result (tree based!)
     * @param array $row given as reference, the ini values are set in here
     */
    protected function mergeWithIni(array $root, array $path, array &$row)
    {
        $row['origin'] = $row['origin'] ?? editor_Models_Config::CONFIG_SOURCE_DB;
        $part = array_shift($path);
        if (! isset($root[$part])) {
            return;
        }
        if (! empty($path)) {
            $this->mergeWithIni($root[$part], $path, $row);

            return;
        }
        $row['origin'] = editor_Models_Config::CONFIG_SOURCE_INI;
        $row['overwritten'] = $row['value'];
        $row['value'] = $root[$part];
        if ($row['type'] == ZfExtended_DbConfig_Type_CoreTypes::TYPE_MAP || $row['type'] == ZfExtended_DbConfig_Type_CoreTypes::TYPE_LIST) {
            $row['value'] = json_encode($row['value'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * merges the ini values into the given array (rows of config entries)
     * @throws Zend_Exception
     */
    public function mergeIniValues(array $rows): array
    {
        $iniOptions = Zend_Registry::get('bootstrap')->getApplication()->getOptions();
        foreach ($rows as &$row) {
            $this->mergeWithIni($iniOptions, explode('.', $row['name']), $row);
        }

        return $rows;
    }

    /**
     * @return array values are all constant values which names match filter
     */
    public function getFilteredConstants(string $filter)
    {
        $refl = new ReflectionClass($this);
        $consts = $refl->getConstants();
        $filtered = [];
        foreach ($consts as $const => $val) {
            if (strpos($const, $filter) !== false) {
                $filtered[$const] = $val;
            }
        }

        return $filtered;
    }

    /**
     * @see ZfExtended_Models_Config::loadListByNamePart()
     */
    public function loadListByNamePart(string $name)
    {
        return $this->mergeIniValues(parent::loadListByNamePart($name));
    }

    public function getConfigLevelLabel(int $level)
    {
        return $this->configLabel[$level] ?? null;
    }

    /***
     * Set the key of the input array from the config name
     * @param array $input
     * @return array[]
     */
    public function nameAsKey(array $input)
    {
        $out = [];
        foreach ($input as $row) {
            $out[$row['name']] = $row;
        }

        return $out;
    }

    /**
     * Return map of level(int key) and translated level name
     * @return array
     */
    public function getLabelMap()
    {
        $translate = ZfExtended_Zendoverwrites_Translate::getInstance();

        /* @var $translate ZfExtended_Zendoverwrites_Translate */
        return array_map(function ($value) use ($translate) {
            //prefix is requred so we do not overwrite the original translation
            return $translate->_('config_overwrite_' . $value);
        }, $this->configLabel);
    }

    /**
     * Get value for given name from main configuration table
     */
    public function getCurrentValue(string $name): ?string
    {
        return $this->loadListByNamePart($name)[0]['value'] ?? '';
    }
}
