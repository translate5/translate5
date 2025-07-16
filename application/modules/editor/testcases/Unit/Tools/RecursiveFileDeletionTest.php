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

namespace MittagQI\Translate5\Test\Unit\Tools;

use MittagQI\ZfExtended\Tools\RecursiveFileDeletion;
use PHPUnit\Framework\TestCase;

class RecursiveFileDeletionTest extends TestCase
{
    private int $now;

    private string $dir;

    /**
     * @var array|string[]
     */
    private array $fileList;

    public function setUp(): void
    {
        $this->now = time();
        chdir(sys_get_temp_dir());
        $this->dir = basename(__FILE__);

        // setup test files and folders
        @mkdir($this->dir . '/one', recursive: true);
        touch($this->dir . '/one/test.txt');
        touch($this->dir . '/one/test.xxx');
        touch($this->dir . '/one/test_old.txt', $this->now - 1000);
        @mkdir($this->dir . '/two', recursive: true);
        touch($this->dir . '/two/test.txt');
        touch($this->dir . '/two/test.xxx');
        touch($this->dir . '/two/test_old.txt', $this->now - 1000);

        // define comparsion list
        $this->fileList = [
            'RecursiveFileDeletionTest.php',
            'RecursiveFileDeletionTest.php/one',
            'RecursiveFileDeletionTest.php/one/test.txt',
            'RecursiveFileDeletionTest.php/one/test_old.txt',
            'RecursiveFileDeletionTest.php/one/test.xxx',
            'RecursiveFileDeletionTest.php/two',
            'RecursiveFileDeletionTest.php/two/test.txt',
            'RecursiveFileDeletionTest.php/two/test_old.txt',
            'RecursiveFileDeletionTest.php/two/test.xxx',
        ];
    }

    public function testRecursiveFileDeletion(): void
    {
        //dry run first
        $delete = new RecursiveFileDeletion(true);
        $delete->deleteOldFiles($this->dir, $this->now);
        $this->assertFileExists($this->dir . '/one/test_old.txt');
        $this->assertFileExists($this->dir . '/two/test_old.txt');

        //if any of the other dry runs are not working, this will fail below in the real deletion test
        $delete->recursiveDelete($this->dir, ['xxx']);
        $this->assertFileExists($this->dir . '/one/test.xxx');
        $this->assertFileExists($this->dir . '/two/test.xxx');

        $delete->recursiveDelete($this->dir, doDeletePassedDirectory: false);
        $delete->recursiveDelete($this->dir);

        $this->assertFileExists($this->dir . '/one/test.txt');
        $this->assertFileExists($this->dir . '/two/test.txt');

        $delete = new RecursiveFileDeletion();

        $delete->deleteOldFiles($this->dir, $this->now);
        $this->removeFromFilelistAndAssertDeleteList('test_old', $delete->getDeletedElements());
        $this->assertFileStructureOnDisk($this->fileList);
        $delete->resetDeletedElements();

        $delete->recursiveDelete($this->dir, ['xxx']);
        $this->removeFromFilelistAndAssertDeleteList('xxx', $delete->getDeletedElements());
        $this->assertFileStructureOnDisk($this->fileList);
        $delete->resetDeletedElements();

        $delete->recursiveDelete($this->dir, doDeletePassedDirectory: false);
        $this->removeFromFilelistAndAssertDeleteList('txt', $delete->getDeletedElements());
        $this->assertFileStructureOnDisk($this->fileList);
        $delete->resetDeletedElements();

        $delete->recursiveDelete($this->dir);
        $this->assertDirectoryDoesNotExist($this->dir);
    }

    private function assertFileStructureOnDisk(array $fileList): void
    {
        exec('find ' . $this->dir, $output);
        sort($output);
        sort($fileList);
        $this->assertEquals($fileList, $output);
    }

    private function removeFromFilelistAndAssertDeleteList(string $toDelete, array $deleteList): void
    {
        $deletedFiles = [];

        //remove the desired files from the comparsion list
        $this->fileList = array_filter($this->fileList, function ($item) use ($toDelete, &$deletedFiles) {
            if (str_contains($item, $toDelete)) {
                if (! str_starts_with($item, '/')) {
                    $item = sys_get_temp_dir() . '/' . $item;
                }
                $deletedFiles[] = $item;

                return false;
            }

            return true;
        });
        sort($deleteList);
        sort($deletedFiles);
        $this->assertEquals($deletedFiles, $deleteList);
        $this->assertFileStructureOnDisk($this->fileList);
    }
}
