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

use MittagQI\Translate5\Cronjob\CronIpFactory;
use MittagQI\Translate5\Plugins\TermImport\TermImport;
use MittagQI\Translate5\Plugins\TermImport\Service\Filesystem\FilesystemFactory;
use MittagQI\Translate5\Plugins\TermImport\Service\LoggerService;

/**
 */
class editor_Plugins_TermImport_TermImportController extends ZfExtended_RestController
{
    

    protected array $_unprotectedActions = [
        'filesystem',
        'crossapi',
        'force',
    ];

    /**
     * (non-PHPdoc)
     * @see ZfExtended_RestController::init()
     *
     * copied the init method, parent can not be used, since no real entity is used here
     */
    public function init()
    {
        $this->initRestControllerSpecific();
    }

    /**
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    public function filesystemAction(): void
    {
        $cronIp = CronIpFactory::create();
        if (!$cronIp->isAllowed()) {
            throw new ZfExtended_Models_Entity_NoAccessException(
                'Wrong IP to call this action! Configure cronIP accordingly!'
            );
        }

        $import = ZfExtended_Factory::get(editor_Plugins_TermImport_Services_Import::class);
        $message = $import->handleFileSystemImport();
        $this->view->messages = $message;
    }


    /**
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    public function crossapiAction(): void
    {
        $cronIp = CronIpFactory::create();
        if (!$cronIp->isAllowed()) {
            throw new ZfExtended_Models_Entity_NoAccessException(
                'Wrong IP to call this action! Configure cronIP accordingly!'
            );
        }

        $import = ZfExtended_Factory::get(editor_Plugins_TermImport_Services_Import::class);
        $message = $import->handleAccrossApiImport();
        $this->view->messages = $message;
    }

    /**
     * @throws ReflectionException
     * @throws \MittagQI\Translate5\Plugins\TermImport\Exception\TermImportException
     */
    public function forceAction(): void
    {

        $request = $this->getRequest();
        $import = new TermImport(new FilesystemFactory(new LoggerService()));

        if ($request->getParam('defaultOnly', false)) {
            $import->queueFilesystem(FilesystemFactory::DEFAULT_HOST_LABEL);

            echo 'Success!';
            return;
        }


        if ($clientIds = $request->getParam('clientIds', false)) {
            $ids = explode(',', $clientIds);

            $customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);

            foreach ($ids as $id) {
                $id = (int) trim($id);
                try {
                    $customer->load($id);
                    $import->queueFilesystem(TermImport::computeFilesystemKey($id));
                } catch (ZfExtended_Models_Entity_NotFoundException) {
                    echo "</br>Customer::[$id] was not found";
                }
            }

            echo '</br>Success!';
            return;
        }

        $import->checkFilesystems();

        echo 'Success!';
    }
}
