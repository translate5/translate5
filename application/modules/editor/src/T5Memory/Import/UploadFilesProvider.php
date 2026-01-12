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

namespace MittagQI\Translate5\T5Memory\Import;

use editor_Utils;
use Zend_Registry;
use Zend_Validate_File_IsCompressed;
use ZfExtended_Logger;
use ZipArchive;

class UploadFilesProvider
{
    public function __construct(
        private readonly ZfExtended_Logger $logger,
    ) {
    }

    public static function create(): self
    {
        return new self(
            Zend_Registry::get('logger')->cloneMe('editor.t5memory.import'),
        );
    }

    /**
     * @return iterable<string>
     */
    public function getFilesFromUpload(?array $fileInfo): iterable
    {
        if (null === $fileInfo) {
            return yield from [];
        }

        $validator = new Zend_Validate_File_IsCompressed();
        if (! $validator->isValid($fileInfo['tmp_name'])) {
            return yield $fileInfo['tmp_name'];
        }

        $zip = new ZipArchive();
        if (! $zip->open($fileInfo['tmp_name'])) {
            $this->logger->error('E1596', 'T5Memory: Unable to open zip file from file-path:' . $fileInfo['tmp_name']);

            return yield from [];
        }

        $newPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . pathinfo($fileInfo['name'], PATHINFO_FILENAME);

        if (! $zip->extractTo($newPath)) {
            $this->logger->error('E1597', 'T5Memory: Content from zip file could not be extracted.');
            $zip->close();

            return yield from [];
        }

        $zip->close();

        foreach (editor_Utils::generatePermutations('tmx') as $patter) {
            yield from glob($newPath . DIRECTORY_SEPARATOR . '*.' . implode($patter)) ?: [];
        }
    }
}
