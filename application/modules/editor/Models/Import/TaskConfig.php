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
declare(strict_types=1);

/**
 * Loads the task-specific config from uploaded task-config and stores it to the task
 */
class editor_Models_Import_TaskConfig
{
    /**
     * @var string
     */
    public const CONFIG_TEMPLATE = 'task-config.ini';

    public function __construct(
        private readonly editor_Models_TaskConfig $taskConfig
    ) {
    }

    /***
     * Load the config template for the task if it is provided in the import package
     * @throws Exception
     */
    public function loadAndProcessConfigTemplate(
        editor_Models_Task $task,
        editor_Models_Import_Configuration $importConfig
    ): void {
        $template = $importConfig->importFolder . '/' . self::CONFIG_TEMPLATE;
        if (! file_exists($template)) {
            return;
        }
        $logData = [
            'filename' => self::CONFIG_TEMPLATE,
            'task' => $task,
        ];
        $config = parse_ini_file($template);
        $log = Zend_Registry::get('logger');
        foreach ($config as $name => $value) {
            try {
                if (str_starts_with($name, 'runtimeOptions.')) {
                    $this->taskConfig->updateInsertConfig($task->getTaskGuid(), $name, $value);

                    continue;
                }
                if ($name === 'fileFilter' && is_array($value)) {
                    foreach ($value as $filter) {
                        $importConfig->fileFilters[] = $filter;
                    }
                }
            } catch (ZfExtended_Models_Entity_Exceptions_IntegrityConstraint) {
                $logData['name'] = $name;
                $log->exception(new editor_Models_Import_FileParser_Exception('E1327', $logData), [
                    'level' => $log::LEVEL_WARN,
                ]);
            } catch (Exception $e) {
                $logData['errorMessage'] = $e->getMessage();

                throw new editor_Models_Import_FileParser_Exception('E1325', $logData);
            }
        }
    }
}
