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

declare(strict_types=1);

use MittagQI\Translate5\Plugins\CotiHotfolder\CotiHotfolder;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\Filesystem\FilesystemFactory;

class editor_Plugins_CotiHotfolder_HotfolderController extends ZfExtended_Controllers_Action
{
    public function init()
    {
        parent::init();
        /** @phpstan-ignore-next-line */
        $this->_helper->viewRenderer->setNoRender();
        /** @phpstan-ignore-next-line */
        $this->_helper->layout->disableLayout();
    }

    public function forceAction(): void
    {
        $request = $this->getRequest();
        $coti = CotiHotfolder::create();

        if ($request->getParam('defaultOnly', false)) {
            $coti->queueFilesystem(FilesystemFactory::DEFAULT_HOST_LABEL);

            echo 'Success!';

            return;
        }

        if ($clientIds = $request->getParam('clientIds', false)) {
            $ids = explode(',', $clientIds);

            $customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);

            foreach ($ids as $id) {
                $id = (int) trim($id);

                try {
                    $customer->load($id);
                    $coti->queueFilesystem(CotiHotfolder::computeFilesystemKey($id));
                } catch (ZfExtended_Models_Entity_NotFoundException) {
                    echo "</br>Customer::[$id] was not found";
                }
            }

            echo '</br>Success!';

            return;
        }

        $coti->checkFilesystems();

        echo 'Success!';
    }
}
