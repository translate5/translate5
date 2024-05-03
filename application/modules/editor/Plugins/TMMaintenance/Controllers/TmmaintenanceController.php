<?php

declare(strict_types=1);

use MittagQI\ZfExtended\CsrfProtection;

class Editor_TmmaintenanceController extends ZfExtended_Controllers_Action
{
    public function indexAction(): void
    {
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $this->view->addScriptPath(APPLICATION_ROOT . '/application/modules/editor/Plugins/TMMaintenance/public/resources/');

        $this->view->csrfToken = CsrfProtection::getInstance()->getToken();

        echo $this->view->render('index.php');
    }
}
