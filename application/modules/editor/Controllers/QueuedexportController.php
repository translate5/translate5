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

use MittagQI\Translate5\Export\Model\QueuedExport;
use MittagQI\Translate5\Export\QueuedExportService;
use MittagQI\ZfExtended\Controller\Response\Header;
use MittagQI\ZfExtended\CsrfProtection;

class editor_QueuedexportController extends ZfExtended_RestController
{
    protected $entityClass = QueuedExport::class;

    private readonly QueuedExportService $exportService;

    public function __construct(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response,
        array $invokeArgs = []
    ) {
        parent::__construct($request, $response, $invokeArgs);
        $this->exportService = QueuedExportService::create();
    }

    public function viewAction()
    {
        $token = $this->getParam('token');

        if (null === $token) {
            throw new ZfExtended_NotFoundException('Token is missing');
        }

        $title = $this->getParam('title', $this->view->translate('Herunterladen'));

        $this->view->assign('title', $title);
        $this->view->assign('csrfToken', CsrfProtection::getInstance()->getToken());

        echo $this->view->render('export/export.phtml');

        exit;
    }

    public function downloadAction()
    {
        $token = $this->getParam('token');

        if (null === $token) {
            throw new ZfExtended_NotFoundException('Token is missing');
        }

        $queue = $this->exportService->getRecordByToken($token);

        if (! $this->exportService->isReady($queue)) {
            throw new ZfExtended_NotFoundException('Export is not ready yet');
        }

        $filePath = $this->exportService->composeExportFilepath($queue);
        if (! file_exists($filePath)) {
            throw new ZfExtended_NotFoundException('File no longer exists');
        }

        Header::sendDownload(
            rawurlencode($queue->getResultFileName()),
            contentType: 'application/octet-stream',
            additionalHeaders: [
                'Accept-Ranges' => 'bytes',
            ]
        );

        $resource = fopen($filePath, 'rb');
        fpassthru($resource);
        fclose($resource);

        $this->exportService->cleanUp($queue);

        exit;
    }

    public function statusAction()
    {
        $token = $this->getParam('token');

        if (null === $token) {
            throw new ZfExtended_NotFoundException('Token is missing');
        }

        $queue = $this->exportService->getRecordByToken($token);

        if (! $this->exportService->isReady($queue)) {
            echo '{"ready":false}';

            exit;
        }

        echo '{"ready":true}';

        exit;
    }
}
