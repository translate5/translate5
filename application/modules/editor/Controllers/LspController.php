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

use MittagQI\Translate5\LSP\LSPService;
use MittagQI\Translate5\LSP\Model\LanguageServiceProvider;

class editor_LspController extends ZfExtended_RestController
{
    /**
     * @var LanguageServiceProvider
     */
    protected $entity;

    protected $entityClass = LanguageServiceProvider::class;

    protected $postBlacklist = ['id'];

    private LSPService $lspService;

    public function init()
    {
        parent::init();
        $this->lspService = LSPService::create();
    }

    public function indexAction()
    {
        $user = ZfExtended_Authentication::getInstance()->getUser();

        $this->view->rows = $this->lspService->getViewListFor($user); // @phpstan-ignore-line
        $this->view->total = count($this->view->rows);
    }

    public function postAction()
    {
        throw new ZfExtended_Models_Entity_NotFoundException('Not implemented');
    }

    public function putAction()
    {
        throw new ZfExtended_Models_Entity_NotFoundException('Not implemented');
    }

    public function getAction(): void
    {
        throw new ZfExtended_Models_Entity_NotFoundException('Not implemented');
    }
}
