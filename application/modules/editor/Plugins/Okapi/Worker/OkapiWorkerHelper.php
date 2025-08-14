<?php
/*
START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2022 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace MittagQI\Translate5\Plugins\Okapi\Worker;

use editor_Models_Task;
use MittagQI\Translate5\Plugins\Okapi\OkapiAdapter;
use MittagQI\Translate5\Plugins\Okapi\OkapiException;
use SplFileInfo;

final class OkapiWorkerHelper
{
    public const OKAPI_REL_DATA_DIR = 'okapi-data';

    /**
     * filename template for storing the manifest files
     * @var string
     */
    public const MANIFEST_FILE = 'manifest-%s.rkm';

    /**
     * filename template for storing the original files
     * @var string
     */
    public const ORIGINAL_FILE = 'original-%s.%s';

    /**
     * returns the original filename for a stored file
     */
    public static function createOriginalFileName(int|string $fileId, string $suffix): string
    {
        return sprintf(self::ORIGINAL_FILE, $fileId, $suffix);
    }

    /**
     * returns the manifest filename for a stored file
     */
    public static function createManifestFileName(int $fileId): string
    {
        return sprintf(self::MANIFEST_FILE, $fileId);
    }

    /**
     * Api for accessing the saved Manifest file from other contexts
     */
    public static function createManifestFilePath(string $absoluteTaskDataPath, int $fileId): string
    {
        return $absoluteTaskDataPath . '/'
            . self::OKAPI_REL_DATA_DIR . '/'
            . sprintf(self::MANIFEST_FILE, $fileId);
    }

    /**
     * Api for accessing the saved original file from other contexts
     */
    public static function createOriginalFilePath(string $absoluteTaskDataPath, int $fileId, string $extension): string
    {
        return $absoluteTaskDataPath . '/'
            . self::OKAPI_REL_DATA_DIR . '/'
            . sprintf(self::ORIGINAL_FILE, $fileId, $extension);
    }

    /**
     * Api for accessing the saved  converted file from other contexts
     */
    public static function createConvertedFilePath(string $absoluteTaskDataPath, int $fileId, string $extension): string
    {
        return self::createOriginalFilePath($absoluteTaskDataPath, $fileId, $extension)
            . OkapiAdapter::OUTPUT_FILE_EXTENSION;
    }

    /**
     * returns the path to the okapi data dir
     * @throws OkapiException
     */
    public static function getDataDir(editor_Models_Task $task): SplFileInfo
    {
        $okapiDataDir = new SplFileInfo($task->getAbsoluteTaskDataPath() . '/' . self::OKAPI_REL_DATA_DIR);
        if (! $okapiDataDir->isDir()) {
            mkdir((string) $okapiDataDir, 0777, true);
        }
        if (! $okapiDataDir->isWritable()) {
            //Okapi Plug-In: Data dir not writeable
            throw new OkapiException('E1057', [
                'okapiDataDir' => $okapiDataDir,
            ]);
        }

        return $okapiDataDir;
    }
}
