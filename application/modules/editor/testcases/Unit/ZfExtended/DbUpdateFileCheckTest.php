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

namespace MittagQI\Translate5\Test\Unit\ZfExtended;

use MittagQI\ZfExtended\Models\Installer\DbUpdateFileCheck;
use PHPUnit\Framework\TestCase;

class DbUpdateFileCheckTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->tempFiles = [];
    }

    /**
     * @dataProvider segmentTableSqlProvider
     */
    public function testSanitizesSqlAndDetectsSegmentTableChanges(string $sql): void
    {
        $sql = "DELIMITER ;;\r\n" .
            "CREATE TRIGGER t1 BEFORE INSERT ON x FOR EACH ROW SET @a=1;;\r\n" .
            "DELIMITER ;\r\n" .
            $sql;

        $path = $this->createTempFile($sql, 'sql');
        $checker = new DbUpdateFileCheck($path);

        $result = $checker->checkAndSanitize();

        $this->assertNotNull($result);
        $this->assertStringNotContainsString('DELIMITER', $result);
        $this->assertStringContainsString('CREATE TRIGGER t1', $result);
        $this->assertStringContainsString('SET @a=1;', $result);
        $this->assertTrue($checker->hasSegmentTablesChanges());
    }

    public function testRejectsDefinerStatements(): void
    {
        $sql = "CREATE VIEW v1 AS SELECT 1;\n" .
            "DEFINER=`root`@`localhost` SQL SECURITY DEFINER";

        $path = $this->createTempFile($sql, 'sql');
        $checker = new DbUpdateFileCheck($path);

        $result = $checker->checkAndSanitize();

        $this->assertNull($result);
        $this->assertStringContainsString('DEFINER=', $checker->getError());
    }

    public function testRejectsDelimiterStatementsOutsideSanitizerPattern(): void
    {
        $sql = "DELIMITER $$\nCREATE PROCEDURE p1() SELECT 1;\n";

        $path = $this->createTempFile($sql, 'sql');
        $checker = new DbUpdateFileCheck($path);

        $result = $checker->checkAndSanitize();

        $this->assertNull($result);
        $this->assertStringContainsString('DELIMITER', $checker->getError());
    }

    public function testRejectsUnnamedConstraints(): void
    {
        $sql = "CREATE TABLE LEK_files (id INT PRIMARY KEY);\n" .
            "CREATE TABLE LEK_segments (\n" .
            "  fileId INT,\n" .
            "  FOREIGN KEY (fileId) REFERENCES LEK_files(id) ON DELETE CASCADE\n" .
            ");\n";

        $path = $this->createTempFile($sql, 'sql');
        $checker = new DbUpdateFileCheck($path);

        $result = $checker->checkAndSanitize();

        $this->assertNull($result);
        $this->assertStringContainsString('CONSTRAINTS', $checker->getError());
    }

    public function testAllowsNamedConstraints(): void
    {
        $sql = "CREATE TABLE LEK_files (id INT PRIMARY KEY);\n" .
            "CREATE TABLE LEK_segments (\n" .
            "  fileId INT,\n" .
            "  CONSTRAINT `LEK_segments_fileId_FK` FOREIGN KEY (fileId) REFERENCES LEK_files(id) ON DELETE CASCADE\n" .
            ");\n";

        $path = $this->createTempFile($sql, 'sql');
        $checker = new DbUpdateFileCheck($path);

        $result = $checker->checkAndSanitize();

        $this->assertNotNull($result);
        $this->assertFalse($checker->hasSegmentTablesChanges());
    }

    public function segmentTableSqlProvider(): array
    {
        return [
            'alter-add-column' => ["ALTER TABLE LEK_segments ADD COLUMN foo INT;\r\n"],
            'alter-add-column-named' => ["ALTER TABLE `LEK_segments` ADD `bar` VARCHAR(10);\r\n"],
            'alter-add-column-db-prefix' => ["ALTER TABLE `t5`.`LEK_segments` ADD COLUMN baz INT;\n"],
            'alter-drop' => ["ALTER TABLE LEK_segments DROP foo;\n"],
            'alter-drop-column' => ["ALTER TABLE LEK_segments DROP COLUMN foo;\n"],
            'alter-modify-column' => ["ALTER TABLE LEK_segments MODIFY COLUMN foo INT NOT NULL;\n"],
            'alter-change-column' => ["ALTER TABLE LEK_segments CHANGE COLUMN foo bar INT;\n"],
            'alter-rename-column' => ["ALTER TABLE LEK_segments RENAME COLUMN foo TO bar;\n"],
            'segment-data-add-column' => ["ALTER TABLE LEK_segment_data ADD COLUMN foo INT;\n"],
            'segment-data-drop-column-db-prefix' => ["ALTER TABLE t5.LEK_segment_data DROP COLUMN foo;\n"],
        ];
    }

    private function createTempFile(string $content, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 't5_dbcheck_');
        if ($path === false) {
            $this->fail('Failed to create a temp file.');
        }

        $pathWithExtension = $path . '.' . $extension;
        rename($path, $pathWithExtension);
        file_put_contents($pathWithExtension, $content);

        $this->tempFiles[] = $pathWithExtension;

        return $pathWithExtension;
    }
}
