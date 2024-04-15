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

namespace Translate5\MaintenanceCli\Command;

use DirectoryIterator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DevelopmentSymlinksCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'dev:symlink';

    protected function configure()
    {
        $this
            ->setDescription('Development: Checks all relevant symlinks and creates them when neccessary for development instances.')
            ->setHelp('Development: Checks all relevant symlinks and creates them when neccessary  for development instances.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->writeTitle('Check all symlinks');

        $pluginsPath = APPLICATION_PATH . '/modules/editor/Plugins';
        $privatePluginsPath = APPLICATION_PATH . '/modules/editor/PrivatePlugins';

        $this->io->section('Libraries');

        // check other basic symlinks (no automatic repair here, target may be different depending on installation
        $linksToCheck = [
            APPLICATION_ROOT . '/public/ext-6.2.0',
            APPLICATION_ROOT . '/public/js/jquery-ui',
            APPLICATION_ROOT . '/public/js/rangy',
            APPLICATION_ROOT . '/public/modules/erp',
            APPLICATION_ROOT . '/public/js/rangy',
            APPLICATION_ROOT . '/public/modules/editor/js/ux/DateTimeField.js',
            APPLICATION_ROOT . '/public/modules/editor/js/ux/DateTimePicker.js',
            APPLICATION_ROOT . '/public/modules/editor/js/ux/LICENSE',
        ];

        foreach ($linksToCheck as $symLinkPath) {
            if (! $this->isValidSymlink($symLinkPath)) {
                $this->io->error('Symlink "' . $symLinkPath . '" is broken, please fix it manually.');
            } else {
                $this->io->success('Symlink "' . $symLinkPath . '" is valid.');
            }
        }

        $this->io->section('PrivatePlugins');

        // check/fix PrivatePlugins Symlinks
        $iterator = new DirectoryIterator($privatePluginsPath);
        foreach ($iterator as $dirinfo) {
            /* @var DirectoryIterator $dirinfo */
            if (! $dirinfo->isDot() && $dirinfo->isDir() && $dirinfo->isReadable()) {
                $pluginDir = $dirinfo->getBasename();
                $privatePluginPath = $privatePluginsPath . '/' . $pluginDir;
                if (file_exists($privatePluginPath . '/Init.php') || file_exists($privatePluginPath . '/Bootstrap.php')) {
                    // seems a proper plugin
                    $symLinkPath = $pluginsPath . '/' . $pluginDir;
                    $isValid = $this->isValidSymlink($symLinkPath);
                    if (! $isValid && ! file_exists($symLinkPath)) {
                        if (! symlink('../PrivatePlugins/' . $pluginDir, $symLinkPath)) {
                            $this->io->error('Could not create missing symlink "' . $symLinkPath . '" to "../PrivatePlugins/' . $pluginDir . '", please create it manually.');
                        } else {
                            $this->io->success('Created missing symlink "' . $symLinkPath . '" to "../PrivatePlugins/' . $pluginDir . '".');
                        }
                    } elseif (! $isValid) {
                        $this->io->error('Symlink "' . $symLinkPath . '" to "../PrivatePlugins/' . $pluginDir . '" is broken, please fix it manually.');
                    } else {
                        $this->io->success('Symlink "' . $symLinkPath . '" is valid.');
                    }
                }
            }
        }

        return self::SUCCESS;
    }

    private function isValidSymlink(string $path): bool
    {
        return is_link($path) && readlink($path) !== false && realpath($path) !== false;
    }
}
