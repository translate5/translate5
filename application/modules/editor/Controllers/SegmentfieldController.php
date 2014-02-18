<?php
/**
 * Created by PhpStorm.
 * User: kkolesnikov
 * Date: 2/10/14
 * Time: 10:20 AM
 */

class Editor_SegmentfieldController extends editor_Controllers_EditorrestController{
    /**
     * @var string
     */
    protected $entityClass = 'editor_Models_SegmentFieldv';
    /**
     * @var int
     */
    protected $segmentfieldId;

    public function indexAction()
    {
        $session = new Zend_Session_Namespace();
        $this->view->rows = $this->entity->loadBytaskGuid($session->taskGuid);
        $this->view->total = count($this->view->rows);
    }

//    public function getAction()
//    {
//
//    }
//
//    public function deleteAction()
//    {
//
//    }
//
//    public function postAction()
//    {
//
//    }
//
//    public function updateAction()
//    {
//
//    }

} 