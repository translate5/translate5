<?php

declare(strict_types=1);

class Editor_TmmaintenanceController extends ZfExtended_Controllers_Action
{
    public function indexAction(): void
    {
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $this->view->addScriptPath(APPLICATION_ROOT . '/application/modules/editor/Plugins/TMMaintenance/public/resources/');

        echo $this->view->render('index.php');
    }
}
