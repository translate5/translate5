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

declare(strict_types=1);

namespace MittagQI\Translate5\T5Memory;

use editor_Models_LanguageResources_LanguageResource as LanguageResource;
use editor_Services_Manager;
use Exception;
use MittagQI\Translate5\LanguageResource\Adapter\Export\ExportTmFileExtension;
use ZfExtended_Factory;
use ZfExtended_Worker_Abstract;

class ExportMemoryWorker extends ZfExtended_Worker_Abstract
{
    private int $languageResourceId;

    private string $mime;

    private string $exportFolder;

    private LanguageResource $languageResource;

    private ExportService $exportService;

    public function __construct()
    {
        parent::__construct();
        $this->log = \Zend_Registry::get('logger')->cloneMe('editor.languageResource.tm.export');
        $this->exportService = ExportService::create();
    }

    protected function validateParameters(array $parameters): bool
    {
        if (! array_key_exists('exportFolder', $parameters)) {
            return false;
        }

        $this->exportFolder = $parameters['exportFolder'];

        if (! array_key_exists('mime', $parameters)) {
            return false;
        }

        $this->mime = $parameters['mime'];

        if (! array_key_exists('languageResourceId', $parameters)) {
            return false;
        }

        $this->languageResourceId = (int) $parameters['languageResourceId'];

        $this->languageResource = ZfExtended_Factory::get(LanguageResource::class);

        try {
            $this->languageResource->load($this->languageResourceId);
        } catch (\ZfExtended_Models_Entity_NotFoundException) {
            return false;
        }

        if (editor_Services_Manager::SERVICE_OPENTM2 !== $this->languageResource->getServiceType()) {
            return false;
        }

        return true;
    }

    protected function handleWorkerException(\Throwable $workException): void
    {
        $this->workerException = $workException;

        rmdir($this->exportFolder);
    }

    protected function work(): bool
    {
        if (is_dir($this->exportFolder)) {
            // export already running
            return true;
        }

        mkdir($this->exportFolder, 0777, true);

        $memories = $this->languageResource->getSpecificData('memories', parseAsArray: true);

        $file = $this->exportService->export(
            $this->languageResource,
            ExportTmFileExtension::fromMimeType($this->mime, $memories > 1),
        );

        if (null === $file || ! file_exists($file)) {
            throw new Exception('Export failed: Nothing was exported');
        }

        $extension = pathinfo($file, PATHINFO_EXTENSION);

        $exportFilename = $this->exportFolder . "/{$this->getModel()->getHash()}.$extension";

        rename($file, $exportFilename);

        if (! file_exists($exportFilename)) {
            throw new Exception('Export failed: Moving file to export dir failed');
        }

        return true;
    }
}
