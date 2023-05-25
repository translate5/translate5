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

use editor_Models_Task_Meta;
use editor_Plugins_Okapi_Bconf_Entity;
use editor_Plugins_Okapi_Bconf_Filter_Entity;
use editor_Plugins_Okapi_Init;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Utils;

class OkapiCleanBconfsCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'okapi:cleanbconfs';

    protected function configure()
    {
        $this
            ->setDescription('Removes all bconfs that do not have a valid /data folder, removes all orphaned data-folders.'
                . ' CAUTION: this potentially deletes user data! Also, the Frontend will have an invalid state')
            ->setHelp('Removes all bconfs that do not have a valid /data folder, removes all orphaned data-folders');

        $this->addOption(
            'delete',
            'd',
            InputOption::VALUE_NONE,
            'Really delete the identified invalid bconfs');

    }

    /**
     * Execute the command
     * {@inheritDoc}
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5AppOrTest();

        $this->writeTitle('Clean invalid bconfs and orphaned data');

        $doDelete = $this->input->getOption('delete');
        $bconfEntity = new editor_Plugins_Okapi_Bconf_Entity();
        $bconfFilterEntity = new editor_Plugins_Okapi_Bconf_Filter_Entity();
        $userDataDir = $bconfEntity::getUserDataDir();
        $allGood = true;

        // if data dir is not readable nor writable, general rights are wrong, and we terminate
        if (!is_dir($userDataDir) || !is_readable($userDataDir) || !is_writable($userDataDir)) {
            $this->io->error('The bconf user-data directory "' . $userDataDir . '" does not exist or is not readable!');
            return self::FAILURE;
        }

        // First, check & fix default bconf
        $defaultBconfId = $bconfEntity->getDefaultBconfId();
        $defaultBconfDir = $userDataDir . DIRECTORY_SEPARATOR . $defaultBconfId;
        if (!is_dir($defaultBconfDir) || !is_readable($defaultBconfDir)) {

            if (is_dir($defaultBconfDir) && !is_readable($defaultBconfDir)) {

                $this->io->warning('The system default bconf data directory "' . $defaultBconfDir . '" is not readable, please change the file-rights manually');

            } else if ($doDelete) {

                // first, make old one not being the system-default
                $oldDefaultBconfId = $defaultBconfId;
                $bconfEntity->db->update(['name' => ZfExtended_Utils::uuid(), 'isDefault' => 0], ['id = ?' => $oldDefaultBconfId]);
                // then generate a new one
                $defaultBconfId = $bconfEntity->importDefaultWhenNeeded();
                // then rewire task-meta bconfIDs to new ID
                $taskMetaEntity = ZfExtended_Factory::get(editor_Models_Task_Meta::class);
                $taskMetaEntity->db->update(['bconfId' => $defaultBconfId], ['bconfId = ?' => $oldDefaultBconfId]);
                // now delete old invalid bconf
                $bconfFilterEntity->db->delete(['bconfId = ?' => $oldDefaultBconfId]);
                $bconfEntity->db->delete(['id = ?' => $oldDefaultBconfId]);

                $this->io->warning('The system default bconf "' . editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME
                    . '" (id ' . $oldDefaultBconfId . ') was invalid and has been replaced with bconf (id ' . $defaultBconfId . ')');

            } else {

                $this->io->warning('The bconf data directory "' . $defaultBconfDir . '" is missing, the systemdefault bconf "'
                    . editor_Plugins_Okapi_Init::BCONF_SYSDEFAULT_IMPORT_NAME . '" (id ' . $defaultBconfId . ') is invalid');
            }
            $allGood = false;
        }

        $allBconfIds = [$defaultBconfId];

        // Check & Fix all others
        foreach ($bconfEntity->loadAll() as $row) {
            $id = intval($row['id']);
            if ($id != $defaultBconfId) {
                $bconfDir = $userDataDir . DIRECTORY_SEPARATOR . $id;
                if (!is_dir($bconfDir) || !is_readable($bconfDir)) {

                    if (is_dir($bconfDir) && !is_readable($bconfDir)) {

                        $this->io->warning('The bconf data directory "' . $bconfDir . '" is not readable, please change the file-rights manually');

                    } else if ($doDelete) {

                        $bconfFilterEntity->db->delete(['bconfId = ?' => $id]);
                        $bconfEntity->db->delete(['id = ?' => $id]);

                        $this->io->warning('The bconf "' . $row['name'] . '" (id ' . $id . ') was invalid and has been removed');

                    } else {

                        $this->io->warning('The bconf data directory "' . $bconfDir . '" is missing, the bconf "'
                            . $row['name'] . '" (id ' . $id . ') is invalid');
                    }
                    $allGood = false;
                }
                $allBconfIds[] = $id;
            }
        }
        $dirHandle = opendir($userDataDir);
        while (false !== ($file = readdir($dirHandle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $path = $userDataDir . DIRECTORY_SEPARATOR . $file;
            // remove dir if dir is not "tmp" and not in the array of existing bconfs
            if ($file != 'tmp' && is_dir($path) && !in_array(intval($file), $allBconfIds)) {
                ZfExtended_Utils::recursiveDelete($path);
                $this->io->info('The orphaned data directory "' . $path . '" has been removed');
            }
        }
        closedir($dirHandle);

        if ($allGood) {
            $this->io->success('All bconfs in this installation are valid.');
        } else if (!$doDelete) {
            $this->io->error('There are invalid bconfs, you need to clean them by running the command with the "-d" option!');
        }

        return self::SUCCESS;
    }
}
