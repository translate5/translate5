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

namespace MittagQI\Translate5\Test\Integration\Maintenance;

use MittagQI\Translate5\Maintenance\CleanUpFolders;
use PHPUnit\Framework\TestCase;

class CleanUpFoldersTest extends TestCase
{
    public function testDeleteOldDateFolders()
    {
        $cleaner = new CleanUpFolders();

        // Create a temporary directory for testing
        $tempDir = sys_get_temp_dir() . '/test_cleanup_' . uniqid();
        mkdir($tempDir);

        // Create test folders
        $oldFolder = (new \DateTime('-10 days'))->format('Y-m-d');
        $newFolder = (new \DateTime('-2 days'))->format('Y-m-d');
        mkdir("$tempDir/$oldFolder");
        mkdir("$tempDir/$oldFolder/subfolder");
        touch("$tempDir/$oldFolder/some.tmx");
        touch("$tempDir/$oldFolder/subfolder/another_file.txt");
        mkdir("$tempDir/$newFolder");
        touch("$tempDir/$newFolder/some.tmx");
        mkdir("$tempDir/not_a_date");

        // Set threshold to 5 days ago
        $threshold = new \DateTime('-5 days');

        // Run the cleanup
        $cleaner->deleteOldDateFolders($tempDir, $threshold);

        // Assert old folder is deleted and new folder still exists
        $this->assertDirectoryDoesNotExist("$tempDir/$oldFolder");
        $this->assertDirectoryDoesNotExist("$tempDir/$oldFolder/subfolder");
        $this->assertFileDoesNotExist("$tempDir/$oldFolder/some.tmx");
        $this->assertFileDoesNotExist("$tempDir/$oldFolder/subfolder/another_file.txt");
        $this->assertDirectoryExists("$tempDir/$newFolder");
        $this->assertFileExists("$tempDir/$newFolder/some.tmx");
        $this->assertDirectoryExists("$tempDir/not_a_date");

        // Clean up
        unlink("$tempDir/$newFolder/some.tmx");
        rmdir("$tempDir/$newFolder");
        rmdir("$tempDir/not_a_date");
        rmdir($tempDir);
    }
}
