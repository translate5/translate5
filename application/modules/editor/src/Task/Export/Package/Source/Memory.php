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

namespace MittagQI\Translate5\Task\Export\Package\Source;

use editor_Models_LanguageResources_LanguageResource;
use editor_Services_Manager;
use editor_Services_OpenTM2_Service;
use MittagQI\Translate5\LanguageResource\TaskAssociation;
use ZfExtended_Factory;
use ZfExtended_Models_Worker;

class Memory extends Base
{
    protected string $fileName = 'tmx';

    public function validate(): void
    {
    }

    public function export(?ZfExtended_Models_Worker $workerModel): void
    {
        $service = ZfExtended_Factory::get(editor_Services_OpenTM2_Service::class);
        /** @var TaskAssociation $assoc */
        $assoc = ZfExtended_Factory::get(TaskAssociation::class);

        $assocs = $assoc->loadAssocByServiceName($this->task->getTaskGuid(), $service->getName());

        $serviceManager = ZfExtended_Factory::get(editor_Services_Manager::class);

        foreach ($assocs as $assoc) {
            $languageResource = ZfExtended_Factory::get(editor_Models_LanguageResources_LanguageResource::class);
            $languageResource->load($assoc['languageResourceId']);

            $connector = $serviceManager->getConnector($languageResource);

            $fullPath = $this->getFolderPath() . DIRECTORY_SEPARATOR . $this->fixFilename($languageResource->getName());
            $file = $connector->export($connector->getValidExportTypes()['TMX']);

            if (null === $file) {
                continue;
            }

            ['extension' => $extension] = pathinfo($file);
            $fullPath .= '.' . $extension;
            rename($file, $fullPath);
        }
    }

    private function fixFilename(string $filename): string
    {
        // Remove all special characters from the filename to prevent errors when copying it to a destination directory
        return preg_replace('/[^\pL0-9_\-\+\(\)\s]/u', '_', $filename);
    }
}
