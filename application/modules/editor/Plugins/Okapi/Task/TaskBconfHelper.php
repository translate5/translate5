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

namespace MittagQI\Translate5\Plugins\Okapi\Task;

use editor_Models_Task;
use editor_Plugins_Okapi_Init;
use MittagQI\Translate5\Plugins\Okapi\Bconf\BconfEntity;
use MittagQI\Translate5\Plugins\Okapi\Worker\OkapiWorkerHelper;

/**
 * Helper class to manage the BCONF's used for import/export
 */
final class TaskBconfHelper
{
    /**
     * Caches a Bconf with a certain ID to prevent multiple DB queries
     * This bconf is evaluated in an import request multiple times on diferent events
     */
    private BconfEntity $cachedBconf;

    /**
     * Caches the system default bconf
     */
    private BconfEntity $cachedSysBconf;

    /**
     * Caches the bconf for the defaultcustomer
     */
    private BconfEntity $cachedDefaultBconf;

    /**
     * Retrieves the path to the task export-BCONF - if the task used a bconf from the file-format-settings
     * If a task was imported with a BCONF in the ZIP or the task is historic (before release of the file-format-settings),
     * a general export-BCONF is used (what can cause problems with advanced features like linked subfilters in the BCONF)
     * @throws \MittagQI\Translate5\Plugins\Okapi\OkapiException
     * @throws \ZfExtended_Models_Entity_NotFoundException
     */
    public function getExportBconfPath(editor_Models_Task $task): string
    {
        // the copy normally should exist
        $bconfPath = $this->createExportBconfCopyPath($task);
        if (file_exists($bconfPath)) {
            return $bconfPath;
        }
        // otherwise we try the original bconf - if it still exists and it was not a zip-based task
        $bconfPath = $this->createExportBconfOriginalPath($task);
        if ($bconfPath !== null && file_exists($bconfPath)) {
            error_log(
                'The export-bconf was not available in the okapi-data folder and the current export-bconf' .
                ' of the originally used import-bconf had to be used'
            );

            return $bconfPath;
        }
        error_log(
            'The export-bconf was not available in the okapi-data folder' .
            ' and originally the BCONF was used from the ZIP' .
            ' so the system-default export bconf had to be used'
        );

        // as a last resort, we use the system default export bconf
        return editor_Plugins_Okapi_Init::getDataDir() . editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_EXPORT;
    }

    /**
     * Creates the path of the copy of the export-bconf in the tasks okapi-data dir
     * The bconf is stored there to be able to change/remove a bconf independently of any existing tasks
     */
    public function createExportBconfCopyPath(editor_Models_Task $task): string
    {
        return $task->getAbsoluteTaskDataPath() . '/' .
            OkapiWorkerHelper::OKAPI_REL_DATA_DIR . '/' .
            OkapiWorkerHelper::EXPORT_BCONF_FILE;
    }

    /**
     * Creates the path of the original of the export-bconf - which is saved in the bconf data-dir
     * When the bconf does not exst anymore, this returns null
     * @throws \MittagQI\Translate5\Plugins\Okapi\OkapiException
     * @throws \ZfExtended_Models_Entity_NotFoundException
     */
    public function createExportBconfOriginalPath(editor_Models_Task $task): ?string
    {
        $tbAssoc = TaskBconfAssocRepository::create()->findForTask($task->getTaskGuid());
        if (! empty($tbAssoc->getBconfId())) {
            $bconf = new BconfEntity();
            $bconf->load($tbAssoc->getBconfId());

            return $bconf->getPath(true);
        }

        return null;
    }

    /**
     * Retrieves the bconf to use for a task
     * This requiers the task to be saved and thus having valid meta entries!
     */
    public function getImportBconf(editor_Models_Task $task): BconfEntity
    {
        // may the task-type is set to use the system-default
        if ($task->getTaskType()->useSystemDefaultFileFormatSettings()) {
            return $this->getSystemDefaultBconf();
        }
        $tbAssoc = TaskBconfAssocRepository::create()->findForTask($task->getTaskGuid());

        return $this->getImportBconfById($task, $tbAssoc->getBconfId());
    }

    /**
     * Retrieves the system default bconf
     * This is the BCONF with the name "Translate5-Standard"
     * @throws \MittagQI\Translate5\Plugins\Okapi\OkapiException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Zend_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    public function getSystemDefaultBconf(): BconfEntity
    {
        if (! isset($this->cachedSysBconf)) {
            $this->cachedSysBconf = BconfEntity::getSystemDefaultBconf();
        }

        return $this->cachedSysBconf;
    }

    /**
     * Retrieves the default-customer bconf.
     * This is the BCONF set as default for the "defaultcustomer"
     * @throws \MittagQI\Translate5\Plugins\Okapi\OkapiException
     * @throws \ReflectionException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Zend_Db_Table_Row_Exception
     * @throws \Zend_Exception
     * @throws \ZfExtended_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws \ZfExtended_NoAccessException
     * @throws \ZfExtended_UnprocessableEntity
     */
    public function getDefaultCustomerBconf(): BconfEntity
    {
        if (! isset($this->cachedDefaultBconf)) {
            $defaultCustomer = \ZfExtended_Factory::get(\editor_Models_Customer_Customer::class);
            $defaultCustomer->loadByDefaultCustomer();
            $bconf = new BconfEntity();
            $this->cachedDefaultBconf = $bconf->getDefaultBconf((int) $defaultCustomer->getId());
        }

        return $this->cachedDefaultBconf;
    }

    /**
     * Fetches the import BCONF to use by id
     * @throws \MittagQI\Translate5\Plugins\Okapi\OkapiException
     * @throws \Zend_Db_Statement_Exception
     * @throws \Zend_Db_Table_Row_Exception
     * @throws \Zend_Exception
     * @throws \ZfExtended_Exception
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws \ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws \ZfExtended_Models_Entity_NotFoundException
     * @throws \ZfExtended_NoAccessException
     * @throws \ZfExtended_UnprocessableEntity
     */
    public function getImportBconfById(
        editor_Models_Task $task,
        int $bconfId = null,
    ): BconfEntity {
        // this may be called multiple times when processing the import upload, so we better cache it
        if (! empty($bconfId) && isset($this->cachedBconf) && (int) $this->cachedBconf->getId() === $bconfId) {
            return $this->cachedBconf;
        }
        $bconf = new BconfEntity();
        // empty covers "not set" and also invalid id '0'
        // somehow dirty: unit tests pass a virtual" bconf-id of "0" to signal to use the system default bconf
        if (empty($bconfId)) {
            $bconf = $bconf->getDefaultBconf((int) $task->getCustomerId());
        } else {
            $bconf->load($bconfId);
        }
        // we update outdated bconfs when accessing them
        $bconf->repackIfOutdated();
        $this->cachedBconf = $bconf;

        return $bconf;
    }

    /**
     * Finds bconf-files in the given directory and returns them as array for the Okapi Import.
     * This API is outdated and only used for the aligned XML/XSLT import in the visual
     */
    public function findImportBconfFileInDir(string $dir): ?string
    {
        $directory = new \DirectoryIterator($dir);
        foreach ($directory as $fileinfo) {
            /** @var \SplFileInfo $fileinfo */
            if (strtolower($fileinfo->getExtension()) === BconfEntity::EXTENSION) {
                return $fileinfo->getPathname();
            }
        }

        return null;
    }
}
