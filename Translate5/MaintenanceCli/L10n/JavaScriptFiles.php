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

/**
 * Finds all (at least most of) the JavaScript Files representing the FrontEnd
 * Ignores symlinks
 */
class JavaScriptFiles
{
    public function __construct(
        private readonly string $rootPath
    ) {
    }

    public function findFiles(bool $asAbsolutePath = true): array
    {
        $cwd = getcwd();
        chdir($this->rootPath);
        // we find all pathes including PrivatPlugins since the pattern is checked against expanded pathes
        $find = 'find ./ \( -path "*/public/modules/editor/js/*" -o -path "*/Plugins/*/public/js/*"' .
            ' -o -path "*/PrivatePlugins/*/public/js/*" \) -iname "*.js"';
        $filesData = shell_exec($find);
        chdir($cwd);

        $files = [];
        foreach (explode("\n", trim($filesData)) as $file) {
            // replace "/PrivatePlugins/" to "real" path
            $file = str_replace('/PrivatePlugins/', '/Plugins/', $file);
            // exclude database-updates & PrivatePlugins
            $absolutePath = $this->rootPath . '/' . ltrim($file, './');
            if (! str_contains($file, '/PrivatePlugins/')) {
                $files[] = $asAbsolutePath ? $absolutePath : $file;
            }
        }

        return array_values(array_unique($files));
    }
}
