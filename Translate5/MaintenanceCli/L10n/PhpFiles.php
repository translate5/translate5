<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

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

namespace Translate5\MaintenanceCli\L10n;

class PhpFiles
{
    public function __construct(
        private readonly string $rootPath
    ) {
    }

    public function findFiles(array $excludePathes = [], bool $asAbsolutePath = false): array
    {
        $cwd = getcwd();
        chdir($this->rootPath);

        $notPathes = [];
        foreach ($excludePathes as $excludePath) {
            if (str_starts_with($excludePath, $this->rootPath)) {
                $excludePath = substr($excludePath, strlen($this->rootPath));
                $excludePath = './' . ltrim($excludePath, '/.');
            }
            $excludePath = rtrim($excludePath, '/*') . '/*';

            $notPathes[] = ' -not -path "' . $excludePath . '"';
        }
        $find = 'find -iname "*.php" -o -iname "*.phtml"' . implode('', $notPathes);
        $filesData = shell_exec($find);
        chdir($cwd);

        $files = [];
        foreach (explode("\n", trim($filesData)) as $file) {
            // exclude database-updates & PrivatePlugins
            if (! str_contains($file, '/database/') && ! str_contains($file, '/PrivatePlugins/')) {
                $files[] = $file;
            }
        }

        if ($asAbsolutePath) {
            foreach ($files as &$file) {
                $file = $this->rootPath . '/' . ltrim($file, './');
            }
        }

        return $files;
    }
}
