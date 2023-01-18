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

namespace MittagQI\Translate5\Task\Export\Package\Source;

use editor_Models_Export_Terminology_Tbx;
use editor_Services_TermCollection_Service;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\Task\Export\Package\ExportSource;
use ZfExtended_Factory;
use ZfExtended_Models_User;
use ZfExtended_Models_Worker;
use ZfExtended_Zendoverwrites_Controller_Action_HelperBroker;

class Collection extends Base
{

    protected string $fileName = 'tbx';

    /**
     * @return void
     */
    public function validate(): void
    {
    }

    /***
     * @param ZfExtended_Models_Worker|null $workeModel
     * @return void
     * @throws \Zend_Db_Statement_Exception
     * @throws \ZfExtended_Models_Entity_NotFoundException
     */
    public function export(?ZfExtended_Models_Worker $workeModel): void
    {
        $params = $workeModel->getParameters();

        $service=ZfExtended_Factory::get(editor_Services_TermCollection_Service::class);
        /** @var TaskAssociation $assoc */
        $assoc = ZfExtended_Factory::get(TaskAssociation::class);

        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $user->loadByGuid($params['userGuid']);

        $assocs = $assoc->loadAssocByServiceName($this->task->getTaskGuid(),$service->getName());

        $export = ZfExtended_Factory::get(editor_Models_Export_Terminology_Tbx::class);
        $export->setExportAsFile(true);

        $localEncoded = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'LocalEncoded'
        );

        foreach ($assocs as $item) {
            $filePath = $localEncoded->encode($this->getFolderPath().DIRECTORY_SEPARATOR.$item['languageResourceId'].'.tbx');
            $export->setFile($filePath);
            $export->exportCollectionById($item['languageResourceId'],$user->getUserName());
        }
    }
}