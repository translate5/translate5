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

declare(strict_types=1);

use MittagQI\ZfExtended\CsrfProtection;

/**
 * @property Zend_View $view
 */
class Editor_TmmaintenanceController extends ZfExtended_Controllers_Action
{
    public function indexAction(): void
    {
        /** @var Zend_Layout $layout */
        $layout = $this->view->layout();
        $layout->disableLayout();
        /** @var Zend_Controller_Action_Helper_ViewRenderer $helper */
        $helper = $this->_helper->getHelper('viewRenderer');
        $helper->setNoRender();
        $this->view->addScriptPath(
            APPLICATION_ROOT . '/application/modules/editor/Plugins/TMMaintenance/public/resources/'
        );
        $this->view->assign([
            'csrfToken' => CsrfProtection::getInstance()->getToken(),
            'userLogin' => ZfExtended_Authentication::getInstance()->getLogin(),
        ]);

        echo $this->view->render('index.php');
    }
}
