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

use MittagQI\Translate5\LanguageResource\Status as LanguageResourceStatus;

/***
 * Imports the language resource file into the language resource.
 */
class editor_Services_ImportWorker extends ZfExtended_Worker_Abstract
{
    /***
     * @var editor_Models_LanguageResources_LanguageResource
     */
    protected $languageResource;

    public function init($taskGuid = null, $parameters = [])
    {
        $this->behaviour->setConfig([
            'isMaintenanceScheduled' => 60,
        ]);
        $init = parent::init($taskGuid, $parameters);

        $workerModel = $this->workerModel;
        Zend_EventManager_StaticEventManager::getInstance()->attach('editor_Models_Terminology_Import_TbxFileImport', 'afterTermEntrySave', function (Zend_EventManager_Event $event) use ($workerModel) {
            $workerModel->updateProgress($event->getParam('progress'));
        }, 0);

        return $init;
    }

    /**
     * (non-PHPdoc)
     * @see ZfExtended_Worker_Abstract::validateParameters()
     */
    protected function validateParameters($parameters = [])
    {
        if (empty($parameters['languageResourceId'])) {
            return false;
        }

        return true;
    }

    /**
     * @see ZfExtended_Worker_Abstract::work()
     */
    public function work()
    {
        return $this->doImport();
    }

    /**
     * Import languaeg resources file from the upload file
     * @return boolean
     */
    protected function doImport()
    {
        $params = $this->workerModel->getParameters();

        $this->languageResource = ZfExtended_Factory::get('editor_Models_LanguageResources_LanguageResource');
        /* @var $languageResource editor_Models_LanguageResources_LanguageResource */
        $this->languageResource->load($params['languageResourceId']);

        $connector = $this->getConnector($this->languageResource);

        try {
            if (isset($params['addnew']) && $params['addnew']) {
                $return = $connector->addTm($params['fileinfo'], $params);
            } else {
                $return = $connector->addAdditionalTm($params['fileinfo'], $params);
            }
        } catch (Exception $e) {
            $this->log->exception($e);
            $this->languageResource->setStatus(LanguageResourceStatus::AVAILABLE);
            $this->languageResource->save();

            return false;
        }

        // Must be reloaded because the status or additional info can be changed in addTem/addAdditionalTm
        $this->languageResource->load($params['languageResourceId']);

        $this->updateLanguageResourceStatus($return);

        if (isset($params['fileinfo']['tmp_name']) && ! empty($params['fileinfo']['tmp_name']) && file_exists($params['fileinfo']['tmp_name'])) {
            //remove the file from the temp dir
            unlink($params['fileinfo']['tmp_name']);
        }

        return $return;
    }

    /**
     * Update language resources status so the resource is available again
     *
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     */
    protected function updateLanguageResourceStatus(bool $success): void
    {
        if ($success) {
            $this->languageResource->setStatus(LanguageResourceStatus::AVAILABLE);
        } else {
            $this->languageResource->setStatus(LanguageResourceStatus::ERROR);
        }

        $this->languageResource->save();
    }

    /***
     * Get the language resource connector
     *
     * @param editor_Models_LanguageResources_LanguageResource $languageResource
     * @return editor_Services_Connector
     */
    protected function getConnector($languageResource)
    {
        $serviceManager = ZfExtended_Factory::get('editor_Services_Manager');
        /* @var $serviceManager editor_Services_Manager */

        return $serviceManager->getConnector($languageResource);
    }
}
