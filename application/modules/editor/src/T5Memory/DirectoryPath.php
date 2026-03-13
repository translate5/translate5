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

namespace MittagQI\Translate5\T5Memory;

class DirectoryPath
{
    public static function create(): self
    {
        return new self();
    }

    public function tmxImportProcessingDir(bool $root = false): string
    {
        $path = APPLICATION_DATA . '/TmxImportPreprocessing';

        $this->createDir($path);

        if ($root) {
            return $path;
        }

        return $this->getDayDir($path);
    }

    public function tmExportDir(bool $root = false): string
    {
        $path = APPLICATION_DATA . '/TMExport';

        $this->createDir($path);

        if ($root) {
            return $path;
        }

        return $this->getDayDir($path);
    }

    public function tmxFilterDir(bool $root = false): string
    {
        $path = APPLICATION_DATA . '/tmx-filter';
        $this->createDir($path);

        if ($root) {
            return $path;
        }

        return $this->getDayDir($path);
    }

    private function createDir(string $path): void
    {
        if (! is_dir($path)) {
            if (! mkdir($path, 0777, true) && ! is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
        }
    }

    private function getDayDir(string $path): string
    {
        $path = $path . '/' . date('Y-m-d');

        $this->createDir($path);

        return $path;
    }
}
