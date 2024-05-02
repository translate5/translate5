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

use MittagQI\Translate5\Task\Current\NoAccessException;
use MittagQI\Translate5\Task\TaskContextTrait;

class Editor_SegmentuserassocController extends ZfExtended_RestController
{
    use TaskContextTrait;

    protected $entityClass = 'editor_Models_SegmentUserAssoc';

    /**
     * @var editor_Models_SegmentUserAssoc
     */
    protected $entity;

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws NoAccessException
     */
    public function init()
    {
        parent::init();
        $this->initCurrentTask();
    }

    public function deleteAction()
    {
        $id = (int) $this->_getParam('id');

        try {
            $this->entity->load($id);
            $this->entity->delete();
        } catch (ZfExtended_Models_Entity_NotFoundException $e) {
            //do nothing, since already deleted!
        }
    }

    /**
     * @throws \MittagQI\Translate5\Task\Current\Exception
     * @throws Zend_Db_Statement_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_NoAccessException
     * @throws ZfExtended_ValidateException
     */
    public function postAction()
    {
        $this->decodePutData();
        $this->checkSegmentTaskGuid($this->data->segmentId);
        $this->entity->createAndSave($this->getCurrentTask()->getTaskGuid(), (int) $this->data->segmentId, ZfExtended_Authentication::getInstance()->getUserGuid());
        $this->view->rows = $this->entity->getDataObject();
    }

    /**
     * compares the taskGuid of the desired segment and the actually loaded taskGuid
     * @throws ZfExtended_Models_Entity_NoAccessException
     */
    protected function checkSegmentTaskGuid(int $segmentId)
    {
        $segment = ZfExtended_Factory::get('editor_Models_Segment');
        /* @var $segment editor_Models_Segment */
        $segment->load($segmentId);
        $this->validateTaskAccess($segment->getTaskGuid());
    }
}
