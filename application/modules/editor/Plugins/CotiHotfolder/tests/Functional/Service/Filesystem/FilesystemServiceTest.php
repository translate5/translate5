<?php
/*
START LICENSE AND COPYRIGHT
 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.
 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file is part of a paid plug-in for translate5.

 The translate5 core software and its freely downloadable plug-ins are licensed under an AGPLv3 open-source license
 (https://www.gnu.org/licenses/agpl-3.0.en.html).
 translate5 can be optained via the instructions that are linked at http://www.translate5.net
 For the license of translate5 itself please see http://www.translate5.net/license.txt
 Paid translate5 plugins can deviate from standard AGPLv3 licensing and therefore constitute an
 exception. As such, translate5 plug-ins can be licensed under either AGPLv3 or GPLv3 (see below for details).

 Briefly summarized, a GPLv3 license dictates the same conditions as its AGPLv3 variant, except that it
 does not require the program (plug-in, in this case) to direct users toward its download location if it is
 only being used via the web in a browser.
 This enables developers to write custom plug-ins for translate5 and keep them private, granted they
 meet the GPLv3 licensing conditions stated above.
 As the source code of this paid plug-in is under open source GPLv3 license, everyone who did obtain
 the source code could pass it on for free or paid to other companies or even put it on the web for
 free download for everyone.

 As this would undermine completely the financial base of translate5s development and the translate5
 community, we at MittagQI would not longer support a company or supply it with updates for translate5,
 that would pass on the source code to third parties.

 Of course as long as the code stays within the company who obtained it, you are free to do
 everything you want with the source code (within the GPLv3 boundaries), like extending it or installing
 it multiple times.

 This file is part of a plug-in for translate5 and may be used under the terms of the
 GNU GENERAL PUBLIC LICENSE version 3 as published by the Free Software Foundation and
 appearing in the file gpl3-license.txt included in the packaging of the translate5 plug-in
 to which this file belongs. Please review the following information to ensure the
 GNU GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/gpl.html

 Please see http://www.translate5.net/plugin-exception.txt or plugin-exception.txt in the
 root folder of translate5. This plug-in exception allows using GPLv3 for translate5 plug-ins,
 although translate5 core is licensed under AGPLv3.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU GENERAL PUBLIC LICENSE version 3 with plugin-execption
             http://www.gnu.org/licenses/gpl.html
             http://www.translate5.net/plugin-exception.txt
END LICENSE AND COPYRIGHT
*/
declare(strict_types=1);

namespace MittagQI\Translate5\Plugins\CotiHotfolder\Tests\Functional\Service\Filesystem;

use League\Flysystem\MountManager;
use League\Flysystem\StorageAttributes;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\Filesystem\FilesystemService;
use MittagQI\Translate5\Plugins\CotiHotfolder\Service\T5Logger;
use MittagQI\Translate5\Test\UnitTestAbstract;
use MittagQI\Translate5\Tools\FlysystemFactory;
use PHPUnit\Framework\ExpectationFailedException;
use stdClass;
use Throwable;

class FilesystemServiceTest extends UnitTestAbstract
{
    public const TEST_IN_PROGRESS_DIR = '_testInProgress';

    private string $testDir = '';

    protected function setUp(): void
    {
        $this->testDir = __DIR__ . '/../../../../../../../../../data/' . self::TEST_IN_PROGRESS_DIR
            . DIRECTORY_SEPARATOR . bin2hex(random_bytes(12));

        mkdir($this->testDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if ($this->hasFailed()) {
            return;
        }

        if (PHP_OS === 'Windows') {
            exec(sprintf("rd /s /q %s", $this->testDir));
        } else {
            exec(sprintf("rm -rf %s", $this->testDir));
        }
    }

    /**
     * @param ExpectationFailedException $t
     */
    protected function onNotSuccessfulTest(Throwable $t): void
    {
        $previous = is_a($t, 'Exception') ? $t : null; // QUIRK previous must be an Exception, Throwable is not enough !
        $comparisionFailure = method_exists($t, 'getComparisonFailure') ? $t->getComparisonFailure() : null;
        parent::onNotSuccessfulTest(new ExpectationFailedException(
            $t->getMessage() . PHP_EOL . sprintf('Test files preserved at: %s', $this->testDir),
            $comparisionFailure,
            $previous
        ));
    }

    public function testMoveSuccessfulDir(): void
    {
        $this->skipIfNeeded();
        $service = $this->prepareService('Import-running');
        $service->moveToSuccessfulDir('remote://Import-running/TestProject.coti', true);

        self::assertFileExists($this->testDir . '/archive/untranslated/TestProject.coti');
    }

    public function testMoveFailedDir(): void
    {
        $this->skipIfNeeded();
        $service = $this->prepareService('Import-running');
        $service->moveToFailedDir('remote://Import-running/TestProject.coti');

        self::assertFileExists($this->testDir . '/errors/untranslated/TestProject.coti');
    }

    public function testUploadFinishedArchive(): void
    {
        $this->skipIfNeeded();
        $service = $this->prepareService('ReadyForExport');
        $service->uploadCotiFile('remote://ReadyForExport/TestProject.coti', 'remote://translated/TestProject.coti');

        $this->assertFileExists($this->testDir . '/translated/TestProject.coti');
    }

    public function testGetReadyDirList(): void
    {
        $logger = static::createConfiguredMock(T5Logger::class, []);

        $localConfig = new stdClass();
        $localConfig->location = __DIR__ . '/_testData/';

        $manager = new MountManager([
            'local' => FlysystemFactory::create(FlysystemFactory::TYPE_LOCAL, $localConfig),
            'remote' => FlysystemFactory::create(FlysystemFactory::TYPE_LOCAL, $localConfig),
        ]);

        $service = new FilesystemService($manager, $logger);

        $files = [];

        foreach ($service->getReadyCotiFilesList('remote://') as $dir) {
            $files[] = $dir;
        }

        self::assertCount(1, $files);
        self::assertSame('remote://FilesystemServiceTest/untranslated/TestProject.coti', $files[0]);
    }

    private function skipIfNeeded(): void
    {
        if (! class_exists(FilesystemService::class)) {
            $this->markTestSkipped(
                'Class under test does not exists. Probably plugin is not symlinked to a plugin folder.'
            );
        }
    }

    private function prepareService(string $baseDirName): FilesystemService
    {
        $logger = static::createConfiguredMock(T5Logger::class, []);

        $localConfig = new stdClass();
        $localConfig->location = __DIR__ . '/_testData/FilesystemServiceTest/untranslated';

        $remoteConfig = new stdClass();
        $remoteConfig->location = $this->testDir;

        $manager = new MountManager([
            'local' => FlysystemFactory::create(FlysystemFactory::TYPE_LOCAL, $localConfig),
            'remote' => FlysystemFactory::create(FlysystemFactory::TYPE_LOCAL, $remoteConfig),
        ]);

        $this->copyFilesForTest($manager, 'local://', "remote://{$baseDirName}/");

        return new FilesystemService($manager, $logger);
    }

    private function copyFilesForTest(
        MountManager $manager,
        string $filesDir,
        string $testDir,
        ?string $baseFilesDir = null,
    ): void {
        /** @var StorageAttributes $item */
        foreach ($manager->listContents($filesDir) as $item) {
            $baseDir = $baseFilesDir ?: $filesDir;
            if ($item->isDir()) {
                $this->copyFilesForTest($manager, $item->path(), $testDir, $baseDir);

                continue;
            }

            $manager->copy($item->path(), str_replace($baseDir, $testDir, $item->path()));
        }
    }
}
