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

use MittagQI\Translate5\Task\CustomFields\Field;

/**
 * @property Field $entity
 */
class editor_TaskcustomfieldController extends ZfExtended_RestController {

    /**
     * Use trait
     */
    use editor_Controllers_Traits_ControllerTrait;

    /***
     * Should the data post/put param be decoded to associative array
     *
     * @var bool
     */
    protected bool $decodePutAssociative = true;

    /**
     * @var string
     */
    protected $entityClass = Field::class;

    protected function decodePutData(): void
    {
        parent::decodePutData();
    }

    public function postAction()
    {
        // Call parent
        parent::postAction();

        // Set roles and refresh rights
        $this->onAfterSave();
    }

    public function putAction()
    {
        // Call parent
        parent::putAction();

        // Set roles and refresh rights
        $this->onAfterSave();
    }

    public function indexAction()
    {
        // Call parent
        parent::indexAction();

        // Make sure roles are available on frontend for each customField
        foreach ($this->view->rows as &$row) {
            $row['roles'] = $this->entity->getRoles($row['id']);
        }
    }

    private function onAfterSave() {

        // Set roles
        $this->entity->setRoles($this->data['roles']);

        // Make it possible to refresh Editor.data.app.userRights
        ZfExtended_Acl::reset();
        $this->view->userRights = ZfExtended_Acl::getInstance()->getFrontendRights(
            ZfExtended_Authentication::getInstance()->getUserRoles()
        );
    }
}