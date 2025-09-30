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

namespace MittagQI\Translate5\Maintenance;

use DateTime;
use DirectoryIterator;
use Exception;
use FilesystemIterator;
use InvalidArgumentException;

class CleanUpFolders
{
    public function deleteOldDateFolders(string $parentDir, DateTime $threshold): void
    {
        if (!is_dir($parentDir)) {
            throw new InvalidArgumentException("Invalid directory: $parentDir");
        }

        foreach (new DirectoryIterator($parentDir) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isDir()) {
                continue;
            }

            $folderName = $fileInfo->getFilename();

            // Check if folder name matches YYYY-MM-DD
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $folderName)) {
                try {
                    $folderDate = new DateTime($folderName);

                    if ($folderDate < $threshold) {
                        $folderPath = $fileInfo->getPathname();
                        $this->deleteDirectoryRecursively($folderPath);
                    }
                } catch (Exception) {
                    // Skip if folder name is not a valid date
                    continue;
                }
            }
        }
    }

    private function deleteDirectoryRecursively(string $dir): void
    {
        $items = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);
        foreach ($items as $item) {
            if ($item->isDir()) {
                $this->deleteDirectoryRecursively($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($dir);
    }
}