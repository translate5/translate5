<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5
 
 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

class Editor_ReferencefileController extends editor_Controllers_EditorrestController {

    protected $entityClass = 'editor_Models_Foldertree';

    /**
     * @var editor_Models_Foldertree
     */
    protected $entity;

    /**
     * delivers the requested file to the browser
     * (non-PHPdoc)
     * @see ZfExtended_RestController::getAction()
     */
    public function getAction() {
        /* @var $task editor_Models_Task */
        $task = ZfExtended_Factory::get('editor_Models_Task');
        $fileToDisplay = $this->getRequestedFileAbsPath($task);
        $file = new SplFileInfo($fileToDisplay);
        if (! $file->isFile()) {
            $logger = Zend_Registry::get('logger')->cloneMe('editor.referencefile');
            $logger->warn('E1216','A non existent reference file "{file}" was requested.',[
                'task' => $task,
                'file' => $this->getRequestedFileRelPath(),
            ]);
            throw new ZfExtended_NotFoundException();
        }

        if(function_exists('apache_setenv')){
            apache_setenv('no-gzip', '1');
        }
        //header("HTTP/1.1 200 OK");
        //header('HTTP/1.1 304 Not Modified');
        header('Content-Description: File Transfer');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header("Content-Type: ".$this->getMime($file), TRUE);
        flush();
        readfile($file);
        exit;
    }

    /**
     * detects the file mime type
     * @param string $file
     * @return string
     */
    protected function getMime($file) {
        $mime = @finfo_open(FILEINFO_MIME);
        $result = finfo_file($mime, $file);
        if (empty($result)) {
            $result = 'application/octet-stream';
        }
        return $result;
    }

    /**
     * returns the absolute file path to the requested file, checks the given URL on ../ based attacks
     * @return string
     */
    protected function getRequestedFileAbsPath(editor_Models_Task $task) {
        $session = new Zend_Session_Namespace();
        
        if(empty($session->taskGuid)){
            throw new ZfExtended_NotAuthenticatedException('No task registered in session!');
        }
        
        $task->loadByTaskGuid($session->taskGuid);

        $config = Zend_Registry::get('config');
        $taskPath = $task->getAbsoluteTaskDataPath();
        $refDir = $taskPath.DIRECTORY_SEPARATOR.$config->runtimeOptions->import->referenceDirectory;
        $requestedFile = $this->getRequestedFileRelPath();
        $baseReal = realpath($refDir);
        $fileReal = realpath($refDir.$requestedFile);
        if($fileReal !== $baseReal.$requestedFile) {
            return null; //tryied hacking with ../ in PathName => send nothing
        }
        return $fileReal;
    }

    /**
     * returns the file path part of the REQUEST URL
     * @return string
     */
    protected function getRequestedFileRelPath() {
        $zcf = Zend_Controller_Front::getInstance();
        $urlBase = array();
        $urlBase[] = $zcf->getBaseUrl();
        $urlBase[] = $this->getRequest()->getModuleName();
        $urlBase[] = $this->getRequest()->getControllerName();
        $urlBase = join('/', $urlBase);
        $file = str_replace('!#START'.$urlBase, '', '!#START'.$zcf->getRequest()->getRequestUri());
        $file = str_replace('/', DIRECTORY_SEPARATOR, $file); //URL to file system
        /* @var localEncoded ZfExtended_Controller_Helper_LocalEncoded */
        $localEncoded = ZfExtended_Zendoverwrites_Controller_Action_HelperBroker::getStaticHelper(
            'LocalEncoded'
        );
        return $localEncoded->encode(urldecode($file));
    }

    /**
     * sends the reference file tree as JSON
     * (non-PHPdoc)
     * @see ZfExtended_RestController::indexAction()
     */
    public function indexAction() {
        $session = new Zend_Session_Namespace();
        $this->entity->loadByTaskGuid($session->taskGuid);
        //by passing output handling, output is already JSON
        $contextSwitch = $this->getHelper('ContextSwitch');
        $contextSwitch->setAutoSerialization(false);
        $this->getResponse()->setBody($this->entity->getReferenceTreeAsJson());
    }

    public function putAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->put');
    }

    public function deleteAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->delete');
    }

    public function postAction() {
        throw new ZfExtended_BadMethodCallException(__CLASS__.'->post');
    }
}