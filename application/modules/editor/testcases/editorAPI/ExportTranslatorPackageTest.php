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

use MittagQI\Translate5\Repository\TaskRepository;
use MittagQI\Translate5\Test\Enums\TestUser;
use MittagQI\Translate5\Test\Import\Config;
use MittagQI\Translate5\Test\JsonTestAbstract;

class ExportTranslatorPackageTest extends JsonTestAbstract
{
    protected static bool $setupOwnCustomer = true;

    protected static function setupImport(Config $config): void
    {
        $sourceLanguage = 'en';
        $targetLanguage = 'de';
        $customerId = self::$ownCustomer->id;

        $config
            ->addLanguageResource('t5memory', null, $customerId, $sourceLanguage, $targetLanguage)
            ->setProperty('name', 'Some resource name')
        ;

        $config
            ->addLanguageResource('t5memory', null, $customerId, $sourceLanguage, $targetLanguage)
            ->setProperty('name', 'Resource containing special characters en > de ~`!@#$%^&*()_-+={[}]|\\:;"\'<,>.?/')
        ;

        $config->addPretranslation();

        $config
            ->addTask($sourceLanguage, $targetLanguage, $customerId, '3-segments-en-de.txt')
        ;
    }

    public function testExportLanguageResourcesContainingSpecialCharactersInName(): void
    {
        self::api()->login(TestUser::TestManager->value);
        $task = self::api()->getTask();

        $taskRepository = TaskRepository::create();
        $taskModel = $taskRepository->get($task->id);
        $taskModel->setReimportable(1);
        $taskModel->save();

        self::api()->get('editor/task/export/id/' . $task->id . '?format=package');

        $this->waitForWorker(MittagQI\Translate5\Task\Export\Exported\PackageWorker::class);

        $response = self::api()->getLastResponse();
        self::assertEquals(200, $response->getStatus());
        $workerId = json_decode($response->getBody(), true, flags: JSON_THROW_ON_ERROR)['workerId'];

        $path = self::api()->getTaskDataDirectory();
        $pathToZip = $path . \editor_Models_Task::STATE_PACKAGE_EXPORT . $workerId . '.zip';
        self::assertFileExists($pathToZip);

        $files = $this->getExportedLanguageResources($pathToZip);

        self::assertEquals(
            [
                'Resource containing special characters en _ de __________()_-+_________________.tmx',
                'Some resource name.tmx',
            ],
            $files
        );
    }

    private function getExportedLanguageResources(string $pathToZip): array
    {
        $zip = new ZipArchive();
        $zip->open($pathToZip);
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'translate5Test' . DIRECTORY_SEPARATOR;
        self::api()->rmDir($dir);

        if (! mkdir($dir) && ! is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }

        $zip->extractTo($dir);

        $files = glob($dir . 'tmx/*.*');

        self::api()->rmDir($dir);

        return array_map('basename', $files);
    }
}
