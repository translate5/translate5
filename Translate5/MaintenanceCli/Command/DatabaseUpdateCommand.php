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

use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;

class DatabaseUpdateCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'database:update';

    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
            ->setDescription('Maintain database updates.')

        // the full command description shown when running the command with
        // the "--help" option
            ->setHelp('Lists and import database update files.');

        $this->addArgument(
            'filename',
            InputArgument::OPTIONAL,
            'Part of a file name or the complete hash of a file - without -i just print the file content.'
        );

        $this->addOption(
            'import',
            'i',
            InputOption::VALUE_NONE,
            'Imports all new database files or a single file if a filename / hash was given.'
        );

        /**
         * TODO Open Ideas:
         */
        //        $this->addOption(
        //            'select',
        //            's',
        //            InputOption::VALUE_NONE,
        //            'Provides an interactive menu to select the files to be imported.');

        //add a recall parameter, so that specific database files can be called again.

        $this->addOption(
            'assume-imported',
            null,
            InputOption::VALUE_NONE,
            'WARNING: Instead of importing the selected file it is just '
                . 'marked as imported without applying the content to the DB!'
        );

        $this->addOption(
            'list-all-installed',
            'l',
            InputOption::VALUE_NONE,
            'Just list all installed DB files, does not import anything.'
        );

        $this->addOption(
            'remove-imported',
            null,
            InputOption::VALUE_REQUIRED,
            'DANGER: removes an entry from the dbversion table, identified by hash. Use only if you know what you do'
        );
    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @return int
     * @throws ReflectionException
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->writeTitle('database management');

        $dbupdater = \ZfExtended_Factory::get(\ZfExtended_Models_Installer_DbUpdater::class, [true]);

        if ($remove = $this->input->getOption('remove-imported')) {
            $dbversion = \ZfExtended_Factory::get(\ZfExtended_Models_Db_DbVersion::class);
            $found = $dbversion->fetchRow($dbversion->select()->where('md5 = ?', $remove));

            if (empty($found)) {
                $this->io->error('No DB entry found for hash ' . $remove);

                return self::FAILURE;
            }

            $this->writeAssoc($found->toArray());
            if ($this->io->confirm(
                'Do you really want to delete the above DB alter entry from the dbversion table?'
            )) {
                $found->delete();
            }

            return self::SUCCESS;
        }

        if ($this->input->getOption('list-all-installed')) {
            $this->renderListTable();

            return self::SUCCESS;
        }

        //print on develop machines the configured sqlPaths and in the Browser GUI
        $usedPaths = $dbupdater->calculateChanges();
        $usedPaths = array_filter($usedPaths, function ($item) {
            return strpos($item, 'library/ZfExtended/database/') === false && strpos($item, 'modules/default/database/') === false && strpos($item, 'modules/editor/database/') === false;
        });
        if (! empty($usedPaths)) {
            array_unshift($usedPaths, 'Additional configured DB search path(s): ');
            $this->io->warning($usedPaths);
        }

        $result = $this->processOnlyOneFile($dbupdater);
        if ($result >= 0) {
            return $result;
        }

        $newFiles = $dbupdater->getNewFiles();
        $toProcess = [];
        if (! empty($newFiles)) {
            $this->io->section('New files (can be imported automatically with "-i")');
            $this->io->table(['origin', 'file', 'hash'], array_map(function ($file) use (&$toProcess) {
                $toProcess[$file['entryHash']] = 1;

                return [$file['origin'], $file['relativeToOrigin'], $file['entryHash']];
            }, $newFiles));
        }

        $modified = $dbupdater->getModifiedFiles();
        if (! empty($modified)) {
            $this->io->section('Modified files (can NOT be imported automatically - "-i" marks them as imported!)');
            $this->io->table(['origin', 'file', 'hash'], array_map(function ($file) use (&$toProcess) {
                $toProcess[$file['entryHash']] = 1;

                return [$file['origin'], $file['relativeToOrigin'], $file['entryHash']];
            }, $modified));
        }

        if (empty($modified) && empty($newFiles)) {
            $this->io->note("Nothing to do: no database files to be imported!");

            return 0;
        }

        if ($this->input->getOption('assume-imported')) {
            $this->io->warning('--assume-imported can only be used on one single file! Nothing is assumed as imported.');

            return 1;
        }
        $import = $this->input->getOption('import');

        //        //print_r($modified);
        //        //print_r($newFiles);
        //        if($this->input->getOption('select')) {
        //            $selected = $this->io->choice("Select file to be imported", $toProcess);
        //            print_r($selected);
        //        }
        //        return 0;

        if (! $import) {
            $import = $this->io->confirm('Import listed files?', false);
        }

        if ($import) {
            $importedCount = $dbupdater->applyNew($toProcess);
            $dbupdater->updateModified($toProcess);
            $errors = $dbupdater->getErrors();
            if (! empty($errors)) {
                $this->io->error($errors);
            }
            $warnings = $dbupdater->getWarnings();
            if (! empty($warnings)) {
                $this->io->warning($warnings);
            }
            if ($importedCount > 0) {
                if (empty($errors)) {
                    $this->io->success('Imported ' . $importedCount . ' files!');
                } else {
                    $this->io->success('Imported ' . $importedCount . ' files successfully before the above error occured!');
                }
            }
            $remaining = count($newFiles) - $importedCount;
            if ($remaining > 0) {
                $this->io->warning('Remaining ' . $remaining . ' files to be imported. Fix errors first - call support if in doubt!');
            }
            if (! empty($modified)) {
                $this->io->warning('Marked ' . count($modified) . ' modified files as up to date - no SQL change was applied to the DB!');
            }
        }

        return 0;
    }

    protected function processOnlyOneFile(\ZfExtended_Models_Installer_DbUpdater $dbupdater)
    {
        $fileName = $this->input->getArgument('filename');
        if (empty($fileName)) {
            return -1;
        }
        $new = $dbupdater->getNewFiles();
        $modified = $dbupdater->getModifiedFiles();
        foreach ($modified as &$mod) {
            $mod['modified'] = 1;
        }
        $all = array_merge($new, $modified);
        $found = false;
        foreach ($all as $file) {
            if ($file['entryHash'] === $fileName || strpos($file['relativeToOrigin'], $fileName) !== false) {
                $found = true;

                break;
            }
        }
        if (! $found) {
            $this->io->warning('No file matching ' . $fileName . ' found!');

            return 1;
        }
        $this->output->writeln(file_get_contents($file['absolutePath']));
        if ($this->input->getOption('assume-imported')) {
            $this->io->success('Marked file as already imported: ' . $file['relativeToOrigin'] . '!');
            $dbupdater->assumeImported([
                $file['entryHash'] => 1,
            ]);

            return 0;
        }
        if (! $this->input->getOption('import')) {
            return 0;
        }
        if (empty($file['modified'])) {
            $dbupdater->applyNew([
                $file['entryHash'] => 1,
            ]);
        } else {
            $dbupdater->updateModified([
                $file['entryHash'] => 1,
            ]);
        }
        $errors = $dbupdater->getErrors();
        if (! empty($errors)) {
            $this->io->error($errors);
            $this->io->warning('Imported with errors - check them!');

            return 1;
        }
        if (empty($file['modified'])) {
            $this->io->success('Imported file ' . $file['relativeToOrigin'] . '!');
        } else {
            $this->io->warning('Marked modified ' . $file['relativeToOrigin'] . ' file as up to date - no SQL change was applied to the DB!');
        }

        return 0;
    }

    /**
     * @throws ReflectionException
     */
    private function renderListTable(): void
    {
        $dbversion = \ZfExtended_Factory::get(\ZfExtended_Models_Db_DbVersion::class);
        $installed = $dbversion->fetchAll()->toArray();
        $table = $this->io->createTable();
        $table->setHeaders(['id', 'origin', 'filename', 'md5', 'appVersion', 'created']);
        foreach ($installed as $inst) {
            $table->addRow($inst);
        }
        $table->render();
    }
}
