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

class editor_TokenController extends ZfExtended_RestController
{
    protected $entityClass = ZfExtended_Auth_Token_Entity::class;

    /**
     * @var ZfExtended_Auth_Token_Entity
     */
    protected $entity;

    public function indexAction(): void
    {
        $this->view->rows = $this->entity->loadAllForFrontEnd();
        $this->view->total = $this->entity->getTotalCount();
    }

    public function postAction(): void
    {
        $userId = $this->getRequest()->getParam('userId');
        $description = $this->getRequest()->getParam('description');
        $expires = $this->getRequest()->getParam('expires');

        $errors = [];
        $t = ZfExtended_Zendoverwrites_Translate::getInstance();

        if (empty($userId)) {
            $errors['userId'] = $t->_('Die Parameter "userId" und "description" sind erforderlich.');
        }
        if (empty($description)) {
            $errors['description'] = $t->_('Die Parameter "userId" und "description" sind erforderlich.');
        }

        $expirationDate = $expires ? new DateTime($expires) : null;

        if ($expirationDate && $expirationDate < new DateTime()) {
            $errors['expires'] = $t->_('Das Ablaufdatum darf nicht kürzer sein als heute');
        }

        if (!empty($errors)) {
            $this->handleErrors($errors);

            return;
        }

        $user = ZfExtended_Factory::get(ZfExtended_Models_User::class);
        $user->load($userId);

        $this->view->success = true;
        $this->view->token = $this->entity->create($user->getLogin(), $description, $expirationDate);
    }

    public function putAction(): void
    {
        $this->entityLoad();

        $description = trim($this->getRequest()->getParam('description'));
        $expires = $this->getRequest()->getParam('expires');

        if (!empty($description) && $this->entity->getDescription() !== $description) {
            $this->entity->setDescription($description);
        }

        $expirationDate = $expires ? new DateTime($expires) : null;

        if ($expirationDate && $expirationDate < new DateTime()) {
            $t = ZfExtended_Zendoverwrites_Translate::getInstance();

            $this->handleErrors(['expires' => $t->_('Das Ablaufdatum darf nicht kürzer sein als heute')]);

            return;
        }

        if ($expirationDate) {
            $this->entity->setExpires($expirationDate->format('Y-m-d H:i:s'));
        }

        if (!empty($description) || !empty($expires)) {
            $this->entity->validate();
            $this->entity->save();
        }

        $this->view->rows = $this->entity->getDataObject();
    }

    public function getAction(): void
    {
        throw new ZfExtended_Models_Entity_NotFoundException();
    }

    public function deleteAction()
    {
        $this->entityLoad();
        $this->entity->delete();
    }


    public function handleErrors(array $errors): void
    {
        $e = new ZfExtended_ValidateException();
        $e->setErrors($errors);
        $this->handleValidateException($e);
    }
}
