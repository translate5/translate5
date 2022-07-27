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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use editor_Plugins_Okapi_Init;
use editor_Plugins_Okapi_Bconf_Segmentation_Translate5;
use editor_Plugins_Okapi_Bconf_ResourceFile;

/**
 * Command to increase the version-index and add FPRM hashes for the BCONF management of the OKAPI plugin
 * see translate5/application/modules/editor/Plugins/Okapi/data/README.md for further explanation
 */
class DevelopmentOkapiBconfNextVersionCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'dev:okapibconfversion';

    private array $messages = [];
    
    protected function configure()
    {
        $this
        // the short description shown while running "php bin/console list"
        ->setDescription('Development: Increases the current revision of the git-based OKAPI bconv version by 1 and checks for SRX changes')
        
        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('Increases the current revision of the OKAPI bconv version in editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX by 1 and checks for SRX changes in translate5-segmentation.json');
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
        $this->initTranslate5();

        $version = editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX;
        $newVersion = $version + 1;
        $okapiInitPath = APPLICATION_PATH.'/modules/editor/Plugins/Okapi/Init.php';
        $content = file_get_contents($okapiInitPath);
        // increase the version index in the PHP file
        $content = preg_replace('/const\s*BCONF_VERSION_INDEX\s*=\s*'.$version.';\s*/U', 'const BCONF_VERSION_INDEX = '.$newVersion.';', $content, 1);
        file_put_contents($okapiInitPath, $content);        
        $this->messages[] = 'Increased editor_Plugins_Okapi_Init::BCONF_VERSION_INDEX to '.$newVersion;

        $this->checkSrxHashes($newVersion);
        
        // show what was done
        $this->io->success(implode("\n", $this->messages));

        return 0;
    }

    /**
     * Updates any hashes of new SRX files
     * @param int $newVersion
     */
    private function checkSrxHashes(int $newVersion){
        $changedHashes = 0;
        $changedVersions = 0;
        $srxInventory = editor_Plugins_Okapi_Bconf_Segmentation_Translate5::instance();
        $srxItems = $srxInventory->getInventory();
        foreach($srxItems as $index => $srxItem){
            if(!$srxItem->sourceHash || strlen($srxItem->sourceHash) != 32){
                $hash = $this->createSrxHash($srxInventory->createSrxPath($srxItem, 'source'));
                $srxItems[$index]->sourceHash = $hash;
                $changedHashes++;
            }
            if(!$srxItem->targetHash || strlen($srxItem->targetHash) != 32){
                if($srxItem->source === $srxItem->target){
                    $srxItems[$index]->targetHash = $srxItems[$index]->sourceHash; // if source and target SRX are identical we can copy the hash ...
                } else {
                    $hash = $this->createSrxHash($srxInventory->createSrxPath($srxItem, 'target'));
                    $srxItems[$index]->targetHash = $hash;
                }
                $changedHashes++;
            }
            if(!$srxItem->version || $srxItem->version < 1){
                $srxItems[$index]->version = $newVersion;
                $changedVersions++;
            }
        }
        if($changedHashes > 0 || $changedVersions > 0){
            // if hashes were missing we update the file
            // correcting potential mishaps with wrongly added versions: sort versions DESC
            usort($srxItems, function ($a, $b) {
                if($a->version === $b->version){
                    return 0;
                }
                return ($a->version > $b->version) ? -1 : 1;
            });
            file_put_contents($srxInventory->getFilePath(), json_encode($srxItems, JSON_PRETTY_PRINT));
            if($changedHashes > 0){
                $this->messages[] = $changedHashes.' hashes in translate5-segmentation.json have been created';
            }
            if($changedVersions > 0){
                $this->messages[] = $changedVersions.' versions in translate5-segmentation.json have been set';
            }
        }
    }

    /**
     * @param string $path
     * @return string
     * @throws Zend_Exception
     */
    private function createSrxHash(string $path) : string {
        if(!file_exists($path)){
            throw new Zend_Exception('Okapi Segmentation Inventory translate5-segmentation.json is missing SRX file '.basename($path));
        }
        return editor_Plugins_Okapi_Bconf_ResourceFile::createHash(file_get_contents($path));
    }
}
