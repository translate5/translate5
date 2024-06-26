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

namespace MittagQI\Translate5\Terminology;

use DirectoryIterator;
use editor_Models_Import_TermListParser_Tbx;
use editor_Models_TermCollection_TermCollection;

/**
 * Remove all files in given term collection if the files are older than FILE_OLDER_THAN_MONTHS.
 * If for the term collection there are 3 or fewer files, nothing will be removed.
 */
class CleanupCollection
{
    /***
     * File older than months
     */
    public const FILE_OLDER_THAN_MONTHS = 3;

    /***
     * How many files should be kept in term collection
     */
    public const KEEP_FILES_COUNT = 3;

    public function __construct(
        private editor_Models_TermCollection_TermCollection $collection
    ) {
    }

    /***
     * Remove all files older than FILE_OLDER_THAN_MONTHS but only if there are more than 3 files for the given term
     * collection
     * @return void
     */
    public function checkAndClean(): void
    {
        $files = $this->getFilesSorted();
        $files = array_slice($files, self::KEEP_FILES_COUNT);

        // Calculate the timestamp for 3 months ago
        $threeMonthsAgo = strtotime('-' . self::FILE_OLDER_THAN_MONTHS . ' months');

        foreach ($files as $path => $fileTimestamp) {
            // Check if the file is older than 3 months
            if ($fileTimestamp < $threeMonthsAgo) {
                unlink($path);
            }
        }
    }

    /***
     * Get all collection files sorted by date.
     * @return array
     */
    private function getFilesSorted(): array
    {
        $collectionPath =
            editor_Models_Import_TermListParser_Tbx::getFilesystemCollectionDir() .
            'tc_' .
            $this->collection->getId();

        if (! is_dir($collectionPath)) {
            return [];
        }

        $files = [];
        $iterator = new DirectoryIterator($collectionPath);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir() || ! $fileInfo->isFile()) {
                continue;
            }
            $fileInfo->getRealPath();

            // Get the file's timestamp
            $fileTimestamp = filemtime($fileInfo->getRealPath());

            // Store the file information in the array
            $files[$fileInfo->getRealPath()] = $fileTimestamp;
        }

        // Sort the files array by timestamp in ascending order
        arsort($files);

        return $files;
    }
}
