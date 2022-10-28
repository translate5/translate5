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


use MittagQI\Translate5\Task\Current\Exception;
use MittagQI\Translate5\Task\Import\FileParser\FileParserHelper;
use MittagQI\Translate5\Task\Reimport\DataProvider;
use MittagQI\Translate5\Task\Reimport\SegmentProcessor\Reimport;
use MittagQI\Translate5\Task\Reimport\Worker;
use MittagQI\Translate5\Task\TaskContextTrait;

/**
 *
 */
class editor_FileController extends ZfExtended_RestController
{

    use TaskContextTrait;

    protected $entityClass = 'editor_Models_File';

    /**
     * @var editor_Models_File
     */
    protected $entity;

    /**
     */
    public function init()
    {
        parent::init();
        $this->initCurrentTask(false);
    }

    public function indexAction()
    {
        $rows = $this->entity->loadByTaskGuid($this->getCurrentTask()->getTaskGuid());
        $this->view->assign('rows',$rows);
        $this->view->assign('total',count($rows));
    }

    /**
     * @throws ZfExtended_ErrorCodeException
     * @throws ZfExtended_Exception
     * @throws Exception
     * @throws ZfExtended_FileUploadException
     */
    public function postAction()
    {

        $fileId = $this->getParam('fileId');

        if (empty($fileId)) {
            throw new \MittagQI\Translate5\Task\Reimport\Exception('E1426');
        }

        $task = $this->getCurrentTask();

        /** @var DataProvider $dataProvider */
        $dataProvider = ZfExtended_Factory::get(DataProvider::class,[
            $fileId
        ]);
        $dataProvider->checkAndPrepare($task);

        /** @var Worker $worker */
        $worker = ZfExtended_Factory::get(Worker::class);

        // init worker and queue it
        if (!$worker->init($task->getTaskGuid(), [ 'fileId' => $fileId,  'file' => $dataProvider->getFile(), 'userGuid' => ZfExtended_Authentication::getInstance()->getUser()->getUserGuid()])) {
            throw new ZfExtended_Exception('Task ReImport Error on worker init()');
        }

        if($worker->run()){
            $dataProvider->archiveImportedData();
        }
    }
}