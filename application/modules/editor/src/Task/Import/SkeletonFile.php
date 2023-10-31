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

namespace MittagQI\Translate5\Task\Import;

use editor_Models_File as File;
use editor_Models_Task as Task;
use ZfExtended_Exception;
use function gzcompress;
use function gzuncompress;

class SkeletonFile
{
    public const SKELETON_DIR_NAME = 'skeletonfiles';
    public const SKELETON_PATH = '/skeletonfiles/file_%d.zlib';

    public function __construct(private Task $task)
    {
    }

    /**
     * Saves the skeleton data for the current file to the disk
     * @param File $file
     * @param string $data Skeletonfile data
     * @throws ZfExtended_Exception
     */
    public function saveToDisk(File $file, string $data): void
    {
        $filePath = $this->getSkeletonPath($file);
        $skelDir = dirname($filePath);
        if (!file_exists($skelDir)) {
            @mkdir($skelDir);
        }
        if (!is_writable($skelDir)) {
            throw new ZfExtended_Exception('Skeleton directory is not writeable! Directory: ' . $skelDir);
        }
        file_put_contents($filePath, gzcompress($data));
    }

    /**
     * Returns tha ABSOLUTE path to the skeleton file
     * @param File $file
     * @return string
     */
    private function getSkeletonPath(File $file): string
    {
        return $this->task->getAbsoluteTaskDataPath() . sprintf(self::SKELETON_PATH, $file->getId());
    }

    /**
     * gets the absolute pathname to other files from the skeleton dir of a task
     * @param string $filename
     * @return string
     */
    public function getAbsolutePath(string $filename): string
    {
        // basename is used to
        // 1. ensure that no subdirs / directory traversal is done
        // 2. strip absolute path part from stored data produced by older versions
        return $this->task->getAbsoluteTaskDataPath() . '/' . self::SKELETON_DIR_NAME . '/' . basename($filename);
    }

    public function ensureDirectory(): void
    {
        $skeletondir = $this->task->getAbsoluteTaskDataPath().'/'. SkeletonFile::SKELETON_DIR_NAME;
        if(!is_dir($skeletondir)) {
            @mkdir($skeletondir);
        }
    }

    /**
     * Loads the skeleton data for the current file from the disk and returns it
     * @param File $file
     * @return string
     * @throws ZfExtended_Exception
     */
    public function loadFromDisk(File $file): string
    {
        $filePath = $this->getSkeletonPath($file);
        if (!file_exists($filePath)) {
            throw new ZfExtended_Exception('Skeleton file does not exist or not readable! File: ' . $filePath);
        }
        $data = gzuncompress(file_get_contents($filePath));
        if ($data === false) {
            return '';
        }
        return $data;
    }
}