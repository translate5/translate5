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


use MittagQI\Translate5\LanguageResource\TaskAssociation;
use MittagQI\Translate5\Task\Current\Exception;
use MittagQI\Translate5\Task\Current\NoAccessException;
use MittagQI\Translate5\Task\Lock;
use MittagQI\Translate5\Task\Reimport\DataProvider\AbstractDataProvider;
use MittagQI\Translate5\Task\Reimport\DataProvider\DataProvider;
use MittagQI\Translate5\Task\Reimport\DataProvider\FileDto;
use MittagQI\Translate5\Task\Reimport\DataProvider\ZipDataProvider;
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
     * inits the internal entity Object, handels given limit, filter and sort parameters
     *
     * @throws MittagQI\Translate5\Task\Current\Exception
     * @throws NoAccessException
     * @throws ZfExtended_Models_Entity_NotFoundException|Exception
     * @see Zend_Controller_Action::init()
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

        $this->taskReimport($fileId);
    }

    /***
     * @return void
     * @throws Throwable
     * @throws ZfExtended_Exception
     */
    public function packageAction(){
        $this->taskReimport(null);
    }

    /***
     * Reimport file or zip package into the current task
     * @param int|null $fileId
     * @return void
     * @throws Exception
     * @throws Throwable
     * @throws ZfExtended_Exception
     */
    private function taskReimport(int $fileId = null){
        $task = $this->getCurrentTask();

        $filesMetaData = $this->getTaskFilesMetaData($task);

        if ($fileId){
            $dataProvider = ZfExtended_Factory::get(DataProvider::class, [$task, $filesMetaData, $fileId]);
        } else {
            $dataProvider = ZfExtended_Factory::get(ZipDataProvider::class, [$task, $filesMetaData]);
        }
        $dataProvider->checkAndPrepare();

        /** @var Worker $worker */
        $worker = ZfExtended_Factory::get(Worker::class);

        // init worker and queue it
        if (!$worker->init($task->getTaskGuid(), [
            'files' => $dataProvider->getFiles(),// fileId => FileDto mapping
            'userGuid' => ZfExtended_Authentication::getInstance()->getUser()->getUserGuid(),
            'segmentTimestamp' => NOW_ISO,
            'dataProviderClass' => $dataProvider::class
        ])) {
            throw new ZfExtended_Exception('Task ReImport Error on worker init()');
        }

        try {
            // use blocking for tests env only
            if( APPLICATION_ENV === ZfExtended_BaseIndex::ENVIRONMENT_TEST){
                $worker->setBlocking();
            }
            $worker->queue();
            if($this->getParam('saveToMemory',false)){
                $this->queueUpdateTmWorkers();
            }

            $this->view->success = true;
        }catch (Throwable $exception){
            Lock::taskUnlock($task);
            $dataProvider->cleanup();
            throw  $exception;
        }
    }

    /***
     * Queue tm update workers for the current task. Only the writable langauge resources will be updated
     */
    protected function queueUpdateTmWorkers(): void
    {

        $assoc = ZfExtended_Factory::get(TaskAssociation::class);

        $resources = $assoc->getTaskUpdatable($this->getCurrentTask()->getTaskGuid());

        foreach ($resources as $resource){
            $worker = ZfExtended_Factory::get('editor_Models_LanguageResources_Worker');
            /* @var editor_Models_LanguageResources_Worker $worker */

            // init worker and queue it
            // Since it has to be done in a none worker request to have session access, we have to insert the worker before the taskPost
            if (!$worker->init($this->getCurrentTask()->getTaskGuid(), [
                'languageResourceId' => $resource['languageResourceId'],
                'segmentFilter' => NOW_ISO
            ])) {
                throw new ZfExtended_Exception('LanguageResource ReImport Error on worker init()');
            }
            $worker->queue();
        }
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws Zend_Exception
     * @throws JsonException
     * @return FileDto[]
     */
    private function getTaskFilesMetaData(editor_Models_Task $task): array
    {
        // load the original file tree and the files path
        $file = ZfExtended_Factory::get(editor_Models_File::class);
        $tree = ZfExtended_Factory::get(editor_Models_Foldertree::class);
        $paths = $tree->getPaths($task->getTaskGuid(), editor_Models_Foldertree::TYPE_FILE);

        $fileFilter = ZfExtended_Factory::get(editor_Models_File_FilterManager::class);
        $fileFilter->initReImport($task, 'REIMPORT_CHECK'); //FIXME make a const

        $filesMetaData = [];
        foreach ($paths as $fileId => $filePath) {
            $file->load($fileId);
            $filesMetaData[$fileId] = new FileDto(
                $fileId,
                $file->getFileParser(),
                $filePath,
                $fileFilter->applyImportFilters($filePath, $fileId)
            );
        }
        return $filesMetaData;
    }
}